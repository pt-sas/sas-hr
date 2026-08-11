<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Division;
use App\Models\M_MasterBundling;
use App\Models\M_Year;
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
        $data = [
            'today'         => date('d-M-Y'),
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
                $row[] = $value->division;
                $row[] = $value->bundling_type;
                $row[] = format_dmy($value->startdate, '-') . ' s/d ' . format_dmy($value->enddate, '-');
                $row[] = $value->description;
                $row[] = $value->createdby;
                $row[] = $this->template->tableButton($ID, 'DR', $this->BTN_Print);
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
                $this->entity->fill($post);

                $response = $this->save();
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function show($id)
    {
        $mDivision = new M_Division($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();

                $rowDiv = $mDivision->where($mDivision->primaryKey, $list[0]->md_division_id)->first();

                $list = $this->field->setDataSelect($mDivision->table, $list, $mDivision->primaryKey, $rowDiv->getDivisionId(), $rowDiv->getName());

                $title = $list[0]->name;
                $list[0]->startdate = format_dmy($list[0]->startdate, "-");
                $list[0]->enddate = format_dmy($list[0]->enddate, "-");

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($list);

                $result = [
                    'header'    => $this->field->store($fieldHeader),
                ];

                $response = message('success', true, $result);
            } catch (\Exception $e) {
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
            $cWfs = new WScenario();
            $mYear = new M_Year($this->request);

            $post = $this->request->getVar();

            $_ID = $post['id'];
            $_DocAction = $post['docaction'];
            $row = $this->model->find($_ID);
            $menu = $this->request->uri->getSegment(2);

            try {
                if (!empty($_DocAction)) {
                    // TODO : Checking Period
                    $dateRange = getDatesFromRange($row->startdate, $row->enddate, [], 'Y-m-d', "all");
                    foreach ($dateRange as $date) {
                        $period = $mYear->getPeriodStatus($date, $row->submissiontype)->getRow();

                        if (empty($period) || $period->period_status == $this->PERIOD_CLOSED) {
                            break;
                        }
                    }

                    if (empty($period)) {
                        $response = message('error', true, "Periode belum dibuat");
                    } else if ($period->period_status == $this->PERIOD_CLOSED) {
                        $response = message('error', true, "Periode {$period->name} ditutup");
                    } else if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session);
                        $response = message('success', true, true);
                    } else if ($_DocAction === $this->DOCSTATUS_Voided) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Voided);
                        $response = $this->save();
                    } else if ($_DocAction === $this->DOCSTATUS_Reopen) {
                        $response = message('error', true, 'Dokumen ini tidak bisa direopen.');
                    } else {
                        $this->entity->setDocStatus($_DocAction);
                        $response = $this->save();
                    }
                } else {
                    $response = message('error', true, 'Silahkan pilih tindakan terlebih dahulu.');
                }
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
                if (isset($post['search'])) {
                    $list = $this->model->like('name', $post['search'])
                        ->orderBy('name', 'ASC')
                        ->findAll();
                } else {
                    $list = $this->model->orderBy('name', 'ASC')
                        ->findAll();
                }

                foreach ($list as $key => $row) :
                    $response[$key]['id'] = $row->name;
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
                $packet = $this->model->where('name', $post['name'])->first();

                $response['nominal_type'] = $packet->nominal_type;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
