<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ConfigurationCarryOverLeaveBalance extends Seeder
{
    public function run()
    {
        $configuration = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'DAY_CUT_OFF_LEAVE',
                'value'         => '5',
                'description'   => '',
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'DAY_CUT_OFF_ALLOWANCE',
                'value'         => '15',
                'description'   => '',
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'CARRY_OVER_AMOUNT_LEAVE_BALANCE',
                'value'         => 'N',
                'description'   => 'N -> No Carry over next year, Y -> Carry over next year',
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'CARRY_OVER_EXPIRED_BY',
                'value'         => 'D',
                'description'   => 'D -> For Days, M -> For Month'
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'CARRY_OVER_EXPIRED_BY_DAYS',
                'value'         => '58',
                'description'   => '',
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'CARRY_OVER_EXPIRED_BY_MONTH',
                'value'         => '2',
                'description'   => '',
            ]
        ];

        $this->db->table('sys_configuration')->insertBatch($configuration);
    }
}
