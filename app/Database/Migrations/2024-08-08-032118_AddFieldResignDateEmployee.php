<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldResignDateEmployee extends Migration
{
    public function up()
    {
        $fields = [
            'resigndate'        =>  [
                'type'          => 'TIMESTAMP',
                'after'         => 'registerdate',
                'null'          => true
            ]
        ];

        $this->forge->addColumn('md_employee', $fields);
    }

    public function down()
    {
        $fields = ['resigndate'];

        $this->forge->dropColumn('md_employee', $fields);
    }
}
