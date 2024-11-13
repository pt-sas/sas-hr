<?php

namespace App\Models;

use CodeIgniter\Model;

use CodeIgniter\HTTP\RequestInterface;

class M_Datatable extends Model
{
    protected $table            = '';
    protected $primaryKey       = '';
    protected $allowedFields    = [];
    protected $useTimestamps    = true;
    protected $returnType       = 'App\Entities\DataTable';
    protected $allowCallbacks   = true;
    protected $beforeInsert     = [];
    protected $afterInsert      = [];
    protected $beforeUpdate     = [];
    protected $afterUpdate      = ['afterUpdate'];
    protected $beforeDelete     = [];
    protected $afterDelete      = [];
    protected $request;
    protected $db;
    protected $builder;

    public function __construct(RequestInterface $request)
    {
        parent::__construct();
        $this->db = db_connect();
        $this->request = $request;
    }

    private function getDatatablesQuery($table, $select, $column_order, $order, $column_search, $join = [], $where = [])
    {
        $post = $this->request->getVar();
        $this->builder = $this->db->table($table);

        if (is_array($select) && count($select) > 0)
            $select;
        else
            $this->builder->select($select);

        if (count($join) > 0)
            $this->setJoin($join);

        if (count($where) > 0) {
            foreach ($where as $key => $value) :
                if (is_numeric($key)) {
                    $this->builder->where($value);
                }

                if (is_string($key) && (is_string($value) || is_integer($value))) {
                    $this->builder->where($key, $value);
                }

                if (is_array($value) && !isset($value['condition'])) {
                    $this->builder->whereIn($key, $value['value']);
                }

                if (is_array($value) && isset($value['condition']) && $value['condition'] === "OR") {
                    $this->builder->orWhere($key, $value['value']);
                }
            endforeach;
        }

        if (isset($post['form']))
            $this->filterDatatable($table, $post, $join);

        $i = 0;
        foreach ($column_search as $item) :
            if ($this->request->getPost('search')['value']) {
                if ($i === 0) {
                    $this->builder->groupStart();
                    $this->builder->like($item, $this->request->getPost('search')['value']);
                } else {
                    $this->builder->orLike($item, $this->request->getPost('search')['value']);
                }
                if (count($column_search) - 1 == $i)
                    $this->builder->groupEnd();
            }
            $i++;
        endforeach;

        if ($this->request->getPost('order')) {
            $this->builder->orderBy($column_order[$this->request->getPost('order')['0']['column']], $this->request->getPost('order')['0']['dir']);
        } else if (isset($order) && !empty($order)) {
            foreach ($order as $column => $param) :
                $this->builder->orderBy($column, $param);
            endforeach;
        }
    }

    public function getDatatables($table, $select, $column_order, $order, $column_search, $join = [], $where = [])
    {
        $this->getDatatablesQuery($table, $select, $column_order, $order, $column_search, $join, $where);

        if ($this->request->getPost('length') != -1)
            $this->builder->limit($this->request->getPost('length'), $this->request->getPost('start'));
        $query = $this->builder->get();
        return $query->getResult();
    }

    public function countAll($table, $select, $column_order, $order, $column_search, $join = [], $where = [])
    {
        if (count($join) > 0 || count($where) > 0)
            $this->getDatatablesQuery($table, $select, $column_order, $order, $column_search, $join, $where);
        else
            $this->builder = $this->db->table($table);

        return $this->builder->countAllResults();
    }

    public function countFiltered($table, $select, $column_order, $order, $column_search, $join = [], $where = [])
    {
        $this->getDatatablesQuery($table, $select, $column_order, $order, $column_search, $join, $where);
        return $this->builder->countAllResults();
    }

