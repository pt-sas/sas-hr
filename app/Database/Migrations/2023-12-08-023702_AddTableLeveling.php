<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTableLeveling extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'md_leveling_id' => [
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
        $this->forge->addKey('md_leveling_id', true);
        $this->forge->createTable('md_leveling');
    }

    public function down()
    {
        $this->forge->dropTable('md_leveling');
    }
}
