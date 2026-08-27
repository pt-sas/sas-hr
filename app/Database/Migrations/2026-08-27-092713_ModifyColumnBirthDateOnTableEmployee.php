<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyColumnBirthDateOnTableEmployee extends Migration
{
    public function up()
    {
        $fields = [
            'birthday' => ['type' => 'DATE', 'null' => false]
        ];

        $this->forge->modifyColumn('md_employee', $fields);
        $this->forge->modifyColumn('md_employee_family', $fields);
        $this->forge->modifyColumn('md_employee_family_core', $fields);
    }

    public function down()
    {
        $fields = [
            'birthday' => ['type' => 'timestamp', 'null' => false]
        ];

        $this->forge->modifyColumn('md_employee', $fields);
        $this->forge->modifyColumn('md_employee_family', $fields);
        $this->forge->modifyColumn('md_employee_family_core', $fields);
    }
}
