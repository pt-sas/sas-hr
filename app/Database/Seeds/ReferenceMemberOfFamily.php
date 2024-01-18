<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReferenceMemberOfFamily extends Seeder
{
    public function run()
    {
        $reference = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'name'          => 'MemberOfFamily',
            'description'   => 'Reference Member Of Family',
            'validationtype' => 'L'
        ];

        $this->db->table('sys_reference')->insert($reference);

        $ref_list = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'Suami',
                'name'          => 'Suami',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'Istri',
                'name'          => 'Istri',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'Anak',
                'name'          => 'Anak',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ]
        ];

        $this->db->table('sys_ref_detail')->insertBatch($ref_list);
    }
}
