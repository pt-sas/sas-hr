<?php

if (! function_exists('appraisal_format_period')) {
    /**
     * Format appraisal cycle period with Indonesian month abbreviations.
     *
     * @param object|array|null $cycle
     */
    function appraisal_format_period($cycle): string
    {
        if (empty($cycle)) {
            return '-';
        }

        $cycleTitle = is_object($cycle) ? ($cycle->title ?? '-') : ($cycle['title'] ?? '-');
        $startDate  = is_object($cycle) ? ($cycle->start_date ?? null) : ($cycle['start_date'] ?? null);
        $endDate    = is_object($cycle) ? ($cycle->end_date ?? null) : ($cycle['end_date'] ?? null);

        if (! empty($startDate) && ! empty($endDate)) {
            $monthMap = ['May' => 'Mei', 'Aug' => 'Agu', 'Oct' => 'Okt', 'Dec' => 'Des'];
            $startFmt = strtr(date('d M Y', strtotime($startDate)), $monthMap);
            $endFmt   = strtr(date('d M Y', strtotime($endDate)), $monthMap);
            return $startFmt . ' s/d ' . $endFmt;
        }

        return ! empty($cycleTitle) ? (string) $cycleTitle : '-';
    }
}

if (! function_exists('appraisal_parse_actions')) {
    /**
     * Parse key_actions / checklist items from JSON string, newline-delimited text, or array.
     *
     * @param mixed $raw
     */
    function appraisal_parse_actions($raw): array
    {
        if (empty($raw)) {
            return [];
        }
        if (is_array($raw)) {
            return array_values(array_filter(array_map('trim', $raw)));
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('trim', $decoded)));
            }
            $lines = explode("\n", $raw);
            return array_values(array_filter(array_map('trim', $lines)));
        }
        return [];
    }
}

if (! function_exists('appraisal_parse_checked')) {
    /**
     * Check whether a checklist option at $idx is checked.
     * Supports canonical integer index arrays ([0, 1]) as well as legacy ['Y', 'N'] or text arrays.
     */
    function appraisal_parse_checked(int $idx, array $checkedList, ?string $actionText = null): bool
    {
        if (empty($checkedList)) {
            return false;
        }

        $hasYorN = false;
        foreach ($checkedList as $v) {
            if (in_array(strtoupper((string) $v), ['Y', 'N'], true)) {
                $hasYorN = true;
                break;
            }
        }

        if ($hasYorN) {
            $val = $checkedList[$idx] ?? $checkedList[(string) $idx] ?? null;
            return in_array(strtoupper((string) $val), ['Y', '1', 'TRUE'], true);
        }

        if (in_array((string) $idx, array_map('strval', $checkedList), true)) {
            return true;
        }

        return $actionText !== null && in_array($actionText, $checkedList, true);
    }
}

if (! function_exists('appraisal_normalize_snapshot')) {
    /**
     * Normalize template_snapshot / schema_json into expanded {template, sections, items} format.
     * Supports both the compact schema_json contract and legacy flat snapshots.
     *
     * @param mixed $raw
     */
    function appraisal_normalize_snapshot($raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $data    = is_array($decoded) ? $decoded : [];
        } elseif (is_array($raw)) {
            $data = $raw;
        } else {
            $data = [];
        }

        if (empty($data)) {
            return ['template' => [], 'sections' => [], 'items' => []];
        }

        return \App\Services\AppraisalTemplateServices::expandSchema($data);
    }
}

if (! function_exists('appraisal_is_operator')) {
    /**
     * Detect whether a template snapshot uses Operator/Pelaksana sections or input types.
     * Supports both compact schema_json (target_group, sections[].type) and expanded format.
     */
    function appraisal_is_operator(array $snapshot): bool
    {
        if (strtoupper((string) ($snapshot['template']['target_group'] ?? '')) === 'OPERATOR') {
            return true;
        }
        foreach ($snapshot['sections'] ?? [] as $s) {
            $st = strtoupper((string) ($s['section_type'] ?? $s['type'] ?? ''));
            if ($st === 'WEIGHTED_CHECKLIST' || $st === 'SINGLE_CHECKLIST') {
                return true;
            }
        }
        foreach ($snapshot['items'] ?? [] as $it) {
            $itType = strtoupper((string) ($it['input_type'] ?? ''));
            if ($itType === 'WEIGHTED_CHECKLIST' || $itType === 'SINGLE_CHECKLIST') {
                return true;
            }
        }
        return false;
    }
}

