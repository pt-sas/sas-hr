<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DocTypeOnboarding extends Seeder
{
    public function run()
    {
        $data = [
            [
                'md_doctype_id' => 100028,
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'name'          => 'Onboarding',
                'isactive'      => 'Y'
            ]
        ];

        $this->db->table('md_doctype')->insertBatch($data);
    }
}
