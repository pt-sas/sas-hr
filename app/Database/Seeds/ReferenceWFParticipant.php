<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReferenceWFParticipant extends Seeder
{
    public function run()
    {
        $reference = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'name'          => 'WF_ParticipantType',
            'description'   => 'Reference Participant Type',
            'validationtype' => 'L'
        ];

        $this->db->table('sys_reference')->insert($reference);

        $ref_list = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'U',
                'name'          => 'User',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'R',
                'name'          => 'Role',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
        ];

        $this->db->table('sys_ref_detail')->insertBatch($ref_list);
    }
}
