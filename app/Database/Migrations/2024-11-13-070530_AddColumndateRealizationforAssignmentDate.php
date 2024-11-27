<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumndateRealizationforAssignmentDate extends Migration
{
    public function up()
    {
        $fields = [
            'branch_in' => [
                'type'          => 'INT',
                'constraint'    => 10,
                'null'          => true
            ],
            'branch_out' => [
                'type'          => 'INT',
                'constraint'    => 10,
                'null'          => true
            ],
            'realization_in' => [
                'type' => 'timestamp',
                'null' => true
            ],
            'realization_out' => [
                'type' => 'timestamp',
                'null' => true
            ],
            'instruction_in' => [
                'type'          => 'CHAR',
                'constraint'    => 1,
                'null'          => true
            ],
            'instruction_out' => [
                'type'          => 'CHAR',
                'constraint'    => 1,
                'null'          => true
            ]
        ];

        $this->forge->addColumn('trx_assignment_date', $fields);
    }

    public function down()
    {
        $fields = ['branch_in', 'branch_out', 'realization_in', 'realization_out', 'instruction_in', 'instruction_out'];

        $this->forge->dropColumn('trx_assignment_date', $fields);
    }
}