<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnEndTimeRealization extends Migration
{
    public function up()
    {
        $fields = [
            'enddate_realization' => ['type' => 'timestamp', 'null' => false, 'default' => '0000-00-00 00:00:00'],
        ];

        $this->forge->addColumn('trx_overtime_detail', $fields);
    }

    public function down()
    {
        $fields = ['enddate_realization'];

        $this->forge->dropColumn('trx_overtime_detail', $fields);
    }
}