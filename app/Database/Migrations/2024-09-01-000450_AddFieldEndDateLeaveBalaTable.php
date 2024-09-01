<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldEndDateLeaveBalaTable extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $modifyFields = [
            'startdate'                 => [
                'after'                 => 'submissiondate',
                'type'                  => 'TIMESTAMP',
                'null'                  => true,
                'default'               => null
            ],
        ];

        $this->forge->modifyColumn('trx_leavebalance', $modifyFields);

        $addFields = [
            'enddate'                   => [
                'after'                 => 'startdate',
                'type'                  => 'TIMESTAMP',
                'null'                  => true,
                'default'               => null
            ],
        ];

        $this->forge->addColumn('trx_leavebalance', $addFields);
    }

    public function down()
    {
        $fields = ['enddate'];

        $this->forge->dropColumn('trx_leavebalance', $fields);
    }
}
