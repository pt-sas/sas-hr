<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnLevelScenario extends Migration
{
    public function up()
    {
        $fields = [
            'md_levelling_id'   =>  [
                'type'          => 'INT',
                'after'         => 'md_division_id',
                'constraint'    => 11,
                'null'          => true
            ]
        ];

        $this->forge->addColumn('sys_wfscenario', $fields);
    }

    public function down()
    {
        $fields = ['md_levelling_id'];
        $this->forge->dropColumn('sys_wfscenario', $fields);
    }
}
