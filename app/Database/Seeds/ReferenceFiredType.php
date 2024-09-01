<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReferenceFiredType extends Seeder
{
    public function run()
    {

        $reference = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'name'          => 'FiredType',
            'description'   => 'Reference Fired Type for DepartureType (Diberhentikan perusahaan)',
            'validationtype' => 'L'
        ];

        $this->db->table('sys_reference')->insert($reference);

        $ref_list = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'Pelanggaran Asusila',
                'name'          => 'Pelanggaran Asusila',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'Penggelapan Uang',
                'name'          => 'Penggelapan Uang',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'Surat Peringatan 3',
                'name'          => 'Surat Peringatan 3',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ]
        ];

        $this->db->table('sys_ref_detail')->insertBatch($ref_list);
    }
}