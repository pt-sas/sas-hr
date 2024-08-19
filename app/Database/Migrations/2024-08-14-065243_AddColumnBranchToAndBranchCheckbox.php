<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnBranchToAndBranchCheckbox extends Migration
{
    public function up()
    {
        $fields = [
            'isbranch'        =>  [
                'type'          => 'CHAR',
                'constraint'    => 1,
                'after'         => 'md_leavetype_id',
                'null'          => true
            ],
            'branch_to'       =>  [
                'type'          => 'INT',
                'constraint'    => 10,
                'after'         => 'isbranch',
                'null'          => true
            ]
        ];

        $this->forge->addColumn('trx_absent', $fields);
    }

    public function down()
    {
        $fields = ['isbranch', 'branch_to'];
        $this->forge->dropColumn('trx_absent', $fields);
    }
}