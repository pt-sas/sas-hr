<?php

namespace App\Libraries;

use App\Models\M_Menu;
use App\Models\M_Submenu;
use App\Models\M_Employee;
use App\Libraries\Access;
use App\Models\M_Appraisal;
use App\Models\M_AppraisalCycle;
use App\Models\M_AppraisalTemplate;
use App\Models\M_DocumentType;
use Config\Services;

class Template
{
    protected $request;
    protected $session;
    protected $access;
    protected $isView = 'isview';
    protected $isCreate = 'iscreate';
    protected $isUpdate = 'isupdate';
    protected $isDelete = 'isdelete';
    protected $PATH_UPLOAD = "/uploads/";
    protected $BTN_Print = 'PRINT';

    public function __construct()
    {
        $this->request = Services::request();
        $this->session = Services::session();
        $this->access = new Access();
    }

    public function render($template = '', $view_data = [])
    {
        $mEmployee = new M_Employee($this->request);
        $mDocType = new M_DocumentType($this->request);

        $uri = $this->request->uri->getSegment(2);
        $picture = base_url("/custom/image/no_image.png");

        // Set previouse url from current url
        $this->session->set(['previous_url' => current_url()]);

        $view_data['title'] = $this->access->getMenu($uri, 'name');
        $view_data['filter'] = $this->renderPage($template, 'form_filter');
        $view_data['table_report'] = $this->renderPage($template, 'table_report');
        $view_data['sidebar'] = $this->menuSidebar();
        $view_data['toolbar_button'] = $this->toolbarButton();
        $view_data['action_button'] = $this->actionButton();
        $view_data['action_menu'] = $this->access->getMenu($uri, 'action');

        $view_data['username'] = $this->access->getUser('username');
        $view_data['name'] = $this->access->getUser('name');
        $view_data['email'] = $this->access->getUser('email');
        $view_data['level'] = $this->access->getRole() ? $this->access->getRole()->getName() : 'No Role';

        if (!empty($this->session->get('md_employee_id'))) {
            $PATH_Karyawan = "karyawan/";
            $rowEmp = $mEmployee->find($this->session->get('md_employee_id'));
            $image = $rowEmp->getImage();
            $path = $this->PATH_UPLOAD . $PATH_Karyawan . $image;

            if (file_exists(FCPATH . $path))
                $picture = base_url($path);
        }

        $view_data['foto'] = $picture;

        return view($template, $view_data);
    }

    private function renderPage($path, $fileName)
    {
        $ext = '.php';
        $view = explode('/', $path);

        // Remove last element array
        array_pop($view);

        $path = implode('/', $view);

        $dir = APPPATH . '/Views/' . $path . '/';

        $file = $dir . $fileName . $ext;

        if (file_exists($file))
            $result = $path . '/' . $fileName;
        else
            $result = false;

        return $result;
    }

