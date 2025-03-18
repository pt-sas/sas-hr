<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnDocumentTypeforWScenario extends Migration
{
    public function up()
    {
        $fields = ['submissiontype' => ['type' => 'INT', 'constraint' => 11, 'null' => false]];
        $this->forge->addColumn('sys_wfscenario', $fields);
    }

    public function down()
    {
        $fields = ['submissiontype'];
        $this->forge->dropColumn('sys_wfscenario', $fields);
    }
}
