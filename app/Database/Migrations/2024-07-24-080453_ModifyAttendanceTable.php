<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyAttendanceTable extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {

        $this->forge->dropTable('trx_attendance', true);
        $this->forge->renameTable('trx_attend', 'trx_attendance');

        $fields = [
            'trx_attend_id'         => [
                'name'              => 'trx_attendance_id',
                'type'              => 'INT',
                'constraint'        => 11,
                'null'              => false,
                'auto_increment'    => true
            ],
        ];
        $this->forge->modifyColumn('trx_attendance', $fields);
    }

    public function down()
    {
    }
}
