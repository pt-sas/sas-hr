<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnReservedLeaveonTransaction extends Migration
{
    public function up()
    {
        $fields = [
            'reserved_amount'  => ['type' => 'DECIMAL', 'constraint' => '10', 'null' => false, 'default' => 0, 'after' => 'amount'],
        ];

        $this->forge->addColumn('md_transaction', $fields);
    }

    public function down()
    {
        $fields = ['reserved_amount'];

        $this->forge->dropColumn('md_transaction', $fields);
    }
}