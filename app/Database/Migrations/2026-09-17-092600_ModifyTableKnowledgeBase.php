<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyColumnOnTableArticle extends Migration
{
    public function up()
    {
        $columns = $this->db->getFieldNames('articles');

        // 1. Drop subcategory if present
        if (in_array('md_subcategory_id', $columns)) {
            $this->forge->dropColumn('articles', 'md_subcategory_id');
        }

        $fields = [];

        // 2. Safely detect category column to rename
        if (in_array('category_id', $columns)) {
            $fields['category_id'] = [
                'name'       => 'md_doctype_id',
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false
            ];
        } elseif (in_array('md_category_id', $columns)) {
            $fields['md_category_id'] = [
                'name'       => 'md_doctype_id',
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false
            ];
        }

        // 3. Update updated_by & status constraints
        if (in_array('updated_by', $columns)) {
            $fields['updated_by'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true
            ];
        }

        if (in_array('status', $columns)) {
            $fields['status'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'DRAFT'
            ];
        }

        if (!empty($fields)) {
            $this->forge->modifyColumn('articles', $fields);
        }
    }

    public function down()
    {
        $columns = $this->db->getFieldNames('articles');

        if (in_array('md_doctype_id', $columns)) {
            $fields = [
                'md_doctype_id' => [
                    'name'       => 'category_id',
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => false
                ]
            ];
            $this->forge->modifyColumn('articles', $fields);
        }
    }
}