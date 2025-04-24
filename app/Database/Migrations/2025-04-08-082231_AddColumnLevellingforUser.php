<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnLevellingforUser extends Migration
{
    public function up()
    {
        $fields = ['md_levelling_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true]];

        $this->forge->addColumn('sys_user', $fields);
    }

    public function down()
    {
        $fields = ['md_levelling_id'];

        $this->forge->dropColumn('sys_user', $fields);
    }
}
