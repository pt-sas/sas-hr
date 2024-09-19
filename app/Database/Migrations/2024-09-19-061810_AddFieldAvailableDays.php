<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldAvailableDays extends Migration
{
    public function up()
    {
        $fields = [
            'availableleavedays'    =>  [
                'type'              => 'DECIMAL',
                'constraint'        => '10,2',
            ]
        ];

        $this->forge->addColumn('trx_absent', $fields);
    }

    public function down()
    {
        $fields = ['availableleavedays'];

        $this->forge->dropColumn('trx_absent', $fields);
    }
}