    public function tableButton($btnID, $status = null, $type = null, $editable = true)
    {
        $uri = $this->request->uri->getSegment(2);
        $allBtn = '';

        if (!is_null($type))
            $type = strtoupper($type);

        $btnUpdate = '<a class="btn" onclick="Edit(' . "'" . $btnID . "'" . ')" id="' . $btnID . '" data-toggle="tooltip" title="Edit" data-original-title="Edit"><i class="fas fa-edit text-info"></i></a>';

        $btnDelete = '<a class="btn" onclick="Destroy(' . "'" . $btnID . "'" . ')" data-toggle="tooltip" title="Hapus" data-original-title="Hapus"><i class="fas fa-trash-alt text-danger"></i></a>';

        $btnProcess = '<a class="btn" onclick="docProcess(' . "'" . $btnID . "'," . "'" . $status . "'" . ')" data-toggle="tooltip" title="Proses" data-original-title="Proses"><i class="fas fa-cog text-primary"></i></a>';

        $btnDetail = '<a class="btn" onclick="Edit(' . "'" . $btnID . "'," . "'" . $status . "'" . ')" id="' . $btnID . '" data-status="' . $status . '" data-toggle="tooltip" title="Detail" data-original-title="Detail"><i class="fas fa-file text-info"></i></a>';

        $btnPrint = '<a class="btn btn_print" data-toggle="tooltip" title="Cetak" data-original-title="Cetak"><i class="fas fa-print text-default"></i></a>';

        $btnPreview = '<a class="btn" onclick="Edit(' . "'" . $btnID . "'," . "'IP'" . ')" id="' . $btnID . '" data-status="IP" data-toggle="tooltip" title="Detail" data-original-title="Detail"><i class="fas fa-file text-info"></i></a>';

        $update = $this->access->checkCrud($uri, $this->isUpdate);
        $delete = $this->access->checkCrud($uri, $this->isDelete);

        if ($update === 'Y' && (empty($status) || ($status === 'DR' && $editable)))
            $allBtn .= $btnUpdate;
        else if ($update === 'Y' && (!empty($status) && $status !== 'DR'))
            $allBtn .= $btnDetail;
        else if ($update === 'Y' && (!empty($status) && ($status == 'DR' && !$editable)))
            $allBtn .= $btnPreview;

        if ($update === 'Y' && !empty($status) && ($status === 'CO' || $status === 'DR' || $status === 'NA') && $editable)
            $allBtn .= $btnProcess;

        if (!is_null($type) && $type === $this->BTN_Print && ($status === 'CO' || $status === 'IP' || $status === 'NA'))
            $allBtn .= $btnPrint;

        if ($delete === 'Y')
            $allBtn .= $btnDelete;

        return $allBtn;
    }

    private function toolbarButton()
    {
        $uri = $this->request->uri->getSegment(2);
        $allBtn = '';

        $btnNew = '<button type="button" class="btn btn-primary btn-sm btn-round ml-auto new_form mr-1" title="New Record"><i class="fas fa-plus fa-fw"></i> Add New</button>';

        $btnExport = '<a id="dt-button"></a> ';

        $btnReQuery = '<button type="button" class="btn btn-black btn-border btn-sm btn-round ml-auto btn_requery mr-1" title="Refresh"><i class="fas fa-sync fa-fw"></i> Refresh </button>';

        $btnPrint = '<button type="button" class="btn btn-danger btn-sm btn-round ml-auto btn_print_qrcode mr-1" title="Print"><i class="fas fa-print fa-fw"></i> Print</button>';

        $check = $this->access->checkCrud($uri, $this->isCreate);
        $role = $this->access->getRole();

        //TODO: Get field action from menu
        $action_menu = $this->access->getMenu($uri, 'action');

        if (($role && $role->getIsCanExport() === 'Y' || $action_menu === 'R') && $action_menu !== 'F')
            $allBtn .= $btnExport;

        if ($action_menu === 'T') {
            if ($check === 'Y')
                $allBtn .= $btnNew;

            $allBtn .= $btnReQuery . ' ';
        }

        if ($action_menu === 'R')
            $allBtn .= $btnPrint;

        return $allBtn;
    }

