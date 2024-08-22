<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Memo;
use App\Models\M_AccessMenu;
use App\Models\M_Employee;
use App\Models\M_Branch;
use App\Models\M_Division;
use App\Models\M_EmpWorkDay;
use App\Models\M_Position;
use App\Models\M_ReferenceDetail;
use Config\Services;
use DateTime;
use TCPDF;

class Memo extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Memo($this->request);
        $this->entity = new \App\Entities\Memo();
    }

    public function index()
    {
        $data = [
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/memo/v_memo', $data);
    }

    public function showAll()
    {
        $mAccess = new M_AccessMenu($this->request);
        $mEmployee = new M_Employee($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = $this->model->column_order;
            $search = $this->model->column_search;
            $sort = $this->model->order;

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

                    $where['trx_hr_memo.md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $where['trx_hr_memo.md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $where['trx_hr_memo.md_employee_id'] = [
                        'value'     => $arrEmpBased
                    ];
                } else {
                    $where['trx_hr_memo.md_employee_id'] = $this->session->get('md_employee_id');
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $where['trx_hr_memo.md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else {
                $where['trx_hr_memo.md_employee_id'] = $this->session->get('md_employee_id');
            }

            $where['trx_hr_memo.memotype'] = $this->model->Pengajuan_Memo_SDM;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_hr_memo_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->employee_fullname;
                $row[] = $value->nik;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = format_dmy($value->memodate, '-');
                $row[] = $value->criteria;
                $row[] = $value->memocontent;
                $row[] = docStatus($value->docstatus);
                $row[] = $value->createdby;
                $row[] = $this->template->tableButton($ID, $value->docstatus, "Print");
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
        $mEmpWork = new M_EmpWorkDay($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();


            try {
                if (!$this->validation->run($post, 'memo')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $today = date('Y-m-d');
                    $day = date('w');
                    $post["necessary"] = 'MS';
                    $employeeId = $post['md_employee_id'];
                    $nik = $post['nik'];
                    $memoDate = $post['memodate'];
                    $post["memotype"] = $this->model->Pengajuan_Memo_SDM;

                    //TODO : Get work day employee
                    $workDay = $mEmpWork->where([
                        'md_employee_id'                            => $post['md_employee_id'],
                        'date_format(validto, "%Y-%m-%d") >='       => $today
                    ])->orderBy('validto', 'ASC')->first();

                    if (is_null($workDay)) {
                        $response = message('success', false, 'Hari kerja belum ditentukan');
                    } else {
                        $this->entity->fill($post);

                        if ($this->isNew()) {
                            $docNo = $this->model->getInvNumber($post);
                            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
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
        $mRefDetail = new M_ReferenceDetail($this->request);
        $mEmployee = new M_Employee($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();

                if (!empty($list[0]->getMemoCriteria())) {
                    $rowCriteria = $mRefDetail->where("name", $list[0]->getMemoCriteria())->first();
                    $list = $this->field->setDataSelect($mRefDetail->table, $list, "memocriteria", $rowCriteria->getValue(), $rowCriteria->getName());
                }

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($list);

                $result = [
                    'header'    => $this->field->store($fieldHeader)
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

    public function indexGenerate()
    {
        $data = [
            'month' => date('M-Y', strtotime('-1 month'))
        ];

        return $this->template->render('generate/listmemo/v_list_memo', $data);
    }

    public function showAllGenerate()
    {
        $post = $this->request->getVar();

        if ($this->request->getMethod(true) === 'POST') {
            $table = "v_attendance_submission";
            $select = $this->model->getSelectList();
            $join = $this->model->getJoinList();
            $order = $this->request->getPost('columns');
            $search = $this->request->getPost('search');
            $sort = ['nik' => 'ASC', 'fullname' => 'ASC'];
            $number = $this->request->getPost('start');

            $period = date('m-Y');

            foreach ($post['form'] as $value) :
                if ($value['name'] === "periode") {
                    if (!empty($value['value']))
                        $period = date('m-Y', strtotime($value['value']));
                }

            endforeach;

            $where = ["v_attendance_submission.period = '{$period}'
                    AND ((v_attendance_submission.type = 'alpa' AND v_attendance_submission.total >= 2) 
                    OR (v_attendance_submission.type = 'kehadiran' AND v_attendance_submission.total >= 6) 
                    OR (v_attendance_submission.type = 'ijin' AND v_attendance_submission.total >= 6))"];

            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);
            $data = [];

            foreach ($list as $val) :
                $row = [];
                $ID = $val->md_employee_id;
                $number++;

                $row[] = $val->nik;
                $row[] = $val->fullname;
                $row[] = $val->branch;
                $row[] = $val->division;
                $row[] = $val->type;
                $row[] = $val->period;
                $row[] = $val->total;
                $row[] = $this->template->buttonGenerateMemo($ID);
                $data[] = $row;
            endforeach;

            $recordTotal = count($data);
            $recordsFiltered = count($data);

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $recordTotal,
                'recordsFiltered'   => $recordsFiltered,
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function generateMemo()
    {
        $mEmployee = new M_Employee($this->request);
        $mBranch = new M_Branch($this->request);
        $mDiv = new M_Division($this->request);
        $mPosition = new M_Position($this->request);

        if ($this->request->isAJAX()) {
            try {
                $post = $this->request->getVar();
                $post = json_decode($post['memos'])[0];
                $post = (array)$post;

                $post['necessary'] = 'MS';
                $today = date('Y-m-d');

                $name = $post['name'];
                $nik = $post['nik'];
                $employeeId = $post['md_employee_id'];
                $branch =  $post['branch'];
                $division =  $post['division'];
                $criteria =  $post['criteria'];
                $period = $post['period'];
                $totalDays = $post['total'];

                $date = DateTime::createFromFormat('m-Y', $period);
                $date->modify('last day of this month');
                $post['memodate'] = $date->format('Y-m-d');
                $post['submissiondate'] = $today;

                $post['md_branch_id'] = $mBranch->where('name', $branch)->first()->getBranchId();
                $post['md_division_id'] = $mDiv->where('name', $division)->first()->getDivisionId();
                $rowEmp = $mEmployee->find($employeeId);

                $rowPoisition = $mPosition->find($rowEmp->md_position_id);
                $position = $rowPoisition ? $rowPoisition->getName() : "";

                $this->entity->setEmployeeId($employeeId);
                $this->entity->setNik($nik);
                $this->entity->setBranchId($post['md_branch_id']);
                $this->entity->setDivisionId($post['md_division_id']);
                $this->entity->setMemoType($this->model->Pengajuan_Memo_SDM);
                $this->entity->setSubmissionDate($post['submissiondate']);
                $this->entity->setMemoDate($post['memodate']);
                $this->entity->setMemoCriteria($criteria);
                $this->entity->setTotalDays($totalDays);
                $this->entity->setDescription("Tanggal pemotongan akan berlaku selama karyawan masuk kerja (tanggal tidak mengikat).");

                $criteria = ucfirst($criteria);
                $this->entity->setMemoContent("Pemotongan Tunjangan kehadiran atas nama : {$name} - {$nik} ({$position} - {$branch}) Sebanyak 5 (Lima) hari kerja, karena {$criteria} lebih dari 5 (Lima) Kali Total {$criteria} dalam bulan {$period} = {$totalDays} Kali");

                if ($this->isNew()) {
                    $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                    $docNo = $this->model->getInvNumber($post);
                    $this->entity->setDocumentNo($docNo);
                }

                $this->save();

                $response = message('success', true, "Memo telah digenerate dengan nomor {$docNo}");
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function exportPDF($id)
    {
        $mEmployee = new M_Employee($this->request);
        $list = $this->model->find($id);
        $employee = $mEmployee->where($mEmployee->primaryKey, $list->created_by)->first();

        $memosplit = preg_split('/(?=Pemotongan|Sebanyak|Total)/', $list->memocontent);


        //bagian PF
        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->AddPage();
        $pdf->Cell(140, 0, 'pt. sahabat abadi sejahtera', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(50, 0, 'No Form : ' . $list->documentno, 0, 1, 'L', false, '', 0, false);
        $pdf->setFont('helvetica', 'B', 20);
        $pdf->Cell(0, 20, 'MEMO SDM', 0, 1, 'C');
        $pdf->setFont('helvetica', '', 12);
        //Ini untuk bagian field nama dan tanggal pengajuan
        $pdf->Cell(40, 0, 'Tanggal Pembuatan', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(90, 0, ': ' . format_dmy($list->memodate, '-'), 0, 1, 'L', false, '', 0, false);
        $pdf->Ln(5);
        //Ini untuk bagian field divisi dan Tanggal diterima
        $pdf->Cell(40, 0, 'Isi Memo :', 0, 1, 'L', false, '', 0, false);
        // $pdf->Cell(3, 0, ':', 0, 1, 'L');
        $pdf->Ln(1);
        $pdf->Cell(0, 0, $memosplit[1], 0, 1, 'L', false, '', 0, false);
        // $pdf->Ln(1);
        $pdf->Cell(0, 0, $memosplit[2], 0, 1, 'L', false, '', 0, false);
        // $pdf->Ln(1);
        $pdf->Cell(0, 0, $memosplit[3], 0, 1, 'L', false, '', 0, false);
        // $pdf->MultiCell(0, 20, $memosplit[1], 0, '', false, 1, null, null, false, 0, false, false, 20);
        $pdf->Ln(5);
        //Ini bagian tanggal ijin dan jam
        $pdf->Cell(10, 0, 'Ket :', 0, 0, 'L', false, '', 0, false);
        $pdf->MultiCell(0, 10, $list->description, 0, '', false, 1, null, null, false, 0, false, false, 20);
        $pdf->Ln(10);
        //Bagian ttd
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(48, 0, 'Dibuat oleh,', 0, 0, 'C');
        $pdf->Cell(48, 0, 'Disetujui oleh,', 0, 0, 'C');
        $pdf->Cell(48, 0, 'Mgr. Ybs,', 0, 0, 'C');
        $pdf->Cell(48, 0, 'Kary. Ybs,', 0, 0, 'C');
        $pdf->Ln(25);
        $pdf->Cell(48, 0, $employee->nickname, 0, 0, 'C');
        $pdf->Cell(48, 0, '(                          )', 0, 0, 'C');
        $pdf->Cell(48, 0, '(                          )', 0, 0, 'C');
        $pdf->Cell(48, 0, '(                          )', 0, 1, 'C');

        $this->response->setContentType('application/pdf');
        $pdf->Output('detail-laporan,pdf', 'I');
    }
}
