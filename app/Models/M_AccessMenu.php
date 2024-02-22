<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_AccessMenu extends Model
{
    protected $table      = 'sys_access_menu';
    protected $primaryKey = 'sys_access_menu_id';
    protected $allowedFields = [
        'sys_role_id',
        'sys_menu_id',
        'sys_submenu_id',
        'isview',
        'iscreate',
        'isupdate',
        'isdelete',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps = true;
    protected $returnType = 'App\Entities\AccessMenu';
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

    public function create($post)
    {
        foreach (json_decode($post['roles']) as $value) :
            $data = [];
            $data['sys_role_id'] = $post['sys_role_id'];
            $data['isactive'] = setCheckbox(isset($post['isactive']));

            if ($value->menu === 'parent') {
                $data['sys_menu_id'] = $value->menu_id;
                $data['sys_submenu_id'] = 0;
            } else {
                $data['sys_menu_id'] = $value->menu;
                $data['sys_submenu_id'] = $value->menu_id;
            }

            $data['isview'] = $value->view;
            $data['iscreate'] = $value->create;
            $data['isupdate'] = $value->update;
            $data['isdelete'] = $value->delete;

            if (isset($post['id']) && !empty($value->access_id)) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                $data['updated_by'] = session()->get('sys_user_id');

                $result = $this->builder->where('sys_access_menu_id', $value->access_id)->update($data);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['created_by'] = session()->get('sys_user_id');
                $data['updated_at'] = date('Y-m-d H:i:s');
                $data['updated_by'] = session()->get('sys_user_id');

                $result = $this->builder->insert($data);
            }
        endforeach;

        return $result;
    }

    public function getAccess($sys_user_id)
    {
        $mBranchAccess = new M_BranchAccess($this->request);
        $mDivAccess = new M_DivAccess($this->request);

        /**
         * Branch
         */
        $branch = $mBranchAccess->where([
            "sys_user_id"   => $sys_user_id,
            "isactive"      => "Y"
        ])->findAll();

        $arrBranch = [];

        if ($branch)
            foreach ($branch as $row) :
                if (!empty($row->getBranchId()))
                    $arrBranch['branch'][] = $row->getBranchId();
            endforeach;

        /**
         * Division
         */
        $div = $mDivAccess->where([
            "sys_user_id"   => $sys_user_id,
            "isactive"      => "Y"
        ])->findAll();

        $arrDiv = [];

        if ($div)
            foreach ($div as $row) :
                if (!empty($row->getDivId()))
                    $arrDiv['division'][] = $row->getDivId();
            endforeach;

        $arr = array_merge($arrBranch, $arrDiv);

        return $arr;
    }
}
