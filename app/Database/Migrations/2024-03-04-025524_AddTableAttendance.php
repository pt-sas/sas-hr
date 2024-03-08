<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTableAttendance extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_attendance_id'         => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'                => ['type' => 'timestamp default current_timestamp'],
            'created_by'                => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'                => ['type' => 'timestamp default current_timestamp'],
            'updated_by'                => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'nik'                       => ['type' => 'VARCHAR', 'constraint' => 6, 'null' => false],
            'date'                      => ['type' => 'timestamp default current_timestamp'],
            'clock_in'                  => ['type' => 'time', 'constraint' => 6, 'null' => true],
            'clock_out'                 => ['type' => 'time', 'constraint' => 6, 'null' => true],
            'absent'                    => ['type' => 'CHAR', 'constraint' => 1, 'null' => false]
        ]);

        $this->forge->addKey('trx_attendance_id', true);
        $this->forge->createTable('trx_attendance', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_attendance');
    }
}