    private function actionButton()
    {
        $mDocType = new M_DocumentType($this->request);

        $uri = $this->request->uri->getSegment(2);
        $allBtn = '';

        $isSubmission = $mDocType->getDocTypeMenu(null, $uri) ?? null;

        //* Button for Table Form 
        $btnTableForm = '<div class="card-action card-button">
                        <button type="button" class="btn btn-outline-danger btn-round ml-auto close_form">Close</button>
                        <button type="button" class="btn btn-primary btn-round ml-auto save_form">Save changes</button>';

        // Send button for broadcast telegram
        if ($uri === 'broadcast-telegram') {
            $btnTableForm .= '<button type="button" class="btn btn-success btn-round save_form save_send_form" style="float: right;">Send Now</button>';
        }

        if ($isSubmission)
            $btnTableForm .= '<button type="button" class="btn btn-warning btn-round ml-1 save_form save_process">Save & Process</button>';

        $btnTableForm .= '</div';

        //* Button for Parameter Form 
        $btnParamForm = '<div class="card-action d-flex justify-content-center">
                            <div>
                                <button type="button" class="btn btn-danger btn-sm btn-round ml-auto btn_reset_form"><i class="fas fa-undo-alt fa-fw"></i> Reset</button>
                                <button type="button" class="btn btn-success btn-sm btn-round ml-auto btn_ok_form"><i class="fas fa-check fa-fw"></i> OK</button>
                            </div>
                        </div>';

        //* Button for Single Form 
        $btnForm = '<div class="card-action">
                        <button type="button" class="btn btn-outline-danger btn-round ml-auto reset_form">Reset</button>
                        <button type="button" class="btn btn-primary btn-round ml-auto save_form">Save changes</button>
                    </div>';

        $check = $this->access->checkCrud($uri, $this->isCreate);

        //TODO: Get field action from menu 
        $action_menu = $this->access->getMenu($uri, 'action');

        if ($check === 'Y') {
            if ($action_menu === 'F') {
                $allBtn .= $btnForm;
            } else if ($action_menu === 'T') {
                $allBtn .= $btnTableForm;
            } else if ($action_menu === 'R') {
                $allBtn .= $btnParamForm;
            }
        }

        return $allBtn;
    }

    private function menuSidebar()
    {
        $uri = $this->request->uri->getSegment(2);
        $menu = new M_Menu($this->request);
        $submenu = new M_Submenu($this->request);

        $menuParent = $menu->where('isactive', 'Y')
            ->orderBy('sequence', 'ASC')
            ->findAll();

        $sidebar = '<ul class="nav nav-primary">';

        foreach ($menuParent as $row) :
            $menu_id = $row->getMenuId();

            // Get value access parent menu
            $check = $this->access->checkCrud(null, $this->isView, $menu_id, 'parent');

            if ($check === 'Y') {
                $isActive = '';
                if ($uri == '' && $row->url === 'dashboard')
                    $isActive = 'active';
                else if ($uri == $row->url)
                    $isActive = 'active';

                $subMenu = $submenu->where([
                    'isactive'          => $this->access->active(),
                    $menu->primaryKey   => $menu_id
                ])->orderBy('sequence', 'ASC')
                    ->findAll();

                if ($subMenu) {
                    $subActive = '';

                    foreach ($subMenu as $row2) :
                        if ($uri == $row2->url)
                            $subActive = 'active';
                    endforeach;

                    $sidebar .= '<li class="nav-item ' . $subActive . ' submenu">
                                <a data-toggle="collapse" href="#' . $row->getUrl() . '">
                                    <i class="' . $row->getIcon() . '"></i>
                                    <p>' . $row->getName() . '</p>
                                    <span class="caret"></span>
                                </a>';

                    if (!empty($subActive))
                        $sidebar .= '<div class="collapse show" id="' . $row->getUrl() . '">';
                    else
                        $sidebar .= '<div class="collapse" id="' . $row->getUrl() . '">';

                    $sidebar .= '<ul class="nav nav-collapse">';

                    foreach ($subMenu as $row2) :
                        $sub_id = $row2->getSubId();

                        // Get value access submenu
                        $check = $this->access->checkCrud(null, $this->isView, $sub_id);

                        $subActive2 = '';

                        if ($uri == $row2->url)
                            $subActive2 = 'active';

                        if ($check === 'Y')
                            $sidebar .= '<li class="' . $subActive2 . '">
                            <a href="' . site_url('sas/' . $row2->getUrl()) . '"><span class="sub-item">' . $row2->getName() . '</span></a>
                        </li>';
                    endforeach;

                    $sidebar .= '</ul>
                                </div>
                            </li>';
                } else {
                    $sidebar .= '<li class="nav-item ' . $isActive . '">';

                    if ($row->url === 'dashboard')
                        $sidebar .= '<a href="' . site_url('sas') . '">';
                    else
                        $sidebar .= '<a href="' . site_url('sas/' . $row->getUrl()) . '">';

                    $sidebar .= '<i class="' . $row->getIcon() . '"></i>
                            <p>' . $row->getName() . '</p>
                        </a>
                    </li>';
                }
            }
        endforeach;

        $sidebar .= '</ul>';

        return $sidebar;
    }

