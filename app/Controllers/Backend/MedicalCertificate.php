<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use Config\Services;
use App\Models\M_AccessMenu;
use App\Models\M_Employee;
use App\Models\M_MedicalCertificate;
use App\Models\M_Year;

class MedicalCertificate extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_MedicalCertificate($this->request);
        $this->entity = new \App\Entities\MedicalCertificate();
    }

    public function index()
    {
        $data = [
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/medicalcertificate/v_medical_certificate', $data);
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

            $where['md_employee.md_employee_id'] = [
                'value'     => $this->access->getEmployeeData(false, true)
            ];

            $where['trx_medical_certificate.submissiontype'] = $this->model->Pengajuan_Surat_Keterangan_Sakit;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_medical_certificate_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = docStatus($value->docstatus);
                $row[] = $value->reference_doc;
                $row[] = $value->employee;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->date, '-');
                $row[] = !is_null($value->approveddate) ? format_dmy($value->approveddate, '-') : "";
                $row[] = $value->reason;
                $row[] = $value->createdby;
                $row[] = $this->template->tableButton($ID, $value->docstatus);
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search, $join, $where),
                'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join, $where),
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $mYear = new M_Year($this->request);
            $post = $this->request->getVar();

            try {
                if (!$this->validation->run($post, 'medical_certificate')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $post["submissiontype"] = $this->model->Pengajuan_Surat_Keterangan_Sakit;
                    $post["necessary"] = 'KS';
                    $this->entity->fill($post);

                    $trx = $this->model->where('trx_absent_id',  $post['trx_absent_id'])->whereIn('docstatus', ['CO', 'IP'])->first();

                    // TODO : Checking Period
                    $period = $mYear->getPeriodStatus(date('Y-m-d', strtotime($post['date'])), $post['submissiontype'])->getRow();

                    if (empty($period)) {
                        $response = message('success', false, "Periode belum dibuat");
                    } else if ($period->period_status == $this->PERIOD_CLOSED) {
                        $response = message('success', false, "Periode {$period->name} ditutup");
                    } else if ($trx) {
                        $response = message('error', true, 'Sudah ada pengajuan lain untuk pengajuan Sakit ini');
                    } else {
                        if ($this->isNew()) {
                            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                            $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Surat_Keterangan_Sakit, $post);
                            $this->entity->setDocumentNo($docNo);
                        }

                        $response = $this->save();
                    }
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function show($id)
    {
        $mEmployee = new M_Employee($this->request);
        $mAbsent = new M_Absent($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();
                $rowSickLeave = $mAbsent->where($mAbsent->primaryKey, $list[0]->getAbsentId())->first();

                $list = $this->field->setDataSelect($mAbsent->table, $list, 'trx_absent_id', $rowSickLeave->getAbsentId(), $rowSickLeave->getDocumentNo() . ' - ' . $rowEmp->getFullName());

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($list);

                $result = [
                    'header'    => $this->field->store($fieldHeader)
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
        $cWfs = new WScenario();
        $mYear = new M_Year($this->request);

        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            $_ID = $post['id'];
            $_DocAction = $post['docaction'];

            $row = $this->model->find($_ID);
            $menu = $this->request->uri->getSegment(2);

            try {
                if (!empty($_DocAction)) {
                    // TODO : Checking Period
                    $period = $mYear->getPeriodStatus(date('Y-m-d', strtotime($row->date)), $row->submissiontype)->getRow();

                    if (empty($period)) {
                        $response = message('error', true, "Periode belum dibuat");
                    } else if ($period->period_status == $this->PERIOD_CLOSED) {
                        $response = message('error', true, "Periode {$period->name} ditutup");
                    } else if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        $trx = $this->model->where('trx_absent_id',  $row->trx_absent_id)->whereIn('docstatus', ['CO', 'IP'])->first();
                        if ($trx) {
                            $response = message('error', true, "Sudah ada pengajuan lain dengan nomor : {$trx->documentno}");
                        } else {
                            $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session);
                            $response = message('success', true, true);
                        }
                    } else if ($_DocAction === $this->DOCSTATUS_Voided) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Voided);
                        $response = $this->save();
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
}
