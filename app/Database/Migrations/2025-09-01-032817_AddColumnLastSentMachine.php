<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnLastSentMachine extends Migration
{
    public function up()
    {
        $fields = [
            'last_sent'   =>  [
                'type'          => 'timestamp NULL DEFAULT NULL',
                'after'         => 'description'
            ]
        ];

        $this->forge->addColumn('md_attendance_machines', $fields);
    }

    public function down()
    {
        $fields = ['last_sent'];
        $this->forge->dropColumn('md_attendance_machines', $fields);
    }
}
