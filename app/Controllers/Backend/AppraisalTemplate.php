<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Entities\AppraisalTemplate as AppraisalTemplateEntity;
use App\Models\M_AppraisalTemplate;
use App\Services\AppraisalTemplateServices;
use App\Models\M_Employee;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Exception;

class AppraisalTemplate extends BaseController
{
    protected AppraisalTemplateServices $templateService; // Fixed: removed trailing 's' to match usage
    protected M_Employee $mEmployee;                      // Fixed: declared property

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->model           = new M_AppraisalTemplate();
        $this->entity          = new AppraisalTemplateEntity();
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
                        'md_branch_id'    => ['md_template_branch',    'md_branch_id'],
                        'md_division_id'  => ['md_template_division',  'md_division_id'],
                        'md_levelling_id' => ['md_template_levelling', 'md_levelling_id'],
                        'md_position_id'  => ['md_template_position',  'md_position_id'],
                    ];
                    if (isset($scopeMap[$name])) {
                        [$scopeTable, $fk] = $scopeMap[$name];
                        $val               = (int) $value;
                        $where[]           = "EXISTS (SELECT 1 FROM {$scopeTable} f WHERE f.md_appraisal_template_id = md_appraisal_template.md_appraisal_template_id AND f.{$fk} = {$val} AND f.isactive = 'Y')";
                    }
                    if ($name === 'status') {
                        $statusMap = [
                            $this->DOCSTATUS_Drafted   => [M_AppraisalTemplate::STATUS_DRAFT, $this->DOCSTATUS_Drafted, 'DRAFT'],
                            $this->DOCSTATUS_Completed => [M_AppraisalTemplate::STATUS_PUBLISHED, $this->DOCSTATUS_Completed, 'PUBLISHED'],
                            $this->DOCSTATUS_Voided    => [M_AppraisalTemplate::STATUS_ARCHIVED, $this->DOCSTATUS_Voided, 'ARCHIVED'],
                        ];
                        if (isset($statusMap[$value])) {
                            $where['md_appraisal_template.status'] = $statusMap[$value];
                        } else {
                            $where['md_appraisal_template.status'] = $value;
                        }
                    }
                }

                $data   = [];
                $start  = (int) ($this->request->getPost('start') ?? 0);
                $length = (int) ($this->request->getPost('length') ?? 10);
                $number = $start;

                $builder = $this->db->table($table)->select($select);
                foreach ($join as $j) {
                    $builder->join($j['tableJoin'], $j['columnJoin'], $j['typeJoin']);
                }

                foreach ($where as $key => $val) {
                    if (is_numeric($key)) {
                        $builder->where($val);
                    } elseif (is_array($val)) {
                        $builder->whereIn($key, $val);
                    } else {
                        $builder->where($key, $val);
                    }
                }

                $recordsTotal = (clone $builder)->countAllResults(false);

                $searchValue = $this->request->getPost('search')['value'] ?? null;
                M_AppraisalTemplate::applyDatatableSearch($builder, $this->db, $table, $searchValue);

                $recordsFiltered = (clone $builder)->countAllResults(false);

                $postOrder = $this->request->getPost('order');
                if (! empty($postOrder) && isset($order[$postOrder[0]['column']]) && $order[$postOrder[0]['column']] !== null) {
                    $dir = strtolower((string) ($postOrder[0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
                    $builder->orderBy($order[$postOrder[0]['column']], $dir);
                } else {
                    foreach ($sort as $col => $dir) {
                        $builder->orderBy($col, $dir);
                    }
                }

                if ($length > 0) {
                    $builder->limit($length, $start);
                }

                $list = $builder->get()->getResult();

                foreach ($list as $value) {
                    $row = [];

                    // Fixed: md_appraisal_template_id instead of appraisal_template_id
                    $ID           = is_object($value) ? ($value->md_appraisal_template_id ?? null) : ($value['md_appraisal_template_id'] ?? null);
                    $branchName   = is_object($value) ? ($value->branch_name ?? 'Semua Cabang') : ($value['branch_name'] ?? 'Semua Cabang');
                    $divisionName = is_object($value) ? ($value->division_name ?? 'Semua Divisi') : ($value['division_name'] ?? 'Semua Divisi');
                    $levelName    = is_object($value) ? ($value->level_name ?? 'Semua Level') : ($value['level_name'] ?? 'Semua Level');
                    $positionName = is_object($value) ? ($value->position_name ?? 'Semua Jabatan') : ($value['position_name'] ?? 'Semua Jabatan');
                    $templateCode = is_object($value) ? ($value->template_code ?? '-') : ($value['template_code'] ?? '-');
                    $version      = is_object($value) ? ($value->version ?? '1') : ($value['version'] ?? '1');
                    $status       = is_object($value) ? ($value->status ?? M_AppraisalTemplate::STATUS_DRAFT) : ($value['status'] ?? M_AppraisalTemplate::STATUS_DRAFT);

                    $number++;

                    // Contextual Action & Usage Check
                    $usageCount   = (int) (is_object($value) ? ($value->appraisal_usage_count ?? 0) : ($value['appraisal_usage_count'] ?? 0));
                    $latestPubVer = (int) (is_object($value) ? ($value->latest_published_version ?? 0) : ($value['latest_published_version'] ?? 0));
                    $inUse        = ($usageCount > 0);

                    $isDraft     = ($status === M_AppraisalTemplate::STATUS_DRAFT);
                    $isPublished = ($status === M_AppraisalTemplate::STATUS_PUBLISHED);
                    $isArchived  = ($status === M_AppraisalTemplate::STATUS_ARCHIVED);

                    // Descriptive 2-line Status Display
                    if ($isDraft) {
                        $primaryText = 'Draft';
                        $subText     = '<small class="text-muted">Sedang Disusun</small>';
                    } elseif ($isPublished) {
                        $primaryText = 'Published';
                        if ($inUse) {
                            $subText = '<small class="text-muted">Terkunci (' . $usageCount . ' Penilaian)</small>';
                        } else {
                            $subText = '<small class="text-muted">Aktif (0 Penilaian)</small>';
                        }
                    } elseif ($isArchived) {
                        $primaryText = ($latestPubVer > $version) ? ('Tergantikan (v' . $latestPubVer . ')') : ('Versi Lalu (v' . $version . ')');
                        if ($inUse) {
                            $subText = '<small class="text-muted">Arsip Siklus Lalu (' . $usageCount . ' Penilaian)</small>';
                        } else {
                            $subText = '<small class="text-muted">Belum Terpakai</small>';
                        }
                    } else {
                        $primaryText = ucfirst(strtolower((string) $status));
                        $subText     = '';
                    }

                    $statusCol = '<div><p class="mb-0 font-weight-bold">' . $primaryText . '</p>' . $subText . '</div>';

                    $row[] = $ID;
                    $row[] = $number;
                    $row[] = $branchName;
                    $row[] = $divisionName;
                    $row[] = $levelName;
                    $row[] = $positionName;
                    $row[] = $templateCode;
                    $row[] = 'v' . $version;
                    $row[] = $statusCol;

                    // Contextual Action Buttons based on Template Status & Usage
                    $row[] = $this->template->appraisalTableButton('template', $ID, [
                        'status'      => $status,
                        'usage_count' => $usageCount,
                    ]);

                    $data[] = $row;
                }

                $result = [
                    'draw'            => (int) ($this->request->getPost('draw') ?? 1),
                    'recordsTotal'    => $recordsTotal,
                    'recordsFiltered' => $recordsFiltered,
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

                // 1. Decode unified payload from form submission
                $payload = json_decode((string) ($post['payload'] ?? ''), true);
                if (!is_array($payload)) {
                    return $this->response->setJSON(message('error', false, 'Payload template tidak valid.'));
                }

                // Determine whether this is an edit or new record:
                // The unified payload explicitly contains md_appraisal_template_id when updating an existing record.
                // If empty or <= 0, it is strictly a new record creation.
                $id = !empty($payload['md_appraisal_template_id']) && (int) $payload['md_appraisal_template_id'] > 0
                    ? (int) $payload['md_appraisal_template_id']
                    : null;

                // 2. Immutability Check (Zero-Usage Immunity)
                if (!empty($id)) {
                    $existing = $this->model->find($id);
                    if ($existing && ! $existing->isDraft()) {
                        $inUse = $this->templateService->isTemplateInUse((int) $id);
                        if ($inUse) {
                            return $this->response->setJSON(message('error', false, 'Template ini sudah digunakan oleh data penilaian karyawan aktif sehingga tidak dapat diubah secara langsung. Silakan buat Versi Baru.'));
                        }
                    }
                }

                // 3. Centralized validation via Config\Validation ($appraisal_template)
                $validationData = [
                    'template_code' => $payload['template_code'] ?? ($post['template_code'] ?? null),
                    'title'         => $payload['title'] ?? ($post['title'] ?? null),
                    'description'   => $payload['description'] ?? ($post['description'] ?? null),
                ];

                $this->validation->reset();
                if (!$this->validation->run($validationData, 'appraisal_template')) {
                    return $this->response->setJSON($this->field->errorValidation($this->model->table, $validationData));
                }

                // 4. Persist template, scoping, sections, and items via Service layer
                $userId     = (int) $this->access->getSessionUser();
                $templateId = $this->templateService->saveTemplate($payload, $userId, !empty($id) ? (int) $id : null);

                $response = message('success', true, notification(!empty($id) ? 'updated' : 'insert'), ['id' => $templateId]);
            } catch (\Exception $e) {
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

                    // Scoping from the 4 mapping tables (id + name for Select2)
                    $scoping = [];
                    foreach (AppraisalTemplateServices::SCOPE_TABLES as $key => $cfg) {
                        $fk = $cfg['fk'];
                        $scoping[$key] = $this->db->table("{$cfg['table']} x")
                            ->select("m.{$fk} AS id, m.name AS text")
                            ->join("{$cfg['master']} m", "m.{$fk} = x.{$fk}")
                            ->where('x.md_appraisal_template_id', $id)
                            ->where('x.isactive', 'Y')
                            ->get()->getResultArray();
                    }

                    // Sections with nested items from schema_json
                    $record = $list[0];
                    $schema = is_string($record->schema_json ?? null)
                        ? (json_decode($record->schema_json, true) ?: [])
                        : ((array) ($record->schema_json ?? []));

                    $sections = [];
                    if (!empty($schema['sections'])) {
                        $expanded = AppraisalTemplateServices::expandSchema($schema, $record);
                        $itemsBySec = [];
                        foreach ($expanded['items'] as $it) {
                            $itemsBySec[(int) $it['md_appraisal_section_id']][] = $it;
                        }
                        $sections = $expanded['sections'];
                        foreach ($sections as &$sec) {
                            $sec['items'] = $itemsBySec[(int) $sec['md_appraisal_section_id']] ?? [];
                        }
                        unset($sec);
                    }

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
     * Publish Template (DRAFT -> PUBLISHED)
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
     * Clone Published Template into New Version Draft (DRAFT)
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
     * Unpublish Template (PUBLISHED -> DRAFT)
     * Allowed only if 0 employee appraisals have snapshotted it.
     */
    public function unpublish($id = null)
    {
        if ($this->request->isAJAX()) {
            try {
                $templateId = (int) ($id ?? $this->request->getVar('id') ?? 0);
                if ($templateId <= 0) {
                    return $this->response->setJSON(message('error', false, 'ID template tidak valid.'));
                }

                $userId = (int) ($this->access->getSessionUser() ?? 0);
                $this->templateService->unpublishTemplate($templateId, $userId);

                $response = message('success', true, 'Publish template berhasil dibatalkan. Template dikembalikan ke status DRAFT.');
            } catch (Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    /**
     * Delete Header & Lines with M_ChangeLog Integration & Safe Cleanup
     */
    public function destroy($id = null)
    {
        if ($this->request->isAJAX()) {
            try {
                $templateId = (int) ($id ?? $this->request->getVar('id') ?? 0);
                if ($templateId <= 0) {
                    return $this->response->setJSON(message('error', false, 'ID template tidak valid.'));
                }

                $this->templateService->deleteTemplate($templateId);
                $response = message('success', true, 'Template appraisal berhasil dihapus.');
            } catch (Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    /**
     * Archive Template (PUBLISHED -> ARCHIVED) / Deactivate
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

                $response = message('success', true, 'Template appraisal berhasil dinonaktifkan (diarsipkan).');
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    /**
     * Activate / Reactivate Template (ARCHIVED -> PUBLISHED)
     */
    public function activate($id = null)
    {
        if ($this->request->isAJAX()) {
            try {
                $templateId = (int) ($id ?? $this->request->getVar('id') ?? 0);
                if ($templateId <= 0) {
                    return $this->response->setJSON(message('error', false, 'ID template tidak valid.'));
                }
                $userId = (int) ($this->access->getSessionUser() ?? 0);
                $this->templateService->activateTemplate($templateId, $userId);

                $response = message('success', true, 'Template appraisal berhasil diaktifkan kembali.');
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
            $levellingIds = $this->normalizeIdArray($scoping['levelling_ids'] ?? $scoping['md_levelling_id'] ?? $scoping['leveling_ids'] ?? []);
            $positionIds  = $this->normalizeIdArray($scoping['position_ids'] ?? $scoping['md_position_id'] ?? []);

            // If template_id provided and no scoping params, load from the 4 mapping tables
            $templateId = (int) ($post['md_appraisal_template_id'] ?? $post['id'] ?? 0);
            if ($templateId > 0 && empty($branchIds) && empty($divisionIds) && empty($levellingIds) && empty($positionIds)) {
                $loadedScopes = [];
                foreach (AppraisalTemplateServices::SCOPE_TABLES as $key => $cfg) {
                    $rows = $this->db->table($cfg['table'])
                        ->select($cfg['fk'])
                        ->where('md_appraisal_template_id', $templateId)
                        ->where('isactive', 'Y')
                        ->get()->getResultArray();
                    $loadedScopes[$key] = array_column($rows, $cfg['fk']);
                }
                $branchIds    = $this->normalizeIdArray($loadedScopes['branch_ids'] ?? []);
                $divisionIds  = $this->normalizeIdArray($loadedScopes['division_ids'] ?? []);
                $levellingIds = $this->normalizeIdArray($loadedScopes['levelling_ids'] ?? []);
                $positionIds  = $this->normalizeIdArray($loadedScopes['position_ids'] ?? []);
            }

            $builder = $this->db->table('md_employee e')
                ->select('e.md_employee_id, e.nik, e.fullname, e.superior_id, '
                    . 'b.name AS branch_name, d.name AS division_name, '
                    . 'l.name AS level_name, p.name AS position_name, '
                    . 'sup.fullname AS evaluator_name, rev.fullname AS reviewer_name')
                ->join('md_employee_branch eb', 'eb.md_employee_id = e.md_employee_id', 'left')
                ->join('md_branch b', 'b.md_branch_id = eb.md_branch_id', 'left')
                ->join('md_employee_division ed', 'ed.md_employee_id = e.md_employee_id', 'left')
                ->join('md_division d', 'd.md_division_id = ed.md_division_id', 'left')
                ->join('md_levelling l', 'l.md_levelling_id = e.md_levelling_id', 'left')
                ->join('md_position p', 'p.md_position_id = e.md_position_id', 'left')
                ->join('md_employee sup', 'sup.md_employee_id = e.superior_id', 'left')
                ->join('md_employee rev', 'rev.md_employee_id = sup.superior_id', 'left')
                ->where('e.isactive', 'Y')
                ->where('(l.name IS NULL OR UPPER(l.name) != "DIREKSI")')
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
            if (!empty($positionIds)) {
                $builder->whereIn('e.md_position_id', $positionIds);
            }

            $list = $builder->orderBy('e.fullname', 'ASC')->get()->getResultArray();

            $data = [];
            $no   = 1;
            foreach ($list as $row) {
                $data[] = [
                    'no'             => $no++,
                    'nik'            => $row['nik'] ?? '-',
                    'employee_name'  => !empty($row['fullname']) ? mb_strtoupper($row['fullname'], 'UTF-8') : '-',
                    'branch_name'    => $row['branch_name'] ?? 'Pusat / Semua Cabang',
                    'division_name'  => $row['division_name'] ?? 'Semua Divisi',
                    'position_name'  => $row['position_name'] ?? '-',
                    'level_name'     => $row['level_name'] ?? '-',
                    'evaluator_name' => !empty($row['evaluator_name']) ? mb_strtoupper($row['evaluator_name'], 'UTF-8') : '(Belum ditentukan)',
                    'reviewer_name'  => !empty($row['reviewer_name']) ? mb_strtoupper($row['reviewer_name'], 'UTF-8') : '-',
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
            $targetGroup   = 'PERMANENT';
            $sections      = [];
            $items         = [];

            // Contextual preview info for simulated employee
            $sampleBranch   = 'Semua Cabang (Global)';
            $sampleDivision = 'Semua Divisi';
            $samplePosition = 'Semua Level / Posisi';

            if (!empty($post['payload'])) {
                // Live form builder preview via canonical compact -> expand contract
                $payload = is_string($post['payload']) ? json_decode($post['payload'], true) : $post['payload'];
                if (is_array($payload)) {
                    $templateTitle = !empty($payload['title']) ? $payload['title'] : $templateTitle;
                    $templateCode  = !empty($payload['template_code']) ? $payload['template_code'] : $templateCode;
                    $version       = (int) ($payload['version'] ?? 1);
                    $targetGroup   = strtoupper((string) ($payload['target_group'] ?? 'PERMANENT'));

                    $rawSections = (array) ($payload['sections'] ?? []);
                    if (empty($payload['target_group'])) {
                        foreach ($rawSections as $rawSec) {
                            $st = strtoupper((string) ($rawSec['section_type'] ?? $rawSec['type'] ?? ''));
                            if ($st === 'WEIGHTED_CHECKLIST' || $st === 'SINGLE_CHECKLIST') {
                                $targetGroup = 'OPERATOR';
                                break;
                            }
                        }
                    }

                    $schema   = AppraisalTemplateServices::compactSchema($rawSections, $targetGroup);
                    $expanded = AppraisalTemplateServices::expandSchema($schema);
                    $sections = $expanded['sections'];
                    $items    = $expanded['items'];
                }
            } elseif ($templateId > 0) {
                // Existing saved template preview
                $tpl = $this->model->find($templateId);
                if ($tpl) {
                    $templateTitle = $tpl->title;
                    $templateCode  = $tpl->template_code;
                    $version       = (int) $tpl->version;
                    $targetGroup   = strtoupper((string) ($tpl->target_group ?? 'PERMANENT'));
                }

                $schema = is_string($tpl->schema_json ?? null)
                    ? (json_decode($tpl->schema_json, true) ?: [])
                    : ((array) ($tpl->schema_json ?? []));

                if (!empty($schema['sections'])) {
                    $expanded    = AppraisalTemplateServices::expandSchema($schema, $tpl);
                    $sections    = $expanded['sections'];
                    $items       = $expanded['items'];
                    $targetGroup = strtoupper((string) ($expanded['template']['target_group'] ?? $targetGroup));
                }
            }

            // Snapshot format consumed by form_appraisal components
            $templateSnapshot = [
                'template' => [
                    'title'         => $templateTitle,
                    'template_code' => $templateCode,
                    'version'       => $version,
                    'target_group'  => $targetGroup,
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
                    'evaluator_comment' => '',
                    'reviewer_comment'  => '',
                ],
                'employee'             => [
                    'nik'           => '123456',
                    'name'          => 'Karyawan Simulasi',
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
                    'name' => 'Penilai',
                ],
                'reviewer'             => [
                    'name' => 'Peninjau',
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

    public function getOptionsByParent()
    {
        $levellingIds = $this->request->getVar('md_levelling_id');
        $positionIds  = $this->request->getVar('md_position_id');
        $divisionIds  = $this->request->getVar('md_division_id');
        $branchIds    = $this->request->getVar('md_branch_id');
        $search       = $this->request->getVar('search') ?? $this->request->getVar('term');
        $type         = $this->request->getVar('type');

        // Pastikan input berupa array bersih jika multi-select
        $levellingIds = !empty($levellingIds) && $levellingIds !== '0' ? (array) $levellingIds : [];
        $positionIds  = !empty($positionIds) && $positionIds !== '0'   ? (array) $positionIds  : [];
        $divisionIds  = !empty($divisionIds) && $divisionIds !== '0'   ? (array) $divisionIds  : [];
        $branchIds    = !empty($branchIds) && $branchIds !== '0'       ? (array) $branchIds    : [];

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
                // Ambil jabatan aktif yang difilter oleh level jika ada
                $builder = $this->db->table('md_position')
                    ->select('md_position_id as id, name as text')
                    ->where('isactive', 'Y');
                if (!empty($levellingIds)) {
                    $builder->whereIn('md_levelling_id', $levellingIds);
                }
                if (!empty(trim($search)) && $search !== 'undefined' && $search !== 'null') {
                    $builder->like('name', trim($search));
                }
                $data = $builder->orderBy('name', 'ASC')->get()->getResultArray();
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