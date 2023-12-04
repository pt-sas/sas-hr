<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Role extends Model
{
    protected $table      = 'sys_role';
    protected $primaryKey = 'sys_role_id';
    protected $allowedFields = [
        'name',
        'description',
        'isactive',
        'ismanual',
        'iscanexport',
        'iscanreport',
        'isallowmultipleprint',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Role';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = ['createAccessRole'];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = ['createAccessRole'];
    protected $beforeDelete         = [];
    protected $afterDelete          = ['deleteAccessRole'];
    protected $column_order = [
        '', // Hide column
        '', // Number column
        'name',
        'description',
        'isactive'
    ];
    protected $column_search = [
        'name',
        'description',
        'isactive'
    ];
    protected $order = ['name' => 'ASC'];
    protected $request;
    protected $db;
    protected $builder;

    public function __construct(RequestInterface $request)
    {
        parent::__construct();
        $this->db = db_connect();
        $this->request = $request;
        $this->builder = $this->db->table($this->table);
    }

    public function detail($param = [], $field = null, $where = null)
    {
        $this->builder->select($this->table . '.*,
                    am.sys_access_menu_id,
                    am.sys_menu_id,
                    am.sys_submenu_id,
                    am.isview,
                    am.iscreate,
                    am.isupdate,
                    am.isdelete');
        $this->builder->join('sys_access_menu am', 'am.sys_role_id = ' . $this->table . '.sys_role_id', 'left');

        if (count($param) > 0)
            $this->builder->where($param);

        if (!empty($where))
            $this->builder->where($field, $where);

        $query = $this->builder->get();
        return $query;
    }

    public function createAccessRole(array $rows)
    {
        $accessMenu = new M_AccessMenu($this->request);

        $post = $this->request->getVar();

        if (isset($post['roles'])) {
            $post['sys_role_id'] = $rows['id'];

            $accessMenu->create($post);
        }
    }

    public function deleteAccessRole(array $rows)
    {
        $accessMenu = new M_AccessMenu($this->request);
        $userRole = new M_UserRole($this->request);

        $accessMenu->where($this->primaryKey, $rows['id'])->delete();

        $userRole->where($this->primaryKey, $rows['id'])->delete();
    }
}
