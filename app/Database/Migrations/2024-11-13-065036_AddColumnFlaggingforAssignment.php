<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnFlaggingforAssignment extends Migration
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
            ]
        ];

        $this->forge->addColumn('trx_assignment', $fields);
    }

    public function down()
    {
        $fields = ['branch_in', 'branch_out'];

        $this->forge->dropColumn('trx_assignment', $fields);
    }
}