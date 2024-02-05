<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Mail extends Model
{
    protected $table            = 'sys_email';
    protected $primaryKey       = 'sys_email_id';
    protected $allowedFields    = [
        'protocol',
        'smtphost',
        'smtpport',
        'smtpcrypto',
        'smtpuser',
        'smtppassword',
        'requestemail',
        'isactive',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps    = true;
    protected $returnType       = 'App\Entities\Mail';
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
