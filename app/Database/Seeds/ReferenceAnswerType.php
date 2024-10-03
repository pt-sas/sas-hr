<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReferenceAnswerType extends Seeder
{
    public function run()
    {
        $reference = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'name'          => 'AnswerType',
            'description'   => 'Reference Answer Type',
            'validationtype' => 'L'
        ];

        $this->db->table('sys_reference')->insert($reference);

        $ref_list = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'checkbox',
                'name'          => 'CHECKBOX',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'text',
                'name'          => 'TEXT',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'list',
                'name'          => 'LIST',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],

            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'scale',
                'name'          => 'Skala',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
        ];

        $this->db->table('sys_ref_detail')->insertBatch($ref_list);
    }
}