    public function tableButtonProcess($btnID, $type = null)
    {
        $uri = $this->request->uri->getSegment(2);
        $allBtn = "";

        $btnNotAgree = '<button class="btn btn-sm btn-danger rounded ml-4 btn_not_agree" id="' . $btnID . '" name="not_agree" data-toggle="tooltip" title="Tidak Setuju" data-original-title="Tidak Setuju"><i class="fas fa-times"></i> Tidak Setuju</button>';

        $btnAgree = '<button class="btn btn-sm btn-success rounded mr-5 btn_agree" id="' . $btnID . '" name="agree" data-toggle="tooltip" title="Setuju" data-original-title="Setuju"><i class="fas fa-check"></i> Setuju</button>';

        $create = $this->access->checkCrud($uri, $this->isCreate);
        $update = $this->access->checkCrud($uri, $this->isUpdate);

        if ($create === 'Y' && $update === 'Y' && is_null($type)) {

            $allBtn = '<div class="d-flex justify-content-between">
                            <div>
                                <button class="btn btn-sm btn-success rounded ml-auto btn_agree" id="' . $btnID . '" name="agree" data-toggle="tooltip" title="Setuju" data-original-title="Setuju"><i class="fas fa-check"></i> Setuju</button>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-danger rounded ml-auto btn_not_agree" id="' . $btnID . '" name="not_agree" data-toggle="tooltip" title="Tidak Setuju" data-original-title="Tidak Setuju"><i class="fas fa-times"></i> Tidak Setuju</button>
                            </div>
                        </div>';
        }

        if ($create === 'Y' && $update === 'Y' && !is_null($type))
            $allBtn = '<div class="d-flex justify-content-center">
                        <div>
                            <button class="btn btn-sm btn-success rounded ml-auto btn_agree" id="' . $btnID . '" name="agree" data-toggle="tooltip" title="Setuju" data-original-title="Setuju"><i class="fas fa-check"></i> Setuju</button>
                        </div>
                    </div>';

        return $allBtn;
    }

    public function toolbarButtonProcess()
    {
        $uri = $this->request->uri->getSegment(2);
        $allBtn = '';

        $btnNotAgree = '<button type="button" class="btn btn-danger btn-sm btn-round ml-auto btn_agree mr-1" data-toggle="tooltip" title="Tidak Setuju" data-original-title="Tidak Setuju"><i class="fas fa-times fa-fw"></i> Tidak Setuju</button>';

        $btnAgree = '<button type="button" class="btn btn-success btn-sm btn-round ml-auto btn_not_agree" data-toggle="tooltip" title="Setuju" data-original-title="Setuju"><i class="fas fa-check fa-fw"></i> Setuju</button>';

        $create = $this->access->checkCrud($uri, $this->isCreate);
        $update = $this->access->checkCrud($uri, $this->isUpdate);

        if ($create === 'Y' && $update === 'Y')
            $allBtn .= $btnNotAgree . $btnAgree;

        return $allBtn;
    }

    public function buttonGenerate()
    {
        $uri = $this->request->uri->getSegment(2);
        $allBtn = '';

        $btnGenerate = '<div class="d-flex justify-content-between">
                            <div>
                                <a class="btn btn-sm btn-danger rounded ml-auto btn_generate_alpa" onclick="Generate()" name="generate_alpa" data-toggle="tooltip" title="Generate Alpa" data-original-title="Generate Alpa"><i class="fas fa-cog"></i> Generate</a>
                            </div>
                        </div>';

        $create = $this->access->checkCrud($uri, $this->isCreate);

        if ($create === 'Y')
            $allBtn = $btnGenerate;

        return $allBtn;
    }

