<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Role extends Seeder
{
    public function run()
    {
        $data = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'W_Not_Default_Status',
                'description'   => 'Role for not default field status',
                'ismanual'      => 'Y',
                'iscanexport'   => 'N',
                'iscanreport'   => 'N',
                'isallowmultipleprint' => 'N',
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'W_View_All_Data',
                'description'   => 'Role for view all data',
                'ismanual'      => 'Y',
                'iscanexport'   => 'N',
                'iscanreport'   => 'N',
                'isallowmultipleprint' => 'N',
            ]
        ];

        $this->db->table('sys_role')->insertBatch($data);
    }
}
