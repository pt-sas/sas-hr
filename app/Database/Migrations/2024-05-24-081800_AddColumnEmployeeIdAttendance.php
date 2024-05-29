<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnEmployeeIdAttendance extends Migration
{
    public function up()
    {
        $fields = [
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false]
        ];

        $this->forge->addColumn('trx_attendance', $fields);
    }

    public function down()
    {
        $fields = ['md_employee_id'];

        $this->forge->dropColumn('trx_attendance', $fields);
    }
}
