<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableUserRepresentative extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'sys_emp_delegation_id'      => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'sys_user_id'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false]
        ]);

        $this->forge->addKey('sys_emp_delegation_id', true);
        $this->forge->createTable('sys_emp_delegation', true);
    }

    public function down()
    {
        $this->forge->dropTable('sys_emp_delegation', true);
    }
}
