<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use PHPExcel;
use PHPExcel_IOFactory;
use App\Models\M_Attendance;
use Config\Services;

class ImportAttendance extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Attendance($this->request);
        $this->entity = new \App\Entities\Attendance();
    }

    public function index()
    {
        $date = format_dmy(date('Y-m-d'), "-");

        $data = [
            'date_range' => $date . ' - ' . $date
        ];
        return $this->template->render('backend/configuration/import/import_attendance', $data);
    }

    public function import()
    {
        if ($this->request->isAJAX()) {
            try {
                $file = $this->request->getFile('file');
                $excelReader  = new PHPExcel();
                $fileLocation = $file->getTempName();
                $objPHPExcel = PHPExcel_IOFactory::load($fileLocation);
                $sheet    = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
                $jmlhbaris = 0;

                // Check Panjang Data Kolom
                foreach ($sheet as $data) {
                    $dataLength = count($data);
                }

                if ($dataLength === 5) {
                    foreach ($sheet as $idx => $data) {
                        if ($idx == 1) {
                            continue;
                        }
                        $nik = $data['A'];
                        $date = date('Y-m-d', strtotime($data['B']));
                        $clock_in = $data['C'];
                        $clock_out = $data['D'];
                        $absent = $data['E'];

                        // insert data
                        $this->model->insert([
                            'nik' => $nik,
                            'date' => $date,
                            'clock_in' => $clock_in,
                            'clock_out' => $clock_out,
                            'absent' => $absent
                        ]);
                        $jmlhbaris++;
                    }
                    $response = message('success', true, $jmlhbaris . ' Baris Berhasil Import');
                } else {
                    $response = message('error', true, 'Isi file tidak sesuai template');
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
