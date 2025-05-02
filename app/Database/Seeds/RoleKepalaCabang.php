<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleKepalaCabang extends Seeder
{
    public function run()
    {
        $data = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'W_Emp_KACAB',
                'description'   => 'Role for employee kepala cabang',
                'ismanual'      => 'Y',
                'iscanexport'   => 'N',
                'iscanreport'   => 'N',
                'isallowmultipleprint' => 'N',
            ]
        ];

        $this->db->table('sys_role')->insertBatch($data);
    }
}