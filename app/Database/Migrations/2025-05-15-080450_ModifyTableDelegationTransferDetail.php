<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyTableDelegationTransferDetail extends Migration
{
    public function up()
    {
        $fields = [
            'istransfered' => ['type' => 'CHAR', 'constraint' => 2, 'null' => true]
        ];

        $this->forge->modifyColumn('trx_delegation_transfer_detail', $fields);
    }

    public function down()
    {
        $fields = ['istransfered' => ['type' => 'CHAR', 'constraint' => 1, 'null' => true]];

        $this->forge->modifyColumn('trx_delegation_transfer_detail', $fields);
    }
}