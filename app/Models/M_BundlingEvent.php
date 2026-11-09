<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_BundlingEvent extends Model
{
    protected $table                = 'trx_bundling_event';
    protected $primaryKey           = 'trx_bundling_event_id';
    protected $allowedFields        = [
        'trx_bundling_participant_id',
        'date',
        'starttime',
        'endtime',
        'time',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\BundlingEvent';
    protected $afterInsert          = [];
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
