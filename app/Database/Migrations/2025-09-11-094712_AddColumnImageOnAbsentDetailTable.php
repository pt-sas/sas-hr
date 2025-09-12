<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnImageOnAbsentDetailTable extends Migration
{
    public function up()
    {
        $fields = [
            'image'            =>  [
                'type'          => 'VARCHAR',
                'constraint'    => 255,
                'null'          => true
            ],
        ];

        $this->forge->addColumn('trx_absent_detail', $fields);
    }

    public function down()
    {
        $fields = ['image'];

        $this->forge->dropColumn('trx_absent_detail', $fields);
    }
}
