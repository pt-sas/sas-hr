<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnFullNameTrxEmployeeDeparture extends Migration
{
    public function up()
    {
        $field = [
            'fullname'          => [
                'type'          => 'VARCHAR',
                'after'         => 'nik',
                'constraint'    => 40,
                'null'          => false
            ]
        ];

        $this->forge->addColumn('trx_employee_departure', $field);
    }

    public function down()
    {
        $field = ['fullname'];

        $this->forge->dropColumn('trx_employee_departure', $field);
    }
}
