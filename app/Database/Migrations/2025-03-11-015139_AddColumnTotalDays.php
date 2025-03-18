<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnTotalDays extends Migration
{
    public function up()
    {
        $fields = ['totaldays' => ['type' => 'INT', 'constraint' => 2, 'null' => false]];

        $this->forge->addColumn('trx_absent', $fields);
        $this->forge->addColumn('trx_assignment', $fields);
    }

    public function down()
    {
        $fields = ['totaldays'];

        $this->forge->dropColumn('trx_absent', $fields);
        $this->forge->dropColumn('trx_assignment', $fields);
    }
}