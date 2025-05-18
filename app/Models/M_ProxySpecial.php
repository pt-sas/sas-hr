<?php

namespace App\Models;

use App\Controllers\Backend\EmpEducation;
use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_ProxySpecial extends Model
{
    protected $table                = 'trx_proxy_special';
    protected $primaryKey           = 'trx_proxy_special_id';
    protected $allowedFields        = [
        'documentno',
        'sys_user_from',
        'sys_user_to',
        'submissiontype',
        'submissiondate',
        'startdate',
        'enddate',
        'reason',
        'ispermanent',
        'docstatus',
        'isapproved',
        'receiveddate',
        'approveddate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\ProxySpecial';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = [];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = ['doAfterUpdate'];
    protected $beforeDelete         = [];
    protected $afterDelete          = [];
    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'trx_proxy_special.documentno',
        'uf.name',
        'ut.name',
        'trx_proxy_special.submissiondate',
        'trx_proxy_special.startdate',
        'trx_proxy_special.enddate',
        'trx_proxy_special.approveddate',
        'trx_proxy_special.reason',
        'trx_proxy_special.docstatus',
        'uc.name'
    ];
    protected $column_search        = [
        'trx_proxy_special.documentno',
        'uf.name',
        'ut.name',
        'trx_proxy_special.submissiondate',
        'trx_proxy_special.startdate',
        'trx_proxy_special.enddate',
        'trx_proxy_special.approveddate',
        'trx_proxy_special.reason',
        'trx_proxy_special.docstatus',
        'uc.name'
    ];
    protected $order                = ['documentno' => 'ASC'];
    protected $request;
    protected $db;
    protected $builder;

    /** Pengajuan Tugas Kantor */
    protected $Pengajuan_Proxy_Khusus = 100025;

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
                uf.name as user_from,
                ut.name as user_to,
                uc.name as createdby';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('sys_user uf', 'uf.sys_user_id = ' . $this->table . '.sys_user_from', 'left'),
            $this->setDataJoin('sys_user ut', 'ut.sys_user_id = ' . $this->table . '.sys_user_to', 'left'),
            $this->setDataJoin('sys_user uc', 'uc.sys_user_id = ' . $this->table . '.created_by', 'left'),
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

    public function getInvNumber($field, $where, $post)
    {
        $year = date("Y", strtotime($post['submissiondate']));
        $month = date("m", strtotime($post['submissiondate']));

        $this->builder->select('MAX(RIGHT(documentno,4)) AS documentno');
        $this->builder->where("DATE_FORMAT(submissiondate, '%m')", $month);
        $this->builder->where($field, $where);
        $sql = $this->builder->get();

        $code = "";
        if ($sql->getNumRows() > 0) {
            foreach ($sql->getResult() as $row) {
                $doc = ((int)$row->documentno + 1);
                $code = sprintf("%04s", $doc);
            }
        } else {
            $code = "0001";
        }
        $first = $post['necessary'];

        $prefix = $first . "/" . $year . "/" . $month . "/" . $code;

        return $prefix;
    }


    public function insertProxy($userFrom, $userTo, $role, $isSpecial = false, $ref_id = null, $isPermanent = null)
    {
        $mUserRole = new M_UserRole($this->request);
        $mProxySwitch = new M_ProxySwitching($this->request);
        $mChangeLog = new M_ChangeLog($this->request);

        $proxyType = $isSpecial ? 'special' : 'reguler';

        $user_by = 100000;

        //TODO : Checking is Role already exists in user switching
        $isRoleExists = $mUserRole->where(['sys_user_id' => $userTo, 'sys_role_id' => $role])->first();

        if (!$isRoleExists) {
            //TODO : Inserting role into user switching
            $entity = new \App\Entities\UserRole();

            $entity->setRoleId($role);
            $entity->setUserId($userTo);
            $entity->setUpdatedBy($user_by);
            $entity->setCreatedBy($user_by);
            $result = $mUserRole->save($entity);

            //TODO : if inserting success then inserting to table Proxy Switching
            if ($result) {
                $entity = new \App\Entities\ProxySwitching();

                $entity->setProxyType($proxyType);
                $entity->setRoleId($role);
                $entity->setUserFrom($userFrom);
                $entity->setUserTo($userTo);
                $entity->setStartDate(date('Y-m-d H:i:s'));

                if ($isPermanent === "Y") {
                    $entity->setState('CO');
                } else {
                    $entity->setState('IP');
                }

                $entity->setUpdatedBy($user_by);
                $entity->setCreatedBy($user_by);

                if ($isSpecial === true) {
                    $entity->setProxySpecialDetailId($ref_id);
                }

                $mProxySwitch->save($entity);
            }
        }

        // TODO : if isPermanent is Y then delete Proxy from old user
        if ($isPermanent === "Y") {
            $oldUserRole = $mUserRole->where(['sys_user_id' => $userFrom, 'sys_role_id' => $role])->first();

            if ($oldUserRole) {
                $mUserRole->delete($oldUserRole->sys_user_role_id);
                $mChangeLog->insertLog($mUserRole->table, $mUserRole->primaryKey, $oldUserRole->sys_user_role_id, $oldUserRole->sys_user_role_id, null, "D", "Delete Old User Proxy");
            }
        }
    }

    public function doAfterUpdate(array $rows)
    {
        $mProxySpecialDetail = new M_ProxySpecialDetail($this->request);

        $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];
        $sql = $this->find($ID);
        $line = $mProxySpecialDetail->where($this->primaryKey, $ID)->findAll();
        $today = date('Y-m-d');
        $date = date('Y-m-d', strtotime($sql->startdate));

        if ($sql->docstatus === "CO" && !empty($line)) {
            if ($date === $today) {
                foreach ($line as $value) {
                    $this->insertProxy($sql->sys_user_from, $sql->sys_user_to, $value->sys_role_id, true, $value->trx_proxy_special_detail_id, $sql->ispermanent);
                }
            }
        }
    }
}