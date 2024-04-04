<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AccessMenu;
use App\Models\M_Employee;
use App\Models\M_AbsentDetail;
use App\Models\M_Holiday;
use App\Models\M_Attendance;
use App\Models\M_Rule;
use Config\Services;

class SickLeave extends BaseController
{
    /** Pengajuan Sakit */
    protected $Pengajuan_Sakit = 'sakit';

    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Absent($this->request);
        $this->modelDetail = new M_AbsentDetail($this->request);
        $this->entity = new \App\Entities\Absent();
    }

    public function index()
    {
        $data = [
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/sickleave/v_sickleave', $data);
    }

    public function showAll()
    {
        $mAccess = new M_AccessMenu($this->request);
        $mEmployee = new M_Employee($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = [
                '', // Hide column
                '', // Number column
                'trx_absent.documentno',
                'md_employee.fullname',
                'trx_absent.nik',
                'md_branch.name',
                'md_division.name',
                'trx_absent.submissiondate',
                'trx_absent.startdate',
                'trx_absent.receiveddate',
                'trx_absent.reason',
                'trx_absent.docstatus',
                'sys_user.name'
            ];
            $search = [
                'trx_absent.documentno',
                'md_employee.fullname',
                'trx_absent.nik',
                'md_branch.name',
                'md_division.name',
                'trx_absent.submissiondate',
                'trx_absent.startdate',
                'trx_absent.enddate',
                'trx_absent.receiveddate',
                'trx_absent.reason',
                'trx_absent.docstatus',
                'sys_user.name'
            ];
            $sort = ['trx_absent.submissiondate' => 'DESC'];

            /**
             * Hak akses
             */
            $roleEmp = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_All_Data');
            $arrAccess = $mAccess->getAccess($this->session->get("sys_user_id"));
            $arrEmployee = $mEmployee->getChartEmployee($this->session->get('md_employee_id'));

            if ($arrAccess && isset($arrAccess["branch"]) && isset($arrAccess["division"])) {
                $arrBranch = $arrAccess["branch"];
                $arrDiv = $arrAccess["division"];

                $arrEmpBased = $mEmployee->getEmployeeBased($arrBranch, $arrDiv);

                if ($roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $arrMerge = array_unique(array_merge($arrEmpBased, $arrEmployee));

                    $where['trx_absent.md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $where['trx_absent.md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $where['trx_absent.md_employee_id'] = [
                        'value'     => $arrEmpBased
                    ];
                } else {
                    $where['trx_absent.md_employee_id'] = $this->session->get('md_employee_id');
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $where['trx_absent.md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else {
                $where['trx_absent.md_employee_id'] = $this->session->get('md_employee_id');
            }

            $where['trx_absent.submissiontype'] = $this->Pengajuan_Sakit;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_absent_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->employee_fullname;
                $row[] = $value->nik;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->startdate, '-') . " s/d " . format_dmy($value->enddate, '-');
                $row[] = !is_null($value->receiveddate) ? format_dmytime($value->receiveddate, '-') : "";
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
        $mEmployee = new M_Employee($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mAttendance = new M_Attendance($this->request);
        $mRule = new M_Rule($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $file = $this->request->getFile('image');
            $file2 = $this->request->getFile('image2');
            $file3 = $this->request->getFile('image3');

            $post["submissiontype"] = $this->Pengajuan_Sakit;
            $post["necessary"] = 'SA';

            try {
                $img_name = "";
                $img2_name = "";
                $img3_name = "";
                $value = "";

                if (!empty($post['md_employee_id'])) {
                    $row = $mEmployee->find($post['md_employee_id']);
                    $lenPos = strpos($row->getValue(), '-');
                    $value = substr_replace($row->getValue(), "", $lenPos);
                    $ymd = date('YmdHis');
                }

                if ($file && $file->isValid()) {
                    $ext = $file->getClientExtension();
                    $img_name = $this->Pengajuan_Sakit . '_' . $value . '_' . $ymd . '.' . $ext;
                    $post['image'] = $img_name;
                }

                if ($file2 && $file2->isValid()) {
                    $ext2 = $file2->getClientExtension();
                    $img2_name = $this->Pengajuan_Sakit . '_' . $value . '2_' . $ymd . '.' . $ext2;
                    $post['image2'] = $img2_name;
                }

                if ($file3 && $file3->isValid()) {
                    $ext3 = $file3->getClientExtension();
                    $img3_name = $this->Pengajuan_Sakit . '_' . $value . '3_' . $ymd . '.' . $ext3;
                    $post['image3'] = $img3_name;
                }

                if (!$this->validation->run($post, 'sakit')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $holidays = $mHoliday->getHolidayDate();
                    $startDate = $post['startdate'];
                    $endDate = $post['enddate'];
                    $nik = $post['nik'];
                    $date = $post['submissiondate'];
                    $dateWorkRange = getDatesFromRange($startDate, $endDate, $holidays);

                    $rule = $mRule->where([
                        'name'      => 'Sakit',
                        'isactive'  => 'Y'
                    ])->first();

                    $countDays = $rule && !empty($rule->min) ? $rule->min : 1;
                    $prevDate = lastWorkingDays($date, $holidays, $countDays);
                    $lastDate = end($prevDate);

                    $att = $mAttendance->where([
                        'nik'       => $nik,
                        'date'      => $startDate,
                        'absent'    => 'N'
                    ])->first();

                    $whereClause = "trx_absent.nik = $nik";
                    $whereClause .= " AND trx_absent.startdate >= '$startDate' AND trx_absent.enddate <= '$endDate'";
                    $whereClause .= " AND trx_absent.docstatus = '$this->DOCSTATUS_Completed'";
                    $whereClause .= " AND trx_absent_detail.isagree = 'Y'";
                    $trx = $this->modelDetail->getAbsentDetail($whereClause)->getResult();

                    $attPresent = $mAttendance->where([
                        'nik'       => $nik,
                        'absent'    => 'N'
                    ])->whereIn('date', $dateWorkRange)->findAll();

                    if ($startDate < $lastDate && ($att || is_null($att))) {
                        $response = message('success', false, 'Tanggal mulai sudah melewati ketentuan, maksimal tanggal mulai : ' . format_dmy($lastDate, '-'));
                    } else if ($startDate = $lastDate && $trx) {
                        $response = message('success', false, 'Tidak bisa mengajukan pada rentang tanggal, karena sudah ada pengajuan lain');
                    } else if ($attPresent) {
                        $date = [];
                        foreach ($attPresent as $val) {
                            $date[] = format_dmy($val->date, '-');
                        }

                        $date = implode(", ", $date);
                        $response = message('success', false, 'Ada kehadiran, tidak bisa mengajukan pada tanggal : [' . $date . ']');
                    } else {
                        $path = $this->PATH_UPLOAD . $this->PATH_Pengajuan . '/';

                        if ($this->isNew()) {
                            uploadFile($file, $path, $img_name);

                            if ($file2 && $file2->isValid())
                                uploadFile($file2, $path, $img2_name);

                            if ($file3 && $file3->isValid())
                                uploadFile($file3, $path, $img3_name);
                        } else {
                            $row = $this->model->find($this->getID());

                            if (!empty($post['image']) && !empty($row->getImage()) && $post['image'] !== $row->getImage()) {
                                if (file_exists($path . $row->getImage()))
                                    unlink($path . $row->getImage());

                                uploadFile($file, $path, $img_name);
                            }

                            if (empty($post['image2']) && !empty($row->getImage2()) && file_exists($path . $row->getImage2())) {
                                unlink($path . $row->getImage2());
                            } else {
                                uploadFile($file2, $path, $img2_name);
                            }

                            if (empty($post['image3']) && !empty($row->getImage3()) && file_exists($path . $row->getImage3())) {
                                unlink($path . $row->getImage3());
                            } else {
                                uploadFile($file3, $path, $img3_name);
                            }
                        }

                        $this->entity->fill($post);

                        if ($this->isNew()) {
                            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
                            $docNo = $this->model->getInvNumber("submissiontype", $this->Pengajuan_Sakit, $post);
                            $this->entity->setDocumentNo($docNo);
                        }

                        $response = $this->save();
                    }
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

                $path = $this->PATH_UPLOAD . $this->PATH_Pengajuan . '/';

                if (file_exists($path . $list[0]->getImage())) {
                    $path = 'uploads/' . $this->PATH_Pengajuan . '/';
                    $list[0]->setImage($path . $list[0]->getImage());
                } else {
                    $list[0]->setImage(null);
                }

                if (!empty($list[0]->getImage2()) && file_exists($path . $list[0]->getImage2())) {
                    $path = 'uploads/' . $this->PATH_Pengajuan . '/';
                    $list[0]->setImage2($path . $list[0]->getImage2());
                } else {
                    $list[0]->setImage2(null);
                }

                if (!empty($list[0]->getImage3()) && file_exists($path . $list[0]->getImage3())) {
                    $path = 'uploads/' . $this->PATH_Pengajuan . '/';
                    $list[0]->setImage3($path . $list[0]->getImage3());
                } else {
                    $list[0]->setImage3(null);
                }

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

                //* Need to set data into date field in form
                $list[0]->setStartDate(format_dmy($list[0]->startdate, "-"));
                $list[0]->setEndDate(format_dmy($list[0]->enddate, "-"));

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($list);

                $result = [
                    'header'    => $this->field->store($fieldHeader),
                    'line'      => $this->tableLine('edit', $detail)
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

        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            $_ID = $post['id'];
            $_DocAction = $post['docaction'];

            $row = $this->model->find($_ID);
            $menu = $this->request->uri->getSegment(2);

            try {
                if (!empty($_DocAction)) {
                    if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session);
                        $response = message('success', true, true);
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
        $table = [];

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $docNoRef = "";
                $line = $this->model->where('trx_absent_id', $row->trx_absent_id)->first();

                if (!empty($row->ref_absent_detail_id)) {
                    $lineRef = $this->modelDetail->getDetail('trx_absent_detail_id', $row->ref_absent_detail_id)->getRow();
                    $docNoRef = $lineRef->documentno;
                }

                $table[] = [
                    $row->lineno,
                    format_dmy($row->date, '-'),
                    $line->getDocumentNo(),
                    $docNoRef,
                    statusRealize($row->isagree)
                ];
            endforeach;
        }

        return json_encode($table);
    }
}
