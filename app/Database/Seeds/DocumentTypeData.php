<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DocumentTypeData extends Seeder
{
    public function run()
    {
        $data = [
            [
                'md_doctype_id' => 100001,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Sakit',
                'isactive'      => 'Y'
            ],
            [
                'md_doctype_id' => 100002,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Alpa',
                'isactive'      => 'Y'
            ],
            [
                'md_doctype_id' => 100003,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Cuti',
                'isactive'      => 'Y'
            ],
            [
                'md_doctype_id' => 100004,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Ijin',
                'isactive'      => 'Y'
            ],
            [
                'md_doctype_id' => 100005,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Ijin Resmi',
                'isactive'      => 'Y'
            ],
            [
                'md_doctype_id' => 100006,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Ijin Keluar Kantor',
                'isactive'      => 'Y'
            ],
            [
                'md_doctype_id' => 100007,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Tugas Kantor',
                'isactive'      => 'Y'
            ],
            [
                'md_doctype_id' => 100008,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Tugas Kantor Khusus',
                'isactive'      => 'Y'
            ],
            [
                'md_doctype_id' => 100009,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Tugas Kantor Setengah Hari',
                'isactive'      => 'Y'
            ],
            [
                'md_doctype_id' => 100010,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Lupa Absen Masuk',
                'isactive'      => 'Y'
            ],
            [
                'md_doctype_id' => 100011,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Lupa Absen Pulang',
                'isactive'      => 'Y'
            ],
            [
                'md_doctype_id' => 100012,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Datang Terlambat',
                'isactive'      => 'Y'
            ],
            [
                'md_doctype_id' => 100013,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Pulang Cepat',
                'isactive'      => 'Y'
            ],
            [
                'md_doctype_id' => 100014,
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Lembur',
                'isactive'      => 'Y'
            ],
        ];

        $this->db->table('md_doctype')->insertBatch($data);
    }
}