    public function buttonEdit($btnID)
    {
        $uri = $this->request->uri->getSegment(2);
        $allBtn = '';

        $btnEdit = '<div class="d-flex justify-content-between">
                            <div>
                                <a class="btn btn-sm btn-primary rounded ml-auto btn_edit_att" id="' . $btnID . '" name="attendance" data-toggle="tooltip" title="Edit Kehadiran" data-original-title="Edit Kehadiran"><i class="fas fa-edit"></i> Edit</a>
                            </div>
                        </div>';

        $create = $this->access->checkCrud($uri, $this->isCreate);

        if ($create === 'Y')
            $allBtn = $btnEdit;

        return $allBtn;
    }

    public function buttonExport()
    {
        $btnExport = '<a id="dt-button"></a> ';

        return $btnExport;
    }

    public function buttonGenerateMemo($id)
    {
        $uri = $this->request->uri->getSegment(2);
        $allBtn = '';

        $btnGenerate = '<div class="d-flex justify-content-between">
                            <div>
                                <a class="btn btn-sm btn-danger rounded ml-auto btn_generate_memo" id="' . $id . '" name="btn_generate_memo" data-toggle="tooltip" title="Generate Memo" data-original-title="Generate Memo"><i class="fas fa-cog"></i> Generate</a>
                            </div>
                        </div>';

        $create = $this->access->checkCrud($uri, $this->isCreate);

        if ($create === 'Y')
            $allBtn = $btnGenerate;

        return $allBtn;
    }

    public function buttonRecordInfo($id, $menu)
    {
        $btnRecordInfo = "<div class='d-flex justify-content-between'>
                            <button type='button' class='btn btn-icon btn-primary btn_record' data-toggle='tooltip' data-placement='top' title='Record Info' id = {$id} data-url = {$menu}>
                                <i class='fas fa-search fa-lg'></i>
                            </button>
                            </div>";

        return $btnRecordInfo;
    }

    public function buttonNews($id)
    {
        $uri = $this->request->uri->getSegment(2);
        $allBtn = '';

        $btnInputNews = '<div class="d-flex justify-content-between">
                            <div>
                                <a class="btn btn-sm btn-info rounded ml-auto btn_input_news" id="' . $id . '" name="input_news" data-toggle="tooltip" title="Input Kabar" data-original-title="Input Kabar"><i class="flaticon-chat-8"></i> Input Kabar</a>
                            </div>
                        </div>';

        $create = $this->access->checkCrud($uri, $this->isCreate);

        if ($create === 'Y')
            $allBtn = $btnInputNews;

        return $allBtn;
    }

    /**
     * Render contextual DataTable action buttons for appraisal modules.
     *
     * @param string     $context 'evaluation' | 'dashboard' | 'cycle' | 'template'
     * @param int|string $id
     * @param array      $options Context-specific data & flags
     */
    public function appraisalTableButton(string $context, $id, array $options = []): string
    {
        switch ($context) {
            case 'evaluation':
                return $this->appraisalEvaluationButton($id, $options);
            case 'dashboard':
                return $this->appraisalDashboardButton($id, $options);
            case 'cycle':
                return $this->appraisalCycleButton($id, $options);
            case 'template':
                return $this->appraisalTemplateButton($id, $options);
            default:
                return '';
        }
    }

