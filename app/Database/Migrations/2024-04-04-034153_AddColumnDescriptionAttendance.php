<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnDescriptionAttendance extends Migration
{
    public function up()
    {
        $fields = [
            'description'       =>  [
                'type'          => 'VARCHAR',
                'after'         => 'absent',
                'constraint'    => 255,
                'null'          => true
            ]
        ];

        $this->forge->addColumn('trx_attendance', $fields);
    }

    public function down()
    {
        $fields = ['description'];

        $this->forge->dropColumn('trx_attendance', $fields);
    }
}
