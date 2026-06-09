<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_ImportAttendanceDetail extends Model
{
    protected $table                = 'trx_import_attendance_detail';
    protected $primaryKey           = 'trx_import_attendance_detail_id';
    protected $allowedFields        = [
        'trx_import_attendance_detail_id',
        'md_employee_id',
        'nik',
        'clock_in',
        'clock_out',
        'isinserted',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\ImportAttendance';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = [];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = [];
    protected $beforeDelete         = [];
    protected $afterDelete          = [];
    protected $order                = [];
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
}
