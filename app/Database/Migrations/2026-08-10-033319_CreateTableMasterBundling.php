<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableMasterBundling extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_master_bundling_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'bundling_type'         => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
            'nominal_type'          => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
            'recurring_type'        => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
            'md_branch_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_division_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'submissiondate'        => ['type' => 'timestamp', 'null' => false],
            'minimal_time'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'estimate_time'         => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'startdate'             => ['type' => 'timestamp', 'null' => false],
            'enddate'               => ['type' => 'timestamp', 'null' => false],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'isapproved'            => ['type' => 'CHAR', 'constraint' => 1, 'null' => true],
            'approved_by'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'approveddate'          => ['type' => 'timestamp', 'null' => true],
            'sys_wfscenario_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ]);

        $this->forge->addKey('trx_master_bundling_id', true);
        $this->forge->createTable('trx_master_bundling', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_master_bundling', true);
    }
}
