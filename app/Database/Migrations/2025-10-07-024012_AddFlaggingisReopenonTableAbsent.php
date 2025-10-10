<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFlaggingisReopenonTableAbsent extends Migration
{
    public function up()
    {
        $fields = ['isreopen' => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'N']];
        $this->forge->addColumn('trx_absent', $fields);
        $this->forge->addColumn('trx_assignment', $fields);
        $this->forge->addColumn('trx_overtime', $fields);
        $this->forge->addColumn('trx_absent_detail', $fields);
        $this->forge->addColumn('trx_assignment_date', $fields);
        $this->forge->addColumn('trx_overtime_detail', $fields);
    }

    public function down()
    {
        $fields = ['isreopen'];

        $this->forge->dropColumn('trx_absent', $fields);
        $this->forge->dropColumn('trx_assignment', $fields);
        $this->forge->dropColumn('trx_overtime', $fields);
        $this->forge->dropColumn('trx_absent_detail', $fields);
        $this->forge->dropColumn('trx_assignment_date', $fields);
        $this->forge->dropColumn('trx_overtime_detail', $fields);
    }
}
