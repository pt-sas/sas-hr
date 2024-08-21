<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTrxMemoHr extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_hr_memo_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'documentno'            => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'nik'                   => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
            'md_branch_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_division_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'submissiondate'        => ['type' => 'timestamp default current_timestamp'],
            'memodate'              => ['type' => 'TIMESTAMP', 'null' => false],
            'memotype'              => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'memocriteria'          => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'memocontent'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'totaldays'             => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'docstatus'             => ['type' => 'CHAR', 'constraint' => 2, 'null' => false],
            'isapproved'            => ['type' => 'CHAR', 'constraint' => 1, 'null' => false],
            'approveddate'          => ['type' => 'date', 'null' => false],
            'sys_wfscenario_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true]
        ]);

        $this->forge->addKey('trx_hr_memo_id', true);
        $this->forge->createTable('trx_hr_memo', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_hr_memo');
    }
}
