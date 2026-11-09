<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Branch;
use App\Models\M_Division;
use App\Models\M_Employee;
use App\Models\M_MasterBundling;
use App\Services\MasterBundlingServices;
use Config\Services;

class MasterBundling extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_MasterBundling($this->request);
        $this->entity = new \App\Entities\MasterBundling();
    }

    public function index()
    {
        $mBranch = new M_Branch($this->request);
        $mDiv = new M_Division($this->request);

        $data = [
            'today'         => date('d-M-Y'),
            'branch'      => $mBranch->where('isactive', 'Y')
                ->orderBy('name', 'ASC')
                ->findAll(),
            'division'    => $mDiv->where('isactive', 'Y')
                ->orderBy('name', 'ASC')
                ->findAll(),
        ];

        return $this->template->render('transaction/masterbundling/v_master_bundling', $data);
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = $this->model->column_order;
            $search = $this->model->column_search;
            $sort = $this->model->order;

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join);

            $data = [];

            foreach ($list as $value) :
                $row = [];
                $ID = $value->{$this->model->primaryKey};

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->name;
                $row[] = $value->doctype_name;
                $row[] = format_dmy($value->created_at, '-');
                $row[] = $value->description;
                $row[] = $value->createdby;
                $row[] = $this->template->tableButton($ID);
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search, $join),
                'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join),
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            try {
                if (!$this->validation->run($post, 'pendaftaran_paket')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    //* Set ID Data
                    $post['trx_master_bundling_id'] = !empty($post['id']) ? $post['id'] : null;

                    $service = new MasterBundlingServices($this->session->get('sys_user_id'), $this->session->get('md_employee_id'));
                    $result = $service->create($post);

                    if ($result && !empty($post['id'])) {
                        $response = message('success', true, "Your data has been updated successfully !");
                    } else if ($result) {
                        $response = message('success', true, "Your data has been inserted successfully !");
                    } else {
                        $response = message('error', false, "No data to Insert");
                    }
                }
            } catch (\App\Exceptions\BaseException $e) {
                $response = message('error', false, $e->getMessage());
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function show($id)
    {
        if ($this->request->isAJAX()) {
            try {
                $service = new MasterBundlingServices($this->session->get('sys_user_id'), $this->session->get('md_employee_id'));

                $data = $service->show($id);

                //* Formating for Web
                $header = $data['header'][0];

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($header->name);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setField(['md_doctype_id']);
                $fieldHeader->setList($data['header']);

                $result = [
                    'header'    => $this->field->store($fieldHeader),
                    'rules'  => $data['rules']
                ];

                $response = message('success', true, $result);
            } catch (\App\Exceptions\BaseException $e) {
                $response = message('error', false, $e->getMessage());
            } catch (\Exception $e) {
                log_message('error', 'MasterBundling [show] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function destroy($id)
    {
        if ($this->request->isAJAX()) {
            try {
                $result = $this->delete($id);
                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function processIt()
    {
        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            try {
                if (empty($post['docaction']))
                    throw new ValidationException("Silahkan pilih tindakan terlebih dahulu");

                $service = new MasterBundlingServices($this->session->get('sys_user_id'), $this->session->get('md_employee_id'));
                $message = $service->proccessTransaction($post['id'], $post['docaction'], $post['subtype']);

                $response = message('success', true, $message);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function getSeqCode()
    {
        if ($this->request->isAJAX()) {
            try {
                $number = $this->model->countAll();
                $number += 1;

                while (strlen($number) < 5) {
                    $number = "0" . $number;
                }

                $docno = "PK" . $number;

                $response = message('success', true, $docno);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function getList()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->getVar();

            $response = [];

            try {
                $mEmployee = new M_Employee($this->request);

                $md_employee_id = $post["md_employee_id"] ?? null;
                $md_division_id = $post["md_division_id"] ?? null;
                $md_branch_id = $post["md_branch_id"] ?? null;

                // $today = date('Y-m-d');

                // $whereClause = "docstatus = '{$this->DOCSTATUS_Completed}'";
                // $whereClause .= " AND '{$today}' <= enddate";

                $whereClause = "1=1";

                if ($md_employee_id) {
                    $employee = $mEmployee->getDetail($md_employee_id)->getResult();

                    $branchIdsStr = implode(",", array_column($employee, 'md_branch_id'));
                    $divisionIdsStr = implode(", ", array_column($employee, 'md_division_id'));

                    $whereClause .= " AND ((mbb.md_branch_id IN ({$branchIdsStr}) AND mbd.md_division_id IN ({$divisionIdsStr})) OR (mbb.md_branch_id IS NULL AND mbd.md_division_id IN ({$divisionIdsStr})) OR (mbb.md_branch_id IN ({$branchIdsStr}) AND mbd.md_division_id IS NULL) OR (mbb.md_branch_id IS NULL AND mbd.md_division_id IS NULL))";
                }

                if ($md_branch_id && $md_division_id) {
                    $whereClause .= " AND ((mbb.md_branch_id = {$md_branch_id} AND mbd.md_division_id = {$md_division_id}) OR (mbb.md_branch_id IS NULL AND mbd.md_division_id = {$md_division_id}) OR (mbb.md_branch_id = {$md_branch_id} AND mbd.md_division_id IS NULL) OR (mbb.md_branch_id IS NULL AND mbd.md_division_id IS NULL))";
                }

                $builder = $this->model;
                $builder->select("trx_master_bundling.trx_master_bundling_id, trx_master_bundling.name")->distinct();
                $builder->join("trx_master_bundling_branch mbb", "mbb.trx_master_bundling_id = trx_master_bundling.trx_master_bundling_id", "left");
                $builder->join("trx_master_bundling_division mbd", "mbd.trx_master_bundling_id = trx_master_bundling.trx_master_bundling_id", "left");
                $builder->where($whereClause);

                if (isset($post['search'])) {
                    $builder->like('name', $post['search']);
                }

                $builder->orderBy('name', 'ASC');
                $list = $builder->findAll();

                foreach ($list as $key => $row) :
                    $response[$key]['id'] = $row->{$this->model->primaryKey};
                    $response[$key]['text'] = $row->name;
                endforeach;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function getPacketDetail()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->getVar();

            try {
                $ID = $post['trx_master_bundling_id'] ?? null;
                $masterBundling = $this->model->where($this->model->primaryKey, $ID)->first();

                $response = message('success', true, [
                    // 'startdate' => $masterBundling->startdate,
                    // 'enddate' => $masterBundling->enddate,
                    'period_type' => $masterBundling->period_type,
                    'compensation_type' => $masterBundling->compensation_type,
                    'project_value' => $masterBundling->project_value,
                    'minimum_hour' => $masterBundling->minimum_hour,
                    'maximum_hour' => $masterBundling->maximum_hour
                ]);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
