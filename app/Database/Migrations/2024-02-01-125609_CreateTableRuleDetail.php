<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableRuleDetail extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'md_rule_detail_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_rule_id'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => false],
            'operation'             => ['type' => 'VARCHAR', 'constraint' => 2, 'null' => true],
            'format_condition'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'condition'             => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'format_value'            => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'value'                 => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'isdetail'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'N'],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => false],
        ]);

        $this->forge->addKey('md_rule_detail_id', true);
        $this->forge->createTable('md_rule_detail', true);
    }

    public function down()
    {
        $this->forge->dropTable('md_rule_detail', true);
    }
}
