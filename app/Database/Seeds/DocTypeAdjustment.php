<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DocTypeAdjustment extends Seeder
{
    public function run()
    {
        $data = [
            [
                'md_doctype_id' => 100029,
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'name'          => 'Penyesuaian Cuti',
                'isactive'      => 'Y'
            ],
            [
                'md_doctype_id' => 100030,
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'name'          => 'Penyesuaian TKH',
                'isactive'      => 'Y'
            ],
        ];

        $this->db->table('md_doctype')->insertBatch($data);
    }
}
