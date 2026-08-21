<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RuleListNews extends Seeder
{
    public function run()
    {
        $rule = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'name'          => 'List Kabar',
            'condition'     => null,
            'value'         => null,
            'min'           => null,
            'max'           => null,
            'menu_url'      => 'list-kabar',
            'priority'      => 7,
            'isdetail'      => 'Y'
        ];

        $this->db->table('md_rule')->insert($rule);

        $rule_detail = [
            [
                'isactive'      => 'Y',
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'md_rule_id'    => $this->db->insertID(),
                'name'          => 'Included Level',
                'operation'     => '>=',
                'format_condition'     => 'Karakter',
                'condition'     => '100002',
                'format_value'  => '',
                'value'         => '',
                'isdetail'      => 'N',
                'description'   => ''
            ],
            [
                'isactive'      => 'Y',
                'created_by'    => 100000,
                'updated_by'    => 100000,
                'md_rule_id'    => $this->db->insertID(),
                'name'          => 'Batas Waktu Input',
                'operation'     => '<=',
                'format_condition'     => 'Jam',
                'condition'     => '17:00',
                'format_value'  => '',
                'value'         => '',
                'isdetail'      => 'N',
                'description'   => ''
            ],
        ];

        $this->db->table('md_rule_detail')->insertBatch($rule_detail);
    }
}
