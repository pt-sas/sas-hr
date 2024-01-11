<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReferenceDocAction extends Seeder
{
    public function run()
    {
        $reference = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'name'          => '_DocAction',
            'description'   => 'Document Action Type list',
            'validationtype' => 'L'
        ];

        $this->db->table('sys_reference')->insert($reference);

        $ref_list = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'CO',
                'name'          => 'Complete',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'VO',
                'name'          => 'Void',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'PR',
                'name'          => 'Prepare',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'XL',
                'name'          => 'Unlock',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ]
        ];

        $this->db->table('sys_ref_detail')->insertBatch($ref_list);
    }
}
