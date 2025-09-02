<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ConfigurationManagerNeedSpecialOfficeDuties extends Seeder
{
    public function run()
    {
        $data = [
            [
                'isactive'      => 'Y',
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'name'          => 'MANAGER_NO_NEED_SPECIAL_OFFICE_DUTIES',
                'value'         => 'Y',
                'description'   => 'For configuration to calculate if manager need special office duties'
            ],
        ];

        $this->db->table('sys_configuration')->insertBatch($data);
    }
}
