<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocTypeTable extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'md_doctype_id'         => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]
        ]);

        $this->forge->addKey('md_doctype_id', true);
        $this->forge->createTable('md_doctype', true);
    }

    public function down()
    {
        $this->forge->dropTable('md_doctype', true);
    }
}
