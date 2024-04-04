<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnOvertimeAmountAndOvertimeBalance extends Migration
{
    public function up()
    {
        $fields = [
            'overtime_balance'  =>  [
                'type'          => 'INT',
                'constraint'    => 10,
                'after'         => 'enddate',
                'null'          => true
            ],

            'overtime_expense'  =>  [
                'type'          => 'INT',
                'constraint'    => 10,
                'after'         => 'overtime_balance',
                'null'          => true
            ],
            'total'             =>  [
                'type'          => 'double',
                'after'         => 'overtime_expense',
                'null'          => true
            ],
            'status'              =>  [
                'type'          => 'CHAR',
                'constraint'    => 1,
                'after'         => 'total',
                'null'          => true
            ]
        ];

        $this->forge->addColumn('trx_overtime_detail', $fields);
    }

    public function down()
    {
        $fields = ['overtime_balance', 'total', 'status', 'overtime_balance'];
        $this->forge->dropColumn('trx_overtime_detail', $fields);
    }
}
