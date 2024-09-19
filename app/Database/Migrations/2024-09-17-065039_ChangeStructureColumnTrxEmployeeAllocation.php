<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeStructureColumnTrxEmployeeAllocation extends Migration
{
    public function up()
    {
        $rmvFields = ['startdate', 'enddate'];

        $this->forge->dropColumn('trx_employee_allocation', $rmvFields);

        $field = [
            'md_position_id'   => ['type' => 'INT', 'after' => 'md_division_id', 'constraint' => 11, 'null' => false],
            'position_to'      => ['type' => 'INT', 'after' => 'division_to', 'constraint' => 11, 'null' => false],
            'date'             => ['type' => 'date', 'after' => 'position_to', 'default' => '0000-00-00', 'null' => false]
        ];

        $this->forge->addColumn('trx_employee_allocation', $field);
    }

    public function down()
    {
        $rmvFields = ['date', 'md_position_id', 'position_to'];

        $this->forge->dropColumn('trx_employee_allocation', $rmvFields);

        $field = [
            'startdate'             => ['type' => 'timestamp', 'after' => 'division_to', 'default' => '0000-00-00 00:00:00', 'null' => false],
            'enddate'               => ['type' => 'timestamp', 'after' => 'startdate', 'default' => '0000-00-00 00:00:00', 'null' => false],
        ];

        $this->forge->addColumn('trx_employee_allocation', $field);
    }
}