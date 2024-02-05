<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrateTableRule extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'md_rule_id'            => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => false],
            'condition'             => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'value'                 => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'menu_url'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'priority'              => ['type' => 'DECIMAL', 'constraint' => 10, 'null' => true],
            'isdetail'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'N'],
        ]);

        $this->forge->addKey('md_rule_id', true);
        $this->forge->createTable('md_rule', true);
    }

    public function down()
    {
        $this->forge->dropTable('md_rule', true);
    }
}
