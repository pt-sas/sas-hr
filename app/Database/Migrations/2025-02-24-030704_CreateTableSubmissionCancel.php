<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableSubmissionCancel extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_submission_cancel_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'documentno'            => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_branch_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_division_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'submissiontype'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'ref_submissiontype'    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'submissiondate'        => ['type' => 'date', 'null' => false],
            'reason'                => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'docstatus'             => ['type' => 'CHAR', 'constraint' => 2, 'null' => false],
            'isapproved'            => ['type' => 'CHAR', 'constraint' => 1, 'null' => true],
            'receiveddate'          => ['type' => 'timestamp', 'null' => true],
            'approveddate'          => ['type' => 'timestamp', 'null' => true],
            'sys_wfscenario_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'image'            =>  [
                'type'          => 'VARCHAR',
                'constraint'    => 255,
                'null'          => true
            ],
            'reference_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'ref_table'          => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
        ]);

        $this->forge->addKey('trx_submission_cancel_id', true);
        $this->forge->createTable('trx_submission_cancel', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_submission_cancel', true);
    }
}