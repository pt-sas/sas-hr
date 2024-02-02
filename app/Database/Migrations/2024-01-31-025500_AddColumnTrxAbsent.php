<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnTrxAbsent extends Migration
{
    public function up()
    {
        $fields = [
            'md_leavetype_id'     =>  [
                'type'      => 'INT',
                'after'     => 'image',
                'null'      => true
            ]
        ];

        $this->forge->addColumn('trx_absent', $fields);
        $this->forge->addKey('md_leavetype_id', false);
    }

    public function down()
    {
        $fields = ['md_leavetype_id'];

        $this->forge->dropColumn('md_leavetype_id', $fields);
    }
}
