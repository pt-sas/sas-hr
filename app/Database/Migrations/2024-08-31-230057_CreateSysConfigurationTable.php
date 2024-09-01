<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSysConfigurationTable extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'sys_configuration_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
            'value'                 => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]
        ]);

        $this->forge->addKey('sys_configuration_id', true);
        $this->forge->createTable('sys_configuration', true);

        $this->db->query('ALTER TABLE sys_configuration AUTO_INCREMENT = 100001');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE sys_configuration AUTO_INCREMENT = 1');
        $this->forge->dropTable('sys_configuration', true);
    }
}
