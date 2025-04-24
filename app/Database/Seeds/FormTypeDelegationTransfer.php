<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FormTypeDelegationTransfer extends Seeder
{
    public function run()
    {
        $data = [
            [
                'md_doctype_id' => 100027,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Transfer Duta',
                'isactive'      => 'Y'
            ],
        ];

        $this->db->table('md_doctype')->insertBatch($data);
    }
}