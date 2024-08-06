<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldCommentTrxAbsent extends Migration
{
    public function up()
    {
        $fields = [
            'comment'           =>  [
                'type'          => 'VARCHAR',
                'constraint'    => 255,
                'after'         => 'reason',
                'null'          => true
            ]
        ];

        $this->forge->addColumn('trx_absent', $fields);
    }

    public function down()
    {
        $fields = ['comment'];

        $this->forge->dropColumn('trx_absent', $fields);
    }
}
