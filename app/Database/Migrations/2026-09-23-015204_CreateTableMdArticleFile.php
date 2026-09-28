<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableMdArticleFile extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'md_article_file_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'md_article_id'      => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'file_path'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'file_name'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'file_size'          => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'file_type'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at'         => ['type' => 'timestamp default current_timestamp'],
            'created_by'         => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'updated_at'         => ['type' => 'timestamp default current_timestamp'],
            'updated_by'         => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'isactive'           => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'Y'],
        ]);

        $this->forge->addPrimaryKey('md_article_file_id');
        $this->forge->addForeignKey('md_article_id', 'md_article', 'md_article_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('md_article_file', true);
    }

    public function down()
    {
        $this->forge->dropTable('md_article_file', true);
    }
}