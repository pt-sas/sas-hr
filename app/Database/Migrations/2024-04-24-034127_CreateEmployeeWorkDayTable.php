<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmployeeWorkDayTable extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'md_employee_work_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_work_id'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'validfrom'             => ['type' => 'timestamp', 'null' => true],
            'validto'               => ['type' => 'timestamp', 'null' => true],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false]
        ]);

        $this->forge->addKey('md_employee_work_id', true);
        $this->forge->createTable('md_employee_work', true);
    }

    public function down()
    {
        $this->forge->dropTable('md_employee_work', true);
    }
}
