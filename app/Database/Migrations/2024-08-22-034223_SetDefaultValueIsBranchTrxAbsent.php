<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SetDefaultValueIsBranchTrxAbsent extends Migration
{
    public function up()
    {
        $fields = [
            'isbranch'        =>  [
                'type'          => 'CHAR',
                'constraint'    => 1,
                'after'         => 'md_leavetype_id',
                'default' => 'N'
            ]
        ];

        $this->forge->modifyColumn('trx_absent', $fields);
    }

    public function down()
    {
        $fields = [
            'isbranch'
        ];
        $this->forge->modifyColumn('trx_absent', $fields);
    }
}
