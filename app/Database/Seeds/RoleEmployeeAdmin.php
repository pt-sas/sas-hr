<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleEmployeeAdmin extends Seeder
{
    public function run()
    {
        $data = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'W_Emp_Admin',
                'description'   => 'Role for manage master data employee',
                'ismanual'      => 'Y',
                'iscanexport'   => 'N',
                'iscanreport'   => 'N',
                'isallowmultipleprint' => 'N',
            ]
        ];

        $this->db->table('sys_role')->insertBatch($data);
    }
}