    public function filterDatatable($table, $post, $joinTable)
    {
        foreach ($post['form'] as $value) :
            if (!empty($value['value'])) {
                //? Cek kolom ditable
                if ($this->db->fieldExists($value['name'], $table)) {
                    $fields = $this->db->getFieldData($table);

                    foreach ($fields as $field) :
                        if ($field->name === $value['name'] && $field->type === 'timestamp') {
                            $datetime = urldecode($value['value']);
                            $date = explode(" - ", $datetime);

                            $this->builder->where('DATE(' . $table . '.' . $value['name'] . ')' . ' >= "' . date("Y-m-d", strtotime($date[0])) . '" AND ' . 'DATE(' . $table . '.' . $value['name'] . ')' . ' <= "' . date("Y-m-d", strtotime($date[1])) . '"');
                        }

                        if ($field->name === $value['name'] && $field->type !== 'timestamp') {
                            if (isset($value['type']) && $value['type'] === 'select-multiple') {
                                $this->builder->whereIn($table . '.' . $value['name'] . '', $value['value']);
                            } else {
                                $datetime = urldecode($value['value']);
                                $date = explode(" - ", $datetime);

                                if (strpos($datetime, " - ") > 0) {
                                    $this->builder->where('DATE(' . $table . '.' . $value['name'] . ')' . ' >= "' . date("Y-m-d", strtotime($date[0])) . '" AND ' . 'DATE(' . $table . '.' . $value['name'] . ')' . ' <= "' . date("Y-m-d", strtotime($date[1])) . '"');
                                } else {
                                    $this->builder->where($table . '.' . $value['name'] . '', $value['value']);
                                }
                            }
                        }
                    endforeach;
                } else {
                    if (!empty($joinTable)) {
                        foreach ($joinTable as $row) :
                            $tableJoin = $row['tableJoin'];

                            //? Cek data join exist alias
                            if (strpos($tableJoin, " ")) {
                                $tableJoin = explode(" ", $tableJoin);
                                $tableJoin = $tableJoin[0];
                            }

                            if ($this->db->fieldExists($value['name'], $tableJoin)) {
                                $fields = $this->db->getFieldData($tableJoin);

                                foreach ($fields as $field) :
                                    if ($field->name === $value['name'] && $field->type === 'timestamp') {
                                        $datetime = urldecode($value['value']);
                                        $date = explode(" - ", $datetime);

                                        $this->builder->where('DATE(' . $tableJoin . '.' . $value['name'] . ')' . ' >= "' . date("Y-m-d", strtotime($date[0])) . '" AND ' . 'DATE(' . $tableJoin . '.' . $value['name'] . ')' . ' <= "' . date("Y-m-d", strtotime($date[1])) . '"');
                                    }

                                    if ($field->name === $value['name'] && $field->type !== 'timestamp') {

                                        if (isset($value['type']) && $value['type'] === 'select-multiple') {
                                            $this->builder->whereIn($tableJoin . '.' . $value['name'] . '', $value['value']);
                                        } else {
                                            $datetime = urldecode($value['value']);
                                            $date = explode(" - ", $datetime);

                                            if (strpos($datetime, " - ") > 0) {
                                                $this->builder->where($tableJoin . '.' . $value['name'] . '', $value['value']);
                                            } else {
                                                $this->builder->where('DATE(' . $tableJoin . '.' . $value['name'] . ')' . ' >= "' . date("Y-m-d", strtotime($date[0])) . '" AND ' . 'DATE(' . $tableJoin . '.' . $value['name'] . ')' . ' <= "' . date("Y-m-d", strtotime($date[1])) . '"');
                                            }
                                        }
                                    }
                                endforeach;
                            }
                        endforeach;
                    }
                }
            }
        endforeach;
    }

    private function setJoin($data)
    {
        foreach ($data as $row) :
            $tableJoin = $row['tableJoin'];
            $columnJoin = $row['columnJoin'];
            $typeJoin = $row['typeJoin'];

            $this->builder->join($tableJoin, $columnJoin, $typeJoin);
        endforeach;
    }

    public function initDataTable($table)
    {
        try {
            if ($this->db->tableExists($table)) {
                $this->table = $table;

                $fields = $this->db->getFieldData($this->table);

                foreach ($fields as $field) {
                    if ($field->primary_key == 1)
                        $this->primaryKey = $field->name;
                    else
                        $this->allowedFields[] = $field->name;
                }

                return $this;
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function afterUpdate(array $rows)
    {
        try {
            if ($this->table === "trx_absent") {
                $model = new M_Absent($this->request);
                $model->doAfterUpdate($rows);
            }

            if ($this->table === "trx_absent_detail") {
                $model = new M_AbsentDetail($this->request);
                $model->doAfterUpdate($rows);
            }

            if ($this->table === "trx_employee_departure") {
                $model = new M_EmployeeDeparture($this->request);
                $model->doAfterUpdate($rows);
            }

            if ($this->table === "trx_interview") {
                $model = new M_Interview($this->request);
                $model->doAfterUpdate($rows);
            }

            if ($this->table === "trx_probation") {
                $model = new M_Probation($this->request);
                $model->doAfterUpdate($rows);
            }

            if ($this->table === "trx_employee_allocation") {
                $model = new M_EmployeeAllocation($this->request);
                $model->doAfterUpdate($rows);
            }

            if ($this->table === "trx_assignment") {
                $model = new M_Assignment($this->request);
                $model->doAfterUpdate($rows);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
