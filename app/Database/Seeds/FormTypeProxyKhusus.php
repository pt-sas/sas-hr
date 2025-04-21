<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FormTypeProxyKhusus extends Seeder
{
    public function run()
    {
        $data = [
            [
                'md_doctype_id' => 100025,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Proxy Khusus',
                'isactive'      => 'Y'
            ],
        ];

        $this->db->table('md_doctype')->insertBatch($data);
    }
}