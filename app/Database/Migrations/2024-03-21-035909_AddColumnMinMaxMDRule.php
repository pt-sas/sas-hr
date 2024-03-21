<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnMinMaxMDRule extends Migration
{
    public function up()
    {
        $fields = [
            'min'               =>  [
                'type'          => 'DECIMAL',
                'constraint'    => 10,
                'after'         => 'value',
                'null'          => true
            ],
            'max'               =>  [
                'type'          => 'DECIMAL',
                'constraint'    => 10,
                'after'         => 'min',
                'null'          => true
            ]
        ];

        $this->forge->addColumn('md_rule', $fields);
    }

    public function down()
    {
        $fields = ['min', 'max'];
        $this->forge->dropColumn('md_rule', $fields);
    }
}
