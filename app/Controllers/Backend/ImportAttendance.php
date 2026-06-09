<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Shared_Date;
use App\Models\M_Employee;
use App\Models\M_ImportAttendance;
use App\Models\M_ImportAttendanceDetail;
use Config\Services;

class ImportAttendance extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_ImportAttendance($this->request);
        $this->modelDetail = new M_ImportAttendanceDetail($this->request);
        $this->entity = new \App\Entities\ImportAttendance();
    }

    public function index()
    {
        $data = [
            'today' => date('d-M-Y')
        ];
        return $this->template->render('transaction/importattendance/v_import_attendance', $data);
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $sort = ['trx_import_attendance.submissiondate' => 'DESC'];
            $order = [
                '', // Hide column
                '', // Number column
                'trx_import_attendance.documentno',
                'md_employee.fullname',
                'trx_import_attendance.submissiondate',
                'trx_import_attendance.startdate',
                'trx_import_attendance.approveddate',
                'trx_import_attendance.reason',
                'trx_import_attendance.docstatus',
                'sys_user.name'
            ];

            $search = [
                '', // Hide column
                '', // Number column
                'trx_import_attendance.documentno',
                'md_employee.fullname',
                'trx_import_attendance.submissiondate',
                'trx_import_attendance.startdate',
                'trx_import_attendance.approveddate',
                'trx_import_attendance.reason',
                'trx_import_attendance.docstatus',
                'sys_user.name'
            ];

            $join = $this->model->getJoin();

            $where['trx_import_attendance.submissiontype'] = $this->model->Pengajuan_Import_Kehadiran;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDataTables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_import_attendance_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->employee_fullname;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->startdate, '-');
                $row[] = format_dmy($value->approveddate, '-');
                $row[] = $value->reason;
                $row[] = docStatus($value->docstatus);
                $row[] = $value->createdby;
                $row[] = $this->template->tableButton($ID, $value->docstatus);
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search, $join, $where),
                'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join, $where),
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $table = json_decode($post['table']);
            //! Mandatory property for detail validation
            $post['line'] = countLine($table);
            $post['detail'] = [
                'table' => arrTableLine($table)
            ];

            try {
                if (!$this->validation->run($post, 'importattendance')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $post["submissiontype"] = $this->model->Pengajuan_Import_Kehadiran;
                    $post["necessary"] = 'IK';
                    $post["enddate"] = $post['startdate'];

                    $this->entity->fill($post);

                    if ($this->isNew()) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                        $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Tugas_Kantor, $post);
                        $this->entity->setDocumentNo($docNo);
                    }

                    $response = $this->save();
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function show($id)
    {
        $mEmployee = new M_Employee($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $detail = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();
                $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

                //Need to set data into date field in form
                $list[0]->startdate = format_dmy($list[0]->startdate, "-");

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($list);

                $result = [
                    'header'    => $this->field->store($fieldHeader),
                    'line'      => $this->tableLine('update', $detail)
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

    public function processIt()
    {
        $cWfs = new WScenario();
        $mAttendance = new M_Attendance($this->request);

        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            $_ID = $post['id'];
            $_DocAction = $post['docaction'];

            $row = $this->model->find($_ID);
            $rowDetail = $this->modelDetail->where($this->model->primaryKey, $row->trx_absent_id)->findAll();
            $menu = $this->request->uri->getSegment(2);
            $today = date("Y-m-d");

            try {
                if (!empty($_DocAction)) {
                    if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {

                        $keys = array_keys($rowDetail);
                        $lastLoop = end($keys);
                        $nik = $row->nik;

                        $process = false;
                        foreach ($rowDetail as $key => $value) {
                            $dateClause = date('Y-m-d', strtotime($value->date));

                            // TODO : Get Office Duties & SickLeave Submission
                            $whereClause = "trx_absent.nik = '{$nik}'";
                            $whereClause .= " AND trx_absent_detail.date = '{$dateClause}'";
                            $whereClause .= " AND trx_absent.docstatus = '{$this->DOCSTATUS_Completed}'";
                            $whereClause .= " AND trx_absent_detail.isagree = 'Y'";
                            $whereClause .= " AND trx_absent.submissiontype IN ({$this->model->Pengajuan_Tugas_Kantor}, {$this->model->Pengajuan_Sakit})";
                            $trx = $this->modelDetail->getAbsentDetail($whereClause)->getResult();

                            //TODO : Get attendance employee
                            $whereClause = "v_attendance.nik = '{$nik}'";
                            $whereClause .= " AND v_attendance.date = '{$dateClause}'";
                            $attPresent = $mAttendance->getAttendance($whereClause)->getRow();

                            $dateNow = format_dmy($value->date, '-');


                            if (($dateClause <= $today) && !$attPresent && !$trx) {
                                $response = message('error', true, "Saldo cuti tanggal {$dateNow} sudah terpakai");
                                break;
                            }

                            if ($key === $lastLoop)
                                $process = true;
                        }

                        if ($process) {
                            $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session);
                            $response = message('success', true, true);
                        }
                    } else if ($_DocAction === $this->DOCSTATUS_Unlock) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
                        $response = $this->save();
                    } else if (($_DocAction === $this->DOCSTATUS_Unlock || $_DocAction === $this->DOCSTATUS_Voided)) {
                        $response = message('error', true, 'Tidak bisa diproses');
                    } else {
                        $this->entity->setDocStatus($_DocAction);
                        $response = $this->save();
                    }
                } else {
                    $response = message('error', true, 'Silahkan pilih tindakan terlebih dahulu.');
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function tableLine($set = null, $detail = [])
    {
        $post = $this->request->getPost();

        $table = [];

        $fieldEmployee = new \App\Entities\Table();
        $fieldEmployee->setName("md_employee_id");
        $fieldEmployee->setIsRequired(true);
        $fieldEmployee->setType("select");
        $fieldEmployee->setClass("select2");
        $fieldEmployee->setField([
            'id'    => 'md_employee_id',
            'text'  => 'value'
        ]);
        $fieldEmployee->setLength(200);

        $fieldNik = new \App\Entities\Table();
        $fieldNik->setName('nik');
        $fieldNik->setId('nik');
        $fieldNik->setType('text');
        $fieldNik->setIsReadonly(true);
        $fieldNik->setLength(100);

        $fieldStartTime = new \App\Entities\Table();
        $fieldStartTime->setName("starttime");
        $fieldStartTime->setId("starttime");
        $fieldStartTime->setIsRequired(true);
        $fieldStartTime->setType("text");
        $fieldStartTime->setClass("timepicker");
        $fieldStartTime->setLength(100);

        $fieldEndTime = new \App\Entities\Table();
        $fieldEndTime->setName("endtime");
        $fieldEndTime->setId("endtime");
        $fieldEndTime->setIsRequired(true);
        $fieldEndTime->setType("text");
        $fieldEndTime->setClass("timepicker");
        $fieldEndTime->setLength(100);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        // ? Create
        if (empty($set)) {
            foreach ($detail as $row) :
                $fieldDate->setValue(format_dmy($row->date, '-'));

                $table[] = [
                    $this->field->fieldTable($fieldLine),
                    $this->field->fieldTable($fieldEmployee),
                    $this->field->fieldTable($fieldNik),
                    $this->field->fieldTable($fieldStartTime),
                    $this->field->fieldTable($fieldEndTime),
                    '',
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $fieldLine->setValue($row->lineno);
                $fieldDate->setValue(format_dmy($row->date, '-'));
                $btnDelete->setValue($row->trx_absent_detail_id);

                if ($row->isagree) {
                    $status = statusRealize($row->isagree);
                } else {
                    $status = '';
                }

                $table[] = [
                    $this->field->fieldTable($fieldLine),
                    $this->field->fieldTable($fieldEmployee),
                    $this->field->fieldTable($fieldNik),
                    $this->field->fieldTable($fieldStartTime),
                    $this->field->fieldTable($fieldEndTime),
                    $status,
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
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
