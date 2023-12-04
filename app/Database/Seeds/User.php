<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class User extends Seeder
{
    public function run()
    {
        $data = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'username'      => 'sas',
            'name'          => 'SAS',
            'password'      => '$2y$10$1.BPejUmjxi7Ljysw.KeEeG7bv0gj4z0xyy8w6uWfFLty.8pjqYSO',
        ];

        $this->db->table('sys_user')->insert($data);

        $data = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'name'          => 'awn Admin',
            'description'   => 'Master Role',
            'ismanual'      => 'N',
            'iscanexport'   => 'Y',
            'iscanreport'   => 'Y',
            'isallowmultipleprint' => 'Y',
        ];

        $this->db->table('sys_role')->insert($data);

        $data = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'sys_role_id'   => 1,
            'sys_user_id'   => 1
        ];

        $this->db->table('sys_user_role')->insert($data);
    }
}
