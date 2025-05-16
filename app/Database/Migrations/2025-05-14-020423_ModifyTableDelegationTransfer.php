<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyTableDelegationTransfer extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('trx_delegation_transfer', 'date');

        $fields = [
            'startdate' => ['type' => 'timestamp', 'null' => false, 'after' => 'submissiondate'],
            'enddate' => ['type' => 'timestamp', 'null' => true, 'after' => 'startdate'],
            'ispermanent' => ['type' => 'CHAR', 'default' => 'N', 'constraint' => 1, 'after' => 'reason']
        ];

        $this->forge->addColumn('trx_delegation_transfer', $fields);
    }

    public function down()
    {
        $fields = [
            'startdate',
            'enddate',
            'ispermanent'
        ];

        $this->forge->dropColumn('trx_delegation_transfer', $fields);

        $field = ['date' => ['type' => 'timestamp', 'null' => false, 'after' => 'submissiondate']];

        $this->forge->addColumn('trx_delegation_transfer', $field);
    }
}