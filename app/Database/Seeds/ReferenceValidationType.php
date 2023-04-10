<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReferenceValidationType extends Seeder
{
    public function run()
    {
        $reference = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'name'          => 'SYS_Reference Validation Types',
            'description'   => 'Reference Validation Type list'
        ];

        $this->db->table('sys_reference')->insert($reference);

        $ref_list = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'D',
                'name'          => 'DataType',
                'description'   => 'DataType',
                'isactive'      => 'N',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'L',
                'name'          => 'List Validation',
                'description'   => 'List Validation',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ]
        ];

        $this->db->table('sys_ref_detail')->insertBatch($ref_list);
    }
}
