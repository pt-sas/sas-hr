<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBranchAccessTable extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'sys_user_branchaccess_id'      => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'sys_user_id'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_branch_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => false]
        ]);

        $this->forge->addKey('sys_user_branchaccess_id', true);
        $this->forge->createTable('sys_user_branchaccess', true);
    }

    public function down()
    {
        $this->forge->dropTable('sys_user_branchaccess', true);
    }
}
