<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldLetterDate extends Migration
{
    public function up()
    {
        $field = [
            'letterdate' => [
                'type'     => 'TIMESTAMP',
                'null'     => true,
                'default'  => null
            ]
        ];

        $this->forge->addColumn('trx_employee_departure', $field);
    }

    public function down()
    {
        $field = ['letterdate'];

        $this->forge->dropColumn('trx_employee_departure', $field);
    }
}