    private function appraisalEvaluationButton($id, array $options): string
    {
        $row     = $options['row'] ?? [];
        $roleTab = (string) ($options['role_tab'] ?? 'EVALUATOR');
        $empId   = (int) ($options['emp_id'] ?? 0);
        $isAdmin = !empty($options['is_admin']);
        $isDuta  = !empty($options['is_duta']);
        $status  = $row['status'] ?? ($options['status'] ?? '');
        $actions = '';

        $cycleStage = null;
        if (!empty($row['start_date']) || !empty($row['deadline_filling'])) {
            $today = time();
            $fill  = !empty($row['deadline_filling']) ? strtotime($row['deadline_filling']) : null;
            $rev   = !empty($row['deadline_review']) ? strtotime($row['deadline_review']) : null;
            $read  = !empty($row['deadline_reading']) ? strtotime($row['deadline_reading']) : null;
            $over  = !empty($row['deadline_override']) ? strtotime($row['deadline_override']) : null;
            $end   = !empty($row['end_date']) ? strtotime($row['end_date']) : null;

            if ($fill && $today <= $fill) {
                $cycleStage = 2;
            } elseif ($rev && $today <= $rev) {
                $cycleStage = 3;
            } elseif ($read && $today <= $read) {
                $cycleStage = 4;
            } elseif ($over && $today <= $over) {
                $cycleStage = 5;
            } elseif ($end && $today > $end) {
                $cycleStage = 6;
            }
        }

        $uri       = $this->request->uri->getSegment(2);
        $canUpdate = $this->access->checkCrud($uri, $this->isUpdate) === 'Y';

        if ($canUpdate && $status === M_Appraisal::STATUS_DRAFT_EVALUATOR && ($roleTab === 'EVALUATOR' || (int) ($row['snapshot_evaluator_id'] ?? 0) === $empId || ($isAdmin && $roleTab !== 'EMPLOYEE'))) {
            $actions .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\')" title="Isi Penilaian"><i class="fas fa-edit text-primary"></i></a>';
        } elseif ($canUpdate && $status === M_Appraisal::STATUS_REVIEW && ($roleTab === 'REVIEWER' || (int) ($row['snapshot_reviewer_id'] ?? 0) === $empId || ($isAdmin && $roleTab !== 'EMPLOYEE'))) {
            $actions .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\')" title="Review Peninjau"><i class="fas fa-user-check text-info"></i></a>';
        } elseif ($canUpdate && $status === M_Appraisal::STATUS_PENDING_ACK && ((int) ($row['md_employee_id'] ?? 0) === $empId || $isDuta || $isAdmin)) {
            $isDirectEmployee = ((int) ($row['md_employee_id'] ?? 0) === $empId);
            // Intervensi HR is strictly AFTER pembacaan (Stage 5)
            if ($isAdmin && !$isDirectEmployee && !$isDuta && $cycleStage === 5) {
                $actions .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\')" title="Edit Penilaian"><i class="fas fa-edit text-primary"></i></a>';
            } else {
                $ttdTitle = 'Konfirmasi TTD';
                if ($isDuta && !$isDirectEmployee) {
                    $ttdTitle .= ' (Duta)';
                }
                $actions .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\')" title="' . $ttdTitle . '"><i class="fas fa-signature text-warning"></i></a>';
            }
        } elseif ($status === M_Appraisal::STATUS_FINALIZED) {
            $actions .= '<a class="btn btn-sm" href="' . base_url('sas/penilaian/complete/' . $id) . '" title="Rapor Kinerja"><i class="fas fa-file-alt text-success"></i></a>';
        } else {
            $actions .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\', \'VIEW\')" title="Lihat Detail"><i class="fas fa-eye text-info"></i></a>';
        }

        // Score Comparison & History Action Button
        $actions .= '<button type="button" class="btn btn-sm" onclick="showScoreComparisonModal(\'' . $id . '\')" title="Riwayat & Komparasi Skor"><i class="fas fa-history text-primary"></i></button>';

        return $actions;
    }

