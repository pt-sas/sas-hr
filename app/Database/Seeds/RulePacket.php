<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RulePacket extends Seeder
{
    public function run()
    {

        $rule = [
            'created_by'    => 100000,
            'updated_by'    => 100000,
            'name'          => 'Paket',
            'condition'     => '',
            'value'         => '',
            'min'           => '',
            'max'           => '',
            'menu_url'      => 'paket',
            'priority'      => 6,
            'isdetail'      => 'Y',
        ];

        $this->db->table('md_rule')->insert($rule);

        $rule_detail_list = [
            [
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'md_rule_id'    => $this->db->insertID(),
                'name'          => 'Paket Closing',
                'operation'     => '>=',
                'format_condition'  => 'Jam',
                'condition'     => '3',
                'format_value'  => 'Rupiah',
                'value'         => '50000',
                'isdetail'      => 'N',
                'description'   => '1'
            ],
            [
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'md_rule_id'    => $this->db->insertID(),
                'name'          => 'Paket Project 1',
                'operation'     => '>=',
                'format_condition'  => 'Jam',
                'condition'     => '3',
                'format_value'  => 'Rupiah',
                'value'         => '50000',
                'isdetail'      => 'N',
                'description'   => '2'
            ],
            [
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'md_rule_id'    => $this->db->insertID(),
                'name'          => 'Paket Project 2',
                'operation'     => '>=',
                'format_condition'  => 'Jam',
                'condition'     => '10',
                'format_value'  => 'Rupiah',
                'value'         => '100000',
                'isdetail'      => 'N',
                'description'   => '3'
            ],
            [
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'md_rule_id'    => $this->db->insertID(),
                'name'          => 'Paket Project 3',
                'operation'     => '>=',
                'format_condition'  => 'Jam',
                'condition'     => '20',
                'format_value'  => 'Rupiah',
                'value'         => '200000',
                'isdetail'      => 'N',
                'description'   => '4'
            ],
            [
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'md_rule_id'    => $this->db->insertID(),
                'name'          => 'Paket Project 4',
                'operation'     => '>=',
                'format_condition'  => 'Jam',
                'condition'     => '30',
                'format_value'  => 'Rupiah',
                'value'         => '300000',
                'isdetail'      => 'N',
                'description'   => '5'
            ],
            [
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'md_rule_id'    => $this->db->insertID(),
                'name'          => 'Paket Project 5',
                'operation'     => '>=',
                'format_condition'  => 'Jam',
                'condition'     => '40',
                'format_value'  => 'Rupiah',
                'value'         => '500000',
                'isdetail'      => 'N',
                'description'   => '6'
            ]
        ];

        $this->db->table('md_rule_detail')->insertBatch($rule_detail_list);
    }
}
