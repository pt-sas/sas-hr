<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class StandardizeAppraisalJsonAndContracts extends Migration
{
    public function up()
    {
        $db = $this->db;

        // 1. Heal md_appraisal.template_snapshot (Unescape double-encoded config strings)
        $appraisals = $db->table('md_appraisal')
            ->select('md_appraisal_id, template_snapshot')
            ->where('template_snapshot IS NOT NULL')
            ->get()
            ->getResultArray();

        foreach ($appraisals as $app) {
            $rawSnapshot = $app['template_snapshot'];
            $snapshot    = is_string($rawSnapshot) ? json_decode($rawSnapshot, true) : (array) $rawSnapshot;

            if (!is_array($snapshot)) {
                continue;
            }

            // Heal items
            if (!empty($snapshot['items']) && is_array($snapshot['items'])) {
                foreach ($snapshot['items'] as &$item) {
                    if (isset($item['config'])) {
                        if (is_string($item['config'])) {
                            $decoded = json_decode($item['config'], true);
                            $item['config'] = is_array($decoded) ? $decoded : [];
                        }
                    }

                    // Cast numeric IDs & weights
                    if (isset($item['weight'])) {
                        $item['weight'] = (float) $item['weight'];
                    }
                    if (isset($item['sequence_order'])) {
                        $item['sequence_order'] = (int) $item['sequence_order'];
                    }
                    if (isset($item['md_appraisal_template_item_id'])) {
                        $item['md_appraisal_template_item_id'] = (int) $item['md_appraisal_template_item_id'];
                    }
                    if (isset($item['md_appraisal_section_id'])) {
                        $item['md_appraisal_section_id'] = (int) $item['md_appraisal_section_id'];
                    }
                }
                unset($item);
            }

            // Heal sections
            if (!empty($snapshot['sections']) && is_array($snapshot['sections'])) {
                foreach ($snapshot['sections'] as &$sec) {
                    if (isset($sec['weight'])) {
                        $sec['weight'] = (float) $sec['weight'];
                    }
                    if (isset($sec['sequence_order'])) {
                        $sec['sequence_order'] = (int) $sec['sequence_order'];
                    }
                    if (isset($sec['md_appraisal_section_id'])) {
                        $sec['md_appraisal_section_id'] = (int) $sec['md_appraisal_section_id'];
                    }
                }
                unset($sec);
            }

            // Heal template
            if (!empty($snapshot['template']) && is_array($snapshot['template'])) {
                if (isset($snapshot['template']['version'])) {
                    $snapshot['template']['version'] = (int) $snapshot['template']['version'];
                }
                if (isset($snapshot['template']['md_appraisal_template_id'])) {
                    $snapshot['template']['md_appraisal_template_id'] = (int) $snapshot['template']['md_appraisal_template_id'];
                }
            }

            $cleanJson = json_encode($snapshot, JSON_UNESCAPED_UNICODE);
            $db->table('md_appraisal')
                ->where('md_appraisal_id', (int) $app['md_appraisal_id'])
                ->update(['template_snapshot' => $cleanJson]);
        }

        // 2. Normalize md_appraisal_response (Penilai & Peninjau responses)
        $responses = $db->table('md_appraisal_response')
            ->select('md_appraisal_response_id, evaluator_response, gm_response')
            ->where('evaluator_response IS NOT NULL OR gm_response IS NOT NULL')
            ->get()
            ->getResultArray();

        $normalizePayload = function ($raw) {
            if ($raw === null) {
                return null;
            }
            $p = is_string($raw) ? json_decode($raw, true) : (array) $raw;
            if (!is_array($p)) {
                return null;
            }

            // Remove redundant FK stored inside JSON
            unset($p['md_appraisal_template_item_id']);

            // Cast scores to float
            if (isset($p['score']) && $p['score'] !== null && $p['score'] !== '') {
                $p['score'] = (float) $p['score'];
            }

            // Cast action scores array to float list
            if (isset($p['action_scores']) && is_array($p['action_scores'])) {
                $p['action_scores'] = array_values(array_map('floatval', $p['action_scores']));
            }

            // Normalize checked_actions indices
            if (isset($p['checked_actions']) && is_array($p['checked_actions'])) {
                $numericIndices = [];
                foreach ($p['checked_actions'] as $idx => $v) {
                    if (is_numeric($v)) {
                        $numericIndices[] = (int) $v;
                    } elseif ($v === 'Y') {
                        $numericIndices[] = (int) $idx;
                    }
                }
                $p['checked_actions'] = array_values(array_unique($numericIndices));
            }

            // If empty, return null
            if (empty($p)) {
                return null;
            }

            return $p;
        };

        foreach ($responses as $row) {
            $newEval = $normalizePayload($row['evaluator_response']);
            $newGm   = $normalizePayload($row['gm_response']);

            $updateData = [
                'evaluator_response' => $newEval ? json_encode($newEval, JSON_UNESCAPED_UNICODE) : null,
                'gm_response'        => $newGm ? json_encode($newGm, JSON_UNESCAPED_UNICODE) : null,
            ];

            $db->table('md_appraisal_response')
                ->where('md_appraisal_response_id', (int) $row['md_appraisal_response_id'])
                ->update($updateData);
        }

        // 3. Normalize md_appraisal_audit_log (payload: [] -> null)
        $auditLogs = $db->table('md_appraisal_audit_log')
            ->select('md_appraisal_audit_log_id, previous_value, new_value')
            ->where('previous_value IS NOT NULL OR new_value IS NOT NULL')
            ->get()
            ->getResultArray();

        $normalizeAuditVal = function ($raw) {
            if ($raw === null) {
                return null;
            }
            $val = is_string($raw) ? json_decode($raw, true) : (array) $raw;
            if (!is_array($val)) {
                return null;
            }
            if (isset($val['payload']) && is_array($val['payload']) && empty($val['payload'])) {
                $val['payload'] = null;
            }
            if (isset($val['payload']['md_appraisal_template_item_id'])) {
                $val['payload']['md_appraisal_template_item_id'] = (int) $val['payload']['md_appraisal_template_item_id'];
            }
            return $val;
        };

        foreach ($auditLogs as $log) {
            $prev = $normalizeAuditVal($log['previous_value']);
            $next = $normalizeAuditVal($log['new_value']);

            $db->table('md_appraisal_audit_log')
                ->where('md_appraisal_audit_log_id', (int) $log['md_appraisal_audit_log_id'])
                ->update([
                    'previous_value' => $prev ? json_encode($prev, JSON_UNESCAPED_UNICODE) : null,
                    'new_value'      => $next ? json_encode($next, JSON_UNESCAPED_UNICODE) : null,
                ]);
        }
    }

    public function down()
    {
        // One-way data sanitization migration
    }
}
