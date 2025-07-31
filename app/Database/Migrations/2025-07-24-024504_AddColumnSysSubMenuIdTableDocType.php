<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnSysSubMenuIdTableDocType extends Migration
{
    public function up()
    {
        $fields = [
            'sys_submenu_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true]
        ];

        $this->forge->addColumn('md_doctype', $fields);
    }

    public function down()
    {
        $fields = ['sys_submenu_id'];

        $this->forge->dropColumn('md_doctype', $fields);
    }
}
