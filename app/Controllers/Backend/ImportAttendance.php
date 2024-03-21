<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Shared_Date;
use App\Models\M_Attendance;
use App\Models\M_Employee;
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

                if (isset($file)) {
                    $excelReader  = new PHPExcel();
                    $fileLocation = $file->getTempName();
                    $excel = PHPExcel_IOFactory::load($fileLocation);

                    // Get Total Data Row with reference data in Column A
                    $Column_A = $excel->getActiveSheet()->getHighestDataRow('A');

                    $row = 2;
                    $jmlhbaris = 0;
                    $jmlhupdate = 0;

                    while ($row <= $Column_A) {
                        $kolomnik = $excel->getActiveSheet()->getCellByColumnAndRow(0, $row)->getValue();
                        $kolommasuk = $excel->getActiveSheet()->getCellByColumnAndRow(2, $row)->getValue();
                        $kolompulang = $excel->getActiveSheet()->getCellByColumnAndRow(3, $row)->getValue();
                        // $Kolomabsent = $excel->getActiveSheet()->getCellByColumnAndRow(4, $row)->getValue();

                        // Check NIK Data Type & Set NIK Variable
                        if (is_numeric($kolomnik) && strlen((string)$kolomnik) == 6) {
                            $nik = $kolomnik;
                        } else {
                            $response = message('error', true, "Nik tidak sesuai pada cell A{$row}");
                            break;
                        }

                        // Check Date Data Type & Set Date Variable
                        if (PHPExcel_Shared_Date::isDateTime($excel->getActiveSheet()->getCellByColumnAndRow(1, $row))) {
                            $dateValue = $excel->getActiveSheet()->getCellByColumnAndRow(1, $row)->getFormattedValue();
                            $date = date('Y-m-d', strtotime($dateValue));

                            if ($date === "1970-01-01" || $date > date('Y-m-d')) {
                                $response = message('error', true, "Tanggal tidak sesuai pada cell B{$row}");
                                break;
                            }
                        } else {
                            $response = message('error', true, "Tanggal tidak sesuai format pada cell B{$row}");
                            break;
                        }

                        $clock_in = $kolommasuk;

                        $clock_out = $kolompulang;

                        // Check if employee absent or Not
                        // if ($Kolomabsent === 'Y' || $Kolomabsent === "N") {
                        //     $absent = $Kolomabsent;
                        // } else {
                        if ($clock_in == null && $clock_out == null) {
                            $absent = 'Y';
                        } else {
                            $absent = 'N';
                        }
                        // }

                        $data[] = ['nik' => $nik, 'date' => $date, 'clock_in' => $clock_in, 'clock_out' => $clock_out, 'absent' => $absent];
                        $row++;
                    }

                    if (($Column_A - 1) == count($data)) {
                        // Process Checking if data need insert or Update Data in Database
                        foreach ($data as $key => $value) {
                            $nik_data = $value['nik'];
                            $date_data = $value['date'];
                            $clock_in_data = $value['clock_in'];
                            $clock_out_data = $value['clock_out'];
                            $absent_data = $value['absent'];

                            // Search if transaction is already exits?
                            $atten = $this->model->where(['nik' => $nik_data, 'date' => $date_data])->find();

                            if (!empty($atten)) {
                                $this->model->save([
                                    'trx_attendance_id' => $atten[0]->trx_attendance_id,
                                    'nik' => $nik_data,
                                    'date' => $date_data,
                                    'clock_in' => $clock_in_data,
                                    'clock_out' => $clock_out_data,
                                    'absent' => $absent_data
                                ]);
                                $jmlhupdate++;
                            } else {
                                $this->model->save([
                                    'nik' => $nik_data,
                                    'date' => $date_data,
                                    'clock_in' => $clock_in_data,
                                    'clock_out' => $clock_out_data,
                                    'absent' => $absent_data
                                ]);
                                $jmlhbaris++;
                            }
                        }
                        $response = message('success', true, "$jmlhbaris Baris Berhasil Import, $jmlhupdate Baris Di Update");
                    }
                } else {
                    $response = message('error', true, 'Mohon pilih file terlebih dahulu');
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
