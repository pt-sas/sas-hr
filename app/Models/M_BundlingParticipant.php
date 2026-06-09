<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_BundlingParticipant extends Model
{
    protected $table                = 'trx_bundling_participant';
    protected $primaryKey           = 'trx_bundling_participant_id';
    protected $allowedFields        = [
        'trx_bundling_id',
        'md_employee_id',
        'total_time',
        'total_amount'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\BundlingParticipant';

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

    public function getEmployeeList(int $trx_bundling_id)
    {
        $data = $this->distinct()->select("md_employee_id")->where('trx_bundling_id', $trx_bundling_id)->findAll();
        $empList = array_column($data, 'md_employee_id');

        return $empList;
    }
}
