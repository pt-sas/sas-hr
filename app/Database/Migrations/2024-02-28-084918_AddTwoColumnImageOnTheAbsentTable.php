<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTwoColumnImageOnTheAbsentTable extends Migration
{
    public function up()
    {
        $fields = [
            'image2'            =>  [
                'type'          => 'VARCHAR',
                'constraint'    => 255,
                'after'         => 'image',
                'null'          => true
            ],
            'image3'            =>  [
                'type'          => 'VARCHAR',
                'constraint'    => 255,
                'after'         => 'image2',
                'null'          => true
            ]
        ];

        $this->forge->addColumn('trx_absent', $fields);
    }

    public function down()
    {
        $fields = ['image2', 'image3'];

        $this->forge->dropColumn('trx_absent', $fields);
    }
}
