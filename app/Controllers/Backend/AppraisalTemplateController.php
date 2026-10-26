<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Entities\AppraisalTemplate;
use App\Models\M_AppraisalTemplate;
use App\Models\M_AppraisalTemplateItem;
use App\Services\AppraisalTemplateServices;
use App\Models\M_Employee;
use Config\Services;
use Exception;

class AppraisalTemplateController extends BaseController
{
    protected AppraisalTemplateServices $templateService; // Fixed: removed trailing 's' to match usage
    protected M_Employee $mEmployee;                      // Fixed: declared property

    public function __construct()
    {
        $this->request         = Services::request();
        $this->model           = new M_AppraisalTemplate();
        $this->modelDetail     = new M_AppraisalTemplateItem();
        $this->entity          = new AppraisalTemplate();
        $this->mEmployee       = new M_Employee($this->request);
        $this->templateService = new AppraisalTemplateServices();

        $this->primaryKey      = $this->model->primaryKey;
        $this->db              = \Config\Database::connect();
        $this->menu            = 'appraisal-template';
    }

    /**
     * Render Main Page View & Load Dropdown Data
     */
    public function index()
    {

        return $this->template->render('masterdata/appraisal-template/v_appraisal_template', []);
    
    }
    /**
     * Datatable Processing with Filter Support
     */
    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            try {
                $table  = $this->model->table;
                $select = $this->model->getSelect();
                $join   = $this->model->getJoin();
                $order  = $this->model->column_order;
                $sort   = $this->model->order;
                $search = $this->model->column_search;

                // Process Filter Array from Form
                $where      = [];
                $formFilter = $this->request->getPost('form') ?? [];

                foreach ($formFilter as $row) {
                    $name  = $row['name']  ?? null;
                    $value = $row['value'] ?? null;

                    if ($value === '' || $value === null || $value === '0') {
                        continue;
                    }

                    $scopeMap = [
                        'md_branch_id'    => 'md_template_branch',
                        'md_division_id'  => 'md_template_division',
                        'md_levelling_id' => 'md_template_levelling',
                    ];
                    if (isset($scopeMap[$name])) {
                        $tbl = $scopeMap[$name];
                        $val = (int) $value;
                        $where[] = "EXISTS (SELECT 1 FROM {$tbl} f WHERE f.md_appraisal_template_id = md_appraisal_template.md_appraisal_template_id AND f.{$name} = {$val} AND f.isactive = 'Y')";
                    }
                    if ($name === 'status') {
                        $where['md_appraisal_template.status'] = $value;
                    }
                }

                $data   = [];
                $number = (int) ($this->request->getPost('start') ?? 0);

                $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

                foreach ($list as $value) {
                    $row = [];

                    // Fixed: md_appraisal_template_id instead of appraisal_template_id
                    $ID           = is_object($value) ? ($value->md_appraisal_template_id ?? null) : ($value['md_appraisal_template_id'] ?? null);
                    $branchName   = is_object($value) ? ($value->branch_name ?? 'Semua Cabang') : ($value['branch_name'] ?? 'Semua Cabang');
                    $divisionName = is_object($value) ? ($value->division_name ?? 'Semua Divisi') : ($value['division_name'] ?? 'Semua Divisi');
                    $levelName    = is_object($value) ? ($value->level_name ?? 'Semua Level') : ($value['level_name'] ?? 'Semua Level');
                    $templateCode = is_object($value) ? ($value->template_code ?? '-') : ($value['template_code'] ?? '-');
                    $version      = is_object($value) ? ($value->version ?? '1') : ($value['version'] ?? '1');
                    $createdAt    = is_object($value) ? ($value->created_at ?? null) : ($value['created_at'] ?? null);
                    $status       = is_object($value) ? ($value->status ?? M_AppraisalTemplate::STATUS_DRAFT) : ($value['status'] ?? M_AppraisalTemplate::STATUS_DRAFT);

                    $number++;

                    $dateFormatted = !empty($createdAt) ? date('d-m-Y', strtotime($createdAt)) : '-';

                    $row[] = $ID;
                    $row[] = $number;
                    $row[] = $branchName;
                    $row[] = $divisionName;
                    $row[] = $levelName;
                    $row[] = $templateCode;
                    $row[] = 'v' . $version;
                    $row[] = $dateFormatted;
                    $row[] = function_exists('docStatus') ? docStatus($status) : $status;
                    $btnTable   = isset($this->template) ? $this->template->tableButton($ID) : '';
                    $btnPreview = '<button type="button" class="btn btn-sm btn_preview_template" data-id="' . $ID . '" data-toggle="tooltip" title="Pratinjau Form" data-original-title="Pratinjau Form"><i class="fas fa-eye text-primary"></i></button>';
                    $row[]      = $btnTable . $btnPreview;

                    $data[] = $row;
                }

                $result = [
                    'draw'            => (int) ($this->request->getPost('draw') ?? 1),
                    'recordsTotal'    => $this->datatable->countAll($table, $select, $order, $sort, $search, $join, $where),
                    'recordsFiltered' => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join, $where),
                    'data'            => $data,
                ];

