<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnBirthDateTableFamilyAndFamilyCore extends Migration
{
    public function up()
    {
        $field = [
            'birthdate' => [
                'type'     => 'TIMESTAMP',
                'null'     => true
            ]
        ];

        $this->forge->addColumn('md_employee_family_core', $field);
        $this->forge->addColumn('md_employee_family', $field);
    }

    public function down()
    {
        $field = ['birthdate'];

        $this->forge->dropColumn('md_employee_family_core', $field);
        $this->forge->dropColumn('md_employee_family', $field);
    }
}
