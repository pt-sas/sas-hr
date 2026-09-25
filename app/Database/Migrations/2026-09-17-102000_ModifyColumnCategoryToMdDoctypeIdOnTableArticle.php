<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyColumnCategoryToMdDoctypeIdOnTableArticle extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('category_id', 'articles')) {
            $this->db->query("ALTER TABLE `articles` CHANGE COLUMN `category_id` `md_doctype_id` INT(11) NOT NULL;");
        } elseif ($this->db->fieldExists('md_category_id', 'articles')) {
            $this->db->query("ALTER TABLE `articles` CHANGE COLUMN `md_category_id` `md_doctype_id` INT(11) NOT NULL;");
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('md_doctype_id', 'articles')) {
            $this->db->query("ALTER TABLE `articles` CHANGE COLUMN `md_doctype_id` `md_category_id` INT(11) NOT NULL;");
        }
    }
}