                return $this->response->setJSON($result);

            } catch (\Throwable $e) {
                return $this->response->setJSON([
                    'draw'            => (int) ($this->request->getPost('draw') ?? 1),
                    'recordsTotal'    => 0,
                    'recordsFiltered' => 0,
                    'data'            => [],
                    'error'           => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Create / Update Header & Both Line Item Tabs
     */
    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            try {
                $post = (array) $this->request->getVar();
                $id   = $this->getID();

                // 1. Immutability Check
                if (!empty($id)) {
                    $existing = $this->model->find($id);
                    if ($existing && ! $existing->isDraft()) {
                        return $this->response->setJSON(message('error', false, 'Template yang sudah dipublish tidak dapat diubah.'));
                    }
                }

                // 2. Decode unified payload (built entirely by the frontend)
                $payload = json_decode((string) ($post['payload'] ?? ''), true);
                if (!is_array($payload)) {
                    // TEMP DEBUG: remove once the payload issue is resolved
                    $raw = (string) ($post['payload'] ?? '');
                    return $this->response->setJSON(message('error', false, 'Payload template tidak valid. '
                        . 'json_error=' . json_last_error_msg()
                        . '; payload_len=' . strlen($raw)
                        . '; head=' . substr($raw, 0, 80)
                        . '; post_keys=' . implode(',', array_keys($post))));
                }

                $sections = array_values(array_filter(
                    (array) ($payload['sections'] ?? []),
                    fn($s) => !empty($s['items']) && is_array($s['items'])
                ));
                $scoping = (array) ($payload['scoping'] ?? []);

                // 3. Light sanity re-check (primary validation is done in the frontend)
                if (trim((string) ($payload['template_code'] ?? '')) === '' || trim((string) ($payload['title'] ?? '')) === '') {
                    return $this->response->setJSON(message('error', false, 'Kode dan judul template wajib diisi.'));
                }
                if (empty($sections)) {
                    return $this->response->setJSON(message('error', false, 'Template harus memiliki minimal satu kelompok kompetensi.'));
                }

                $totalScoreWeight = 0.0;
                $hasScore         = false;
                foreach ($sections as $sec) {
                    foreach ($sec['items'] as $item) {
                        if (($item['input_type'] ?? '') === 'SCORE') {
                            $hasScore          = true;
                            $totalScoreWeight += (float) ($item['weight'] ?? 0);
                        }
                    }
                }
                if ($hasScore && abs($totalScoreWeight - 100.0) > 0.05) {
                    return $this->response->setJSON(message('error', false, 'Total bobot kelompok skala harus tepat 100%. Total saat ini: ' . $totalScoreWeight . '%'));
                }

                $this->db->transStart();

                // 4. Header
                $this->entity->fill([
                    'template_code' => trim($payload['template_code']),
                    'title'         => trim($payload['title']),
                    'isactive'      => (($payload['isactive'] ?? 'N') === 'Y') ? 'Y' : 'N',
                ]);
                if (empty($id)) {
                    $this->entity->status  = M_AppraisalTemplate::STATUS_DRAFT;
                    $this->entity->version = (int) ($payload['version'] ?? 1) ?: 1;
                }

                $response = $this->save();

                if (isset($response['status']) && $response['status'] === false) {
                    $this->db->transRollback();
                    return $this->response->setJSON($response);
                }

                $templateId = !empty($id) ? $id : ($response['id'] ?? $this->model->getInsertID());
                if (empty($templateId)) {
                    $this->db->transRollback();
                    return $this->response->setJSON(message('error', false, 'Gagal menyimpan header template.'));
                }

                $userId = $this->access->getSessionUser();

                // 5. Scoping (replace-all)
                $maps = [
                    'md_template_branch'    => ['md_branch_id',    'branch_ids'],
                    'md_template_division'  => ['md_division_id',  'division_ids'],
                    'md_template_levelling' => ['md_levelling_id', 'levelling_ids'],
                ];
                foreach ($maps as $table => [$col, $key]) {
                    $this->db->table($table)->where('md_appraisal_template_id', $templateId)->delete();
                    $rawIds = $scoping[$key] ?? [];
                    if ($key === 'levelling_ids' && empty($rawIds)) {
                        $rawIds = $scoping['leveling_ids'] ?? $scoping['position_ids'] ?? [];
                    }
                    $ids = array_unique(array_filter(array_map('intval', (array) $rawIds)));
                    foreach ($ids as $scopeId) {
                        $this->db->table($table)->insert([
                            'md_appraisal_template_id' => $templateId,
                            $col                       => $scopeId,
                            'created_by'               => $userId,
                            'updated_by'               => $userId,
                        ]);
                    }
                }

                // 6. Sections & items (clean sync)
                $sectionModel = new \App\Models\M_AppraisalSection();
                $keptSections = [];
                $keptItems    = [];

                foreach ($sections as $sIdx => $sec) {
                    $sectionData = [
                        'md_appraisal_template_id' => $templateId,
                        'name'                     => trim((string) ($sec['name'] ?? 'Section')),
                        'section_type'             => $sec['section_type'] ?? 'SCORE',
                        'weight'                   => (float) ($sec['weight'] ?? 0),
                        'sequence_order'           => (int) ($sec['sequence_order'] ?? ($sIdx + 1)),
                        'description'              => $sec['description'] ?? null,
                        'isactive'                 => 'Y',
                        'updated_by'               => $userId,
                    ];

                    $sectionId = (int) ($sec['md_appraisal_section_id'] ?? 0);
                    $owned     = $sectionId > 0 && $sectionModel->where('md_appraisal_template_id', $templateId)->find($sectionId);
                    if ($owned) {
                        $sectionModel->update($sectionId, $sectionData);
                    } else {
                        $sectionData['created_by'] = $userId;
                        $sectionId = (int) $sectionModel->insert($sectionData, true);
                    }
                    if (!$sectionId) {
                        $this->db->transRollback();
                        return $this->response->setJSON(message('error', false, 'Gagal simpan section: ' . json_encode($sectionModel->errors())));
                    }
                    $keptSections[] = $sectionId;

                    foreach ($sec['items'] as $iIdx => $item) {
                        $type     = $item['input_type'] ?? ($sec['section_type'] ?? 'SCORE');
                        $lineData = [
                            'md_appraisal_section_id' => $sectionId,
                            'input_type'              => $type,
                            'title'                   => trim((string) ($item['title'] ?? '')),
                            'description'             => $item['description'] ?? null,
                            'weight'                  => $type === 'BOOLEAN' ? 0 : (float) ($item['weight'] ?? 0),
                            'sequence_order'          => (int) ($item['sequence_order'] ?? ($iIdx + 1)),
                            'is_required'             => (($item['is_required'] ?? 'Y') === 'N') ? 'N' : 'Y',
                            'config'                  => json_encode($item['config'] ?? new \stdClass(), JSON_UNESCAPED_UNICODE),
                            'isactive'                => 'Y',
                            'updated_by'              => $userId,
                        ];

                        $itemId = (int) ($item['md_appraisal_template_item_id'] ?? 0);
                        $ok     = $itemId > 0 && $this->db->table('md_appraisal_template_item')
                            ->where('md_appraisal_template_item_id', $itemId)
                            ->whereIn('md_appraisal_section_id', $keptSections)
                            ->countAllResults() > 0;

                        if ($ok) {
                            $this->modelDetail->update($itemId, $lineData);
                        } else {
                            $lineData['created_by'] = $userId;
                            $itemId = (int) $this->modelDetail->insert($lineData, true);
                        }
                        if (!$itemId) {
                            $this->db->transRollback();
                            return $this->response->setJSON(message('error', false, 'Gagal simpan item: ' . json_encode($this->modelDetail->errors())));
                        }
                        $keptItems[] = $itemId;
                    }
                }

                // Delete removed items & sections (items first; FK cascades cover the rest)
                $sectionIds = array_column(
                    $this->db->table('md_appraisal_section')->select('md_appraisal_section_id')->where('md_appraisal_template_id', $templateId)->get()->getResultArray(),
                    'md_appraisal_section_id'
                );
                if (!empty($sectionIds)) {
                    $b = $this->db->table('md_appraisal_template_item')->whereIn('md_appraisal_section_id', $sectionIds);
                    if (!empty($keptItems)) {
                        $b->whereNotIn('md_appraisal_template_item_id', $keptItems);
                    }
                    $b->delete();
                }
                $b = $this->db->table('md_appraisal_section')->where('md_appraisal_template_id', $templateId);
                if (!empty($keptSections)) {
                    $b->whereNotIn('md_appraisal_section_id', $keptSections);
                }
                $b->delete();

                $this->db->transComplete();

                if ($this->db->transStatus() === false) {
                    return $this->response->setJSON(message('error', false, 'Gagal menyimpan template.'));
                }

            } catch (\Exception $e) {
                $this->db->transRollback();
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    /**
     * Fetch Record Details for View / Edit Modal Auto-fill
     */
    public function show($id = null)
    {
        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();

                if (!empty($list)) {
                    $title = method_exists($list[0], 'getTitle')
                        ? $list[0]->getTitle()
                        : ($list[0]->title ?? 'Detail Template Appraisal');

                    $fieldHeader = new \App\Entities\Table();
                    $fieldHeader->setTitle($title);
                    $fieldHeader->setTable($this->model->table);
                    $fieldHeader->setList($list);

                    // Scoping (id + name, so the frontend can preload Select2 options)
                    $scoping = [];
                    foreach ([
                        'branch_ids'    => ['md_template_branch',    'md_branch',    'md_branch_id'],
                        'division_ids'  => ['md_template_division',  'md_division',  'md_division_id'],
                        'levelling_ids' => ['md_template_levelling', 'md_levelling', 'md_levelling_id'],
                    ] as $key => [$map, $master, $fk]) {
                        $scoping[$key] = $this->db->table("{$map} x")
                            ->select("m.{$fk} AS id, m.name AS text")
                            ->join("{$master} m", "m.{$fk} = x.{$fk}")
                            ->where('x.md_appraisal_template_id', $id)
                            ->where('x.isactive', 'Y')
                            ->get()->getResultArray();
                    }
                    // Alias for backwards compatibility with any UI elements still referencing position_ids
                    $scoping['position_ids'] = $scoping['levelling_ids'];

                    // Sections with nested items (config decoded)
                    $sections = $this->db->table('md_appraisal_section')
                        ->where('md_appraisal_template_id', $id)
                        ->where('isactive', 'Y')
                        ->orderBy('sequence_order', 'ASC')
                        ->get()->getResultArray();

                    foreach ($sections as &$sec) {
                        $items = $this->db->table('md_appraisal_template_item')
                            ->where('md_appraisal_section_id', $sec['md_appraisal_section_id'])
                            ->where('isactive', 'Y')
                            ->orderBy('sequence_order', 'ASC')
                            ->get()->getResultArray();
                        foreach ($items as &$it) {
                            $cfg          = json_decode((string) ($it['config'] ?? ''), true);
                            $it['config'] = is_array($cfg) ? $cfg : [];
                        }
                        unset($it);
                        $sec['items'] = $items;
                    }
                    unset($sec);

                    $result = [
                        'header'   => $this->field->store($fieldHeader),
                        'scoping'  => $scoping,
                        'sections' => $sections,
                    ];

                    $response = message('success', true, $result);
                } else {
                    $response = message('error', false, 'Template Appraisal tidak ditemukan.');
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    /**
     * Publish Template ('DR' -> 'CO')
     */
    public function publish($id = null)
    {
        if ($this->request->isAJAX()) {
            try {
                $userId = $this->access->getSessionUser();
                $this->templateService->publishTemplate((int) $id, $userId);

                $response = message('success', true, 'Template appraisal berhasil dipublish dan dikunci.');
            } catch (Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    /**
     * Clone Published Template into New Version Draft ('DR')
     */
    public function cloneVersion($id = null)
    {
        if ($this->request->isAJAX()) {
            try {
                $userId      = $this->access->getSessionUser();
                $newTemplate = $this->templateService->createNewVersion((int) $id, $userId);

                $response = [
                    'status'  => true,
                    'message' => "Versi baru template ({$newTemplate->version}) berhasil dibuat sebagai DRAFT.",
                    'data'    => $newTemplate,
                ];
            } catch (Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    /**
     * Delete Header & Lines with M_ChangeLog Integration
     */
    public function destroy($id = null)
    {
        if ($this->request->isAJAX()) {
            try {
                $result   = $this->delete((int) $id);
                $response = message('success', $result, notification('updated'));
            } catch (Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    /**
     * Archive Template ('CO' -> 'VO')
     */
    public function archive($id = null)
    {
        if ($this->request->isAJAX()) {
            try {
                $templateId = (int) ($id ?? $this->request->getVar('id') ?? 0);
                if ($templateId <= 0) {
                    return $this->response->setJSON(message('error', false, 'ID template tidak valid.'));
                }
                $userId = (int) ($this->access->getSessionUser() ?? 0);
                $this->templateService->archiveTemplate($templateId, $userId);

                $response = message('success', true, 'Template appraisal berhasil diarsipkan.');
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    /**
     * Preview participants matching target scoping (branch_ids, division_ids, levelling_ids)
     */
    public function previewParticipants()
    {
        try {
            $post = (array) $this->request->getVar();
            if (empty($post)) {
                $post = $_POST;
            }
            if (empty($post['payload']) && !empty($_POST['payload'])) {
                $post['payload'] = $_POST['payload'];
            }
            if (!empty($post['payload'])) {
                $decoded = is_string($post['payload']) ? json_decode($post['payload'], true) : $post['payload'];
                if (is_array($decoded)) {
                    $post = array_merge($post, $decoded);
                }
            }

            $scoping = is_array($post['scoping'] ?? null) ? $post['scoping'] : $post;

            $branchIds    = $this->normalizeIdArray($scoping['branch_ids'] ?? $scoping['md_branch_id'] ?? []);
            $divisionIds  = $this->normalizeIdArray($scoping['division_ids'] ?? $scoping['md_division_id'] ?? []);
            $levellingIds = $this->normalizeIdArray($scoping['levelling_ids'] ?? $scoping['md_levelling_id'] ?? $scoping['leveling_ids'] ?? $scoping['position_ids'] ?? []);

            // If template_id provided and no scoping params, load from junction tables
            $templateId = (int) ($post['md_appraisal_template_id'] ?? $post['id'] ?? 0);
            if ($templateId > 0 && empty($branchIds) && empty($divisionIds) && empty($levellingIds)) {
                $branchIds = array_column(
                    $this->db->table('md_template_branch')
                        ->select('md_branch_id')
                        ->where('md_appraisal_template_id', $templateId)
                        ->where('isactive', 'Y')
                        ->get()->getResultArray(),
                    'md_branch_id'
                );
                $divisionIds = array_column(
                    $this->db->table('md_template_division')
                        ->select('md_division_id')
                        ->where('md_appraisal_template_id', $templateId)
                        ->where('isactive', 'Y')
                        ->get()->getResultArray(),
                    'md_division_id'
                );
                $levellingIds = array_column(
                    $this->db->table('md_template_levelling')
                        ->select('md_levelling_id')
                        ->where('md_appraisal_template_id', $templateId)
                        ->where('isactive', 'Y')
                        ->get()->getResultArray(),
                    'md_levelling_id'
                );
                $branchIds    = $this->normalizeIdArray($branchIds);
                $divisionIds  = $this->normalizeIdArray($divisionIds);
                $levellingIds = $this->normalizeIdArray($levellingIds);
            }

            $builder = $this->db->table('md_employee e')
                ->select('e.md_employee_id, e.nik, e.fullname, e.superior_id, '
                    . 'b.name AS branch_name, d.name AS division_name, '
                    . 'l.name AS level_name, p.name AS position_name, '
                    . 'sup.fullname AS evaluator_name, gm.fullname AS reviewer_name')
                ->join('md_employee_branch eb', 'eb.md_employee_id = e.md_employee_id', 'left')
                ->join('md_branch b', 'b.md_branch_id = eb.md_branch_id', 'left')
                ->join('md_employee_division ed', 'ed.md_employee_id = e.md_employee_id', 'left')
                ->join('md_division d', 'd.md_division_id = ed.md_division_id', 'left')
                ->join('md_levelling l', 'l.md_levelling_id = e.md_levelling_id', 'left')
                ->join('md_position p', 'p.md_position_id = e.md_position_id', 'left')
                ->join('md_employee sup', 'sup.md_employee_id = e.superior_id', 'left')
                ->join('md_employee gm', 'gm.md_employee_id = sup.superior_id', 'left')
                ->where('e.isactive', 'Y')
                ->where('(e.resigndate IS NULL OR e.resigndate > CURDATE() OR CAST(e.resigndate AS CHAR) = "0000-00-00")');

            if (!empty($branchIds)) {
                $builder->whereIn('eb.md_branch_id', $branchIds);
            }
            if (!empty($divisionIds)) {
                $builder->whereIn('ed.md_division_id', $divisionIds);
            }
            if (!empty($levellingIds)) {
                $builder->whereIn('e.md_levelling_id', $levellingIds);
            }

            $list = $builder->orderBy('e.fullname', 'ASC')->get()->getResultArray();

            $data = [];
            $no   = 1;
            foreach ($list as $row) {
                $data[] = [
                    'no'             => $no++,
                    'nik'            => $row['nik'] ?? '-',
                    'employee_name'  => $row['fullname'] ?? '-',
                    'branch_name'    => $row['branch_name'] ?? 'Pusat / Semua Cabang',
                    'division_name'  => $row['division_name'] ?? 'Semua Divisi',
                    'position_name'  => $row['position_name'] ?? '-',
                    'level_name'     => $row['level_name'] ?? '-',
                    'evaluator_name' => $row['evaluator_name'] ?? '(Belum ditentukan)',
                    'reviewer_name'  => $row['reviewer_name'] ?? '-',
                ];
            }

            return $this->response->setJSON([
                'status'  => true,
                'total'   => count($data),
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status'  => false,
                'total'   => 0,
                'data'    => [],
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Preview questionnaire form evaluation sheet (live builder payload or saved template)
     * Reuses form_appraisal mode VIEW components
     */
    public function previewForm($id = null)
    {
        try {
            $templateId = (int) ($id ?? $this->request->getVar('id') ?? ($_POST['id'] ?? 0));
            $post       = (array) $this->request->getVar();
            if (empty($post)) {
                $post = $_POST;
            }
            if (empty($post['payload']) && !empty($_POST['payload'])) {
                $post['payload'] = $_POST['payload'];
            }

            $templateTitle = 'Preview Evaluasi Kinerja';
            $templateCode  = '-';
            $version       = 1;
            $sections      = [];
            $items         = [];

            // Contextual preview info for simulated employee
            $sampleBranch   = 'Semua Cabang (Global)';
            $sampleDivision = 'Semua Divisi';
            $samplePosition = 'Semua Level / Posisi';

            if (!empty($post['payload'])) {
                // Live form builder preview
                $payload = is_string($post['payload']) ? json_decode($post['payload'], true) : $post['payload'];
                if (is_array($payload)) {
                    $templateTitle = !empty($payload['title']) ? $payload['title'] : $templateTitle;
                    $templateCode  = !empty($payload['template_code']) ? $payload['template_code'] : $templateCode;
                    $version       = (int) ($payload['version'] ?? 1);

                    $secCounter  = 1;
                    $itemCounter = 1;
                    foreach ($payload['sections'] ?? [] as $sec) {
                        $secId   = (int) ($sec['md_appraisal_section_id'] ?? $secCounter++);
                        $secType = strtoupper($sec['section_type'] ?? 'SCORE');
                        $sections[] = [
                            'md_appraisal_section_id' => $secId,
                            'name'                    => $sec['name'] ?? ($secType === 'BOOLEAN' ? 'Kriteria Kepatuhan' : 'Kompetensi & Performa Kerja'),
                            'section_type'            => $secType,
                            'sequence_order'          => (int) ($sec['sequence_order'] ?? $secCounter),
                            'weight'                  => (float) ($sec['weight'] ?? 0),
                        ];

                        foreach ($sec['items'] ?? [] as $it) {
                            $itemId    = (int) ($it['md_appraisal_template_item_id'] ?? $itemCounter++);
                            $configVal = $it['config'] ?? [];
                            if (is_array($configVal)) {
                                $configVal = json_encode($configVal);
                            }
                            $items[] = [
                                'md_appraisal_template_item_id' => $itemId,
                                'md_appraisal_section_id'       => $secId,
                                'title'                         => $it['title'] ?? '',
                                'description'                   => $it['description'] ?? '',
                                'input_type'                    => strtoupper($it['input_type'] ?? $secType),
                                'sequence_order'                => (int) ($it['sequence_order'] ?? $itemCounter),
                                'weight'                        => (float) ($it['weight'] ?? 0),
                                'config'                        => $configVal,
                            ];
                        }
                    }
                }
            } elseif ($templateId > 0) {
                // Existing saved template preview
                $tpl = $this->model->find($templateId);
                if ($tpl) {
                    $templateTitle = $tpl->title;
                    $templateCode  = $tpl->template_code;
                    $version       = (int) $tpl->version;
                }

                $rawSections = $this->db->table('md_appraisal_section')
                    ->where('md_appraisal_template_id', $templateId)
                    ->where('isactive', 'Y')
                    ->orderBy('sequence_order', 'ASC')
                    ->get()->getResultArray();

                foreach ($rawSections as $sec) {
                    $secId = (int) $sec['md_appraisal_section_id'];
                    $sections[] = $sec;

                    $secItems = $this->db->table('md_appraisal_template_item')
                        ->where('md_appraisal_section_id', $secId)
                        ->where('isactive', 'Y')
                        ->orderBy('sequence_order', 'ASC')
                        ->get()->getResultArray();

                    foreach ($secItems as $si) {
                        $items[] = $si;
                    }
                }
            }

            // Snapshot format consumed by form_appraisal components
            $templateSnapshot = [
                'template' => [
                    'title'         => $templateTitle,
                    'template_code' => $templateCode,
                    'version'       => $version,
                ],
                'sections' => $sections,
                'items'    => $items,
            ];

            // Simulated appraisal payload for VIEW mode
            $viewData = [
                'appraisal_id'         => 0,
                'is_preview'           => true,
                'user_mode'            => 'VIEW',
                'has_reviewer'         => true,
                'template_snapshot'    => $templateSnapshot,
                'responses_by_item_id' => [],
                'appraisal'            => (object) [
                    'md_appraisal_id'   => 0,
                    'final_score'       => 3.00,
                    'status'            => 'PREVIEW',
                    'evaluator_comment' => 'Ini adalah pratinjau lembar formulir evaluasi kinerja template ' . $templateCode . ' (v' . $version . '). Formulir dalam mode VIEW (hanya-baca).',
                    'gm_comment'        => '',
                ],
                'employee'             => [
                    'nik'           => 'SIMULASI-001',
                    'name'          => 'Karyawan Simulasi (Contoh)',
                    'division_name' => $sampleDivision,
                    'position_name' => $samplePosition,
                    'branch_name'   => $sampleBranch,
                ],
                'cycle'                => (object) [
                    'title'      => 'Pratinjau Siklus Penilaian',
                    'start_date' => date('Y-m-d'),
                    'end_date'   => date('Y-m-d', strtotime('+30 days')),
                ],
                'evaluator'            => [
                    'name' => 'Atasan Penilai Langsung (Evaluator)',
                ],
                'gm'                   => [
                    'name' => 'Reviewer Atasan (General Manager)',
                ],
            ];

            // If requested directly via browser (non-AJAX)
            if (! $this->request->isAJAX() && $this->request->getMethod(true) === 'GET' && empty($_POST['payload'])) {
                return $this->template->render('masterdata/appraisal-filling/form_appraisal_filling', $viewData);
            }

            $html = view('masterdata/appraisal-filling/form_appraisal_filling_inner', $viewData);

            return $this->response->setJSON([
                'status'      => true,
                'title'       => $templateTitle . ' (' . $templateCode . ' v' . $version . ')',
                'html'        => $html,
                'template'    => [
                    'title'         => $templateTitle,
                    'template_code' => $templateCode,
                    'version'       => $version,
                ],
                'sections'    => $sections,
                'total_items' => count($items),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Helper to normalize ID array
     *
     * @param mixed $raw
     * @return int[]
     */
    private function normalizeIdArray($raw): array
    {
        if (is_string($raw)) {
            $raw = array_filter(array_map('trim', explode(',', $raw)), static fn($v) => $v !== '');
        }
        if (!is_array($raw)) {
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

/**
     * AJAX Endpoint: Get Division List (Cascaded by Position)
     */
    public function getListDivision()
    {
        $positionId = $this->request->getVar('md_position_id');
        $search     = $this->request->getVar('search') ?? $this->request->getVar('term');
        
        try {
            if (!empty($positionId) && $positionId !== '0') {
                // Get divisions linked to this position via M_Employee helper
                $list = $this->mEmployee->getDivisionsByPosition($positionId);
            } else {
                // Fallback: Get all active divisions
                $builder = $this->db->table('md_division')
                    ->select('md_division_id as id, name as text')
                    ->where('isactive', 'Y');

                if (!empty(trim($search)) && $search !== 'undefined' && $search !== 'null') {
                    $builder->like('name', trim($search));
                }

                $list = $builder->orderBy('name', 'ASC')->get()->getResultArray();
            }

            // Normalize array to ensure 'id' and 'text' exist
            $response = [];
            $response[] = ['id' => '', 'text' => 'Semua Divisi'];
            foreach ($list as $row) {
                $rowText = $row['text'] ?? $row['name'] ?? '';
                if (!empty(trim($search)) && !empty($positionId) && $positionId !== '0') {
                    if (stripos($rowText, trim($search)) === false) {
                        continue;
                    }
                }
                $response[] = [
                    'id'   => (string) ($row['id'] ?? $row['md_division_id']),
                    'text' => $rowText,
                ];
            }
            
            return $this->response->setJSON($response);

        } catch (\Throwable $e) {
            return $this->response->setJSON([]);
        }
    }

    /**
     * AJAX Endpoint: Get Branch List (Cascaded by Position & Division)
     */
    public function getListBranch()
    {
        $positionId = $this->request->getVar('md_position_id');
        $divisionId = $this->request->getVar('md_division_id');
        $search     = $this->request->getVar('search') ?? $this->request->getVar('term');
        
        try {
            if ((!empty($positionId) && $positionId !== '0') || (!empty($divisionId) && $divisionId !== '0')) {
                // Get branches linked to position/division via M_Employee helper
                $list = $this->mEmployee->getBranchesByPositionAndDivision($positionId, $divisionId);
            } else {
                // Fallback: Get all active branches
                $builder = $this->db->table('md_branch')
                    ->select('md_branch_id as id, name as text')
                    ->where('isactive', 'Y');

                if (!empty(trim($search)) && $search !== 'undefined' && $search !== 'null') {
                    $builder->like('name', trim($search));
                }

                $list = $builder->orderBy('name', 'ASC')->get()->getResultArray();
            }

            // Normalize array to ensure 'id' and 'text' exist
            $response = [];
            $response[] = ['id' => '', 'text' => 'Semua Cabang'];
            foreach ($list as $row) {
                $rowText = $row['text'] ?? $row['name'] ?? '';
                if (!empty(trim($search)) && ((!empty($positionId) && $positionId !== '0') || (!empty($divisionId) && $divisionId !== '0'))) {
                    if (stripos($rowText, trim($search)) === false) {
                        continue;
                    }
                }
                $response[] = [
                    'id'   => (string) ($row['id'] ?? $row['md_branch_id']),
                    'text' => $rowText,
                ];
            }

            return $this->response->setJSON($response);

        } catch (\Throwable $e) {
            return $this->response->setJSON([]);
        }
    }

    /**
     * Select2 Dropdown: Levelling List
     */
    public function getListLevelling()
    {
        $response = [];
        try {
            $search  = $this->request->getVar('search') ?? $this->request->getVar('term');
            $builder = $this->db->table('md_levelling')
                ->select('md_levelling_id as id, name as text')
                ->where('isactive', 'Y');

            if (!empty(trim($search)) && $search !== 'undefined' && $search !== 'null') {
                $builder->like('name', trim($search));
            }

            $list = $builder->orderBy('name', 'ASC')->get()->getResultArray();

            $response[] = ['id' => '', 'text' => 'Semua Level'];
            foreach ($list as $row) {
                $response[] = [
                    'id'   => (string) $row['id'],
                    'text' => $row['text'],
                ];
            }
        } catch (\Throwable $e) {
            $response = [];
        }

        return $this->response->setJSON($response);
    }

    /**
     * Select2 Dropdown: Position List
     */
    public function getListPosition()
    {
        $response = [];
        try {
            $search  = $this->request->getVar('search') ?? $this->request->getVar('term');
            $builder = $this->db->table('md_position')
                ->select('md_position_id as id, name as text')
                ->where('isactive', 'Y');

            if (!empty(trim($search)) && $search !== 'undefined' && $search !== 'null') {
                $builder->like('name', trim($search));
            }

            $list = $builder->orderBy('name', 'ASC')->get()->getResultArray();

            $response[] = ['id' => '', 'text' => 'Semua Posisi'];
            foreach ($list as $row) {
                $response[] = [
                    'id'   => (string) $row['id'],
                    'text' => $row['text'],
                ];
            }
        } catch (\Throwable $e) {
            $response = [];
        }

        return $this->response->setJSON($response);
    }

    public function getOptionsByParent()
    {
        $positionIds = $this->request->getVar('md_position_id');
        $divisionIds = $this->request->getVar('md_division_id');
        $branchIds   = $this->request->getVar('md_branch_id');
        $search      = $this->request->getVar('search') ?? $this->request->getVar('term');
        $type        = $this->request->getVar('type');

        // Pastikan input berupa array bersih jika multi-select
        $positionIds = !empty($positionIds) && $positionIds !== '0' ? (array) $positionIds : [];
        $divisionIds = !empty($divisionIds) && $divisionIds !== '0' ? (array) $divisionIds : [];
        $branchIds   = !empty($branchIds) && $branchIds !== '0'     ? (array) $branchIds   : [];

        $mEmployee = new \App\Models\M_Employee($this->request);
        $data      = [];

        switch ($type) {
            case 'levelling':
                $builder = $this->db->table('md_levelling')
                    ->select('md_levelling_id as id, name as text')
                    ->where('isactive', 'Y');
                if (!empty(trim($search))) {
                    $builder->like('name', trim($search));
                }
                $data = $builder->orderBy('name', 'ASC')->get()->getResultArray();
                break;

            case 'position':
                // Ambil semua jabatan aktif (bisa difilter search teks)
                $data = $mEmployee->getPositions($search);
                break;

            case 'division':
                // Position -> Division
                $data = $mEmployee->getDivisionsByPosition($positionIds, $search);
                break;

            case 'branch':
                // (Position + Division) -> Branch
                $data = $mEmployee->getBranchesByPositionAndDivision($positionIds, $divisionIds, $search);
                break;

            case 'employee':
                // Format hasil karyawan menjadi id & text untuk Select2
                $rawEmployees = $mEmployee->getFilteredEmployees($branchIds, $divisionIds, $positionIds, $search);
                foreach ($rawEmployees as $emp) {
                    $data[] = [
                        'id'   => $emp['md_employee_id'],
                        'text' => $emp['value'] . ' - ' . $emp['fullname'],
                    ];
                }
                break;

            default:
                $data = [];
                break;
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $data,
        ]);
    }
    /**
     * AJAX Endpoint: Auto-resolve Penilai (Manager) & Reviewer (GM)
     * Route: GET /template-penilaian/getHierarchyInfo
     */
    public function getHierarchyInfo()
    {
        $positionId = $this->request->getVar('md_position_id');
        $divisionId = $this->request->getVar('md_division_id');
        $branchId   = $this->request->getVar('md_branch_id');
        $employeeId = $this->request->getVar('md_employee_id');

        $hierarchy = ['manager_id' => 0, 'reviewer_id' => 0];

        if (!empty($employeeId) && $employeeId !== '0') {
            $hierarchy = $this->mEmployee->getAppraisalHierarchy($employeeId);
        } else if (!empty($positionId) && $positionId !== '0') {
            $hierarchy = $this->mEmployee->getHierarchyByPosition($positionId, $divisionId, $branchId);
        }

        $manager  = !empty($hierarchy['manager_id']) ? $this->mEmployee->find($hierarchy['manager_id']) : null;
        $reviewer = !empty($hierarchy['reviewer_id']) ? $this->mEmployee->find($hierarchy['reviewer_id']) : null;

        return $this->response->setJSON([
            'status'   => 'success',
            'penilai'  => [
                'id'   => $hierarchy['manager_id'] ?? 0,
                'text' => $manager ? ($manager->fullname ?? $manager->value) : 'Pilih Penilai'
            ],
            'reviewer' => [
                'id'   => $hierarchy['reviewer_id'] ?? 0,
                'text' => $reviewer ? ($reviewer->fullname ?? $reviewer->value) : 'Pilih Reviewer'
            ]
        ]);
    }
}