<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTrxInterviewDetail extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_interview_detail_id'   => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'                  => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'                => ['type' => 'timestamp default current_timestamp'],
            'created_by'                => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'                => ['type' => 'timestamp default current_timestamp'],
            'updated_by'                => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'trx_interview_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_question_group_id'      => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'no'                        => ['type' => 'INT', 'constraint' => 10, 'null' => false],
            'md_question_id'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'answertype'                => ['type' => 'CHAR', 'constraint' => 100, 'null' => true],
            'answer'                    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]
        ]);

        $this->forge->addKey('trx_interview_detail_id', true);
        $this->forge->createTable('trx_interview_detail', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_interview_detail', true);
    }
}
