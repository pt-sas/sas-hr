<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Addtablesmdskill extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'md_skill_id' => [
                'type'          => 'INT',
                'constraint'    => 6,
                'auto_increment' => true,
                'null'          => false
            ],
            'isactive'  => [
                'type'      => 'CHAR',
                'constraint'    => 2,
                'default'   => 'Y',
                'null'          => false
            ],
            'created_at'  => [
                'type'      => 'timestamp default current_timestamp',
                'null'          => false
            ],
            'created_by'  => [
                'type'      => 'INT',
                'null'          => false
            ],
            'updated_at'  => [
                'type'      => 'timestamp default current_timestamp',
                'null'          => false
            ],
            'updated_by'  => [
                'type'      => 'INT',
                'null'          => false
            ],
            'value'       => [
                'type'      => 'VARCHAR',
                'constraint' => 20,
                'null' => false
            ],
            'name'       => [
                'type'      => 'VARCHAR',
                'constraint' => 40,
                'null' => false
            ],
            'description'       => [
                'type'      => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
        ]);
        $this->forge->addKey('md_skill_id', true);
        $this->forge->createTable('md_skill');
    }

    public function down()
    {
        $this->forge->dropTable('md_skill');
    }
}