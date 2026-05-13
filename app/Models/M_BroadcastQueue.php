<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_BroadcastQueue extends Model
{
    protected $table            = 'trx_broadcast_queue';
    protected $primaryKey       = 'trx_broadcast_queue_id';
    protected $allowedFields    = [
        'trx_broadcast_id',
        'status',
        'starttime',
        'endtime',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps    = true;
    protected $returnType       = 'App\Entities\BroadcastQueue';
    protected $request;
    protected $db;
    protected $builder;

    public function __construct(RequestInterface $request = null)
    {
        parent::__construct();
        $this->db = db_connect();
        $this->request = $request;
        $this->builder = $this->db->table($this->table);
    }
}
