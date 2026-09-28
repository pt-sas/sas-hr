<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyContentInMdArticle extends Migration
{
    public function up()
    {
        $fields = [
            'content' => [
                'type' => 'MEDIUMTEXT',
                'null' => true,
            ],
        ];

        $this->forge->modifyColumn('md_article', $fields);
    }

    public function down()
    {
        $fields = [
            'content' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ];

        $this->forge->modifyColumn('md_article', $fields);
    }
}