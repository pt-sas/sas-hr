<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnIsLemburEmployee extends Migration
{
    public function up()
    {
        $fields = [
            'isovertime'              =>  [
                'type'          => 'CHAR',
                'constraint'    => 1,
                'null'          => false,
                'default'       => 'N'

            ]
        ];

        $this->forge->addColumn('md_employee', $fields);
    }

    public function down()
    {
        $fields = ['isovertime'];

        $this->forge->dropColumn('md_employee', $fields);
    }
}
