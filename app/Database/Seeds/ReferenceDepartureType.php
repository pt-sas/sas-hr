<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReferenceDepartureType extends Seeder
{
    public function run()
    {
        $reference = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'name'          => 'DepartureType',
            'description'   => 'Reference Departure Type',
            'validationtype' => 'L'
        ];

        $this->db->table('sys_reference')->insert($reference);

        $ref_list = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'Atas kemauan sendiri',
                'name'          => 'Atas kemauan sendiri',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'Diberhentikan perusahaan',
                'name'          => 'Diberhentikan perusahaan',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'Pensiun',
                'name'          => 'Pensiun',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
        ];

        $this->db->table('sys_ref_detail')->insertBatch($ref_list);
    }
}