if (! function_exists('appraisal_rating_meta')) {
    /**
     * Resolve rating label and CSS class for Staff (0-4) or Operator (totNilai 5-15) overall scores.
     */
    function appraisal_rating_meta(?float $score, bool $isOperator = false): array
    {
        if ($score === null) {
            return ['label' => 'Belum Dinilai', 'class' => 'r-muted'];
        }

        if ($isOperator) {
            $finalScoreInt = (int) $score;
            if ($finalScoreInt >= 13 && $finalScoreInt <= 15) {
                return ['label' => 'Sangat Baik', 'class' => 'r-great'];
            }
            if ($finalScoreInt >= 8 && $finalScoreInt <= 12) {
                return ['label' => 'Baik', 'class' => 'r-good'];
            }
            if ($finalScoreInt >= 5 && $finalScoreInt <= 7) {
                return ['label' => 'Kurang', 'class' => 'r-low'];
            }
            return ['label' => '-', 'class' => 'r-muted'];
        }

        if ($score >= 3.50) {
            return ['label' => 'Sangat Baik', 'class' => 'r-great'];
        }
        if ($score >= 2.75) {
            return ['label' => 'Baik', 'class' => 'r-good'];
        }
        if ($score >= 2.00) {
            return ['label' => 'Cukup', 'class' => 'r-fair'];
        }
        return ['label' => 'Kurang', 'class' => 'r-low'];
    }
}

