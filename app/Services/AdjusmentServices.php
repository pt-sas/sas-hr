<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Models\M_Adjustment;
use App\Models\M_DocumentType;
use App\Services\PeriodServices;

class AdjusmentServices extends BaseServices
{
    protected $baseSubType;

    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        $this->userID = $userID;
        $this->employeeID = $employeeID;

        $this->model = new M_Adjustment($this->request);
        $this->entity = new \App\Entities\Adjustment();
    }

    public function proccessTransaction(int $id, string $docaction, int $subTypeTarget = null)
    {
        $WScenarioServices = new WScenarioServices($this->userID, $this->employeeID);
        $periodServices    = new PeriodServices($this->userID, $this->employeeID);

        $mDocType    = new M_DocumentType($this->request);

        $row = $this->model->where($this->model->primaryKey, $id)->first();

        if (empty($row))
            throw new NotFoundException("Pengajuan tidak ditemukan");

        if ($docaction === $row->getDocStatus())
            throw new ValidationException("Silahkan refresh terlebih dahulu");

        $docType = $mDocType->getDocTypeMenu($row->submissiontype);

        if (empty($docType->sys_submenu_id))
            throw new NotFoundException("Tipe Pengajuan {$docType->name} belum diset acuan menu-nya");

        $date = date('Y-m-d', strtotime($row->date));

        $periodServices->validatePeriod($row->submissiontype, $date, $date);

        if ($docaction === $this->DOCSTATUS_Completed) {
            // TODO : In Progress Document
            $whereClause = "DATE(date) = '{$date}'
                        AND md_employee_id = {$row->md_employee_id}
                        AND submissiontype = {$row->submissiontype}
                        AND docstatus = '{$this->DOCSTATUS_Inprogress}'";
            $trx = $this->model->where($whereClause)->first();

            if ($trx) throw new ValidationException("Ada document berjalan dengan nomor {$trx->documentno}");

            $WScenarioServices->setScenario($this->entity, $this->model, $this->modelDetail, $id, $docaction, $docType->url);

            return 'Pengajuan berhasil Diproses';
        } else if ($docaction === $this->DOCSTATUS_Voided) {
            $this->entity->setDocStatus($this->DOCSTATUS_Voided);
            $this->entity->setAbsentId($id);
            $this->save();
            return 'Pengajuan berhasil Divoid';
        } else {
            throw new BusinessException("Dokumen aksi ini tidak tersedia pada tipe pengajuan ini");
        }
    }
}
