<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnLeaveBalanceOnTableAbsent extends Migration
{
    public function up()
    {
        $fields = [
            'leavebalance'    =>  [
                'type'              => 'DECIMAL',
                'constraint'        => '10,2',
                'after'            => 'reference_id'
            ]
        ];

        $this->forge->addColumn('trx_absent', $fields);
    }

    public function down()
    {
        $fields = ['leavebalance'];

        $this->forge->dropColumn('trx_absent', $fields);
    }
}