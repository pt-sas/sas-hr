<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Transaction extends Model
{
    protected $table                = 'md_transaction';
    protected $primaryKey           = 'md_transaction_id';
    protected $allowedFields        = [
        'transactiondate',
        'transactiontype',
        'year',
        'record_id',
        'table',
        'amount',
        'md_employee_id',
        'isprocessed',
        'description',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Transaction';
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
