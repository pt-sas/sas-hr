<?php

namespace App\Models;

use CodeIgniter\Model;

class M_RefreshToken extends Model
{
    protected $table                = 'sys_refresh_token';
    protected $primaryKey           = 'sys_refresh_token_id';
    protected $allowedFields        = [
        'sys_user_id',
        'user_agent',
        'token',
        'expired_date',
        'isrevoked',
        'isactive',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType = '\App\Entities\RefreshToken';
    protected $request;
    protected $db;
    protected $builder;

    public function __construct()
    {
        parent::__construct();
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
    }

    public function revokeToken(string $token)
    {
        return $this->where('token', $token)->set(['isrevoked' => 'Y'])->update();
    }
}
