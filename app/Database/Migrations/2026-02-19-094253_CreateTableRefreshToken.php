<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableRefreshToken extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'sys_refresh_token_id'  => ['type' => 'INT', 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'Y'],
            'sys_user_id'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'user_agent'            => ['type' => 'VARCHAR',  'constraint' => 255, 'null' => false],
            'token'                 => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'expired_date'          => ['type' => 'timestamp', 'null' => false],
            'isrevoked'             => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'N']
        ]);

        $this->forge->addPrimaryKey('sys_refresh_token_id');
        $this->forge->createTable('sys_refresh_token', true);
    }

    public function down()
    {
        $this->forge->dropTable('sys_refresh_token', true);
    }
}
