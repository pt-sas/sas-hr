<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyColumnStatusOvertimeDetail extends Migration
{
    public function up()
    {
        $fields = [
            'status' => [
                'name'       => 'isagree',
                'type'       => 'CHAR',
                'constraint' => 1,
                'null'       => true,
            ],
        ];

        $this->forge->modifyColumn('trx_overtime_detail', $fields);
    }

    public function down()
    {
        $fields = [
            'isagree' => [
                'name'       => 'status',
                'type'       => 'CHAR',
                'constraint' => 1,
                'null'       => true,
            ],
        ];

        $this->forge->modifyColumn('trx_overtime_detail', $fields);
    }
}