<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnsValueBatch2 extends Migration
{
    public function up()
    {
        $fields = [
            'value'     =>  [
                'type'      => 'VARCHAR',
                'after'     => 'updated_by',
                'constraint' => 20,
                'null'      => false
            ]
        ];

        $this->forge->addColumn('md_day', $fields);
    }

    public function down()
    {
        $fields = ['value'];

        $this->forge->dropColumn('md_day', $fields);
    }
}
