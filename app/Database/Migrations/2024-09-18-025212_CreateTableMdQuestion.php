<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableMdQuestion extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'md_question_id'                => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'                      => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'                    => ['type' => 'timestamp default current_timestamp'],
            'created_by'                    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'                    => ['type' => 'timestamp default current_timestamp'],
            'updated_by'                    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_question_group_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'no'                            => ['type' => 'INT', 'constraint' => 10, 'null' => false],
            'question'                      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'answertype'                    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]
        ]);

        $this->forge->addKey('md_question_id', true);
        $this->forge->createTable('md_question', true);
    }

    public function down()
    {
        $this->forge->dropTable('md_question', true);
    }
}
