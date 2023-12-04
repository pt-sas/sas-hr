<?php

namespace App\Libraries;

use App\Models\M_Menu;
use App\Models\M_Submenu;
use App\Libraries\Access;
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
    protected $Movement_Kirim = 'KIRIM';
    protected $Movement_Terima = 'TERIMA';

    public function __construct()
    {
        $this->request = Services::request();
        $this->session = Services::session();
        $this->access = new Access();
    }

    public function render($template = '', $view_data = [])
    {
        $uri = $this->request->uri->getSegment(2);

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

    public function tableButton($btnID, $status = null, $type = null)
    {
        $uri = $this->request->uri->getSegment(2);
        $allBtn = '';

        $btnUpdate = '<a class="btn" onclick="Edit(' . "'" . $btnID . "'" . ')" id="' . $btnID . '" data-toggle="tooltip" title="Edit" data-original-title="Edit"><i class="fas fa-edit text-info"></i></a>';

        $btnDelete = '<a class="btn" onclick="Destroy(' . "'" . $btnID . "'" . ')" data-toggle="tooltip" title="Delete" data-original-title="Delete"><i class="fas fa-trash-alt text-danger"></i></a>';

        $btnProcess = '<a class="btn" onclick="docProcess(' . "'" . $btnID . "'," . "'" . $status . "'" . ')" data-toggle="tooltip" title="Document Action" data-original-title="Document Action"><i class="fas fa-cog text-primary"></i></a>';

        $btnDetail = '<a class="btn" onclick="Edit(' . "'" . $btnID . "'," . "'" . $status . "'" . ')" id="' . $btnID . '" data-status="' . $status . '" data-toggle="tooltip" title="Detail" data-original-title="Detail"><i class="fas fa-file text-info"></i></a>';

        $btnAccept = '<a class="btn" onclick="Accept(' . "'" . $btnID . "'" . ')" data-toggle="tooltip" title="Accept" data-original-title="Detail"><i class="fas fa-check fa-lg text-success"></i></a>';

        $update = $this->access->checkCrud($uri, $this->isUpdate);
        $delete = $this->access->checkCrud($uri, $this->isDelete);

        //? Belum di deploy 
        // if ($update === 'Y' && strtoupper($type) === $this->Movement_Terima && ($status === 'DR' || $status === 'IP'))
        //     $allBtn .= $btnAccept;

        if ($update === 'Y' && (empty($status) || $status === 'DR'))
            $allBtn .= $btnUpdate;
        else if ($update === 'Y' && (!empty($status) && $status !== 'DR'))
            $allBtn .= $btnDetail;

        if ($update === 'Y' && !empty($status) && ($status === 'CO' || $status === 'DR' || $status === 'NA'))
            $allBtn .= $btnProcess;

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
        $uri = $this->request->uri->getSegment(2);
        $allBtn = '';

        //* Button for Table Form 
        $btnTableForm = '<div class="card-action card-button">
                        <button type="button" class="btn btn-outline-danger btn-round ml-auto close_form">Close</button>
                        <button type="button" class="btn btn-primary btn-round ml-auto save_form">Save changes</button>
                    </div>';

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
}
