<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReferenceResignType extends Seeder
{
    public function run()
    {

        $reference = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'name'          => 'ResignType',
            'description'   => 'Reference Resign Type for DepartureType (Atas kemauan sendiri)',
            'validationtype' => 'L'
        ];

        $this->db->table('sys_reference')->insert($reference);

        $ref_list = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'Saat ini',
                'name'          => 'Saat ini',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => '1 month',
                'name'          => '1 month',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => '3 month',
                'name'          => '3 month',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => '6 month',
                'name'          => '6 month',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
        ];

        $this->db->table('sys_ref_detail')->insertBatch($ref_list);
    }
}