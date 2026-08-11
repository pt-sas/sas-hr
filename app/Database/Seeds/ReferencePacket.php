<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReferencePacket extends Seeder
{
    public function run()
    {

        $reference = [
            'created_by'    => 100000,
            'updated_by'    => 100000,
            'name'          => 'Paket',
            'description'   => 'Reference Paket List',
            'validationtype' => 'L'
        ];

        $this->db->table('sys_reference')->insert($reference);

        $ref_list = [
            [
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'value'         => '1',
                'name'          => 'Paket Closing',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'value'         => '2',
                'name'          => 'Paket Project 1',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],

            [
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'value'         => '3',
                'name'          => 'Paket Project 2',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'value'         => '4',
                'name'          => 'Paket Project 3',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'value'         => '5',
                'name'          => 'Paket Project 4',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'value'         => '6',
                'name'          => 'Paket Project 5',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
        ];

        $this->db->table('sys_ref_detail')->insertBatch($ref_list);
    }
}
