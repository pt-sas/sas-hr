<?php

namespace App\Services;

use App\Models\M_Absent;
use App\Models\M_DocumentType;
use App\Models\M_Employee;
use App\Models\M_WScenario;
use App\Models\M_WScenarioDetail;

class WScenarioServices extends BaseServices
{
    protected $sys_wfscenario_id = 0;

    public function __construct(int $userID)
    {
        parent::__construct();

        $this->userID = $userID;
        $this->model = new M_WScenario($this->request);
        $this->modelDetail = new M_WScenarioDetail($this->request);
        $this->entity = new \App\Entities\WScenario();
    }

    public function setScenario($entity, $model, $modelDetail = null, $trxID, $docStatus, $menu, $modelSubDetail = null, $isSubmission = false)
    {
        //* Call Services
        $WActivityServices = new WActivityServices($this->userID);

        //* Call Models
        $mWfs = new M_WScenario($this->request);
        $mDocType = new M_DocumentType($this->request);
        $mEmployee = new M_Employee($this->request);
        $mAbsent = new M_Absent($this->request);

        $this->model = $model;
        $this->entity = $entity;

        $table = $this->model->table;
        $primaryKey = $this->model->primaryKey;
        $isWfscenario = false;
        $totalDays = null;

        $trx = $this->model->find($trxID);
        $reopen = $trx->isreopen == "Y" ? true : false;

        if (!is_null($modelDetail)) {
            $this->modelDetail = $modelDetail;
            $trxLine = $this->modelDetail->where($primaryKey, $trxID)->findAll();

            if (!is_null($modelSubDetail)) {
                $this->modelSubDetail = $modelSubDetail;
                $lineID = array_column($trxLine, $this->modelDetail->primaryKey);
                $trxSubLine = $this->modelSubDetail->whereIn($this->modelDetail->primaryKey, $lineID)->findAll();
            }
        }

        $docType = $mDocType->find($trx->submissiontype);

        if (!$trx && $docStatus === $this->DOCSTATUS_Completed) {
            $this->entity->setDocStatus($this->DOCSTATUS_Invalid);
            $this->entity->setWfScenarioId(0);
        } else if ($docStatus === $this->DOCSTATUS_Voided) {
            $this->entity->setDocStatus($this->DOCSTATUS_Voided);
        } else if ($trx && $docStatus === $this->DOCSTATUS_Completed) {
            $employee = $mEmployee->find($trx->md_employee_id);

            if ($table === 'trx_absent') {
                if ($trx->submissiontype == $this->model->Pengajuan_Cuti) {
                    $totalDays = count($trxLine);

                    if ($totalDays <= 3)
                        $totalDays = 3; //Set GT sesuai scenario
                    else if ($totalDays > 3 && $totalDays <= 5)
                        $totalDays = 5; //Set GT sesuai scenario
                    else if ($totalDays > 5)
                        $totalDays = 6; //Set GT sesuai scenario
                }

                $this->sys_wfscenario_id = $mWfs->getScenario($menu, null, null, $trx->md_branch_id, $trx->md_division_id, $employee->md_levelling_id, null, $totalDays, $trx->ref_submissiontype);
            } else if ($table === "trx_submission_cancel") {
                if ($trx->ref_submissiontype == 100003) {
                    // TODO : Get Total Days from reference transaction Leave
                    $ref_doc = $mAbsent->find($trx->reference_id);
                    $totalDays = $ref_doc->totaldays;

                    if ($totalDays <= 3)
                        $totalDays = 3; //Set GT sesuai scenario
                    else if ($totalDays > 3 && $totalDays <= 5)
                        $totalDays = 5; //Set GT sesuai scenario
                    else if ($totalDays > 5)
                        $totalDays = 6; //Set GT sesuai scenario

                    $this->sys_wfscenario_id = $mWfs->getScenario($menu, null, null, $trx->md_branch_id, $trx->md_division_id, $employee->md_levelling_id, null, $totalDays, $trx->ref_submissiontype);
                } else {
                    $this->sys_wfscenario_id = $mWfs->getScenario($menu, null, null, $trx->md_branch_id, $trx->md_division_id, $employee->md_levelling_id, null, null, $trx->ref_submissiontype);
                }
            } else if ($table === "trx_adjustment") {
                $totalTKH = null;

                if ($trx->submissiontype == $this->model->Pengajuan_Adj_TKH) {
                    if (abs($trx->adjustment) > 1) {
                        $totalTKH = 2;
                    } else {
                        $totalTKH = 1;
                    }
                }

                $this->sys_wfscenario_id = $mWfs->getScenario($menu, null, null, $trx->md_branch_id, $trx->md_division_id, null, null, $totalTKH, $trx->submissiontype);
            } else if ($table === "trx_proxy_special") {
                $this->sys_wfscenario_id = $mWfs->getScenario($menu, null, null, null, null, null, null, null, $trx->submissiontype);
            } else if ($table === "trx_employee_allocation") {
                if ($trx->submissiontype == 100023 || $trx->submissiontype == 100024) {
                    $this->sys_wfscenario_id = $mWfs->getScenario($menu, null, null, $trx->md_branch_id, $trx->md_division_id, $employee->md_levelling_id, null, null, $trx->submissiontype);
                } else {
                    $this->sys_wfscenario_id = $mWfs->getScenario(
                        $menu,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        $trx->submissiontype
                    );
                }
            } else {
                $this->sys_wfscenario_id = $mWfs->getScenario($menu, null, null, $trx->md_branch_id, $trx->md_division_id, null);
            }

            if ($this->sys_wfscenario_id && !$reopen) {
                $this->entity->setDocStatus($this->DOCSTATUS_Inprogress);
                $this->entity->setWfScenarioId($this->sys_wfscenario_id);
                $this->entity->setIsApproved("");
                $isWfscenario = true;
            } else if ($docType->getIsRealization() === "Y") {
                $this->entity->setDocStatus($this->DOCSTATUS_Inprogress);
                $this->entity->setIsApproved("Y");
            } else {
                $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                $this->entity->setIsApproved("Y");
            }
        } else if ($trx && $docStatus === $this->DOCSTATUS_Requested) {
            if ($table === 'trx_absent') {
                $this->sys_wfscenario_id = $mWfs->getScenario('request-anulir');

                if ($this->sys_wfscenario_id) {
                    $this->entity->setDocStatus($this->DOCSTATUS_Requested);
                    $this->entity->setWfScenarioId($this->sys_wfscenario_id);
                    $isWfscenario = true;
                } else {
                    $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                }
            }
        }

        $this->entity->setUpdatedBy($this->userID);
        $this->entity->{$primaryKey} = $trxID;
        $result = $this->save();

        if ($result && $isWfscenario) {
            $totalDays = $trx->totaldays ?? 0;

            if (($docType->getIsApprovedLine() === "Y" && $totalDays <= 14) && !is_null($modelDetail) && $trxLine) {
                $this->modelDetail = $modelDetail;

                $tableLine = $this->modelDetail->table;
                $primaryKey = $this->modelDetail->primaryKey;

                foreach ($trxLine as $line) {
                    $WActivityServices->setActivity(null, $this->sys_wfscenario_id, $this->getScenarioResponsible($this->sys_wfscenario_id), $this->userID, $this->DOCSTATUS_Suspended, false, null, $table, $trxID, $menu, null, $tableLine, $line->{$primaryKey});
                }
            } else {
                $WActivityServices->setActivity(null, $this->sys_wfscenario_id, $this->getScenarioResponsible($this->sys_wfscenario_id), $this->userID, $this->DOCSTATUS_Suspended, false, null, $table, $trxID, $menu);
            }

            // TODO : Update line status to Waiting Approval
            if ($isSubmission) {
                $data = [];
                $mDetail = !is_null($modelSubDetail) ? $modelSubDetail : $modelDetail;
                $lineData = !is_null($modelSubDetail) ? $trxSubLine : $trxLine;

                foreach ($lineData as $line) {
                    $data = [
                        'isagree' => $this->LINESTATUS_Approval
                    ];

                    $mDetail->update($line->{$mDetail->primaryKey}, $data);
                }
            }
        }

        return $result;
    }

    private function getScenarioResponsible($sys_wfscenario_id)
    {
        $this->modelDetail = new M_WScenarioDetail($this->request);

        $row = $this->modelDetail->where([
            'sys_wfscenario_id'       => $sys_wfscenario_id,
            'isactive'                => 'Y'
        ])->orderBy('lineno', 'ASC')->first();

        return $row->getWfResponsibleId();
    }
}
