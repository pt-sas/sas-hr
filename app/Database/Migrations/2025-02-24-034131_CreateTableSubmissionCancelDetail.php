<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableSubmissionCancelDetail extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_submission_cancel_detail_id'    => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'                  => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'                => ['type' => 'timestamp default current_timestamp'],
            'created_by'                => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'                => ['type' => 'timestamp default current_timestamp'],
            'updated_by'                => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'trx_submission_cancel_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'lineno'                    => ['type' => 'INT', 'constraint' => 2,  'null' => false],
            'md_employee_id'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'date'                      => ['type' => 'timestamp default current_timestamp'],
            'isagree'                   => ['type' => 'CHAR', 'constraint' => 1, 'null' => false],
            'reference_id'              => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'description'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ref_table'          => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
        ]);

        $this->forge->addKey('trx_submission_cancel_detail_id', true);
        $this->forge->createTable('trx_submission_cancel_detail', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_submission_cancel_detail', true);
    }
}