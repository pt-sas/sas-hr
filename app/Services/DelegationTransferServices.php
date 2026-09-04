<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Models\M_DelegationTransfer;
use App\Models\M_DelegationTransferDetail;
use App\Models\M_DocumentType;
use App\Services\PeriodServices;

class DelegationTransferServices extends BaseServices
{
    protected $baseSubType;

    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        $this->userID = $userID;
        $this->employeeID = $employeeID;

        $this->model = new M_DelegationTransfer($this->request);
        $this->modelDetail = new M_DelegationTransferDetail($this->request);
        $this->entity = new \App\Entities\DelegationTransfer();
        $this->baseSubType = $this->model->Pengajuan_Transfer_Duta;
    }

    public function proccessTransaction(int $id, string $docaction, int $subTypeTarget = null)
    {
        $WScenarioServices = new WScenarioServices($this->userID, $this->employeeID);
        $periodServices    = new PeriodServices($this->userID, $this->employeeID);

        $mDocType    = new M_DocumentType($this->request);

        $row = $this->model->where([$this->model->primaryKey => $id, 'submissiontype' => $this->baseSubType])->first();

        if (empty($row))
            throw new NotFoundException("Pengajuan tidak ditemukan");

        if ($docaction === $row->getDocStatus())
            throw new ValidationException("Silahkan refresh terlebih dahulu");

        $docType = $mDocType->getDocTypeMenu($row->submissiontype);

        if (empty($docType->sys_submenu_id))
            throw new NotFoundException("Tipe Pengajuan {$docType->name} belum diset acuan menu-nya");

        $periodServices->validatePeriod($row->submissiontype, $row->startdate, $row->enddate);

        if ($docaction === $this->DOCSTATUS_Completed) {
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
