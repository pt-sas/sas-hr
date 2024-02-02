<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableEmployeeDivision extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'md_employee_division_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 6, 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 6, 'null' => false],
            'table'                 => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'record_id'             => ['type' => 'INT', 'constraint' => 6, 'null' => false],
            'submissiontype'        => ['type' => 'INT', 'constraint' => 6, 'null' => false],
            'allowancedate'        => ['type' => 'timestamp', 'null' => false],
            'amount'                => ['type' => 'DECIMAL', 'constraint' => 10, 'null' => false],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false]
        ]);
        $this->forge->addKey('trx_allow_attendance_id', true);
        $this->forge->addKey('md_employee_id', false);
        $this->forge->createTable('trx_allow_attendance', true);
    }

    public function down()
    {
        $this->forge->dropTable('md_employee_division', true);
    }
}