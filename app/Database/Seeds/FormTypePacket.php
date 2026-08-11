<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FormTypePacket extends Seeder
{
    public function run()
    {
        $data = [
            [
                'md_doctype_id' => 100031,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Paket',
                'isactive'      => 'Y',
                'isrealization' => 'N',
                'isapprovedline' => 'N',
                'auto_not_approve_days' => 2
            ],
        ];

        $this->db->table('md_doctype')->insertBatch($data);
    }
}
