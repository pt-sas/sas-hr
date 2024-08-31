<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldTableLineAndIDWActivity extends Migration
{
    public function up()
    {
        $fields = [
            'tableline'         =>  [
                'type'          => 'VARCHAR',
                'constraint'    => 32,
                'null'          => true,
                'default'       => null
            ],
            'recordline_id'     =>  [
                'type'          => 'INT',
                'constraint'    => 10,
                'null'          => true,
                'default'       => null
            ]
        ];

        $this->forge->addColumn('sys_wfactivity', $fields);
        $this->forge->addColumn('sys_wfevent_audit', $fields);
    }

    public function down()
    {
        $fields = ['tableline', 'recordline_id'];

        $this->forge->dropColumn('sys_wfactivity', $fields);
        $this->forge->dropColumn('sys_wfevent_audit', $fields);
    }
}
