<?php

namespace App\Services;

use App\Models\M_Employee;
use App\Services\BaseServices;

class UploadServices extends BaseServices
{

    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        //* Set User & Employee Session
        $this->userID = $userID;
        $this->employeeID = $employeeID;
    }

    public function saveImage($file, int $md_employee_id, int $submissionType)
    {
        //* Call Models
        $mEmployee = new M_Employee($this->request);

        $img_name = "";

        $row = $mEmployee->find($md_employee_id);
        $lenPos = strpos($row->getValue(), '-');
        $value = substr_replace($row->getValue(), "", $lenPos);
        $ymd = date('YmdHis');

        if ($file && $file->isValid()) {
            $ext = $file->getClientExtension();
            $img_name = $submissionType . '_' . $value . '_' . $ymd . '.' . $ext;
        }

        $path = $this->PATH_UPLOAD . $this->PATH_Pengajuan . '/';
        uploadFile($file, $path, $img_name);

        return $img_name;
    }
}
