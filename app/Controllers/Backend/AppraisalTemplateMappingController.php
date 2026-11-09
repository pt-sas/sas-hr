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
     * Datatable / summary list of template target scoping across
     * md_template_branch, md_template_division, and md_template_levelling
     */
    public function showAll()
    {
        try {
            $templates = $this->db->table('md_appraisal_template')
                ->select('md_appraisal_template_id, template_code, title, version, isactive, updated_at')
                ->orderBy('md_appraisal_template_id', 'DESC')
                ->get()
                ->getResultArray();

            $branches = $this->db->table('md_template_branch tb')
                ->select('tb.md_template_branch_id, tb.md_appraisal_template_id, tb.md_branch_id, b.name AS branch_name')
                ->join('md_branch b', 'b.md_branch_id = tb.md_branch_id', 'left')
                ->where('tb.isactive', 'Y')
                ->get()
                ->getResultArray();

            $divisions = $this->db->table('md_template_division td')
                ->select('td.md_template_division_id, td.md_appraisal_template_id, td.md_division_id, d.name AS division_name')
                ->join('md_division d', 'd.md_division_id = td.md_division_id', 'left')
                ->where('td.isactive', 'Y')
                ->get()
                ->getResultArray();

            $levellings = $this->db->table('md_template_levelling tl')
                ->select('tl.md_template_levelling_id, tl.md_appraisal_template_id, tl.md_levelling_id, l.name AS levelling_name')
                ->join('md_levelling l', 'l.md_levelling_id = tl.md_levelling_id', 'left')
                ->where('tl.isactive', 'Y')
                ->get()
                ->getResultArray();

            $branchByTpl    = [];
            $divisionByTpl  = [];
            $levellingByTpl = [];

            foreach ($branches as $row) {
                $branchByTpl[(int) $row['md_appraisal_template_id']][] = [
                    'id'   => (int) $row['md_branch_id'],
                    'text' => $row['branch_name'] ?? ('#' . $row['md_branch_id']),
                ];
            }

            foreach ($divisions as $row) {
                $divisionByTpl[(int) $row['md_appraisal_template_id']][] = [
                    'id'   => (int) $row['md_division_id'],
                    'text' => $row['division_name'] ?? ('#' . $row['md_division_id']),
                ];
            }

            foreach ($levellings as $row) {
                $levellingByTpl[(int) $row['md_appraisal_template_id']][] = [
                    'id'   => (int) $row['md_levelling_id'],
                    'text' => $row['levelling_name'] ?? ('#' . $row['md_levelling_id']),
                ];
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
                    'positions'                => $levellingByTpl[$tplId] ?? [], // Backward compatibility
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
     * Sync target scoping tables (md_template_branch, md_template_division, md_template_levelling)
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
        $levellingIds = $this->normalizeIdList($scoping['levelling_ids'] ?? $scoping['md_levelling_id'] ?? $scoping['leveling_ids'] ?? $scoping['position_ids'] ?? []);

        $userId = (int) ($this->access->getSessionUser() ?? 0);
        $now    = date('Y-m-d H:i:s');

        $this->db->transBegin();

        try {
            $this->db->table('md_template_branch')->where('md_appraisal_template_id', $templateId)->delete();
            foreach ($branchIds as $branchId) {
                $this->db->table('md_template_branch')->insert([
                    'md_appraisal_template_id' => $templateId,
                    'md_branch_id'             => $branchId,
                    'isactive'                 => 'Y',
                    'created_by'               => $userId,
                    'updated_by'               => $userId,
                    'created_at'               => $now,
                    'updated_at'               => $now,
                ]);
            }

            $this->db->table('md_template_division')->where('md_appraisal_template_id', $templateId)->delete();
            foreach ($divisionIds as $divisionId) {
                $this->db->table('md_template_division')->insert([
                    'md_appraisal_template_id' => $templateId,
                    'md_division_id'           => $divisionId,
                    'isactive'                 => 'Y',
                    'created_by'               => $userId,
                    'updated_by'               => $userId,
                    'created_at'               => $now,
                    'updated_at'               => $now,
                ]);
            }

            $this->db->table('md_template_levelling')->where('md_appraisal_template_id', $templateId)->delete();
            foreach ($levellingIds as $levellingId) {
                $this->db->table('md_template_levelling')->insert([
                    'md_appraisal_template_id' => $templateId,
                    'md_levelling_id'          => $levellingId,
                    'isactive'                 => 'Y',
                    'created_by'               => $userId,
                    'updated_by'               => $userId,
                    'created_at'               => $now,
                    'updated_at'               => $now,
                ]);
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
     * Resolve active template across md_template_branch, md_template_division, and md_template_levelling
     */
    public function resolve()
    {
        $branchId    = (int) ($this->request->getVar('branch_id') ?? $this->request->getVar('md_branch_id') ?? 0);
        $divisionId  = (int) ($this->request->getVar('division_id') ?? $this->request->getVar('md_division_id') ?? 0);
        $levellingId = (int) ($this->request->getVar('levelling_id') ?? $this->request->getVar('md_levelling_id') ?? $this->request->getVar('leveling_id') ?? $this->request->getVar('position_id') ?? 0);

        try {
            $candidates = $this->db->table('md_appraisal_template')
                ->select('md_appraisal_template_id, template_code, title, version')
                ->where('isactive', 'Y')
                ->orderBy('version', 'DESC')
                ->orderBy('md_appraisal_template_id', 'DESC')
                ->get()
                ->getResultArray();

            $bestMatch = null;
            $bestScore = -1;

            foreach ($candidates as $tpl) {
                $tplId = (int) $tpl['md_appraisal_template_id'];

                $tplBranches = array_column(
                    $this->db->table('md_template_branch')
                        ->select('md_branch_id')
                        ->where('md_appraisal_template_id', $tplId)
                        ->where('isactive', 'Y')
                        ->get()
                        ->getResultArray(),
                    'md_branch_id'
                );

                $tplDivisions = array_column(
                    $this->db->table('md_template_division')
                        ->select('md_division_id')
                        ->where('md_appraisal_template_id', $tplId)
                        ->where('isactive', 'Y')
                        ->get()
                        ->getResultArray(),
                    'md_division_id'
                );

                $tplLevellings = array_column(
                    $this->db->table('md_template_levelling')
                        ->select('md_levelling_id')
                        ->where('md_appraisal_template_id', $tplId)
                        ->where('isactive', 'Y')
                        ->get()
                        ->getResultArray(),
                    'md_levelling_id'
                );

                $tplBranches   = array_map('intval', $tplBranches);
                $tplDivisions  = array_map('intval', $tplDivisions);
                $tplLevellings = array_map('intval', $tplLevellings);

                // If a template specifies targets in a dimension, the requested ID must match
                if (! empty($tplLevellings) && ($levellingId <= 0 || ! in_array($levellingId, $tplLevellings, true))) {
                    continue;
                }
                if (! empty($tplDivisions) && ($divisionId <= 0 || ! in_array($divisionId, $tplDivisions, true))) {
                    continue;
                }
                if (! empty($tplBranches) && ($branchId <= 0 || ! in_array($branchId, $tplBranches, true))) {
                    continue;
                }

                // Specificity score: Levelling (4) > Division (2) > Branch (1) > Global (0)
                $score = (! empty($tplLevellings) ? 4 : 0)
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
                    'md_position_id'           => $levellingId, // Backward compatibility alias
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
            $this->db->table('md_template_branch')->where('md_appraisal_template_id', $templateId)->delete();
            $this->db->table('md_template_division')->where('md_appraisal_template_id', $templateId)->delete();
            $this->db->table('md_template_levelling')->where('md_appraisal_template_id', $templateId)->delete();

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
