<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ConfigurationTokenBOTTelegram extends Seeder
{
    public function run()
    {
        $data = [
            [
                'isactive'      => 'Y',
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'name'          => 'TOKEN_BOT_TELEGRAM',
                'value'         => '',
                'description'   => 'For configuration telegram token'
            ],
        ];

        $this->db->table('sys_configuration')->insertBatch($data);
    }
}