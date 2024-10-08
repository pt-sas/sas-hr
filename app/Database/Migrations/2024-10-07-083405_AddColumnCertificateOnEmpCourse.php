<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnCertificateOnEmpCourse extends Migration
{
    public function up()
    {
        $field = ['certificate' => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'Y']];

        $this->forge->addColumn('md_employee_courses', $field);
    }

    public function down()
    {
        $field = ['certificate'];

        $this->forge->dropColumn('md_employee_courses', $field);
    }
}
