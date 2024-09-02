<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_LeaveBalance;
use App\Models\M_Transaction;
use Config\Services;

class Rpt_LeaveBalance extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Transaction($this->request);
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
        $data = [
            'year'          => date('Y')
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
                $table = 'v_summary_leavebalance';
                $select = $this->model->getSelect();
                $join = $this->model->getJoin();
                $order = $this->request->getPost('columns');
                $sort = ['md_employee.fullname' => 'ASC'];
                $search = $this->request->getPost('search');

                $number = $this->request->getPost('start');
                $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join);

                foreach ($list as $value) :
                    $row = [];

                    $number++;

                    $row[] = $number;
                    $row[] = $value->employee_fullname;
                    $row[] = $value->branch;
                    $row[] = $value->divisi;
                    $row[] = 0;
                    $row[] = $value->amount;

                    //* Januari 
                    $jan = $this->model->getBalance([
                        'md_transaction.md_employee_id'             => $value->md_employee_id,
                        'MONTH(md_transaction.transactiondate)'     => 1,
                        'md_transaction.isprocessed'                => "N"
                    ]);
                    $useJan = 0;

                    //* Februari 
                    $feb = $this->model->getBalance([
                        'md_transaction.md_employee_id'           => $value->md_employee_id,
                        'MONTH(md_transaction.transactiondate)'   => 2,
                        'md_transaction.isprocessed'              => "N"
                    ]);
                    $useFeb = 0;

                    //* Maret 
                    $mar = $this->model->getBalance([
                        'md_transaction.md_employee_id'           => $value->md_employee_id,
                        'MONTH(md_transaction.transactiondate)'   => 3,
                        'md_transaction.isprocessed'              => "N"
                    ]);
                    $useMar = 0;

                    //* April 
                    $apr = $this->model->getBalance([
                        'md_transaction.md_employee_id'           => $value->md_employee_id,
                        'MONTH(md_transaction.transactiondate)'   => 4,
                        'md_transaction.isprocessed'              => "N"
                    ]);
                    $useApr = 0;

                    //* Mei 
                    $mei = $this->model->getBalance([
                        'md_transaction.md_employee_id'           => $value->md_employee_id,
                        'MONTH(md_transaction.transactiondate)'   => 5,
                        'md_transaction.isprocessed'              => "N"
                    ]);
                    $useMei = 0;

                    //* Jun 
                    $jun = $this->model->getBalance([
                        'md_transaction.md_employee_id'           => $value->md_employee_id,
                        'MONTH(md_transaction.transactiondate)'   => 6,
                        'md_transaction.isprocessed'              => "N"
                    ]);
                    $useJun = 0;

                    //* Jul
                    $jul = $this->model->getBalance([
                        'md_transaction.md_employee_id'           => $value->md_employee_id,
                        'MONTH(md_transaction.transactiondate)'   => 7,
                        'md_transaction.isprocessed'              => "N"
                    ]);
                    $useJul = 0;

                    //* Ags
                    $ags = $this->model->getBalance([
                        'md_transaction.md_employee_id'           => $value->md_employee_id,
                        'MONTH(md_transaction.transactiondate)'   => 8,
                        'md_transaction.isprocessed'              => "N"
                    ]);
                    $useAgs = 0;

                    //* Sep
                    $sep = $this->model->getBalance([
                        'md_transaction.md_employee_id'           => $value->md_employee_id,
                        'MONTH(md_transaction.transactiondate)'   => 9,
                        'md_transaction.isprocessed'              => "N"
                    ]);
                    $useSep = 0;

                    //* Okt
                    $okt = $this->model->getBalance([
                        'md_transaction.md_employee_id'           => $value->md_employee_id,
                        'MONTH(md_transaction.transactiondate)'   => 10,
                        'md_transaction.isprocessed'              => "N"
                    ]);
                    $useOkt = 0;

                    //* Nov
                    $nov = $this->model->getBalance([
                        'md_transaction.md_employee_id'           => $value->md_employee_id,
                        'MONTH(md_transaction.transactiondate)'   => 11,
                        'md_transaction.isprocessed'              => "N"
                    ]);
                    $useNov = 0;

                    //* Des
                    $des = $this->model->getBalance([
                        'md_transaction.md_employee_id'           => $value->md_employee_id,
                        'MONTH(md_transaction.transactiondate)'   => 12,
                        'md_transaction.isprocessed'              => "N"
                    ]);
                    $useDes = 0;

                    if (!is_null($jan->amount))
                        $useJan = intval($jan->amount);

                    $row[] = $useJan;

                    if (!is_null($feb->amount))
                        $useFeb = intval($feb->amount);

                    $row[] = $useFeb;

                    if (!is_null($mar->amount))
                        $useMar = intval($mar->amount);

                    $row[] = $useMar;

                    if (!is_null($apr->amount))
                        $useApr = intval($apr->amount);

                    $row[] = $useApr;

                    if (!is_null($mei->amount))
                        $useMei = intval($mei->amount);

                    $row[] = $useMei;

                    if (!is_null($jun->amount))
                        $useJun = intval($jun->amount);

                    $row[] = $useJun;

                    if (!is_null($jul->amount))
                        $useJul = intval($jul->amount);

                    $row[] = $useJul;

                    if (!is_null($ags->amount))
                        $useAgs = intval($ags->amount);

                    $row[] = $useAgs;

                    if (!is_null($sep->amount))
                        $useSep = intval($sep->amount);

                    $row[] = $useSep;

                    if (!is_null($okt->amount))
                        $useOkt = intval($okt->amount);

                    $row[] = $useOkt;

                    if (!is_null($nov->amount))
                        $useNov = intval($nov->amount);

                    $row[] = $useNov;

                    if (!is_null($des->amount))
                        $useDes = intval($des->amount);

                    $row[] = $useDes;

                    $totalSaldo = $value->amount + $useJan + $useFeb + $useMar + $useApr + $useMei + $useJun + $useJul + $useSep + $useOkt + $useNov + $useDes;
                    $row[] = $totalSaldo;
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
}
