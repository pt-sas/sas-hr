<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnTableforAbsentDetail extends Migration
{
    public function up()
    {
        $fields = [
            'table'                     => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false, 'before' => 'ref_absent_detail_id'],
        ];

        $this->forge->addColumn('trx_absent_detail', $fields);
    }

    public function down()
    {
        $fields = ['table'];

        $this->forge->dropColumn('trx_absent_detail', $fields);
    }
}
