<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnEmployeeTransactionTable extends Migration
{
    public function up()
    {
        $fields = [
            'md_employee_id'    =>  [
                'type'          => 'INT',
                'constraint'    => 11,
                'after'         => 'amount',
                'null'          => true
            ]
        ];

        $this->forge->addColumn('md_transaction', $fields);
    }

    public function down()
    {
        $fields = ['md_employee_id'];

        $this->forge->dropColumn('md_transaction', $fields);
    }
}
