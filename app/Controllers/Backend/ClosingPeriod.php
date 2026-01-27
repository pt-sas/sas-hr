<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_Adjustment;
use App\Models\M_DelegationTransfer;
use App\Models\M_DocumentType;
use App\Models\M_MedicalCertificate;
use App\Models\M_Memo;
use App\Models\M_Overtime;
use App\Models\M_Period;
use App\Models\M_PeriodControl;
use App\Models\M_ProxySpecial;
use App\Models\M_Reference;
use App\Models\M_SubmissionCancelDetail;
use App\Models\M_Year;
use Config\App;
use Config\Services;

class ClosingPeriod extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Year($this->request);
        $this->modelDetail = new M_Period($this->request);
        $this->modelSubDetail = new M_PeriodControl($this->request);
        $this->entity = new \App\Entities\Year();
    }

    public function index()
    {
        return $this->template->render('process/closingperiod/v_closing_period');
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = "*";
            $order = $this->model->column_order;
            $sort = $this->model->order;
            $search = $this->model->column_search;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->{$this->model->primaryKey};

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->year;
                $row[] = $value->description;
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

                if (!$this->validation->run($post, 'closing_period')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $response = $this->save();

                    if ($this->isNew())
                        $response[0]['primarykey'] = $this->insertID;
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }
            return $this->response->setJSON($response);
        }
    }

    public function show($id)
    {
        if ($this->request->isAJAX()) {
            $mPeriod = new M_Period($this->request);
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $detail = $mPeriod->where($this->model->primaryKey, $id)->findAll();

                $title = 'Closing Period';

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
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
        $table = [];

        if ((!empty($set) && $set == 'period control') && count($detail) > 0) {
            $mReference = new M_Reference($this->request);
            $mDocType = new M_DocumentType($this->request);

            $fieldStatus = new \App\Entities\Table();
            $fieldStatus->setName("period_status");
            $fieldStatus->setIsRequired(true);
            $fieldStatus->setType("select");
            $fieldStatus->setClass("select2");
            $fieldStatus->setField([
                'id'    => 'value',
                'text'  => 'name'
            ]);
            $fieldStatus->setLength(200);

            $formatValList = $mReference->findBy([
                'sys_reference.name'              => 'PeriodStatus',
                'sys_reference.isactive'          => 'Y',
                'sys_ref_detail.isactive'         => 'Y',
            ], null, [
                'field'     => 'sys_ref_detail.name',
                'option'    => 'ASC'
            ])->getResult();

            $fieldStatus->setList($formatValList);
            $allDocType = array_column($mDocType->findAll(), null, 'md_doctype_id');
            $number = 1;

            foreach ($detail as $row) :
                if (!isset($allDocType[$row->md_doctype_id])) continue;
                $fieldStatus->setValue($row->period_status);
                $fieldStatus->setAttribute(['data-id' => $row->md_period_control_id]);

                $table[] = [
                    $row->md_period_control_id,
                    $number,
                    $allDocType[$row->md_doctype_id]->name,
                    $this->field->fieldTable($fieldStatus)
                ];
                $number++;

            endforeach;
        } else if (!empty($set) && count($detail) > 0) {
            $btnPeriodControl =  new \App\Entities\Table();
            $btnPeriodControl->setName($this->modelDetail->primaryKey);
            $btnPeriodControl->setType("button");
            $btnPeriodControl->setClass("update-period");

            foreach ($detail as $row) :
                $btnPeriodControl->setValue($row->{$this->modelDetail->primaryKey});
                $table[] = [
                    $row->name,
                    format_dmy($row->startdate, '-'),
                    format_dmy($row->enddate, '-'),
                    $this->field->fieldTable($btnPeriodControl)
                ];
            endforeach;
        }

        return json_encode($table);
    }

    public function generatePeriod()
    {
        if ($this->request->isAJAX()) {
            $mPeriod = new M_Period($this->request);
            $mPeriodControl = new M_PeriodControl($this->request);
            $mDocType = new M_DocumentType($this->request);
            $post = $this->request->getPost();
            $ID = $post['id'];
            $userID = $this->session->get('sys_user_id');

            try {
                if (!$this->validation->run($post, 'create_period')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else if (empty($ID)) {
                    $response = message('success', false, "Mohon simpan lebih dahulu");
                } else {
                    $period = $mPeriod->where('md_year_id', $ID)->findAll();
                    $mPeriod->db->transBegin();
                    $startPeriod = date('Y-m-d', strtotime($post['startdate']));
                    $periodCycle = 12;
                    $yearHeader = $this->model->find($ID)->year;
                    $docType = $mDocType->findAll();

                    $day = date('d', strtotime($startPeriod));
                    $lastDay = date('t', strtotime($startPeriod));

                    if ($day == '01') {
                        $dateOfMonth = 'first';
                    } else if ($day == $lastDay) {
                        $dateOfMonth = 'last';
                    } else {
                        $dateOfMonth = 'middle';
                    }

                    for ($i = 1; $i <= $periodCycle; $i++) {
                        $periodName = "{$yearHeader}-{$i}";

                        $base = date('Y-m-01', strtotime($startPeriod));
                        $nextMonth = date('Y-m-01', strtotime($base . '+1 month'));

                        switch ($dateOfMonth) {
                            case 'first':
                                $endPeriod   = date('Y-m-d', strtotime("$nextMonth -1 day"));
                                break;

                            case 'last':
                                $endPeriod   = date('Y-m-t', strtotime($nextMonth));
                                $endPeriod   = date('Y-m-d', strtotime("$endPeriod -1 day"));
                                break;

                            default:
                                $endDay   = date('d', strtotime("$startPeriod -1 day"));

                                $endPeriod   = date("Y-m-$endDay",  strtotime($nextMonth));
                                break;
                        }

                        // TODO : Check is Period are exists
                        $whereClause = "(startdate <= '{$startPeriod}' AND enddate >= '{$startPeriod}')
                                        OR (startdate <= '{$endPeriod}' AND enddate >= '{$endPeriod}')";
                        $isExistPeriod = $mPeriod->where($whereClause)->first();

                        if ($isExistPeriod) {
                            $response = message('success', false, "Periode bentrok dengan periode {$isExistPeriod->name}");
                            $mPeriod->db->transRollback();
                            break;
                        }

                        $pEntity = new \App\Entities\Period();
                        $pEntity->md_year_id = $ID;
                        $pEntity->periodno = $i;
                        $pEntity->name = $periodName;
                        $pEntity->startdate = $startPeriod;
                        $pEntity->enddate = $endPeriod;
                        $pEntity->created_by = $userID;
                        $pEntity->updated_by = $userID;

                        if ($mPeriod->save($pEntity)) {
                            $insertID = $mPeriod->getInsertID();
                            $dataPeriodControl = array_map(function ($v) use ($insertID, $userID) {
                                return [
                                    'created_by'    => $userID,
                                    'updated_by'    => $userID,
                                    'md_period_id'  => $insertID,
                                    'md_doctype_id' => $v->md_doctype_id,
                                    'period_status' => 'C'
                                ];
                            }, $docType);

                            $mPeriodControl->builder->insertBatch($dataPeriodControl);
                        }

                        switch ($dateOfMonth) {
                            case 'first':
                                $startPeriod = $nextMonth;
                                break;

                            case 'last':
                                $startPeriod = date('Y-m-t', strtotime($nextMonth));
                                break;

                            default:
                                $startDay = date('d', strtotime($startPeriod));
                                $endDay   = date('d', strtotime($endPeriod));

                                $startPeriod = date("Y-m-$startDay", strtotime($nextMonth));
                                break;
                        }

                        if ($i == $periodCycle) {
                            if (!empty($period)) {
                                foreach ($period as $val) {
                                    $mPeriodControl->where('md_period_id', $val->md_period_id)->delete();
                                    $mPeriod->delete($val->md_period_id);
                                }
                            }

                            $mPeriod->db->transCommit();
                            $response = message('success', true, 'Period Berhasil dibuat');
                        }
                    }
                }
            } catch (\Exception $e) {
                $mPeriod->db->transRollback();
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function refreshTableLine()
    {
        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            $ID = $post['id'];
            $detail = $this->modelDetail->where('md_year_id', $ID)->findAll();

            $result = $this->tableLine('edit', $detail);

            return $this->response->setJSON($result);
        }
    }

    public function showPeriodControl()
    {

        if ($this->request->isAjax()) {
            $get = $this->request->getGet();

            $id = $get['foreignkey'];
            try {
                $list = $this->modelDetail->where($this->modelDetail->primaryKey, $id)->findAll();
                $detail = $this->modelSubDetail->where($this->modelDetail->primaryKey, $id)->findAll();

                $result = [
                    'header'   => $list[0]->name,
                    'line'     => $this->tableLine('period control', $detail)
                ];

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function processPeriod()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->getPost();

            try {
                $mAbsent = new M_Absent($this->request);
                $mOvertime = new M_Overtime($this->request);
                $mSubmissionCancelDetail = new M_SubmissionCancelDetail($this->request);
                $mMedicalCertificate = new M_MedicalCertificate($this->request);
                $mMemo = new M_Memo($this->request);
                $mDelegationTransfer = new M_DelegationTransfer($this->request);
                $mAdjustment = new M_Adjustment($this->request);
                $mProxySpecial = new M_ProxySpecial($this->request);

                $md_period_id = $post['md_period_id'];
                $table = json_decode($post['table']);

                $period = $this->modelDetail->where('md_period_id', $md_period_id)->first();
                $allPeriodControl = array_column($this->modelSubDetail->where('md_period_id', $md_period_id)->findAll(), null, 'md_period_control_id');

                $startDate = date('Y-m-d', strtotime($period->startdate));
                $endDate = date('Y-m-d', strtotime($period->enddate));

                $data = [];

                foreach ($table as $row) {
                    $md_period_control_id = $row->id;

                    // TODO : If period control not exists, then continue
                    if (!isset($allPeriodControl[$md_period_control_id])) continue;
                    $periodControl = $allPeriodControl[$md_period_control_id];

                    // TODO : If new value same with old value, then continue
                    if ($periodControl->period_status == $row->period_status) continue;
                    $docType = $periodControl->md_doctype_id;
                    $pendingTrx = null;

                    // TODO : If Status Period is Closed, then do checking pending transaction
                    if ($row->period_status == 'C') {
                        if (in_array($docType, array_merge($this->Form_Satu_Hari, $this->Form_Setengah_Hari))) {
                            // Block Attendance Submission
                            $whereClause = " trx_absent.submissiontype = {$docType}
                                             AND ((DATE(trx_absent_detail.date) BETWEEN '{$startDate}' AND '{$endDate}'
                                             AND trx_absent.docstatus = '{$this->DOCSTATUS_Inprogress}'
                                             AND trx_absent_detail.isagree IN ('{$this->LINESTATUS_Approval}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Realisasi_HRD}'))
                                             OR (trx_absent.docstatus = '{$this->DOCSTATUS_Drafted}'
                                                 AND (DATE(trx_absent.startdate) BETWEEN '{$startDate}' AND '{$endDate}' 
                                                 OR DATE(trx_absent.enddate) BETWEEN '{$startDate}' AND '{$endDate}')))";

                            $pendingTrx = $mAbsent->join('trx_absent_detail', 'trx_absent.trx_absent_id = trx_absent_detail.trx_absent_id')->where($whereClause)->first();
                        } else if ($docType == 100014) {
                            // Block Overtime Submission
                            $whereClause = "(DATE(trx_overtime_detail.startdate) BETWEEN '{$startDate}' AND '{$endDate}'
                                            AND trx_overtime.docstatus = '{$this->DOCSTATUS_Inprogress}'
                                            AND trx_overtime_detail.isagree IN ('{$this->LINESTATUS_Approval}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Realisasi_HRD}'))
                                            OR (DATE(trx_overtime.startdate) BETWEEN '{$startDate}' AND '{$endDate}' 
                                                AND trx_overtime.docstatus = '{$this->DOCSTATUS_Drafted}')";

                            $pendingTrx = $mOvertime->getOvertimeDetail($whereClause)->getRow();
                        } else if ($docType == 100018) {
                            // Block SubmissionCancel
                            $whereClause = "DATE(trx_submission_cancel_detail.date) BETWEEN '{$startDate}' AND '{$endDate}'
                                            AND trx_submission_cancel.docstatus IN ('{$this->DOCSTATUS_Inprogress}', '{$this->DOCSTATUS_Drafted}')
                                            AND trx_submission_cancel_detail.isagree IN ('{$this->LINESTATUS_Approval}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Realisasi_HRD}', '')";

                            $pendingTrx = $mSubmissionCancelDetail->getDetail(null, $whereClause)->getRow();
                        } else if ($docType == 100026) {
                            // Block Medical Certificate 
                            $whereClause = "DATE(date) BETWEEN '{$startDate}' AND '{$endDate}'
                                            AND docstatus IN ('{$this->DOCSTATUS_Inprogress}', '{$this->DOCSTATUS_Drafted}')";

                            $pendingTrx = $mMedicalCertificate->where($whereClause)->first();
                        } else if ($docType == 100015) {
                            // Block Memo SDM
                            $whereClause = "DATE(memodate) BETWEEN '{$startDate}' AND '{$endDate}'
                                            AND docstatus IN ('{$this->DOCSTATUS_Inprogress}', '{$this->DOCSTATUS_Drafted}')";

                            $pendingTrx = $mMemo->where($whereClause)->first();
                        } else if ($docType == 100027) {
                            // Block Transfer Duta
                            $whereClause = "(DATE(startdate) BETWEEN '{$startDate}' AND '{$endDate}' OR DATE(enddate) BETWEEN '{$startDate}' AND '{$endDate}')
                                            AND docstatus IN ('{$this->DOCSTATUS_Inprogress}', '{$this->DOCSTATUS_Drafted}')";

                            $pendingTrx = $mDelegationTransfer->where($whereClause)->first();
                        } else if ($docType == 100029 || $docType == 100030) {
                            // Block Adjusment
                            $whereClause = "DATE(date) BETWEEN '{$startDate}' AND '{$endDate}'
                                            AND submissiontype = {$docType}
                                            AND docstatus IN ('{$this->DOCSTATUS_Inprogress}', '{$this->DOCSTATUS_Drafted}')";

                            $pendingTrx = $mAdjustment->where($whereClause)->first();
                        } else if ($docType == 100025) {
                            // Block Proxy Khusus
                            $whereClause = "(DATE(startdate) BETWEEN '{$startDate}' AND '{$endDate}'
                                            OR DATE(enddate) BETWEEN '{$startDate}' AND '{$endDate}')
                                            AND docstatus IN ('{$this->DOCSTATUS_Inprogress}', '{$this->DOCSTATUS_Drafted}')";

                            $pendingTrx = $mProxySpecial->where($whereClause)->first();
                        }

                        if ($pendingTrx) break;
                    }

                    $data[] = [$this->modelSubDetail->primaryKey => $md_period_control_id, 'period_status' => $row->period_status];
                }

                if (!empty($pendingTrx)) {
                    $response = message('error', true, "Dokumen {$pendingTrx->documentno} masih In Progress atau Draft");
                } else if (!empty($data)) {
                    $this->modelSubDetail->builder->updateBatch($data, $this->modelSubDetail->primaryKey);
                    $response = message('success', true, "Period berhasil diupdate");
                } else {
                    $response = message('error', true, "Tidak ada data yang diupdate");
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
                    $list = $this->model->where('isactive', 'Y')
                        ->like('year', $post['search'])
                        ->orderBy('year', 'ASC')
                        ->findAll();
                } else {
                    $list = $this->model->where('isactive', 'Y')
                        ->orderBy('year', 'ASC')
                        ->findAll();
                }

                foreach ($list as $key => $row) :
                    $response[$key]['id'] = $row->md_year_id;
                    $response[$key]['text'] = $row->year;
                endforeach;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
