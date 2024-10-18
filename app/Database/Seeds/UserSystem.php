<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSystem extends Seeder
{
    public function run()
    {
        $data = [
            'sys_user_id'   => 100000,
            'created_by'    => 100000,
            'updated_by'    => 100000,
            'username'      => 'System',
            'name'          => 'System',
            'password'      => '$2y$10$1.BPejUmjxi7Ljysw.KeEeG7bv0gj4z0xyy8w6uWfFLty.8pjqYSO',
        ];

        $this->db->table('sys_user')->insert($data);
    }
}
