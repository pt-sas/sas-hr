<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
use App\Models\M_Employee;
use App\Models\M_Holiday;
use Config\Services;
use Kint\Zval\Value;

class Rpt_AbsentSummary extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Absent($this->request);
        $this->entity = new \App\Entities\Absent();
    }

    public function reportIndex()
    {
        $date = format_dmy(date('Y-m-d'), "-");

        $absent = $this->model->where([
            'trx_absent.docstatus' => 'CO',
            'trx_absent.md_employee_id' => 100002
        ])->findAll();

        foreach ($absent as $key) {
            $necessary = $key->necessary;
            $startdate = $key->startdate;
            $enddate = $key->enddate;

            $date_range = getDatesFromRange($startdate, $enddate);
            $count = count($date_range);
            $result = [$necessary => $count];

            // foreach ($result as $item) {
            foreach ($result as $key => $value) {
                if (!isset($sums[$key])) {
                    $sums[$key] = $value;
                } else {
                    $sums[$key] += $value;
                }
            }
        }

        dd($absent);



        // $data = [
        //     'date_range' => $date . ' - ' . $date
        // ];

        // return $this->template->render('report/absentsummary/v_rpt_absent_summary', $data);
        // return json_encode($sums["SA"]);
        // echo gettype($absent);
    }

    public function reportShowAll()
    {
        $mEmployee = new M_Employee($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);
        $mEmpDiv = new M_EmpDivision($this->request);

        $post = $this->request->getVar();
        $data = [];

        $recordTotal = 0;
        $recordsFiltered = 0;

        if ($this->request->getMethod(true) === 'POST') {
            if (isset($post['form']) && $post['clear'] === 'false') {
                $table = $mEmployee->table;
                $select = $mEmployee->findAll();
                $order = $this->request->getPost('columns');
                $sort = ['fullname' => 'ASC'];
                $search = $this->request->getPost('search');
                $where['md_employee.isactive'] = 'Y';
                $total = [];

                foreach ($post['form'] as $value) {
                    if (!empty($value['value'])) {
                        if ($value['name'] === "startdate") {
                            $datetime = urldecode($value['value']);
                            $date = explode(" - ", $datetime);
                        }

                        if ($value['name'] === "md_division_id") {
                            $arrDiv_id = $value['value'];

                            $listDiv = $mEmpDiv->whereIn("md_division_id", $arrDiv_id)->findAll();
                            $where['md_employee.md_employee_id'] = [
                                'value'     => array_column($listDiv, "md_employee_id")
                            ];
                        }

                        if ($value['name'] === "md_branch_id") {
                            $arrBranch_id = $value['value'];

                            $listBranch = $mEmpBranch->whereIn("md_branch_id", $arrBranch_id)->findAll();
                            $where['md_employee.md_employee_id'] = [
                                'value'     => array_column($listBranch, "md_employee_id")
                            ];
                        }
                    }
                }

                $number = $this->request->getPost('start');
                $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, [], $where);

                foreach ($list as $val) :
                    $row = [];
                    $result = [];

                    $parAbsent = [
                        'trx_absent.md_employee_id'    => $val->md_employee_id,
                        'trx_absent.docstatus'        => 'CO'
                    ];

                    $absent = $this->model->where($parAbsent)->findAll();
                    foreach ($absent as $key) {
                        $necessary = $key->necessary;
                        $startdate = $key->startdate;
                        $enddate = $key->enddate;

                        $date_range = getDatesFromRange($startdate, $enddate);
                        $count = count($date_range);
                        $result = [$necessary => $count];

                        foreach ($result as $key => $value) {
                            if (!isset($sums[$key])) {
                                $sums[$key] = $value;
                            } else {
                                $sums[$key] += $value;
                            }
                        }
                        $total = $sums;
                    }

                    $number++;

                    $row[] = $number;
                    $row[] = $val->nik;
                    $row[] = $val->fullname;
                    $row[] = $total['SA'] ?? 0;
                    // $row[] = "total";
                    // $row[] = isset($total["SA"]) ? $total["SA"] : 0;
                    $row[] = '';
                    // $row[] = isset($total["TK"]) ? $total["TK"] : 0;
                    $row[] = ""; //$total["CT"];
                    $row[] = ""; //$total["IJ"];
                    $row[] = ""; //$total["IR"];
                    $row[] = ""; //$total["AP"];
                    $row[] = ""; //$total["DT"];
                    $row[] = ""; //$total["PJ"];
                    $row[] = $total['LM'] ?? 0; //$total["LM"];
                    $row[] = ""; //$total["LP"];
                    $data[] = $row;
                // $total = null;
                endforeach;

                $recordTotal = count($data);
                $recordsFiltered = count($data);
            }

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $recordTotal,
                'recordsFiltered'   => $recordsFiltered,
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }
}
