<?php

namespace App\Services;

use App\Entities\Appraisal;
use App\Models\M_Appraisal;
use App\Models\M_AppraisalResponse;
use App\Models\M_AppraisalAuditLog;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Exception;

class AppraisalScoringService
{
    protected M_Appraisal $appraisalModel;
    protected M_AppraisalResponse $responseModel;
    protected M_AppraisalAuditLog $auditLogModel;

    public function __construct()
    {
        $this->appraisalModel = new M_Appraisal();
        $this->responseModel  = new M_AppraisalResponse();
        $this->auditLogModel  = new M_AppraisalAuditLog();
    }

    /**
     * Recalculate weighted scores and update the Appraisal header.
     *
     * @param int $appraisalId
     * @param int $userId
     * @return Appraisal
     * @throws Exception
     */
    public function calculateAndUpdateScores(int $appraisalId, int $userId): Appraisal
    {
        $appraisal = $this->appraisalModel->find($appraisalId);

        if (! $appraisal) {
            throw new Exception('Form penilaian tidak ditemukan.');
        }

        $responses = $this->responseModel->where('md_appraisal_id', $appraisalId)
                                         ->where('isactive', 'Y')
                                         ->findAll();

        if (empty($responses)) {
            return $appraisal;
        }

        // Decode JSON template snapshot to match item definitions & weights
        $snapshot = is_string($appraisal->template_snapshot) 
            ? json_decode($appraisal->template_snapshot, true) 
            : $appraisal->template_snapshot;

        $itemsById = [];
        if (! empty($snapshot['items'])) {
            foreach ($snapshot['items'] as $item) {
                $itemId = $item['md_appraisal_template_item_id'] ?? $item['template_item_id'] ?? null;
                if ($itemId) {
                    $itemsById[(int) $itemId] = $item;
                }
            }
        }

        $sectionsById = [];
        if (! empty($snapshot['sections'])) {
            foreach ($snapshot['sections'] as $sec) {
                $secId = $sec['md_appraisal_section_id'] ?? null;
                if ($secId) {
                    $sectionsById[(int) $secId] = $sec;
                }
            }
        }

        // Group item scores per section
        $sectionScores = [];
        $totalDirectWeightedScore = 0.0;
        $totalDirectWeight        = 0.0;

        foreach ($responses as $resp) {
            $itemId  = (int) $resp->md_appraisal_template_item_id;
            $itemDef = $itemsById[$itemId] ?? null;

            if (! $itemDef) {
                continue;
            }

            $secId = (int) ($itemDef['md_appraisal_section_id'] ?? 0);
            $weight = (float) ($itemDef['weight'] ?? 1.0);
            $score  = $resp->score !== null ? (float) $resp->score : null;

            // If score is not yet set in normalized column, try extract from evaluator_response
            if ($score === null && ! empty($resp->evaluator_response)) {
                $payload = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
                if (isset($payload['score']) && $payload['score'] !== '') {
                    $score = (float) $payload['score'];
                }
            }

            // Exclude unweighted or non-numeric items (e.g. TEXT, BOOLEAN)
            $inputType = strtoupper($itemDef['input_type'] ?? 'SCORE');
            if ($inputType === 'TEXT' || $inputType === 'BOOLEAN') {
                $weight = 0.0;
                $score  = null;
            }

            if (! isset($sectionScores[$secId])) {
                $sectionScores[$secId] = [
                    'weighted_sum' => 0.0,
                    'weight_sum'   => 0.0,
                ];
            }

            if ($score !== null && $weight > 0) {
                $sectionScores[$secId]['weighted_sum'] += ($score * $weight);
                $sectionScores[$secId]['weight_sum']   += $weight;

                $totalDirectWeightedScore += ($score * $weight);
                $totalDirectWeight        += $weight;
            }
        }

        // Calculate final score: prioritize section weights if defined
        $finalScore = 0.00;
        $totalSectionWeight = 0.0;
        $totalSectionWeightedScore = 0.0;

        foreach ($sectionScores as $sId => $sData) {
            $secDef = $sectionsById[$sId] ?? null;
            $secWeight = (float) ($secDef['weight'] ?? 0.0);

            if ($sData['weight_sum'] > 0) {
                $secAvg = $sData['weighted_sum'] / $sData['weight_sum'];

                if ($secWeight > 0) {
                    $totalSectionWeightedScore += ($secAvg * $secWeight);
                    $totalSectionWeight        += $secWeight;
                }
            }
        }

        if ($totalSectionWeight > 0) {
            $finalScore = round($totalSectionWeightedScore / $totalSectionWeight, 2);
        } elseif ($totalDirectWeight > 0) {
            $finalScore = round($totalDirectWeightedScore / $totalDirectWeight, 2);
        }

        $ratingScale = $snapshot['config']['rating_scale'] ?? null;
        $rating      = $this->determineRatingLabel($finalScore, $ratingScale);

        // Update appraisal header
        $appraisal->performance_score = $finalScore;
        $appraisal->final_score       = $finalScore;
        $appraisal->rating            = $rating;
        $appraisal->updated_by        = $userId;

        $this->appraisalModel->save($appraisal);

        return $this->appraisalModel->find($appraisalId);
    }

