<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Day;
use App\Models\M_Work;
use App\Models\M_WorkDetail;
use App\Models\M_EmpWorkDay;
use Config\Services;

class Work extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Work($this->request);
        $this->modelDetail = new M_WorkDetail($this->request);
        $this->entity = new \App\Entities\Work();
    }

    public function index()
    {
        return $this->template->render('masterdata/work/v_work');
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->findAll();
            $order = $this->model->column_order;
            $sort = $this->model->order;
            $search = $this->model->column_search;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->md_work_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->name;
                $row[] = $value->workhour;
                // $row[] = $value->fulltime;
                $row[] = active($value->isactive);
                $row[] = $this->template->tableButton($ID);
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search),
                'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search),
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

                // if (!$this->validation->run($post, 'work')) {
                //     $response = $this->field->errorValidation($this->model->table, $post);
                // } else {
                $response = $this->save();
                // }
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
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $detail = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($list[0]->name);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($list);

                $result = [
                    'header'   => $this->field->store($fieldHeader),
                    'line'     => $this->tableLine('edit', $detail)
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

    public function tableLine($set = null, $detail = [])
    {
        $mDay = new M_Day($this->request);

        $table = [];

        $fieldDays = new \App\Entities\Table();
        $fieldDays->setName("md_day_id");
        $fieldDays->setType("select");
        $fieldDays->setClass("select2");
        $fieldDays->setIsRequired(true);
        $fieldDays->setField([
            "id"    => "md_day_id",
            "text"  => "name"
        ]);

        $dayList = $mDay->where('isactive', 'Y')->orderBy('value', 'ASC')->findAll();

        $fieldDays->setList($dayList);
        $fieldDays->setLength(150);

        $fieldStartWork = new \App\Entities\Table();
        $fieldStartWork->setName("startwork");
        $fieldStartWork->setType("text");
        $fieldStartWork->setClass("timepicker");
        $fieldStartWork->setIsRequired(true);
        $fieldStartWork->setLength(150);

        $fieldEndWork = new \App\Entities\Table();
        $fieldEndWork->setName("endwork");
        $fieldEndWork->setType("text");
        $fieldEndWork->setClass("timepicker");
        $fieldEndWork->setIsRequired(true);
        $fieldEndWork->setLength(150);

        $fieldBreakIn = new \App\Entities\Table();
        $fieldBreakIn->setName("breakstart");
        $fieldBreakIn->setType("text");
        $fieldBreakIn->setClass("timepicker");
        $fieldBreakIn->setIsRequired(true);
        $fieldBreakIn->setLength(150);

        $fieldBreakOut = new \App\Entities\Table();
        $fieldBreakOut->setName("breakend");
        $fieldBreakOut->setType("text");
        $fieldBreakOut->setClass("timepicker");
        $fieldBreakOut->setIsRequired(true);
        $fieldBreakOut->setLength(150);

        $fieldActive = new \App\Entities\Table();
        $fieldActive->setName("isactive");
        $fieldActive->setType("checkbox");
        $fieldActive->setClass("active");
        $fieldActive->setIsChecked(true);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        //? Create
        if (empty($set)) {
            $table = [
                $this->field->fieldTable($fieldDays),
                $this->field->fieldTable($fieldStartWork),
                $this->field->fieldTable($fieldEndWork),
                $this->field->fieldTable($fieldBreakIn),
                $this->field->fieldTable($fieldBreakOut),
                $this->field->fieldTable($fieldActive),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $fieldDays->setValue($row->md_day_id);
                $fieldStartWork->setValue($row->startwork);
                $fieldEndWork->setValue($row->endwork);
                $fieldBreakIn->setValue($row->breakstart);
                $fieldBreakOut->setValue($row->breakend);
                $fieldActive->setValue($row->isactive);
                $btnDelete->setValue($row->{$this->modelDetail->primaryKey});

                if ($row->isactive === "N") {
                    $fieldDays->setIsReadonly(true);
                    $fieldStartWork->setIsReadonly(true);
                    $fieldEndWork->setIsReadonly(true);
                    $fieldBreakIn->setIsReadonly(true);
                    $fieldBreakOut->setIsReadonly(true);
                    $fieldActive->setIsChecked(false);
                } else {
                    $fieldDays->setIsReadonly(false);
                    $fieldStartWork->setIsReadonly(false);
                    $fieldEndWork->setIsReadonly(false);
                    $fieldBreakIn->setIsReadonly(false);
                    $fieldBreakOut->setIsReadonly(false);
                    $fieldActive->setIsChecked(true);
                }

                $table[] = [
                    $this->field->fieldTable($fieldDays),
                    $this->field->fieldTable($fieldStartWork),
                    $this->field->fieldTable($fieldEndWork),
                    $this->field->fieldTable($fieldBreakIn),
                    $this->field->fieldTable($fieldBreakOut),
                    $this->field->fieldTable($fieldActive),
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }

    public function daysOff($employeeId)
    {
        $mEmpWork = new M_EmpWorkDay($this->request);

        if ($this->request->isAJAX()) {
            try {
                $today = date('Y-m-d');

                $work = $mEmpWork->where([
                    'md_employee_id'    => $employeeId,
                    'validfrom <='      => $today
                ])->orderBy('validfrom', 'ASC')->first();

                if ($work) {
                    //TODO : Get Work Detail
                    $whereClause = "md_work_detail.isactive = 'Y'";
                    $whereClause .= " AND md_employee_work.md_employee_id = $employeeId";
                    $whereClause .= " AND md_work.md_work_id = $work->md_work_id";
                    $workDetail = $this->modelDetail->getWorkDetail($whereClause)->getResult();

                    $response = getDaysOff($workDetail);
                } else {
                    $response = [0, 6];
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
