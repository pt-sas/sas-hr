<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReferenceStatusEducation extends Seeder
{
    public function run()
    {
        $reference = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'name'          => 'StatusEducation',
            'description'   => 'Reference Status Education',
            'validationtype' => 'L'
        ];

        $this->db->table('sys_reference')->insert($reference);

        $ref_list = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'Lulus',
                'name'          => 'Lulus',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'Tidak Lulus',
                'name'          => 'Tidak Lulus',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
        ];

        $this->db->table('sys_ref_detail')->insertBatch($ref_list);
    }
}