    /**
     * Save/batch update responses for an evaluation form.
     * Records granular item-level score and response delta audit logs.
     *
     * @param int $appraisalId
     * @param array $responseList List of items to save
     * @param int $userId
     * @param string $actorRole 'EVALUATOR' or 'GM'
     * @throws Exception
     */
    public function saveResponses(int $appraisalId, array $responseList, int $userId, string $actorRole = 'EVALUATOR'): void
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $appraisal = $this->appraisalModel->find($appraisalId);
        $snapshot  = ($appraisal && ! empty($appraisal->template_snapshot))
            ? (is_string($appraisal->template_snapshot) ? json_decode($appraisal->template_snapshot, true) : $appraisal->template_snapshot)
            : [];
        $itemsDefById = [];
        if (! empty($snapshot['items'])) {
            foreach ($snapshot['items'] as $itemDef) {
                $tId = (int) ($itemDef['md_appraisal_template_item_id'] ?? $itemDef['template_item_id'] ?? 0);
                if ($tId > 0) {
                    $itemsDefById[$tId] = $itemDef;
                }
            }
        }

        $auditDeltas = [];

        foreach ($responseList as $row) {
            $itemId     = (int) ($row['md_appraisal_template_item_id'] ?? $row['template_item_id'] ?? 0);
            $responseId = (int) ($row['md_appraisal_response_id'] ?? $row['appraisal_response_id'] ?? 0);

            // Locate response row
            $resp = null;
            if ($responseId > 0) {
                $resp = $this->responseModel->find($responseId);
            } elseif ($itemId > 0) {
                $resp = $this->responseModel
                    ->where('md_appraisal_id', $appraisalId)
                    ->where('md_appraisal_template_item_id', $itemId)
                    ->first();
            }

            if (! $resp || (int) $resp->md_appraisal_id !== $appraisalId) {
                continue;
            }

            $actualItemId = (int) $resp->md_appraisal_template_item_id;
            $itemDef      = $itemsDefById[$actualItemId] ?? null;
            $inputType    = strtoupper($itemDef['input_type'] ?? 'SCORE');
            $isBoolean    = ($inputType === 'BOOLEAN' || isset($row['boolean_value']) || isset($row['checked_actions']));

            // Extract numeric score only for scored items
            $score = null;
            if (! $isBoolean) {
                if (isset($row['score']) && $row['score'] !== '') {
                    $score = (float) $row['score'];
                } elseif (isset($row['evaluator_score']) && $row['evaluator_score'] !== '') {
                    $score = (float) $row['evaluator_score'];
                }
            }

            $prevScore = $resp->score !== null ? (float) $resp->score : null;

            // Assemble payload
            if ($actorRole === 'GM') {
                $existingGm = is_string($resp->gm_response) ? json_decode($resp->gm_response, true) : ($resp->gm_response ?? []);
                $newGm      = array_merge($existingGm ?: [], $row['gm_response'] ?? $row);
                if (isset($row['action_scores'])) {
                    $newGm['action_scores'] = $row['action_scores'];
                }
                if ($isBoolean) {
                    unset($newGm['score']);
                    $resp->score = null;
                } else {
                    if ($score !== null) {
                        $newGm['score'] = number_format($score, 2, '.', '');
                        $resp->score    = $score;
                    }
                }
                $resp->gm_response = $newGm;
                $effectiveScore    = $resp->score;

                // Check delta
                $scoreChanged   = ($score !== null && ($prevScore === null || round($score, 2) !== round($prevScore, 2)));
                $payloadChanged = (json_encode($existingGm) !== json_encode($newGm));

                if ($scoreChanged || $payloadChanged) {
                    $auditDeltas[] = [
                        'md_appraisal_id'               => $appraisalId,
                        'actor_id'                      => $userId,
                        'action'                        => 'GM_OVERRIDE_ITEM',
                        'md_appraisal_template_item_id' => $actualItemId,
                        'previous_value'                => json_encode(['score' => $prevScore, 'payload' => $existingGm]),
                        'new_value'                     => json_encode(['score' => $effectiveScore, 'payload' => $newGm]),
                        'notes'                         => 'GM memperbarui nilai/catatan butir penilaian.',
                        'created_at'                    => date('Y-m-d H:i:s'),
                    ];
                }
            } else {
                $existingEval = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : ($resp->evaluator_response ?? []);
                $newEval      = array_merge($existingEval ?: [], $row['evaluator_response'] ?? $row);
                if (isset($row['action_scores'])) {
                    $newEval['action_scores'] = $row['action_scores'];
                }
                if ($isBoolean) {
                    unset($newEval['score']);
                    $resp->score = null;
                } else {
                    if ($score !== null) {
                        $newEval['score'] = number_format($score, 2, '.', '');
                        $resp->score      = $score;
                    }
                }
                $resp->evaluator_response = $newEval;

                // Check delta
                $scoreChanged   = ($score !== null && ($prevScore === null || round($score, 2) !== round($prevScore, 2)));
                $payloadChanged = (json_encode($existingEval) !== json_encode($newEval));

                if ($scoreChanged || $payloadChanged) {
                    $auditDeltas[] = [
                        'md_appraisal_id'               => $appraisalId,
                        'actor_id'                      => $userId,
                        'action'                        => 'UPDATE_ITEM_SCORE',
                        'md_appraisal_template_item_id' => $actualItemId,
                        'previous_value'                => json_encode(['score' => $prevScore, 'payload' => $existingEval]),
                        'new_value'                     => json_encode(['score' => $score, 'payload' => $newEval]),
                        'notes'                         => 'Penilai memperbarui nilai/catatan butir penilaian.',
                        'created_at'                    => date('Y-m-d H:i:s'),
                    ];
                }
            }

            $resp->updated_by = $userId;
            $this->responseModel->save($resp);
        }

