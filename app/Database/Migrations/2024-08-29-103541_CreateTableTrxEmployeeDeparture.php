<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTrxEmployeeDeparture extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_employee_departure_id' => ['type' => "INT", 'constraint' => 11, 'auto_increment' => true, 'null' => false],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'documentno'            => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'submissiondate'        => ['type' => 'timestamp default current_timestamp', 'null' => false],
            'submissiontype'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'nik'                   => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
            'md_branch_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_division_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_position_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'date'                  => ['type' => 'timestamp', 'default' => '0000-00-00 00:00:00', 'null' => false],
            'departuretype'         => ['type' => 'CHAR', 'constraint' => 100, 'null' => true],
            'departurerule'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'docstatus'             => ['type' => 'CHAR', 'constraint' => 2, 'null' => false],
            'isapproved'            => ['type' => 'CHAR', 'constraint' => 1, 'null' => false],
            'approveddate'          => ['type' => 'timestamp', 'null' => true],
            'sys_wfscenario_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true]
        ]);

        $this->forge->addKey('trx_employee_departure_id', true);

        $this->forge->createTable('trx_employee_departure', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_employee_departure', true);
    }
}