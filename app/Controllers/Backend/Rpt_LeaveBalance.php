<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_LeaveBalance;
use Config\Services;

class Rpt_LeaveBalance extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_LeaveBalance($this->request);
    }

    public function index()
    {
        $date = format_dmy(date('Y-m-d'), "-");

        $data = [
            'date_range' => $date . ' - ' . $date
        ];

        return $this->template->render('report/leavebalance/v_leavebalance', $data);
    }

    public function showAll()
    {
        $post = $this->request->getVar();
        $data = [];

        $recordTotal = 0;
        $recordsFiltered = 0;

        if ($this->request->getMethod(true) === 'POST') {
            if (isset($post['form']) && $post['clear'] === 'false') {
                $table = $this->model->table;
                $select = $this->model->getSelect();
                $join = $this->model->getJoin();
                $order = $this->request->getPost('columns');
                $sort = ['md_employee.fullname' => 'ASC', 'trx_leavebalance.submissiondate' => 'ASC'];
                $search = $this->request->getPost('search');

                $number = $this->request->getPost('start');
                $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join);

                foreach ($list as $value) :
                    $row = [];

                    $number++;

                    $row[] = $number;
                    $row[] = $value->employee_fullname;
                    $row[] = format_dmy($value->submissiondate, "-");
                    $row[] = $value->amount;
                    $row[] = $value->documentno;
                    $row[] = "";
                    $row[] = $value->description;
                    $data[] = $row;

                endforeach;

                $recordTotal = $this->datatable->countAll($table, $select, $order, $sort, $search, $join);
                $recordsFiltered = $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join);
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

    public function indexSummary()
    {
        $date = format_dmy(date('Y-m-d'), "-");

        $data = [
            'date_range' => $date . ' - ' . $date
        ];

        return $this->template->render('report/leavebalancesummary/v_leavebalancesummary', $data);
    }

    public function showAllSummary()
    {
        $post = $this->request->getVar();
        $data = [];

        $recordTotal = 0;
        $recordsFiltered = 0;

        if ($this->request->getMethod(true) === 'POST') {
            if (isset($post['form']) && $post['clear'] === 'false') {
                $table = $this->model->table;
                $select = $this->model->getSelect();
                $join = $this->model->getJoin();
                $order = $this->request->getPost('columns');
                $sort = ['md_employee.fullname' => 'ASC'];
                $search = $this->request->getPost('search');
                $where = ['trx_leavebalance.description' => 'saldo awal'];

                $number = $this->request->getPost('start');
                $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

                foreach ($list as $value) :
                    $row = [];

                    $number++;

                    $row[] = $number;
                    $row[] = $value->employee_fullname;
                    $row[] = $value->branch;
                    $row[] = $value->divisi;
                    $row[] = "";
                    $row[] = $value->amount;

                    //* Januari 
                    $jan = $this->model->getBalance([
                        'trx_leavebalance.md_employee_id'           => $value->md_employee_id,
                        'MONTH(trx_leavebalance.submissiondate)'    => 1,
                        'trx_leavebalance.description'              => null
                    ]);
                    $useJan = 0;

                    //* Februari 
                    $feb = $this->model->getBalance([
                        'trx_leavebalance.md_employee_id'           => $value->md_employee_id,
                        'MONTH(trx_leavebalance.submissiondate)'    => 2,
                        'trx_leavebalance.description'              => null
                    ]);
                    $useFeb = 0;

                    //* Maret 
                    $mar = $this->model->getBalance([
                        'trx_leavebalance.md_employee_id'           => $value->md_employee_id,
                        'MONTH(trx_leavebalance.submissiondate)'    => 3,
                        'trx_leavebalance.description'              => null
                    ]);
                    $useMar = 0;

                    //* April 
                    $apr = $this->model->getBalance([
                        'trx_leavebalance.md_employee_id'           => $value->md_employee_id,
                        'MONTH(trx_leavebalance.submissiondate)'    => 4,
                        'trx_leavebalance.description'              => null
                    ]);
                    $useApr = 0;

                    //* Mei 
                    $mei = $this->model->getBalance([
                        'trx_leavebalance.md_employee_id'           => $value->md_employee_id,
                        'MONTH(trx_leavebalance.submissiondate)'    => 5,
                        'trx_leavebalance.description'              => null
                    ]);
                    $useMei = 0;

                    //* Jun 
                    $jun = $this->model->getBalance([
                        'trx_leavebalance.md_employee_id'           => $value->md_employee_id,
                        'MONTH(trx_leavebalance.submissiondate)'    => 6,
                        'trx_leavebalance.description'              => null
                    ]);
                    $useJun = 0;

                    //* Jul
                    $jul = $this->model->getBalance([
                        'trx_leavebalance.md_employee_id'           => $value->md_employee_id,
                        'MONTH(trx_leavebalance.submissiondate)'    => 7,
                        'trx_leavebalance.description'              => null
                    ]);
                    $useJul = 0;

                    //* Ags
                    $ags = $this->model->getBalance([
                        'trx_leavebalance.md_employee_id'           => $value->md_employee_id,
                        'MONTH(trx_leavebalance.submissiondate)'    => 8,
                        'trx_leavebalance.description'              => null
                    ]);
                    $useAgs = 0;

                    //* Sep
                    $sep = $this->model->getBalance([
                        'trx_leavebalance.md_employee_id'           => $value->md_employee_id,
                        'MONTH(trx_leavebalance.submissiondate)'    => 9,
                        'trx_leavebalance.description'              => null
                    ]);
                    $useSep = 0;

                    //* Okt
                    $okt = $this->model->getBalance([
                        'trx_leavebalance.md_employee_id'           => $value->md_employee_id,
                        'MONTH(trx_leavebalance.submissiondate)'    => 10,
                        'trx_leavebalance.description'              => null
                    ]);
                    $useOkt = 0;

                    //* Nov
                    $nov = $this->model->getBalance([
                        'trx_leavebalance.md_employee_id'           => $value->md_employee_id,
                        'MONTH(trx_leavebalance.submissiondate)'    => 11,
                        'trx_leavebalance.description'              => null
                    ]);
                    $useNov = 0;

                    //* Des
                    $des = $this->model->getBalance([
                        'trx_leavebalance.md_employee_id'           => $value->md_employee_id,
                        'MONTH(trx_leavebalance.submissiondate)'    => 12,
                        'trx_leavebalance.description'              => null
                    ]);
                    $useDes = 0;

                    if (!is_null($jan->amount))
                        $useJan = $jan->amount;

                    $row[] = $useJan;

                    if (!is_null($feb->amount))
                        $useFeb = $feb->amount;

                    $row[] = $useFeb;

                    if (!is_null($mar->amount))
                        $useMar = $mar->amount;

                    $row[] = $useMar;

                    if (!is_null($apr->amount))
                        $useApr = $apr->amount;

                    $row[] = $useApr;

                    if (!is_null($mei->amount))
                        $useMei = $mei->amount;

                    $row[] = $useMei;

                    if (!is_null($jun->amount))
                        $useJun = $jun->amount;

                    $row[] = $useJun;

                    if (!is_null($jul->amount))
                        $useJul = $jul->amount;

                    $row[] = $useJul;

                    if (!is_null($ags->amount))
                        $useAgs = $ags->amount;

                    $row[] = $useAgs;

                    if (!is_null($sep->amount))
                        $useSep = $sep->amount;

                    $row[] = $useSep;

                    if (!is_null($okt->amount))
                        $useOkt = $okt->amount;

                    $row[] = $useOkt;

                    if (!is_null($nov->amount))
                        $useNov = $nov->amount;

                    $row[] = $useNov;

                    if (!is_null($des->amount))
                        $useDes = $des->amount;

                    $row[] = $useDes;

                    $totalSaldo = $value->amount + $useJan + $useFeb + $useMar + $useApr + $useMei + $useJun + $useJul + $useSep + $useOkt + $useNov + $useDes;
                    $row[] = $totalSaldo;
                    $data[] = $row;

                endforeach;

                $recordTotal = $this->datatable->countAll($table, $select, $order, $sort, $search, $join, $where);
                $recordsFiltered = $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join, $where);
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
