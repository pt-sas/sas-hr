<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyTableMasterBundling extends Migration
{
    public function up()
    {
        $this->forge->dropTable('trx_master_bundling', true);

        $this->forge->addField([
            'trx_master_bundling_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'value'                 => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'md_doctype_id'         => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'minimum_hour'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'compensation_type'     => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'period_type'           => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'project_value'         => ['type' => 'FLOAT', 'null' => false, 'default' => 0],
            'divisionrule'          => ['type' => 'CHAR', 'constraint' => 1, 'null' => false],
            'isprorate'             => ['type' => 'CHAR', 'constraint' => 1, 'null' => false],
            'submissiontype'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 510, 'null' => true],
            'docstatus'             => ['type' => 'CHAR', 'constraint' => 2, 'null' => false],
            'isapproved'            => ['type' => 'CHAR', 'constraint' => 1, 'null' => true],
            'approveddate'          => ['type' => 'timestamp', 'null' => true],
            'sys_wfscenario_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ]);

        $this->forge->addKey('trx_master_bundling_id', true);
        $this->forge->createTable('trx_master_bundling', true);

        $this->forge->addField([
            'trx_bundling_division_rule_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'trx_master_bundling_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_division_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'maximum_submission'    => ['type' => 'INT', 'constraint' => 6, 'null' => false],
        ]);

        $this->forge->addKey('trx_bundling_division_rule_id', true);
        $this->forge->createTable('trx_bundling_division_rule', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_bundling_division_rule', true);
        $this->forge->dropTable('trx_master_bundling_branch', true);
        $this->forge->dropTable('trx_master_bundling', true);

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
}
