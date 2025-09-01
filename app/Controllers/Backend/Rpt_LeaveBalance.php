<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Employee;
use App\Models\M_LeaveBalance;
use App\Models\M_AccessMenu;
use App\Models\M_Transaction;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
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
        $data = [
            'year'          => date('Y')
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
                $select = $this->model->getSelectDetail();
                $join = $this->model->getJoinDetail();
                $order = $this->request->getPost('columns');
                $sort = ['md_transaction.created_at' => 'ASC'];
                $search = $this->request->getPost('search');

                foreach ($post['form'] as $value) {
                    if (!empty($value['value'])) {
                        if ($value['name'] === "year") {
                            $year = $value['value'];
                        }

                        if ($value['name'] === "md_employee_id") {
                            $md_employee_id = $value['value'];
                        }
                    }
                }

                $where['md_transaction.md_employee_id'] = $md_employee_id;
                $where['md_transaction.year'] = $year;

                $number = $this->request->getPost('start');
                $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join);

                // $beginingBalance = $this->model->where(['md_employee_id' => $md_employee_id, 'year' => $year, 'transactiontype' => 'C+', 'isprocessed' => 'Y'])->first();
                // logMessage($beginingBalance);

                $available = 0;
                $saldo = 0;
                foreach ($list as $value) :
                    $row = [];

                    $number++;

                    if ($value->amount != 0) {
                        $saldo += $value->amount;
                    }

                    if ($value->reserved_amount != 0 && $value->amount == 0) {
                        $available -= $value->reserved_amount;
                    }

                    if ($value->reserved_amount == 0 && $value->amount != 0) {
                        $available += $value->amount;
                    }

                    $row[] = $number;
                    $row[] = $value->employee_fullname;
                    $row[] = $value->documentno;
                    $row[] = format_dmy($value->transactiondate, "-");
                    $row[] = intval($value->amount);
                    $row[] = intval($value->reserved_amount);
                    $row[] = $available;
                    $row[] = $saldo;
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
        $mAccess      = new M_AccessMenu($this->request);
        $mEmpBranch   = new M_EmpBranch($this->request);
        $mEmployee    = new M_Employee($this->request);

        $userId = $this->session->get('sys_user_id');
        $empId  = $this->session->get('md_employee_id');

        $roleKACAB = $this->access->getUserRoleName($userId, 'W_Emp_KACAB');
        $roleEmp   = $this->access->getUserRoleName($userId, 'W_Emp_All_Data');

        $arrAccess     = $mAccess->getAccess($userId);
        $empDelegation = $mEmployee->getEmpDelegation($userId);

        $arrEmployee = array_unique(array_merge(
            $mEmployee->getChartEmployee($empId),
            $empDelegation ?? []
        ));
        $arrEmpStr = implode(',', $arrEmployee);

        $arrEmpBranch = array_column(
            $mEmpBranch->select('md_branch_id')->where($mEmployee->primaryKey, $empId)->findAll(),
            'md_branch_id'
        );

        $whereEmp = "md_employee_id IN ($empId)";

        if ($roleKACAB) {
            $empBranch = $mEmployee->getEmployeeBased($arrEmpBranch);
            $whereEmp  = "md_employee_id IN (" . implode(',', $empBranch) . ")";
        } elseif ($arrAccess && isset($arrAccess['branch'], $arrAccess['division'])) {
            $arrEmpBased = $mEmployee->getEmployeeBased($arrAccess['branch'], $arrAccess['division']);
            if (!empty($empDelegation)) {
                $arrEmpBased = array_unique(array_merge($arrEmpBased, $empDelegation));
            }

            if ($roleEmp && !empty($empId)) {
                $arrMerge  = implode(',', array_unique(array_merge($arrEmpBased, $arrEmployee)));
                $whereEmp  = "md_employee_id IN ($arrMerge)";
            } elseif ($roleEmp && empty($empId)) {
                $whereEmp  = "md_employee_id IN (" . implode(',', $arrEmpBased) . ")";
            } elseif (!$roleEmp && !empty($empId)) {
                $whereEmp  = "md_employee_id IN ($arrEmpStr)";
            }
        } elseif (!empty($empId)) {
            $whereEmp = "md_employee_id IN ($arrEmpStr)";
        }

        $whereEmp .= " AND md_status_id NOT IN (100002,100003,100004,100005,100006,100007,100008)";

        $data = [
            'year'          => date('Y'),
            'ref_employee' =>  $mEmployee->getEmployeeValue($whereEmp)->getResult(),
        ];

        return $this->template->render('report/leavebalancesummary/v_leavebalancesummary', $data);
    }

    public function showAllSummary()
    {
        $mLeaveBalance = new M_LeaveBalance($this->request);
        $mEmployee = new M_Employee($this->request);
        $mAccess = new M_AccessMenu($this->request);

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

                /**
                 * Mendapatkan Hak Akses Karyawan
                 */
                $roleEmp = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_All_Data');
                $empDelegation = $mEmployee->getEmpDelegation($this->session->get('sys_user_id'));
                $arrAccess = $mAccess->getAccess($this->session->get("sys_user_id"));
                $arrEmployee = $mEmployee->getChartEmployee($this->session->get('md_employee_id'));

                if (!empty($empDelegation)) {
                    $arrEmployee = array_unique(array_merge($arrEmployee, $empDelegation));
                }

                if ($arrAccess && isset($arrAccess["branch"]) && isset($arrAccess["division"])) {
                    $arrBranch = $arrAccess["branch"];
                    $arrDiv = $arrAccess["division"];

                    $arrEmpBased = $mEmployee->getEmployeeBased($arrBranch, $arrDiv);

                    if (!empty($empDelegation)) {
                        $arrEmpBased = array_unique(array_merge($arrEmpBased, $empDelegation));
                    }

                    if ($roleEmp && !empty($this->session->get('md_employee_id'))) {
                        $arrMerge = array_unique(array_merge($arrEmpBased, $arrEmployee));

                        $where['v_summary_leavebalance.md_employee_id'] = [
                            'value'     => $arrMerge
                        ];
                    } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                        $where['v_summary_leavebalance.md_employee_id'] = [
                            'value'     => $arrEmployee
                        ];
                    } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                        $where['v_summary_leavebalance.md_employee_id'] = [
                            'value'     => $arrEmpBased
                        ];
                    } else {
                        $where['v_summary_leavebalance.md_employee_id'] = $this->session->get('md_employee_id');
                    }
                } else if (!empty($this->session->get('md_employee_id'))) {
                    $where['v_summary_leavebalance.md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else {
                    $where['v_summary_leavebalance.md_employee_id'] = $this->session->get('md_employee_id');
                }

                foreach ($post['form'] as $value) {
                    if (!empty($value['value'])) {
                        if ($value['name'] === "year") {
                            $year = $value['value'];
                        }

                        if ($value['name'] === "md_employee_id") {
                            $emp_id = $value['value'];

                            $where['v_summary_leavebalance.md_employee_id'] = [
                                'value'     => $emp_id
                            ];
                        }
                    }
                }

                $prevYear = $year - 1;

                $number = $this->request->getPost('start');
                $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

                foreach ($list as $value) :
                    $row = [];

                    $number++;

                    $balance = $mLeaveBalance->where([
                        'year'              => $prevYear,
                        'md_employee_id'    => $value->md_employee_id
                    ])->first();

                    $row[] = $number;
                    $row[] = $value->employee_fullname;
                    $row[] = $value->branch;
                    $row[] = $value->divisi;
                    $row[] = $balance ? intval($balance->balance_amount) : 0;
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
