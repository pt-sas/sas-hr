<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyConstraintNameTableFamily extends Migration
{
    public function up()
    {
        $field = ['name' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false]];

        $this->forge->modifyColumn('md_employee_family', $field);
        $this->forge->modifyColumn('md_employee_family_core', $field);
    }

    public function down()
    {
        $field = ['name' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false]];

        $this->forge->modifyColumn('md_employee_family', $field);
        $this->forge->modifyColumn('md_employee_family_core', $field);
    }
}
