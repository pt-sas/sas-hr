<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAttendanceMachineTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'md_attendance_machines_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'                      => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'                    => ['type' => 'timestamp default current_timestamp'],
            'created_by'                    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'                    => ['type' => 'timestamp default current_timestamp'],
            'updated_by'                    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'serialnumber'                  => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'name'                          => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'additional_info'               => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'attlog_stamp'                  => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'default' => NULL],
            'operlog_stamp'                 => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'default' => NULL],
            'attphotolog_stamp'             => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'default' => NULL],
            'delay'                         => ['type' => 'TINYINT', 'constraint' => 4, 'null' => true, 'default' => NULL],
            'error_delay'                   => ['type' => 'TINYINT', 'constraint' => 4, 'null' => true, 'default' => NULL],
            'trans_times'                   => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
            'trans_interval'                => ['type' => 'TINYINT', 'constraint' => 4, 'null' => false, 'default' => 1],
            'trans_flag'                    => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
            'timezone'                      => ['type' => 'TINYINT', 'constraint' => 4, 'null' => false, 'default' => 7],
            'realtime'                      => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'encrypt'                       => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'N'],
            'server_version'                => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'md_branch_id'                  => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'description'                   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]
        ]);

        $this->forge->addKey('md_attendance_machines_id', true);
        $this->forge->createTable('md_attendance_machines', true);
    }

    public function down()
    {
        $this->forge->dropTable('md_attendance_machines', true);
    }
}
