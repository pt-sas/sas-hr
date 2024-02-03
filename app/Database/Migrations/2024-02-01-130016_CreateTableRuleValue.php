<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableRuleValue extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'md_rule_value_id'      => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_rule_detail_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => false],
            'value'                 => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => false]
        ]);

        $this->forge->addKey('md_rule_value_id', true);
        $this->forge->createTable('md_rule_value', true);
    }

    public function down()
    {
        $this->forge->dropTable('md_rule_value', true);
    }
}