        // Insert granular audit deltas batch if any
        if (! empty($auditDeltas)) {
            $db->table('md_appraisal_audit_log')->insertBatch($auditDeltas);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new DatabaseException('Gagal menyimpan respons evaluasi ke database.');
        }

        // Recalculate overall appraisal score
        $this->calculateAndUpdateScores($appraisalId, $userId);
    }

    /**
     * Map numerical score to performance rating label.
     * Evaluates snapshot-configured scale tiers if provided, falling back to standard 4-tier bounds.
     *
     * @param float $score
     * @param array|null $ratingScale
     * @return string
     */
    public function determineRatingLabel(float $score, ?array $ratingScale = null): string
    {
        if (! empty($ratingScale) && is_array($ratingScale)) {
            foreach ($ratingScale as $tier) {
                $min   = isset($tier['min']) ? (float) $tier['min'] : 0.00;
                $max   = isset($tier['max']) ? (float) $tier['max'] : 4.00;
                $label = $tier['label'] ?? '';

                if ($score >= $min && $score <= $max) {
                    return $label;
                }
            }
        }

        if ($score >= 3.50) {
            return 'Sangat Baik (Exceeds Expectations)';
        }
        if ($score >= 2.75) {
            return 'Baik (Meets Expectations)';
        }
        if ($score >= 2.00) {
            return 'Cukup (Needs Improvement)';
        }
        return 'Kurang (Unsatisfactory)';
    }
}
