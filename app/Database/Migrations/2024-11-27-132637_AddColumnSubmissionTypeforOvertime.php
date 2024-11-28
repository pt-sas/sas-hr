<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnSubmissionTypeforOvertime extends Migration
{
    public function up()
    {
        $fields = [
            'submissiontype' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
                'after'     => 'submissiondate'
            ],
        ];

        $this->forge->addColumn('trx_overtime', $fields);
    }

    public function down()
    {
        $fields = ['submissiontype'];

        $this->forge->dropColumn('trx_overtime', $fields);
    }
}