<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ConfigurationAutomationApproval extends Seeder
{
    public function run()
    {
        $data = [
            [
                'isactive'      => 'Y',
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'name'          => 'AUTO_REJECT_APPROVAL',
                'value'         => '2',
                'description'   => 'For Auto Reject Approval, value is mean how long day for auto reject'
            ],
            [
                'isactive'      => 'Y',
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'name'          => 'AUTO_APPROVE_REALIZATION',
                'value'         => '1',
                'description'   => 'For Auto Approve Realization, Value mean how long days for auto Approve'
            ]
        ];

        $this->db->table('sys_configuration')->insertBatch($data);
    }
}