<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnStartDateRealizationonAbsent extends Migration
{
    public function up()
    {
        $fields = [
            'startdate_realization' => ['type' => 'timestamp', 'null' => false, 'default' => '0000-00-00 00:00:00', 'after' => 'branch_to'],
        ];

        $this->forge->addColumn('trx_absent', $fields);
    }

    public function down()
    {
        $fields = ['startdate_realization'];

        $this->forge->dropColumn('trx_absent', $fields);
    }
}