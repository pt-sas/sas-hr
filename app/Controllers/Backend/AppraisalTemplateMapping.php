<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AppraisalTemplate;
use App\Services\AppraisalTemplateServices;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Exception;

class AppraisalTemplateMapping extends BaseController
{
    protected BaseConnection $db;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->db    = \Config\Database::connect();
        $this->model = new M_AppraisalTemplate();
    }

    /**
     * Datatable / summary list of template target scoping across the 4 mapping tables
     */
    public function showAll()
    {
        if ($this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['error' => 'Method Not Allowed']);
        }

        try {
            $table  = $this->model->table;
            $select = "{$table}.md_appraisal_template_id, {$table}.template_code, {$table}.title, {$table}.version, {$table}.isactive, {$table}.updated_at";
            $order  = [null, "{$table}.template_code", "{$table}.title", "{$table}.version", "{$table}.isactive"];
            $sort   = [$table . '.md_appraisal_template_id' => 'DESC'];
            $search = $this->model->column_search;
            $join   = $this->model->getJoin();

            $templates   = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join);
            $templateIds = array_map(static fn($t) => (int) $t->md_appraisal_template_id, $templates);

            $byTpl = [];
            if (! empty($templateIds)) {
                foreach (AppraisalTemplateServices::SCOPE_TABLES as $key => $cfg) {
                    $fk   = $cfg['fk'];
                    $rows = $this->db->table("{$cfg['table']} s")
                        ->select("s.md_appraisal_template_id, s.{$fk} AS scope_id, m.name AS scope_name")
                        ->join("{$cfg['master']} m", "m.{$fk} = s.{$fk}", 'left')
                        ->whereIn('s.md_appraisal_template_id', $templateIds)
                        ->where('s.isactive', 'Y')
                        ->get()
                        ->getResultArray();

                    foreach ($rows as $row) {
                        $tId = (int) $row['md_appraisal_template_id'];
                        $byTpl[$key][$tId][] = [
                            'id'   => (int) $row['scope_id'],
                            'text' => $row['scope_name'] ?? ('#' . $row['scope_id']),
                        ];
                    }
                }
            }

            $data = [];
            $no   = (int) ($this->request->getPost('start') ?? 0) + 1;
            foreach ($templates as $tpl) {
                $tplId  = (int) $tpl->md_appraisal_template_id;
                $data[] = [
                    $no++,
                    $tpl->template_code,
                    $tpl->title,
                    (int) $tpl->version,
                    $tpl->isactive,
                    'branches'                 => $byTpl['branch_ids'][$tplId] ?? [],
                    'divisions'                => $byTpl['division_ids'][$tplId] ?? [],
                    'levellings'               => $byTpl['levelling_ids'][$tplId] ?? [],
                    'positions'                => $byTpl['position_ids'][$tplId] ?? [],
                    'md_appraisal_template_id' => $tplId,
                ];
            }

            return $this->response->setJSON([
                'draw'            => (int) ($this->request->getPost('draw') ?? 1),
                'recordsTotal'    => $this->datatable->countAll($table, $select, $order, $sort, $search, $join),
                'recordsFiltered' => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join),
                'data'            => $data,
            ]);
        } catch (Exception $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    /**
     * Sync target scoping across the 4 mapping tables (branch, division, levelling, position)
     * for a given md_appraisal_template_id
     */
    public function create()
    {
        if (strpos($this->request->getHeaderLine('Content-Type'), 'application/json') !== false) {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                $_POST = $json;
            }
        }

        $post = $this->request->getPost();
        if (! empty($post['payload'])) {
            $decoded = is_string($post['payload']) ? json_decode($post['payload'], true) : $post['payload'];
            if (is_array($decoded)) {
                $post = array_merge($post, $decoded);
            }
        }

        $templateId = (int) ($post['md_appraisal_template_id'] ?? $post['appraisal_template_id'] ?? 0);
        if ($templateId <= 0) {
            return $this->response->setJSON(message('error', false, 'Template ID wajib diisi.'));
        }

        $template = $this->db->table('md_appraisal_template')
            ->where('md_appraisal_template_id', $templateId)
            ->get()
            ->getRowArray();

        if (! $template) {
            return $this->response->setJSON(message('error', false, 'Template tidak ditemukan.'));
        }

        $scoping = is_array($post['scoping'] ?? null) ? $post['scoping'] : $post;

        $branchIds    = $this->normalizeIdList($scoping['branch_ids'] ?? $scoping['md_branch_id'] ?? []);
        $divisionIds  = $this->normalizeIdList($scoping['division_ids'] ?? $scoping['md_division_id'] ?? []);
        $levellingIds = $this->normalizeIdList($scoping['levelling_ids'] ?? $scoping['md_levelling_id'] ?? $scoping['leveling_ids'] ?? []);
        $positionIds  = $this->normalizeIdList($scoping['position_ids'] ?? $scoping['md_position_id'] ?? []);

        $userId = (int) ($this->access->getSessionUser() ?? 0);
        $now    = date('Y-m-d H:i:s');

        $this->db->transBegin();

        try {
            $idsByKey = [
                'branch_ids'    => $branchIds,
                'division_ids'  => $divisionIds,
                'levelling_ids' => $levellingIds,
                'position_ids'  => $positionIds,
            ];

            foreach (AppraisalTemplateServices::SCOPE_TABLES as $key => $cfg) {
                $this->db->table($cfg['table'])->where('md_appraisal_template_id', $templateId)->delete();
                foreach ($idsByKey[$key] ?? [] as $scopeId) {
                    $this->db->table($cfg['table'])->insert([
                        'md_appraisal_template_id' => $templateId,
                        $cfg['fk']                 => $scopeId,
                        'isactive'                 => 'Y',
                        'created_by'               => $userId,
                        'updated_by'               => $userId,
                        'created_at'               => $now,
                        'updated_at'               => $now,
                    ]);
                }
            }

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                return $this->response->setJSON(message('error', false, 'Gagal menyimpan target scoping template.'));
            }

            $this->db->transCommit();
            return $this->response->setJSON(message('success', true, notification('updated')));
        } catch (Exception $e) {
            $this->db->transRollback();
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    /**
     * Resolve active template across the 4 scope mapping tables
     */
    public function resolve()
    {
        $branchId    = (int) ($this->request->getVar('branch_id') ?? $this->request->getVar('md_branch_id') ?? 0);
        $divisionId  = (int) ($this->request->getVar('division_id') ?? $this->request->getVar('md_division_id') ?? 0);
        $levellingId = (int) ($this->request->getVar('levelling_id') ?? $this->request->getVar('md_levelling_id') ?? $this->request->getVar('leveling_id') ?? 0);
        $positionId  = (int) ($this->request->getVar('position_id') ?? $this->request->getVar('md_position_id') ?? 0);

        try {
            $candidates = $this->db->table('md_appraisal_template')
                ->select('md_appraisal_template_id, template_code, title, version')
                ->where('isactive', 'Y')
                ->orderBy('version', 'DESC')
                ->orderBy('md_appraisal_template_id', 'DESC')
                ->get()
                ->getResultArray();

            $candidateIds = array_column($candidates, 'md_appraisal_template_id');
            $scopesByTpl  = [];
            if (! empty($candidateIds)) {
                foreach (AppraisalTemplateServices::SCOPE_TABLES as $cfg) {
                    $rows = $this->db->table($cfg['table'])
                        ->select("md_appraisal_template_id, {$cfg['fk']} AS scope_id")
                        ->whereIn('md_appraisal_template_id', $candidateIds)
                        ->where('isactive', 'Y')
                        ->get()->getResultArray();
                    foreach ($rows as $sr) {
                        $scopesByTpl[(int) $sr['md_appraisal_template_id']][$cfg['type']][] = (int) $sr['scope_id'];
                    }
                }
            }

            $bestMatch = null;
            $bestScore = -1;

            foreach ($candidates as $tpl) {
                $tplId         = (int) $tpl['md_appraisal_template_id'];
                $tplScopes     = $scopesByTpl[$tplId] ?? [];
                $tplBranches   = $tplScopes['BRANCH'] ?? [];
                $tplDivisions  = $tplScopes['DIVISION'] ?? [];
                $tplLevellings = $tplScopes['LEVELLING'] ?? [];
                $tplPositions  = $tplScopes['POSITION'] ?? [];

                // If a template specifies targets in a dimension, the requested ID must match
                if (! empty($tplPositions) && ($positionId <= 0 || ! in_array($positionId, $tplPositions, true))) {
                    continue;
                }
                if (! empty($tplLevellings) && ($levellingId <= 0 || ! in_array($levellingId, $tplLevellings, true))) {
                    continue;
                }
                if (! empty($tplDivisions) && ($divisionId <= 0 || ! in_array($divisionId, $tplDivisions, true))) {
                    continue;
                }
                if (! empty($tplBranches) && ($branchId <= 0 || ! in_array($branchId, $tplBranches, true))) {
                    continue;
                }

                // Specificity score: Position (8) > Levelling (4) > Division (2) > Branch (1) > Global (0)
                $score = (! empty($tplPositions) ? 8 : 0)
                    + (! empty($tplLevellings) ? 4 : 0)
                    + (! empty($tplDivisions) ? 2 : 0)
                    + (! empty($tplBranches) ? 1 : 0);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $tpl;
                }
            }

            if (! $bestMatch) {
                return $this->response->setJSON(message('error', false, 'Tidak ada template aktif yang cocok dengan kriteria target.'));
            }

            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Template resolved successfully.',
                'data'    => [
                    'md_branch_id'             => $branchId,
                    'md_division_id'           => $divisionId,
                    'md_levelling_id'          => $levellingId,
                    'md_position_id'           => $positionId,
                    'md_appraisal_template_id' => (int) $bestMatch['md_appraisal_template_id'],
                    'appraisal_template_id'    => (int) $bestMatch['md_appraisal_template_id'],
                    'template_code'            => $bestMatch['template_code'],
                    'title'                    => $bestMatch['title'],
                    'version'                  => (int) $bestMatch['version'],
                    'specificity_score'        => $bestScore,
                ],
            ]);
        } catch (Exception $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    /**
     * Clear target scoping mappings for a template ID
     */
    public function destroy($id = null)
    {
        $templateId = (int) ($id ?? $this->request->getVar('id') ?? 0);
        if ($templateId <= 0) {
            return $this->response->setJSON(message('error', false, 'ID template tidak valid.'));
        }

        $this->db->transBegin();

        try {
            foreach (AppraisalTemplateServices::SCOPE_TABLES as $cfg) {
                $this->db->table($cfg['table'])->where('md_appraisal_template_id', $templateId)->delete();
            }

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                return $this->response->setJSON(message('error', false, 'Gagal menghapus target scoping template.'));
            }

            $this->db->transCommit();
            return $this->response->setJSON(message('success', true, notification('deleted')));
        } catch (Exception $e) {
            $this->db->transRollback();
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    /**
     * Normalize comma-separated string or array of IDs into unique positive integers
     *
     * @param mixed $raw
     * @return int[]
     */
    private function normalizeIdList($raw): array
    {
        if (is_string($raw)) {
            $raw = array_filter(array_map('trim', explode(',', $raw)), static fn($v) => $v !== '');
        }
        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $item) {
            $val = is_array($item) ? (int) ($item['id'] ?? 0) : (int) $item;
            if ($val > 0) {
                $ids[$val] = $val;
            }
        }

        return array_values($ids);
    }
}
