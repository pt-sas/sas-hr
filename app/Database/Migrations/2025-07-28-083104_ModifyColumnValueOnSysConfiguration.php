<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyColumnValueOnSysConfiguration extends Migration
{
    public function up()
    {
        $fields = [
            'value' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
        ];

        $this->forge->modifyColumn('sys_configuration', $fields);
    }

    public function down()
    {
        $fields = ['value' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],];

        $this->forge->modifyColumn('sys_configuration', $fields);
    }
}