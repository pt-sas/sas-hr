<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableMdArticle extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'md_article_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'        => ['type' => 'timestamp default current_timestamp'],
            'created_by'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'        => ['type' => 'timestamp default current_timestamp'],
            'updated_by'        => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'isactive'          => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'Y'],
            'sys_ref_detail_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'title'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'slug'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'content'           => ['type' => 'TEXT', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('md_article_id');
        $this->forge->createTable('md_article', true);
    }

    public function down()
    {
        $this->forge->dropTable('md_article', true);
    }
}