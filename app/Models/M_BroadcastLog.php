<?php

namespace App\Models;

use CodeIgniter\Model;

class M_BroadcastLog extends Model
{
    protected $table = 'trx_broadcast_log';
    protected $primaryKey = 'trx_broadcast_log_id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'trx_broadcast_id',
        'md_employee_id',
        'sentmethod',
        'error_message'
    ];
    protected $useTimestamps = false;

    public function logBroadcast($broadcastId, $employeeId, $method, $errorMessage)
    {
        $data = [
            'trx_broadcast_id' => $broadcastId,
            'md_employee_id' => $employeeId,
            'sentmethod' => $method,
            'error_message' => $errorMessage
        ];

        return $this->insert($data);
    }
    
}