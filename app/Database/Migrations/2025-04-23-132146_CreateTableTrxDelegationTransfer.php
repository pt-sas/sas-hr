<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTrxDelegationTransfer extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_delegation_transfer_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'documentno'            => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'employee_from'         => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_branch_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_division_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'employee_to'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'submissiontype'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'submissiondate'        => ['type' => 'date', 'null' => false],
            'date'                  => ['type' => 'timestamp', 'null' => false],
            'reason'                => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'docstatus'             => ['type' => 'CHAR', 'constraint' => 2, 'null' => false],
            'isapproved'            => ['type' => 'CHAR', 'constraint' => 1, 'null' => true],
            'approved_by'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'receiveddate'          => ['type' => 'timestamp', 'null' => true],
            'approveddate'          => ['type' => 'timestamp', 'null' => true],
            'sys_wfscenario_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ]);

        $this->forge->addKey('trx_delegation_transfer_id', true);
        $this->forge->createTable('trx_delegation_transfer', true);
        $this->db->query('ALTER TABLE trx_delegation_transfer AUTO_INCREMENT = 100001');
    }

    public function down()
    {
        $this->forge->dropTable('trx_delegation_transfer', true);
    }
}