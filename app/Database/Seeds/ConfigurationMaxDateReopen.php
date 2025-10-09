<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ConfigurationMaxDateReopen extends Seeder
{
    public function run()
    {
        $data = [
            [
                'isactive'      => 'Y',
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'name'          => 'MAX_DATE_REOPEN',
                'value'         => '24-12',
                'description'   => 'For Max Reopen, (DD-MM) Format'
            ],
        ];

        $this->db->table('sys_configuration')->insertBatch($data);
    }
}
