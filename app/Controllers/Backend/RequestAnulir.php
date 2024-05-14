<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use Config\Services;

class RequestAnulir extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Absent($this->request);
        $this->modelDetail = new M_AbsentDetail($this->request);
        $this->entity = new \App\Entities\Absent();
    }

    public function index()
    {
        return $this->template->render('transaction/requestanulir/v_requestanulir');
    }

    public function create()
    {
        $cWfs = new WScenario();

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            try {

                // $this->entity->fill($post);

                if (!$this->validation->run($post, 'anulir')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    // $row->getSubmissionType() = $post['row->getSubmissionType()'];
                    $documentNo = $post['documentno'];

                    $row = $this->model->where([
                        'documentno'        => $documentNo,
                        'docstatus'         => $this->DOCSTATUS_Completed
                    ])->first();

                    if (is_null($row)) {
                        $response = message('success', false, 'Doc No Pengajuan tidak bisa diproses');
                    } else {
                        if ($row->getSubmissionType() == $this->model->Pengajuan_Sakit) {
                            $menu = 'sakit';
                        } else if ($row->getSubmissionType() == $this->model->Pengajuan_Alpa) {
                            $menu = 'alpa';
                        } else if ($row->getSubmissionType() == $this->model->Pengajuan_Cuti) {
                            $menu = 'cuti';
                        } else if ($row->getSubmissionType() == $this->model->Pengajuan_Ijin) {
                            $menu = 'ijin';
                        } else if ($row->getSubmissionType() == $this->model->Pengajuan_Ijin_Resmi) {
                            $menu = 'ijin-resmi';
                        } else if ($row->getSubmissionType() == $this->model->Pengajuan_Ijin_Keluar_Kantor) {
                            $menu = 'ijin-keluar-kantor';
                        } else if ($row->getSubmissionType() == $this->model->Pengajuan_Tugas_Kantor) {
                            $menu = 'tugas-kantor';
                        } else if ($row->getSubmissionType() == $this->model->Pengajuan_Tugas_Kantor_setengah_Hari) {
                            $menu = 'tugas-kantor-fka';
                        } else if ($row->getSubmissionType() == $this->model->Pengajuan_Lupa_Absen_Masuk) {
                            $menu = 'lupa-absen-masuk';
                        } else if ($row->getSubmissionType() == $this->model->Pengajuan_Lupa_Absen_Pulang) {
                            $menu = 'lupa-absen-pulang';
                        } else if ($row->getSubmissionType() == $this->model->Pengajuan_Datang_Terlambat) {
                            $menu = 'datang-terlambat';
                        } else if ($row->getSubmissionType() == $this->model->Pengajuan_Pulang_Cepat) {
                            $menu = 'pulang-cepat';
                        }

                        $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $row->getAbsentId(), $this->DOCSTATUS_Requested, $menu, $this->session);
                        $response = message('success', true, 'Permintaan anulir berhasil diproses');
                    }
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }
            // return $this->response->setJSON($response);
            return json_encode($response);
        }
    }
}
