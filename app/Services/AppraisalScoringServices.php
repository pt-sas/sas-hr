<?php

namespace App\Services;

use App\Entities\Appraisal;
use App\Exceptions\NotFoundException;
use App\Models\M_Appraisal;
use App\Models\M_AppraisalResponse;
use App\Models\M_AppraisalAuditLog;
use CodeIgniter\Database\Exceptions\DatabaseException;

class AppraisalScoringServices extends BaseServices
{
    protected M_Appraisal $appraisalModel;
    protected M_AppraisalResponse $responseModel;
    protected M_AppraisalAuditLog $auditLogModel;

    public function __construct()
    {
        parent::__construct();
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
            throw new NotFoundException('Form penilaian tidak ditemukan.');
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
        $snapshot = AppraisalTemplateServices::expandSchema(is_array($snapshot) ? $snapshot : []);

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

        // Determine if scored items in sections have zero/unspecified weights and need equal distribution
        $sectionItemWeights = [];
        foreach ($snapshot['items'] ?? [] as $it) {
            $sId = (int) ($it['md_appraisal_section_id'] ?? 0);
            $inType = strtoupper($it['input_type'] ?? 'SCORE');
            if ($inType === 'SCORE' || $inType === 'WEIGHTED_CHECKLIST') {
                $sectionItemWeights[$sId][] = (float) ($it['weight'] ?? 0);
            }
        }
        $sectionNeedsEqualWeights = [];
        foreach ($sectionItemWeights as $sId => $wList) {
            $sumW = array_sum($wList);
            if ($sumW <= 0) {
                $sectionNeedsEqualWeights[$sId] = true;
            }
        }

        // Group item scores per section
        $sectionScores = [];
        $totalDirectWeightedScore = 0.0;
        $totalDirectWeight        = 0.0;
        $unweightedScoresSum      = 0.0;
        $unweightedScoresCount    = 0;

        foreach ($responses as $resp) {
            $itemId  = (int) $resp->md_appraisal_template_item_id;
            $itemDef = $itemsById[$itemId] ?? null;

            if (! $itemDef) {
                continue;
            }

            $secId = (int) ($itemDef['md_appraisal_section_id'] ?? 0);
            $rawWeight = (float) ($itemDef['weight'] ?? 0);
            $inputType = strtoupper($itemDef['input_type'] ?? 'SCORE');
            $isScoredType = ($inputType === 'SCORE' || $inputType === 'WEIGHTED_CHECKLIST');

            if ($isScoredType && ($rawWeight <= 0 || ! empty($sectionNeedsEqualWeights[$secId]))) {
                $weight = 1.0;
            } else {
                $weight = $rawWeight;
            }

            $score = $resp->score !== null ? (float) $resp->score : null;

            // If score is not yet set in normalized column, check Reviewer or evaluator payload
            if ($score === null) {
                if (! empty($resp->reviewer_response)) {
                    $reviewerPayload = is_string($resp->reviewer_response) ? json_decode($resp->reviewer_response, true) : $resp->reviewer_response;
                    if (isset($reviewerPayload['score']) && $reviewerPayload['score'] !== '') {
                        $score = (float) $reviewerPayload['score'];
                    } elseif (! empty($reviewerPayload['action_scores']) && is_array($reviewerPayload['action_scores'])) {
                        $validActs = array_filter($reviewerPayload['action_scores'], 'is_numeric');
                        if (! empty($validActs)) {
                            $score = array_sum($validActs) / count($validActs);
                        }
                    }
                }
                if ($score === null && ! empty($resp->evaluator_response)) {
                    $evalPayload = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
                    if (isset($evalPayload['score']) && $evalPayload['score'] !== '') {
                        $score = (float) $evalPayload['score'];
                    } elseif (! empty($evalPayload['action_scores']) && is_array($evalPayload['action_scores'])) {
                        $validActs = array_filter($evalPayload['action_scores'], 'is_numeric');
                        if (! empty($validActs)) {
                            $score = array_sum($validActs) / count($validActs);
                        }
                    }
                }
            }

            // Exclude unweighted or non-numeric items (e.g. TEXT, BOOLEAN, SINGLE_CHECKLIST)
            if (! $isScoredType) {
                $weight = 0.0;
                $score  = null;
            }

            if (! isset($sectionScores[$secId])) {
                $sectionScores[$secId] = [
                    'weighted_sum' => 0.0,
                    'weight_sum'   => 0.0,
                    'unweighted_sum'   => 0.0,
                    'unweighted_count' => 0,
                ];
            }

            if ($score !== null) {
                $sectionScores[$secId]['unweighted_sum'] += $score;
                $sectionScores[$secId]['unweighted_count']++;
                $unweightedScoresSum   += $score;
                $unweightedScoresCount++;

                if ($weight > 0) {
                    $sectionScores[$secId]['weighted_sum'] += ($score * $weight);
                    $sectionScores[$secId]['weight_sum']   += $weight;

                    $totalDirectWeightedScore += ($score * $weight);
                    $totalDirectWeight        += $weight;
                }
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
            } elseif ($sData['unweighted_count'] > 0) {
                $secAvg = $sData['unweighted_sum'] / $sData['unweighted_count'];
            } else {
                continue;
            }

            if ($secWeight > 0) {
                $totalSectionWeightedScore += ($secAvg * $secWeight);
                $totalSectionWeight        += $secWeight;
            }
        }

        helper('appraisal_view');
        $isOperator = appraisal_is_operator($snapshot);

        if ($isOperator) {
            $finalScore = round($totalDirectWeightedScore, 2);
        } elseif ($totalSectionWeight > 0) {
            $finalScore = round($totalSectionWeightedScore / $totalSectionWeight, 2);
        } elseif ($totalDirectWeight > 0) {
            $finalScore = round($totalDirectWeightedScore / $totalDirectWeight, 2);
        } elseif ($unweightedScoresCount > 0) {
            $finalScore = round($unweightedScoresSum / $unweightedScoresCount, 2);
        }

        $ratingScale = $snapshot['config']['rating_scale'] ?? null;
        $rating      = $this->determineRatingLabel($finalScore, $ratingScale, $isOperator);

        // Update appraisal header
        $appraisal->final_score = $finalScore;
        $appraisal->rating      = $rating;
        $appraisal->updated_by  = $userId;

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
        $db->transBegin();

        try {

        $appraisal = $this->appraisalModel->find($appraisalId);
        $snapshot  = ($appraisal && ! empty($appraisal->template_snapshot))
            ? (is_string($appraisal->template_snapshot) ? json_decode($appraisal->template_snapshot, true) : $appraisal->template_snapshot)
            : [];
        $snapshot  = AppraisalTemplateServices::expandSchema(is_array($snapshot) ? $snapshot : []);
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
            $isBoolean    = ($inputType === 'BOOLEAN' || $inputType === 'SINGLE_CHECKLIST' || isset($row['boolean_value']) || isset($row['checked_actions']));

            // Extract numeric score only for scored items
            $score = null;
            if (! $isBoolean) {
                if (isset($row['score']) && $row['score'] !== '') {
                    $score = (float) $row['score'];
                } elseif (isset($row['evaluator_score']) && $row['evaluator_score'] !== '') {
                    $score = (float) $row['evaluator_score'];
                } elseif (! empty($row['action_scores']) && is_array($row['action_scores'])) {
                    $validActs = array_filter($row['action_scores'], 'is_numeric');
                    if (! empty($validActs)) {
                        $score = round(array_sum($validActs) / count($validActs), 2);
                    }
                }
            }

            $prevScore = $resp->score !== null ? (float) $resp->score : null;

            // Assemble payload using unified normalization
            $isPeninjau = in_array(strtoupper((string) $actorRole), ['PENINJAU', 'REVIEWER'], true);

            $formatAuditValue = static function (array $payload, ?float $colScore): ?string {
                $data = $payload;
                if ($colScore !== null && ! isset($data['score']) && empty($data['action_scores'])) {
                    $data['score'] = round($colScore, 2);
                }
                return ! empty($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : null;
            };

            if ($isPeninjau) {
                $existingReviewer = is_string($resp->reviewer_response) ? json_decode($resp->reviewer_response, true) : ($resp->reviewer_response ?? []);
                $existingReviewer = is_array($existingReviewer) ? $existingReviewer : [];
                $newReviewer      = $this->prepareResponsePayload($existingReviewer, $row['reviewer_response'] ?? $row, $score, $isBoolean);

                if ($isBoolean) {
                    $resp->score = null;
                } elseif ($score !== null) {
                    $resp->score = $score;
                }
                $resp->reviewer_response = $newReviewer;
                $effectiveScore          = $resp->score !== null ? (float) $resp->score : null;

                // Check delta
                $scoreChanged   = ($score !== null && ($prevScore === null || round($score, 2) !== round($prevScore, 2)));
                $payloadChanged = (json_encode($existingReviewer) !== json_encode($newReviewer));

                if ($scoreChanged || $payloadChanged) {
                    $auditDeltas[] = [
                        'md_appraisal_id'               => $appraisalId,
                        'actor_id'                      => $userId,
                        'action'                        => 'PENINJAU_OVERRIDE_ITEM',
                        'md_appraisal_template_item_id' => $actualItemId,
                        'previous_value'                => $formatAuditValue($existingReviewer, $prevScore),
                        'new_value'                     => $formatAuditValue($newReviewer, $effectiveScore),
                        'notes'                         => 'Peninjau memperbarui nilai/catatan butir penilaian.',
                        'created_at'                    => date('Y-m-d H:i:s'),
                    ];
                }
            } else {
                $existingEval = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : ($resp->evaluator_response ?? []);
                $existingEval = is_array($existingEval) ? $existingEval : [];
                $newEval      = $this->prepareResponsePayload($existingEval, $row['evaluator_response'] ?? $row, $score, $isBoolean);

                if ($isBoolean) {
                    $resp->score = null;
                } elseif ($score !== null) {
                    $resp->score = $score;
                }
                $resp->evaluator_response = $newEval;
                $effectiveScore           = $resp->score !== null ? (float) $resp->score : null;

                // Check delta
                $scoreChanged   = ($score !== null && ($prevScore === null || round($score, 2) !== round($prevScore, 2)));
                $payloadChanged = (json_encode($existingEval) !== json_encode($newEval));

                if ($scoreChanged || $payloadChanged) {
                    $auditDeltas[] = [
                        'md_appraisal_id'               => $appraisalId,
                        'actor_id'                      => $userId,
                        'action'                        => 'UPDATE_ITEM_SCORE',
                        'md_appraisal_template_item_id' => $actualItemId,
                        'previous_value'                => $formatAuditValue($existingEval, $prevScore),
                        'new_value'                     => $formatAuditValue($newEval, $effectiveScore),
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

            if ($db->transStatus() === false) {
                $db->transRollback();
                throw new DatabaseException('Gagal menyimpan respons evaluasi ke database.');
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
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
    /**
     * Normalize checked_actions into canonical zero-based integer indices [0, 1, ...].
     */
    protected function normalizeCheckedActions(array $raw): array
    {
        $hasYorN = false;
        foreach ($raw as $v) {
            if (in_array(strtoupper((string) $v), ['Y', 'N'], true)) {
                $hasYorN = true;
                break;
            }
        }

        if ($hasYorN) {
            $indices = [];
            foreach ($raw as $k => $v) {
                if (in_array(strtoupper((string) $v), ['Y', '1', 'TRUE'], true)) {
                    $indices[] = (int) $k;
                }
            }
            return array_values($indices);
        }

        return array_values(array_map('intval', array_filter($raw, 'is_numeric')));
    }

    /**
     * Merge and standardize response JSON fields, omitting duplicate score when action_scores exists.
     */
    protected function prepareResponsePayload(array $existing, array $incoming, ?float $score, bool $isBoolean): array
    {
        $payload = array_merge($existing, $incoming);
        unset($payload['md_appraisal_template_item_id']);

        if (isset($incoming['action_scores']) && is_array($incoming['action_scores'])) {
            $payload['action_scores'] = array_values(array_map(static function ($s) {
                return round((float) $s, 2);
            }, $incoming['action_scores']));
        }
        if (isset($incoming['checked_actions']) && is_array($incoming['checked_actions'])) {
            $payload['checked_actions'] = $this->normalizeCheckedActions($incoming['checked_actions']);
        }
        if (isset($incoming['other_text'])) {
            $payload['other_text'] = trim((string) $incoming['other_text']);
        }

        if ($isBoolean || (! empty($payload['action_scores']) && is_array($payload['action_scores']))) {
            unset($payload['score']);
        } elseif ($score !== null) {
            $payload['score'] = round((float) $score, 2);
        }

        return $payload;
    }

    public function determineRatingLabel(float $score, ?array $ratingScale = null, bool $isOperator = false): string
    {
        if (! $isOperator && ! empty($ratingScale) && is_array($ratingScale)) {
            foreach ($ratingScale as $tier) {
                $min   = isset($tier['min']) ? (float) $tier['min'] : 0.00;
                $max   = isset($tier['max']) ? (float) $tier['max'] : 4.00;
                $label = $tier['label'] ?? '';

                if ($score >= $min && $score <= $max) {
                    return $label;
                }
            }
        }

        if ($isOperator) {
            $finalScoreInt = (int) $score;
            if ($finalScoreInt >= 13 && $finalScoreInt <= 15) {
                return 'Sangat Baik';
            }
            if ($finalScoreInt >= 8 && $finalScoreInt <= 12) {
                return 'Baik';
            }
            if ($finalScoreInt >= 5 && $finalScoreInt <= 7) {
                return 'Kurang';
            }
            return '-';
        }

        if ($score >= 3.50) {
            return 'Sangat Baik';
        }
        if ($score >= 2.75) {
            return 'Baik';
        }
        if ($score >= 2.00) {
            return 'Cukup';
        }
        return 'Kurang';
    }
}
