<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ConfigurationEmpDelegation extends Seeder
{
    public function run()
    {
        $data = [
            [
                'isactive'      => 'Y',
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'name'          => 'IS_DUTA_CHECK_LEVEL_ACCESS',
                'value'         => 'Y',
                'description'   => 'For configuration duta, if value Yes then checking level User to Get Employee Based On Level'
            ],
        ];

        $this->db->table('sys_configuration')->insertBatch($data);
    }
}
