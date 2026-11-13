<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AppraisalTemplate;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Exception;

class AppraisalTemplateMappingController extends BaseController
{
    protected BaseConnection $db;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->db    = \Config\Database::connect();
        $this->model = new M_AppraisalTemplate();
    }

    /**
     * Datatable / summary list of template target scoping in md_template_scope
     */
    public function showAll()
    {
        try {
            $templates = $this->db->table('md_appraisal_template')
                ->select('md_appraisal_template_id, template_code, title, version, isactive, updated_at')
                ->orderBy('md_appraisal_template_id', 'DESC')
                ->get()
                ->getResultArray();

            $scopes = $this->db->table('md_template_scope s')
                ->select("s.md_appraisal_template_id, s.scope_type, s.scope_id, "
                    . "COALESCE(b.name, d.name, l.name, p.name) AS scope_name")
                ->join('md_branch b', "b.md_branch_id = s.scope_id AND s.scope_type = 'BRANCH'", 'left')
                ->join('md_division d', "d.md_division_id = s.scope_id AND s.scope_type = 'DIVISION'", 'left')
                ->join('md_levelling l', "l.md_levelling_id = s.scope_id AND s.scope_type = 'LEVELLING'", 'left')
                ->join('md_position p', "p.md_position_id = s.scope_id AND s.scope_type = 'POSITION'", 'left')
                ->where('s.isactive', 'Y')
                ->get()
                ->getResultArray();

            $branchByTpl    = [];
            $divisionByTpl  = [];
            $levellingByTpl = [];
            $positionByTpl  = [];

            foreach ($scopes as $row) {
                $tId  = (int) $row['md_appraisal_template_id'];
                $item = [
                    'id'   => (int) $row['scope_id'],
                    'text' => $row['scope_name'] ?? ('#' . $row['scope_id']),
                ];
                if ($row['scope_type'] === 'BRANCH') {
                    $branchByTpl[$tId][] = $item;
                } elseif ($row['scope_type'] === 'DIVISION') {
                    $divisionByTpl[$tId][] = $item;
                } elseif ($row['scope_type'] === 'LEVELLING') {
                    $levellingByTpl[$tId][] = $item;
                } elseif ($row['scope_type'] === 'POSITION') {
                    $positionByTpl[$tId][] = $item;
                }
            }

            $data = [];
            $no   = 1;
            foreach ($templates as $tpl) {
                $tplId = (int) $tpl['md_appraisal_template_id'];
                $data[] = [
                    $no++,
                    $tpl['template_code'],
                    $tpl['title'],
                    (int) $tpl['version'],
                    $tpl['isactive'],
                    'branches'                 => $branchByTpl[$tplId] ?? [],
                    'divisions'                => $divisionByTpl[$tplId] ?? [],
                    'levellings'               => $levellingByTpl[$tplId] ?? [],
                    'positions'                => $positionByTpl[$tplId] ?? [],
                    'md_appraisal_template_id' => $tplId,
                ];
            }

            return $this->response->setJSON([
                'draw'            => (int) ($this->request->getVar('draw') ?? 1),
                'recordsTotal'    => count($data),
                'recordsFiltered' => count($data),
                'data'            => $data,
            ]);
        } catch (Exception $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    /**
     * Sync target scoping in md_template_scope (BRANCH, DIVISION, LEVELLING, POSITION)
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
            $this->db->table('md_template_scope')->where('md_appraisal_template_id', $templateId)->delete();

            foreach ([
                'BRANCH'    => $branchIds,
                'DIVISION'  => $divisionIds,
                'LEVELLING' => $levellingIds,
                'POSITION'  => $positionIds,
            ] as $scopeType => $ids) {
                foreach ($ids as $scopeId) {
                    $this->db->table('md_template_scope')->insert([
                        'md_appraisal_template_id' => $templateId,
                        'scope_type'               => $scopeType,
                        'scope_id'                 => $scopeId,
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
     * Resolve active template across md_template_scope (BRANCH, DIVISION, LEVELLING, POSITION)
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
            $scopeRows = !empty($candidateIds)
                ? $this->db->table('md_template_scope')
                    ->select('md_appraisal_template_id, scope_type, scope_id')
                    ->whereIn('md_appraisal_template_id', $candidateIds)
                    ->where('isactive', 'Y')
                    ->get()->getResultArray()
                : [];
            $scopesByTpl = [];
            foreach ($scopeRows as $sr) {
                $scopesByTpl[(int) $sr['md_appraisal_template_id']][$sr['scope_type']][] = (int) $sr['scope_id'];
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
            $this->db->table('md_template_scope')->where('md_appraisal_template_id', $templateId)->delete();

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
