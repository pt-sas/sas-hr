<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyColumnBirthDateOnTableEmployee extends Migration
{
    public function up()
    {
        $fields = [
            'birthday' => ['type' => 'DATE', 'null' => false],
            'registerdate' => ['type' => 'DATE', 'null' => false],
            'resigndate' => ['type' => 'DATE', 'null' => false],
        ];

        $this->forge->modifyColumn('md_employee', $fields);

        $fields = [
            'dateofdeath' => ['type' => 'DATE', 'null' => false],
            'birthdate' => ['type' => 'DATE', 'null' => false]
        ];
        $this->forge->modifyColumn('md_employee_family', $fields);
        $this->forge->modifyColumn('md_employee_family_core', $fields);
    }

    public function down()
    {
        $fields = [
            'birthday' => ['type' => 'timestamp', 'null' => false],
            'registerdate' => ['type' => 'timestamp', 'null' => false],
            'resigndate' => ['type' => 'timestamp', 'null' => false],
        ];

        $this->forge->modifyColumn('md_employee', $fields);

        $fields = [
            'dateofdeath' => ['type' => 'timestamp', 'null' => false],
            'birthdate' => ['type' => 'timestamp', 'null' => false]
        ];

        $this->forge->modifyColumn('md_employee_family', $fields);
        $this->forge->modifyColumn('md_employee_family_core', $fields);
    }
}
