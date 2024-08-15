<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldOutsourceTrxOvertime extends Migration
{
    public function up()
    {
        $fields = [
            'isemployee'        =>  [
                'type'          => 'CHAR',
                'constraint'    => 1,
                'after'         => 'approveddate',
                'null'          => false,
                'default'       => 'Y'
            ],
            'receiveddate'      =>  [
                'type'          => 'TIMESTAMP',
                'after'         => 'submissiondate',
                'null'          => true,
            ],
            'md_supplier_id'    =>  [
                'type'          => 'INT',
                'constraint'    => 11,
                'after'         => 'isemployee',
                'null'          => true,
            ],
        ];

        $this->forge->addColumn('trx_overtime', $fields);
    }

    public function down()
    {
        $fields = ['isemployee', 'receiveddate', 'md_supplier_id'];

        $this->forge->dropColumn('trx_overtime', $fields);
    }
}
