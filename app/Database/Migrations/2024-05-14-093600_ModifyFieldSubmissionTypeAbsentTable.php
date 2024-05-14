<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyFieldSubmissionTypeAbsentTable extends Migration
{
    public function up()
    {
        $fields = [
            'submissiontype' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
        ];

        $this->forge->modifyColumn('trx_absent', $fields);
        $this->forge->modifyColumn('trx_allow_attendance', $fields);
    }

    public function down()
    {
        $fields = [
            'submissiontype' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => false,
            ],
        ];

        $this->forge->modifyColumn('trx_absent', $fields);
        $this->forge->modifyColumn('trx_allow_attendance', $fields);
    }
}