    private function appraisalDashboardButton($id, array $options): string
    {
        $appStatus    = (string) ($options['status'] ?? '');
        $employeeName = (string) ($options['employee_name'] ?? 'Karyawan');
        $isAdmin      = !empty($options['is_admin']);

        $actions = '<div class="d-flex align-items-center justify-content-center" style="gap: 4px;">';
        if ($appStatus !== M_Appraisal::STATUS_FINALIZED) {
            // Pending / Active: Nudge (HR/Admin only) + Preview Form
            if ($isAdmin) {
                $actions .= '<button type="button" class="btn btn-sm" onclick="window.nudgeAppraisal(' . $id . ', \'' . esc(addslashes($employeeName)) . '\')" data-toggle="tooltip" title="Kirim Pengingat (Nudge)"><i class="fas fa-bell text-warning"></i></button>';
            }
            $actions .= '<a href="' . base_url('sas/penilaian/complete/' . $id) . '" class="btn btn-sm" data-toggle="tooltip" title="Pratinjau Form"><i class="fas fa-eye text-info"></i></a>';
        } else {
            // Finalized: Rapor Lengkap + Cetak PDF
            $actions .= '<a href="' . base_url('sas/penilaian/complete/' . $id) . '" class="btn btn-sm" data-toggle="tooltip" title="Lihat Rapor Lengkap"><i class="fas fa-file-alt text-success"></i></a>';
            $actions .= '<a href="' . base_url('sas/penilaian/exportPDF/' . $id) . '" target="_blank" class="btn btn-sm" data-toggle="tooltip" title="Cetak Rapor (PDF)"><i class="fas fa-print text-danger"></i></a>';
        }
        // Riwayat & Komparasi Skor Historis
        $actions .= '<button type="button" class="btn btn-sm" onclick="showScoreComparisonModal(\'' . $id . '\')" title="Riwayat &amp; Komparasi Skor"><i class="fas fa-history text-primary"></i></button>';
        $actions .= '</div>';

        return $actions;
    }

    private function appraisalCycleButton($id, array $options): string
    {
        $uri       = $this->request->uri->getSegment(2);
        $canUpdate = $this->access->checkCrud($uri, $this->isUpdate) === 'Y';
        $canDelete = $this->access->checkCrud($uri, $this->isDelete) === 'Y';
        $status    = (string) ($options['status'] ?? '');
        $actions   = '';

        if ($status === M_AppraisalCycle::STATUS_DRAFT) {
            if ($canUpdate) {
                $actions .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\')" id="' . $id . '" title="Edit"><i class="fas fa-edit text-info"></i></a>';
                $actions .= '<a class="btn btn-sm" onclick="window.cycPreviewBlast(\'' . $id . '\')" title="Simulasi Distribusi"><i class="fas fa-paper-plane text-warning"></i></a>';
                $actions .= '<a class="btn btn-sm" onclick="window.cycOpen(\'' . $id . '\')" title="Buka Siklus"><i class="fas fa-play text-success"></i></a>';
            } else {
                $actions .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\', \'CLOSED\')" id="' . $id . '" data-status="CLOSED" title="Detail"><i class="fas fa-eye text-info"></i></a>';
            }
            if ($canDelete) {
                $actions .= '<a class="btn btn-sm" onclick="Destroy(\'' . $id . '\')" title="Hapus"><i class="fas fa-trash-alt text-danger"></i></a>';
            }
        } elseif ($status === M_AppraisalCycle::STATUS_OPEN) {
            $actions .= '<a class="btn btn-sm" onclick="window.cycProgress(\'' . $id . '\')" title="Monitoring Progres"><i class="fas fa-chart-line text-primary"></i></a>';
            $actions .= '<a class="btn btn-sm" onclick="window.cycPreviewBlast(\'' . $id . '\')" title="Daftar Karyawan"><i class="fas fa-users text-info"></i></a>';
            if ($canUpdate) {
                $actions .= '<a class="btn btn-sm" onclick="window.cycClose(\'' . $id . '\')" title="Tutup Siklus"><i class="fas fa-lock text-danger"></i></a>';
            }
        } else {
            $actions .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\', \'CLOSED\')" id="' . $id . '" data-status="CLOSED" title="Detail"><i class="fas fa-eye text-info"></i></a>';
            $actions .= '<a class="btn btn-sm" onclick="window.cycProgress(\'' . $id . '\')" title="Laporan Akhir"><i class="fas fa-chart-line text-primary"></i></a>';
        }

        return $actions;
    }

