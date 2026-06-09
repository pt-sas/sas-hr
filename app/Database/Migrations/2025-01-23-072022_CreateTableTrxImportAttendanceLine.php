<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTrxImportAttendanceLine extends Migration
{
    public function up()
    {
        $this->forge->addfield([
            'trx_import_attendance_detail_id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true, 'null' => false],
            'isactive'                        => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'trx_import_attendance_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'lineno'                => ['type' => 'DECIMAL', 'constraint' => '10,0', 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'nik'                   => ['type' => 'CHAR', 'constraint'  => 12, 'null' => false],
            'clock_in'              => ['type' => 'timestamp', 'null' => false],
            'clock_out'             => ['type' => 'timestamp', 'null' => false],
            'isinserted'            => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'N'],
        ]);

        $this->forge->addKey('trx_import_attendance_detail_id', true);
        $this->forge->createTable('trx_import_attendance_detail', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_import_attendance_detail', true);
    }
}