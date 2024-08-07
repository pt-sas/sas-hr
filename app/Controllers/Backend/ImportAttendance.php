<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AllowanceAtt;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Shared_Date;
use App\Models\M_Attendance;
use App\Models\M_Employee;
use App\Models\M_Rule;
use App\Models\M_RuleDetail;
use App\Models\M_WorkDetail;
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
        $mEmployee = new M_Employee($this->request);

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

                    /** 
                     * This Section is for validating data before inserting
                     */
                    while ($row <= $Column_A) {
                        $kolomnik = $excel->getActiveSheet()->getCellByColumnAndRow(0, $row)->getValue();
                        $kolommasuk = $excel->getActiveSheet()->getCellByColumnAndRow(2, $row)->getValue();
                        $kolompulang = $excel->getActiveSheet()->getCellByColumnAndRow(3, $row)->getValue();

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

                            if ($date === "1970-01-01") {
                                $response = message('error', true, "Tanggal tidak sesuai pada cell B{$row}");
                                break;
                            }
                        } else {
                            $response = message('error', true, "Tanggal tidak sesuai format pada cell B{$row}");
                            break;
                        }

                        $clock_in = $kolommasuk;
                        $clock_out = $kolompulang;

                        $employee = $mEmployee->where('nik', $kolomnik)->first();

                        if (is_null($employee)) {
                            $response = message('error', true, "Master karyawan tidak ditemukan dengan nik {$nik} pada baris A{$row}");
                            break;
                        }

                        // Check if employee absent or Not
                        if ($clock_in == null && $clock_out == null) {
                            $absent = 'N';
                        } else {
                            $absent = 'Y';
                        }

                        $data[] = ['nik' => $nik, 'date' => $date, 'clock_in' => $clock_in, 'clock_out' => $clock_out, 'absent' => $absent, 'md_employee_id' => $employee->md_employee_id];
                        $row++;
                    }

                    /**
                     * This section is process for inserting or updating data to Database
                     */
                    if (($Column_A - 1) == count($data)) {
                        // Process Checking if data need insert or Update Data in Database
                        foreach ($data as $value) {

                            $nik_data = $value['nik'];
                            $date_data = $value['date'];
                            $clock_in_data = $value['clock_in'];
                            $clock_out_data = $value['clock_out'];
                            $absent_data = $value['absent'];
                            $employee_id = $value['md_employee_id'];

                            // Search if transaction is already exits?
                            $atten = $this->model->where(['nik' => $nik_data, 'date' => $date_data])->first();

                            if (!empty($atten)) {
                                $this->model->save([
                                    'trx_attendance_id' => $atten->trx_attendance_id,
                                    'md_employee_id' => $employee_id,
                                    'nik' => $nik_data,
                                    'date' => $date_data,
                                    'clock_in' => $clock_in_data,
                                    'clock_out' => $clock_out_data,
                                    'absent' => $absent_data
                                ]);
                                $jmlhupdate++;
                            } else {
                                $this->model->save([
                                    'md_employee_id' => $employee_id,
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
                $this->model->db->transCommit();
            } catch (\Exception $e) {
                $this->model->db->transRollback();
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
