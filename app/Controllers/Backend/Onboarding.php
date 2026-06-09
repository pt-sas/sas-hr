<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Interview;
use App\Models\M_InterviewDetail;
use Config\Services;

class Onboarding extends BaseController
{
    protected $baseSubType;

    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Interview($this->request);
        $this->modelDetail = new M_InterviewDetail($this->request);
        $this->entity = new \App\Entities\Interview();
        $this->baseSubType = $this->model->Pengajuan_Onboarding;
    }

    public function index()
    {
        $data = ['today'     => date('d-M-Y')];

        return $this->template->render('transaction/exitinterview/v_exit_interview', $data);
    }
}
