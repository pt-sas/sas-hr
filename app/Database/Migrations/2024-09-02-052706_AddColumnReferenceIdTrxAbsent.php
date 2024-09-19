<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnReferenceIdTrxAbsent extends Migration
{
    public function up()
    {
        $fields = [
            'reference_id'    =>  [
                'type'          => 'INT',
                'constraint'    => 11,
                'null'          => true
            ]
        ];

        $this->forge->addColumn('trx_absent', $fields);
    }

    public function down()
    {
        $fields = ['reference_id'];

        $this->forge->dropColumn('trx_absent', $fields);
    }
}