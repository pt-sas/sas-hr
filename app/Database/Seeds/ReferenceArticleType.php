<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReferenceArticleType extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();

        $now = date('Y-m-d H:i:s');

        // 1. Check if reference header already exists to prevent duplicate entries
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

        // 2. Insert detail list if the value doesn't already exist
        $existingDetail = $this->db->table('sys_ref_detail')
            ->where([
                'sys_reference_id' => $sys_reference_id,
                'value'            => 'UMUM'
            ])
            ->get()
            ->getRow();

        if (!$existingDetail) {
            $ref_list = [
                [
                    'sys_reference_id' => $sys_reference_id,
                    'value'            => 'UMUM',
                    'name'             => 'Umum',
                    'description'      => 'Kategori umum',
                    'isactive'         => 'Y',
                    'created_by'       => 1,
                    'updated_by'       => 1,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ],
            ];

            $this->db->table('sys_ref_detail')->insertBatch($ref_list);
        }

        $this->db->enableForeignKeyChecks();
    }
}