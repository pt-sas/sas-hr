<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableArticle extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'article_id'   => ['type' => 'INT', 'null' => false, 'auto_increment' => true],
            'created_at'   => ['type' => 'timestamp default current_timestamp'],
            'created_by'   => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'   => ['type' => 'timestamp default current_timestamp'],
            'updated_by'   => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'isactive'     => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'Y'],
            'category_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'title'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'slug'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'content'      => ['type' => 'TEXT', 'null' => true],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft']
        ]);

        $this->forge->addPrimaryKey('article_id');
        $this->forge->createTable('articles', true);
    }

    public function down()
    {
        $this->forge->dropTable('articles', true);
    }
}