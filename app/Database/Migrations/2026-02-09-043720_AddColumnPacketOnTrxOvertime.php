<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnPacketOnTrxOvertime extends Migration
{
    public function up()
    {

        $fields = [
            'trx_bundling_id'    =>  [
                'type'              => 'INT',
                'constraint'        => '11',
                'null'              => true
            ],
            'ispacket'          => [
                'type'  => 'CHAR',
                'constraint'    => 1
            ]
        ];

        $this->forge->addColumn('trx_overtime', $fields);
    }

    public function down()
    {

        $fields = ['trx_bundling_id', 'ispacket'];

        $this->forge->dropColumn('trx_overtime', $fields);
    }
}
