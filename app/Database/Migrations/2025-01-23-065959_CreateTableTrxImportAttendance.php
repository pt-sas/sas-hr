<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTrxImportAttendance extends Migration
{
    public function up()
    {
        $this->forge->addfield([
            'trx_import_attendance_id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true, 'null' => false],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'documentno'            => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'submissiondate'        => ['type' => 'date', 'null' => false],
            'submissiontype'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'startdate'             => ['type' => 'timestamp', 'null' => false],
            'enddate'               => ['type' => 'timestamp', 'null' => false],
            'reason'                => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'docstatus'             => ['type' => 'CHAR', 'constraint' => 2, 'null' => false],
            'isapproved'            => ['type' => 'CHAR', 'constraint' => 1, 'null' => false],
            'approveddate'          => ['type' => 'date', 'null' => false],
            'sys_wfscenario_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true]
        ]);

        $this->forge->addKey('trx_import_attendance_id', true);
        $this->forge->createTable('trx_import_attendance', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_import_attendance', true);
    }
}
