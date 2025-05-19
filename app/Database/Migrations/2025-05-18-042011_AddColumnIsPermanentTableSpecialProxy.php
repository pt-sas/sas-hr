<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnIsPermanentTableSpecialProxy extends Migration
{
    public function up()
    {
        $fields = [
            'ispermanent' => ['type' => 'CHAR', 'default' => 'N', 'constraint' => 1, 'after' => 'reason']
        ];

        $this->forge->addColumn('trx_proxy_special', $fields);
    }

    public function down()
    {
        $fields = ['ispermanent'];

        $this->forge->dropColumn('trx_proxy_special', $fields);
    }
}