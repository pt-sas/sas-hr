<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTrxProbation extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_probation_id'      => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'documentno'            => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'category'              => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'submissiontype'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'submissiondate'        => ['type' => 'timestamp default current_timestamp', 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'nik'                   => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
            'md_branch_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_division_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_position_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'registerdate'          => ['type' => 'timestamp', 'null' => false],
            'notes'                 => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'feedback'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'passed'                => ['type' => 'CHAR', 'constraint' => 1, 'null' => true],
            'docstatus'             => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'probation_enddate'     => ['type' => 'timestamp', 'null' => true],
            'isapproved'            => ['type' => 'CHAR', 'constraint' => 1, 'null' => true],
            'approveddate'          => ['type' => 'date', 'null' => false],
            'sys_wfscenario_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true]
        ]);

        $this->forge->addKey('trx_probation_id', true);
        $this->forge->createTable('trx_probation', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_probation', true);
    }
}