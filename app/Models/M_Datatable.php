<?php

namespace App\Models;

use CodeIgniter\Model;

use CodeIgniter\HTTP\RequestInterface;

class M_Datatable extends Model
{
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
                if (gettype($value) === "string") {
                    $this->builder->where($key, $value);
                }

                if (gettype($value) === "array" && !isset($value['condition'])) {
                    $this->builder->whereIn($key, $value);
                }

                if (gettype($value) === "array" && isset($value['condition']) && $value['condition'] === "OR") {
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
            $this->builder->orderBy(key($order), $order[key($order)]);
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
                            $datetime =  urldecode($value['value']);
                            $date = explode(" - ", $datetime);

                            $this->builder->where('DATE(' . $table . '.' . $value['name'] . ')' . ' >= "' . date("Y-m-d", strtotime($date[0])) . '" AND ' . 'DATE(' . $table . '.' . $value['name'] . ')' . ' <= "' . date("Y-m-d", strtotime($date[1])) . '"');
                        }

                        if ($field->name === $value['name'] && $field->type !== 'timestamp') {
                            if (isset($value['type']) && $value['type'] === 'select-multiple') {
                                $this->builder->whereIn($table . '.' . $value['name'] . '', $value['value']);
                            } else {
                                $this->builder->where($table . '.' . $value['name'] . '', $value['value']);
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
                                        $datetime =  urldecode($value['value']);
                                        $date = explode(" - ", $datetime);

                                        $this->builder->where($tableJoin . '.' . $value['name'] . ' >= "' . date("Y-m-d", strtotime($date[0])) . '" AND ' . $tableJoin . '.' . $value['name'] . ' <= "' . date("Y-m-d", strtotime($date[1])) . '"');
                                    }

                                    if ($field->name === $value['name'] && $field->type !== 'timestamp') {
                                        if (isset($value['type']) && $value['type'] === 'select-multiple') {
                                            $this->builder->whereIn($tableJoin . '.' . $value['name'] . '', $value['value']);
                                        } else {
                                            $this->builder->where($tableJoin . '.' . $value['name'] . '', $value['value']);
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
}
