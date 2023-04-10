<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\M_Role;
use App\Models\M_AccessMenu;

class M_Submenu extends Model
{
    protected $table      = 'sys_submenu';
    protected $primaryKey = 'sys_submenu_id';
    protected $allowedFields = [
        'name',
        'sequence',
        'url',
        'status',
        'sys_menu_id',
        'isactive',
        'created_by',
        'updated_by',
        'action'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Submenu';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = ['createAccessRole'];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = [];
    protected $beforeDelete         = [];
    protected $afterDelete          = ['deleteAccessRole'];
    protected $column_order = [
        '', // Hide column
        '', // Number column
        'sys_submenu.name',
        'sys_menu.name',
        'sys_submenu.url',
        'sys_submenu.sequence',
        'sys_submenu.isactive'
    ];
    protected $column_search = [
        'sys_submenu.name',
        'sys_menu.name',
        'sys_submenu.url',
        'sys_submenu.sequence',
        'sys_submenu.isactive'
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

    public function getSelect()
    {
        $sql = $this->table . '.*,
                    sys_menu.name as parent';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('sys_menu', 'sys_menu.sys_menu_id = ' . $this->table . '.sys_menu_id', 'left')
        ];

        return $sql;
    }

    private function setDataJoin($tableJoin, $columnJoin, $typeJoin = "inner")
    {
        return [
            "tableJoin" => $tableJoin,
            "columnJoin" => $columnJoin,
            "typeJoin" => $typeJoin
        ];
    }

    public function detail($field = null, $where = null)
    {
        $this->builder->select(
            $this->table . '.*,
            sys_menu.name as parent'
        );

        $this->builder->join('sys_menu', 'sys_menu.sys_menu_id = ' . $this->table . '.sys_menu_id', 'left');

        if (!empty($where))
            $this->builder->where($field, $where);

        $query = $this->builder->get();
        return $query;
    }

    public function createAccessRole(array $rows)
    {
        $role = new M_Role($this->request);
        $access = new M_AccessMenu($this->request);
        $entity = new \App\Entities\AccessMenu();

        $post = $this->request->getVar();

        $list = $role->where([
            'isactive'  => 'Y',
            'ismanual'  => 'N'
        ])->findAll();

        foreach ($list as $key => $val) :
            $entity->setRoleId($val->getRoleId());
            $entity->setMenuId($post['sys_menu_id']);
            $entity->setSubmenuId($rows['id']);
            $entity->setIsView('Y');
            $entity->setIsCreate('Y');
            $entity->setIsUpdate('Y');
            $entity->setIsDelete('Y');
            $entity->setCreatedBy(session()->get('sys_user_id'));
            $entity->setUpdatedBy(session()->get('sys_user_id'));

            $access->save($entity);
        endforeach;
    }

    public function deleteAccessRole(array $rows)
    {
        $access = new M_AccessMenu($this->request);
        $access->where($this->primaryKey, $rows['id'])->delete();
    }
}
