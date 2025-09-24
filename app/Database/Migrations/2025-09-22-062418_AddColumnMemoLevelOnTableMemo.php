<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnMemoLevelOnTableMemo extends Migration
{
    public function up()
    {
        $fields = [
            'memo_level' => ['type' => 'INT', 'constraint' => 1, 'null' => false],
        ];

        $this->forge->addColumn('trx_hr_memo', $fields);
    }

    public function down()
    {
        $fields = ['memo_level'];

        $this->forge->dropColumn('trx_hr_memo', $fields);
    }
}