    private function appraisalTemplateButton($id, array $options): string
    {
        $uri         = $this->request->uri->getSegment(2);
        $canUpdate   = $this->access->checkCrud($uri, $this->isUpdate) === 'Y';
        $canDelete   = $this->access->checkCrud($uri, $this->isDelete) === 'Y';
        $status      = (string) ($options['status'] ?? M_AppraisalTemplate::STATUS_DRAFT);
        $usageCount  = (int) ($options['usage_count'] ?? 0);
        $inUse       = ($usageCount > 0);

        $isDraft     = ($status === M_AppraisalTemplate::STATUS_DRAFT);
        $isPublished = ($status === M_AppraisalTemplate::STATUS_PUBLISHED);

        $actionBtns = '';
        if ($isDraft) {
            if ($canUpdate) {
                $actionBtns .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\')" id="' . $id . '" title="Edit Template"><i class="fas fa-edit text-info"></i></a>';
                $actionBtns .= '<a class="btn btn-sm" onclick="window.tplPublish(\'' . $id . '\')" title="Publish Template"><i class="fas fa-check-circle text-success"></i></a>';
            } else {
                $actionBtns .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\', \'CO\')" id="' . $id . '" data-status="CO" title="Lihat Detail"><i class="fas fa-eye text-info"></i></a>';
            }
            if ($canDelete) {
                $actionBtns .= '<a class="btn btn-sm" onclick="Destroy(\'' . $id . '\')" title="Hapus Draft"><i class="fas fa-trash-alt text-danger"></i></a>';
            }
        } elseif ($isPublished) {
            if (!$inUse) {
                if ($canUpdate) {
                    $actionBtns .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\')" id="' . $id . '" title="Edit Template (Belum Pernah Digunakan)"><i class="fas fa-edit text-info"></i></a>';
                    $actionBtns .= '<a class="btn btn-sm" onclick="window.tplUnpublish(\'' . $id . '\')" title="Batalkan Publish (Kembalikan ke Draft)"><i class="fas fa-undo text-warning"></i></a>';
                } else {
                    $actionBtns .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\', \'CO\')" id="' . $id . '" data-status="CO" title="Lihat Detail"><i class="fas fa-eye text-info"></i></a>';
                }
                if ($canDelete) {
                    $actionBtns .= '<a class="btn btn-sm" onclick="Destroy(\'' . $id . '\')" title="Hapus Template (Belum Pernah Digunakan)"><i class="fas fa-trash-alt text-danger"></i></a>';
                }
            } else {
                $actionBtns .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\', \'CO\')" id="' . $id . '" data-status="CO" title="Lihat Detail (Terkunci: ' . $usageCount . ' Penilaian)"><i class="fas fa-eye text-info"></i></a>';
                if ($canUpdate) {
                    $actionBtns .= '<a class="btn btn-sm" onclick="window.tplClone(\'' . $id . '\')" title="Buat Versi Baru (Draft)"><i class="fas fa-code-branch text-warning"></i></a>';
                    $actionBtns .= '<a class="btn btn-sm" onclick="window.tplArchive(\'' . $id . '\')" title="Nonaktifkan / Arsipkan Template (Terkunci)"><i class="fas fa-power-off text-secondary"></i></a>';
                }
            }
        } else {
            // ARCHIVED or others
            $actionBtns .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\', \'VO\')" id="' . $id . '" data-status="VO" title="Lihat Detail"><i class="fas fa-eye text-info"></i></a>';
            if ($inUse) {
                if ($canUpdate) {
                    $actionBtns .= '<a class="btn btn-sm" onclick="window.tplActivate(\'' . $id . '\')" title="Aktifkan Kembali Template (Published)"><i class="fas fa-check-circle text-success"></i></a>';
                }
            } elseif ($canDelete) {
                $actionBtns .= '<a class="btn btn-sm" onclick="Destroy(\'' . $id . '\')" title="Hapus Template (Belum Pernah Digunakan)"><i class="fas fa-trash-alt text-danger"></i></a>';
            }
        }

        $btnPreview = '<button type="button" class="btn btn-sm btn_preview_template" data-id="' . $id . '" data-toggle="tooltip" title="Pratinjau Form" data-original-title="Pratinjau Form"><i class="fas fa-file-alt text-primary"></i></button>';

        return $actionBtns . $btnPreview;
    }
}
