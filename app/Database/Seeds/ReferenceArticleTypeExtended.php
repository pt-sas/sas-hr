<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReferenceArticleTypeExtended extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();

        $now = date('Y-m-d H:i:s');

        // 1. Get or create the reference header
        $existingRef = $this->db->table('sys_reference')
            ->where('name', 'ArticleCategory')
            ->get()
            ->getRow();

        if ($existingRef) {
            $sys_reference_id = $existingRef->sys_reference_id;
        } else {
            $reference = [
                'name'           => 'ArticleCategory',
                'description'    => 'Reference Article Category',
                'validationtype' => 'L',
                'isactive'       => 'Y',
                'created_by'     => 1,
                'updated_by'     => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            $this->db->table('sys_reference')->insert($reference);
            $sys_reference_id = $this->db->insertID();
        }

        // 2. Additional categories to seed
        $categories = [
            [
                'value'       => 'PROCEDURE',
                'name'        => 'Prosedur & Panduan',
                'description' => 'SOP dan panduan langkah demi langkah',
            ],
            [
                'value'       => 'POLICY',
                'name'        => 'Kebijakan Perusahaan',
                'description' => 'Aturan dan kebijakan internal',
            ],
            [
                'value'       => 'BENEFITS',
                'name'        => 'Kesejahteraan & Benefit',
                'description' => 'Informasi asuransi, klaim, dan benefit karyawan',
            ],
            [
                'value'       => 'ATTENDANCE',
                'name'        => 'Kehadiran & Cuti',
                'description' => 'Pengajuan izin, sakit, cuti, dan presensi',
            ],
            [
                'value'       => 'IT_SUPPORT',
                'name'        => 'Bantuan IT',
                'description' => 'Masalah sistem, akun, dan perangkat kerja',
            ],
        ];

        // 3. Filter out categories that already exist in sys_ref_detail
        $newCategories = [];

        foreach ($categories as $cat) {
            $exists = $this->db->table('sys_ref_detail')
                ->where([
                    'sys_reference_id' => $sys_reference_id,
                    'value'            => $cat['value'],
                ])
                ->get()
                ->getRow();

            if (!$exists) {
                $newCategories[] = [
                    'sys_reference_id' => $sys_reference_id,
                    'value'            => $cat['value'],
                    'name'             => $cat['name'],
                    'description'      => $cat['description'],
                    'isactive'         => 'Y',
                    'created_by'       => 1,
                    'updated_by'       => 1,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }
        }

        // 4. Batch insert only new items
        if (!empty($newCategories)) {
            $this->db->table('sys_ref_detail')->insertBatch($newCategories);
        }

        $this->db->enableForeignKeyChecks();
    }
}