<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnLevelEmployeeAllocationTable extends Migration
{
    public function up()
    {
        $fields = [
            'md_levelling_id'   =>  [
                'type'          => 'INT',
                'after'         => 'md_division_id',
                'constraint'    => 11,
                'null'          => true
            ],
            'levelling_to'   =>  [
                'type'          => 'INT',
                'after'         => 'division_to',
                'constraint'    => 11,
                'null'          => true
            ]
        ];

        $this->forge->addColumn('trx_employee_allocation', $fields);
    }

    public function down()
    {
        $fields = ['md_levelling_id', 'levelling_to'];
        $this->forge->dropColumn('trx_employee_allocation', $fields);
    }
}
