<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnTransactionType extends Migration
{
    public function up()
    {
        $fields = ['transactiontype'       => ['type' => 'CHAR', 'constraint' => 2, 'null' => false, 'after' => 'table']];

        $this->forge->addColumn('trx_allow_attendance', $fields);
    }

    public function down()
    {
        $fields = ['transactiontype'];

        $this->forge->dropColumn('trx_allow_attendance', $fields);
    }
}