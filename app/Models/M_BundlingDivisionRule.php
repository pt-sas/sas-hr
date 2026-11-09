<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_BundlingDivisionRule extends Model
{
    protected $table                = 'trx_bundling_division_rule';
    protected $primaryKey           = 'trx_bundling_division_rule_id';

    protected $allowedFields        = [
        "trx_master_bundling_id",
        'md_division_id',
        'maximum_submission',
        'created_by',
        'updated_by',
    ];

    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\BundlingDivisionRule';

    protected $request;
    protected $db;
    protected $builder;

    public function __construct(RequestInterface $request)
    {
        parent::__construct();

        $this->db      = db_connect();
        $this->request = $request;
        $this->builder = $this->db->table($this->table);
    }
}
