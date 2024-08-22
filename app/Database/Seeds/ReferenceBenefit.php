<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReferenceBenefit extends Seeder
{
    public function run()
    {
        $reference = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'name'          => 'BenefitType',
            'description'   => 'Reference Benefit Type',
            'validationtype' => 'L'
        ];

        $this->db->table('sys_reference')->insert($reference);

        $ref_list = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'COP',
                'name'          => 'COP',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'TUNJANGAN',
                'name'          => 'TUNJANGAN',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'LEMBUR',
                'name'          => 'LEMBUR',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'ASURANSI',
                'name'          => 'ASURANSI',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'INSENTIF',
                'name'          => 'INSENTIF',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'value'         => 'BPJS',
                'name'          => 'BPJS',
                'isactive'      => 'Y',
                'sys_reference_id' => $this->db->insertID()
            ],

        ];

        $this->db->table('sys_ref_detail')->insertBatch($ref_list);
    }
}