if (! function_exists('appraisal_extract_complete_summary')) {
    /**
     * Compute summary lists (Nilai 4, Nilai 1, Kompetensi Tambahan) or Operator total nilai
     * for the Complete Appraisal report view.
     */
    function appraisal_extract_complete_summary(array $snapshot, array $responsesByItemId, bool $isOperator): array
    {
        $snapshot = appraisal_normalize_snapshot($snapshot);
        $items    = $snapshot['items'] ?? [];

        if ($isOperator) {
            $totNilai     = 0.0;
            $checkedCount = 0;
            foreach ($items as $item) {
                $inputType = strtoupper($item['input_type'] ?? '');
                $itemId    = (int) ($item['md_appraisal_template_item_id'] ?? $item['template_item_id'] ?? 0);
                $resp      = $responsesByItemId[$itemId] ?? null;

                if ($inputType === 'WEIGHTED_CHECKLIST') {
                    $cfg   = (array) ($item['config'] ?? []);
                    $bobot = (float) ($item['weight'] ?? $cfg['bobot'] ?? 1.0);
                    if ($bobot <= 0) {
                        $bobot = 1.0;
                    }
                    $score = null;
                    if ($resp) {
                        if (! empty($resp->reviewer_response)) {
                            $p     = is_string($resp->reviewer_response) ? json_decode($resp->reviewer_response, true) : $resp->reviewer_response;
                            $score = isset($p['score']) ? (float) $p['score'] : ($resp->score !== null ? (float) $resp->score : null);
                        } elseif (! empty($resp->evaluator_response)) {
                            $p     = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
                            $score = isset($p['score']) ? (float) $p['score'] : ($resp->score !== null ? (float) $resp->score : null);
                        } elseif ($resp->score !== null) {
                            $score = (float) $resp->score;
                        }
                    }
                    if ($score !== null) {
                        $totNilai += ($bobot * $score);
                    }
                } elseif ($inputType === 'SINGLE_CHECKLIST' && $resp) {
                    $p = [];
                    if (! empty($resp->reviewer_response)) {
                        $p = is_string($resp->reviewer_response) ? json_decode($resp->reviewer_response, true) : $resp->reviewer_response;
                    } elseif (! empty($resp->evaluator_response)) {
                        $p = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
                    }
                    if (isset($p['boolean_value']) && in_array(strtoupper((string) $p['boolean_value']), ['Y', '1', 'TRUE'], true)) {
                        $checkedCount++;
                    } elseif (! empty($p['checked_actions'])) {
                        $checkedCount++;
                    }
                }
            }
            $suffixA          = str_repeat('A', $checkedCount);
            $displaySkorAkhir = ((int) $totNilai) . $suffixA;

            return [
                'totNilai'         => $totNilai,
                'checkedCount'     => $checkedCount,
                'suffixA'          => $suffixA,
                'displaySkorAkhir' => $displaySkorAkhir,
            ];
        }

        $itemsNilai4             = [];
        $itemsNilai1             = [];
        $itemsKompetensiTambahan = [];
        $cleanText               = static function (string $t): string {
            return preg_replace('/^[a-z0-9][\.\)]\s*/i', '', trim($t));
        };

        foreach ($items as $item) {
            $itemId    = (int) ($item['md_appraisal_template_item_id'] ?? $item['template_item_id'] ?? 0);
            $cfg       = (array) ($item['config'] ?? []);
            $inputType = strtoupper($item['input_type'] ?? 'SCORE');
            $actions   = appraisal_parse_actions($cfg['key_actions'] ?? null);
            $resp      = $responsesByItemId[$itemId] ?? null;
            $payload   = [];
            if ($resp && ! empty($resp->reviewer_response)) {
                $payload = is_string($resp->reviewer_response) ? json_decode($resp->reviewer_response, true) : $resp->reviewer_response;
            } elseif ($resp && ! empty($resp->evaluator_response)) {
                $payload = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
            }

            if ($inputType === 'BOOLEAN') {
                $checkedActions = $payload['checked_actions'] ?? [];
                $booleanValue   = $payload['boolean_value'] ?? null;
                if (! empty($actions)) {
                    foreach ($actions as $kIdx => $ka) {
                        $isChecked = is_array($checkedActions) && appraisal_parse_checked((int) $kIdx, $checkedActions, (string) $ka);
                        if ($isChecked) {
                            $itemsKompetensiTambahan[] = $cleanText($ka);
                        }
                    }
                } elseif (in_array(strtoupper((string) ($booleanValue ?? '')), ['Y', '1', 'TRUE'], true)) {
                    $title = ! empty($cfg['competency']) ? $cfg['competency'] : ($item['title'] ?? '');
                    if ($title !== '') {
                        $itemsKompetensiTambahan[] = $cleanText($title);
                    }
                }
            } else {
                $savedActionScores = $payload['action_scores'] ?? [];
                $savedItemScore    = isset($payload['score']) && $payload['score'] !== ''
                    ? (float) $payload['score']
                    : (($resp && $resp->score !== null) ? (float) $resp->score : null);

                if (! empty($actions)) {
                    foreach ($actions as $aIdx => $actionText) {
                        $s = $savedActionScores[$aIdx] ?? $savedActionScores[(string) $aIdx] ?? $savedItemScore ?? 3.00;
                        $s = max(1.00, min(4.00, round(((float) $s) / 0.05) * 0.05));
                        if ($s >= 3.99) {
                            $itemsNilai4[] = $cleanText($actionText);
                        } elseif ($s <= 1.01) {
                            $itemsNilai1[] = $cleanText($actionText);
                        }
                    }
                } else {
                    $s     = max(1.00, min(4.00, round(((float) ($savedItemScore ?? 3.00)) / 0.05) * 0.05));
                    $title = ! empty($cfg['competency']) ? $cfg['competency'] : ($item['title'] ?? '');
                    if ($s >= 3.99 && $title !== '') {
                        $itemsNilai4[] = $cleanText($title);
                    } elseif ($s <= 1.01 && $title !== '') {
                        $itemsNilai1[] = $cleanText($title);
                    }
                }
            }
        }

        return [
            'itemsNilai4'             => $itemsNilai4,
            'itemsNilai1'             => $itemsNilai1,
            'itemsKompetensiTambahan' => $itemsKompetensiTambahan,
        ];
    }
}
