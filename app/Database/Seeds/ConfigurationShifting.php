<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ConfigurationShifting extends Seeder
{
    public function run()
    {
        $data = [
            [
                'isactive'      => 'Y',
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'name'          => 'BASED_ON_WORK_HOUR',
                'value'         => 'N',
                'description'   => 'For configuration shifting system, if value is Y then the calculation of attendance based on total Work Hour of the employee'
            ],
        ];

        $this->db->table('sys_configuration')->insertBatch($data);
    }
}