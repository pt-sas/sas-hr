<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyTableBundling extends Migration
{
    public function up()
    {
        $this->forge->dropTable('trx_bundling', true);
        $this->forge->dropTable('trx_bundling_participant', true);
        $this->forge->dropTable('trx_bundling_event', true);

        $this->forge->addField([
            'trx_bundling_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'trx_master_bundling_id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
            'minimum_hour'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'maximum_hour'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'compensation_type'     => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'period_type'           => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'result_oriented'       => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'task_description'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'project_value'         => ['type' => 'FLOAT', 'null' => false, 'default' => 0],
            'documentno'            => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_branch_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_division_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'submissiontype'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'submissiondate'        => ['type' => 'timestamp', 'null' => false],
            'startdate'             => ['type' => 'date', 'null' => false],
            'enddate'               => ['type' => 'date', 'null' => false],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'docstatus'             => ['type' => 'CHAR', 'constraint' => 2, 'null' => false],
            'isapproved'            => ['type' => 'CHAR', 'constraint' => 1, 'null' => true],
            'isspecial'             => ['type' => 'CHAR', 'constraint' => 1, 'null' => true],
            'approved_by'           => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'realization_date'      => ['type' => 'timestamp', 'null' => true],
            'realization_by'        => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'verified_date'         => ['type' => 'timestamp', 'null' => true],
            'verified_by'           => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'receiveddate'          => ['type' => 'timestamp', 'null' => true],
            'approveddate'          => ['type' => 'timestamp', 'null' => true],
            'sys_wfscenario_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'image'                 => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]
        ]);

        $this->forge->addKey('trx_bundling_id', true);
        $this->forge->createTable('trx_bundling', true);

        $this->forge->addField([
            'trx_bundling_task_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'trx_bundling_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'iscompleted'           => ['type' => 'VARCHAR', 'constraint' => 1, 'null' => false],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false]
        ]);

        $this->forge->addKey('trx_bundling_task_id', true);
        $this->forge->createTable('trx_bundling_task', true);

        $this->forge->addField([
            'trx_bundling_participant_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'trx_bundling_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'isjoined'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => true, 'after' => 'md_employee_id'],
            'total_time'            => ['type' => 'INT', 'constraint' => 2, 'null' => true],
            'total_amount'          => ['type' => 'decimal', 'constraint' => '15,2', 'null' => true],
        ]);

        $this->forge->addKey('trx_bundling_participant_id', true);
        $this->forge->createTable('trx_bundling_participant', true);

        $this->forge->addField([
            'trx_bundling_event_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'trx_bundling_participant_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'date'                  => ['type' => 'timestamp', 'null' => false],
            'starttime'             => ['type' => 'TIME', 'null' => false],
            'endtime'               => ['type' => 'TIME', 'null' => false],
            'time'                  => ['type' => 'INT', 'constraint' => 11, 'null' => false],
        ]);

        $this->forge->addKey('trx_bundling_event_id', true);
        $this->forge->createTable('trx_bundling_event', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_bundling', true);
        $this->forge->dropTable('trx_bundling_participant', true);
        $this->forge->dropTable('trx_bundling_event', true);
        $this->forge->dropTable('trx_bundling_task', true);

        $this->forge->addField([
            'trx_bundling_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'documentno'            => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
            'bundling_type'         => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_branch_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_division_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'submissiontype'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'submissiondate'        => ['type' => 'timestamp', 'null' => false],
            'estimate_time'         => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'startdate'             => ['type' => 'timestamp', 'null' => false],
            'enddate'               => ['type' => 'timestamp', 'null' => false],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'docstatus'             => ['type' => 'CHAR', 'constraint' => 2, 'null' => false],
            'isapproved'            => ['type' => 'CHAR', 'constraint' => 1, 'null' => true],
            'approved_by'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'receiveddate'          => ['type' => 'timestamp', 'null' => true],
            'approveddate'          => ['type' => 'timestamp', 'null' => true],
            'sys_wfscenario_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ]);

        $this->forge->addKey('trx_bundling_id', true);
        $this->forge->createTable('trx_bundling', true);

        $this->forge->addField([
            'trx_bundling_participant_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'trx_bundling_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'total_time'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'total_amount'          => ['type' => 'decimal', 'constraint' => '15,2', 'null' => true],
        ]);

        $this->forge->addKey('trx_bundling_participant_id', true);
        $this->forge->createTable('trx_bundling_participant', true);

        $this->forge->addField([
            'trx_bundling_event_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'trx_bundling_participant_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'trx_overtime_detail_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'date'                  => ['type' => 'timestamp', 'null' => false],
            'time'                  => ['type' => 'INT', 'constraint' => 11, 'null' => false],
        ]);

        $this->forge->addKey('trx_bundling_event_id', true);
        $this->forge->createTable('trx_bundling_event', true);
    }
}
