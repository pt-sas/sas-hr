<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocActionTable extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'sys_docaction_id'      => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'sys_role_id'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'menu'                  => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'ref_list'              => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false]
        ]);
        $this->forge->addKey('sys_docaction_id', true);
        $this->forge->createTable('sys_docaction', true);
    }

    public function down()
    {
        $this->forge->dropTable('sys_docaction', true);
    }
}
