<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnMandatoryDutaTablePosition extends Migration
{
    public function up()
    {
        $fields = [
            'ismandatoryduta' => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'N']
        ];

        $this->forge->addColumn('md_position', $fields);
    }

    public function down()
    {
        $fields = ['ismandatoryduta'];

        $this->forge->dropColumn('md_position', $fields);
    }
}
