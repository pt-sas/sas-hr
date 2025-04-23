<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FormTypeMedicalCertificate extends Seeder
{
    public function run()
    {
        $data = [
            [
                'md_doctype_id' => 100026,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Surat Keterangan Sakit',
                'isactive'      => 'Y'
            ],
        ];

        $this->db->table('md_doctype')->insertBatch($data);
    }
}