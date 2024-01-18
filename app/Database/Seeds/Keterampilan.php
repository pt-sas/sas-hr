<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Keterampilan extends Seeder
{
    public function run()
    {
        $data = [
            [
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'value'         => 'KT00001',
                'name'          => 'ALAT MUSIK',
                'description'   => '',
                'isactive'      => 'Y'
            ],
            [
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'value'         => 'KT00002',
                'name'          => 'DESIGN',
                'description'   => '',
                'isactive'      => 'Y'
            ],
            [
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'value'         => 'KT00003',
                'name'          => 'FOTOGRAPHY',
                'description'   => '',
                'isactive'      => 'Y'
            ],
            [
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'value'         => 'KT00004',
                'name'          => 'KOMEDI',
                'description'   => '',
                'isactive'      => 'Y'
            ],
            [
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'value'         => 'KT00005',
                'name'          => 'MELUKIS',
                'description'   => '',
                'isactive'      => 'Y'
            ],
            [
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'value'         => 'KT00006',
                'name'          => 'MEMASAK',
                'description'   => '',
                'isactive'      => 'Y'
            ],
            [
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'value'         => 'KT00007',
                'name'          => 'MENARI',
                'description'   => '',
                'isactive'      => 'Y'
            ],
            [
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'value'         => 'KT00008',
                'name'          => 'MENJAHIT',
                'description'   => '',
                'isactive'      => 'Y'
            ],
            [
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'value'         => 'KT00009',
                'name'          => 'MENYANYI',
                'description'   => '',
                'isactive'      => 'Y'
            ],
            [
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'value'         => 'KT00010',
                'name'          => 'MULTIMEDIA',
                'description'   => '',
                'isactive'      => 'Y'
            ],
            [
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'value'         => 'KT00011',
                'name'          => 'SENI PERAN',
                'description'   => '',
                'isactive'      => 'Y'
            ],
            [
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'value'         => 'KT00012',
                'name'          => 'SENI RUPA',
                'description'   => '',
                'isactive'      => 'Y'
            ],
            [
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'value'         => 'KT00013',
                'name'          => 'SPORT',
                'description'   => '',
                'isactive'      => 'Y'
            ],
            [
                'created_by'    => 100001,
                'updated_by'    => 100001,
                'value'         => 'KT00014',
                'name'          => 'VIDEOGRAPHY',
                'description'   => '',
                'isactive'      => 'Y'
            ],
        ];

        $this->db->table('md_skill')->insertBatch($data);
    }
}