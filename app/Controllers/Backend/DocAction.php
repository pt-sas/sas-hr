<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_DocAction;
use App\Models\M_Reference;
use Config\Services;

class DocAction extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_DocAction($this->request);
        $this->entity = new \App\Entities\DocAction();
    }

    public function getDocaction()
    {
        $mRef = new M_Reference($this->request);

        $post = $this->request->getPost();

        $response = [];

        try {
            $checkAccess = $this->model->where('sys_role_id', $this->access->getSessionRole())->first();

            if ($checkAccess) {
                if ($post['status'] === $this->DOCSTATUS_Drafted) {
                    $list = $this->model->where([
                        'menu'          => $post['url'],
                        'isactive'      => 'Y',
                        'sys_role_id'   => $this->access->getSessionRole()
                    ])->whereIn('ref_list', [$this->DOCSTATUS_Voided, $this->DOCSTATUS_Completed])->findAll();
                } else {
                    $list = $this->model->where([
                        'menu'      => $post['url'],
                        'isactive'  => 'Y',
                        'sys_role_id'   => $this->access->getSessionRole()
                    ])->whereNotIn('ref_list', [$this->DOCSTATUS_Completed])->findAll();
                }

                foreach ($list as $key => $row) :
                    //* Data Reference
                    $ref = $mRef->findBy([
                        'sys_reference.name'    => '_DocAction',
                        'sys_ref_detail.value'  => $row->ref_list,
                    ])->getRow();

                    $response[$key]['id'] = $row->ref_list;
                    $response[$key]['text'] = $ref->name;
                endforeach;
            }
        } catch (\Exception $e) {
            $response = message('error', false, $e->getMessage());
        }

        return json_encode($response);
    }
}
