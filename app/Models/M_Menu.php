<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\M_Role;
use App\Models\M_AccessMenu;

class M_Menu extends Model
{
    protected $table      = 'sys_menu';
    protected $primaryKey = 'sys_menu_id';
    protected $allowedFields = [
        'name',
        'sequence',
        'url',
        'icon',
        'status',
        'isactive',
        'created_by',
        'updated_by',
        'action'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Menu';
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
        'name',
        'url',
        'sequence',
        'icon',
        'isactive'
    ];
    protected $column_search = [
        'name',
        'url',
        'sequence',
        'icon',
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
            $entity->setMenuId($rows['id']);
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

    public function getMenu()
    {
        $submenu = new M_Submenu($this->request);

        $dataMenu = $this->where('isactive', 'Y')
            ->orderBy('sequence', 'ASC')
            ->findAll();

        $arrMenu = [];

        foreach ($dataMenu as $row) :
            $menu_id = $row->sys_menu_id;

            $data = $submenu->where([
                'isactive'          => 'Y',
                $this->primaryKey   => $menu_id
            ])->orderBy('sequence', 'ASC')
                ->findAll();

            if ($data) {
                foreach ($data as $row2) :
                    $arrMenu[] = $row2->name;
                endforeach;
            } else {
                $arrMenu[] = $row->name;
            }
        endforeach;

        sort($arrMenu);

        return $arrMenu;
    }

    public function getMenuUrl()
    {
        $submenu = new M_Submenu($this->request);

        $dataMenu = $this->where('isactive', 'Y')
            ->orderBy('sequence', 'ASC')
            ->findAll();

        $arrMenu = [];

        foreach ($dataMenu as $row) :
            $result = [];
            $menu_id = $row->sys_menu_id;

            $data = $submenu->where([
                'isactive'          => 'Y',
                $this->primaryKey   => $menu_id
            ])->orderBy('sequence', 'ASC')
                ->findAll();

            if ($data) {
                foreach ($data as $row2) :
                    $result['name'] = $row2->name;
                    $result['url'] = $row2->url;
                    $arrMenu[] = $result;
                endforeach;
            } else {
                $result['name'] = $row->name;
                $result['url'] = $row->url;
                $arrMenu[] = $result;
            }
        endforeach;

        return $arrMenu;
    }

    public function getMenuBy($url)
    {
        $submenu = new M_Submenu($this->request);

        // Check uri segment from submenu
        $sub = $submenu->where('url', $url)->first();

        // Check uri segment from main menu
        $parent = $this->where('url', $url)->first();

        $result = "No Menu";

        if ($sub) {
            $result = $sub->name;
        } else if ($parent) {
            $result = $parent->name;
        }

        return $result;
    }
}
