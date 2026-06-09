<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableUserDevices extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'sys_user_device_id'    => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'Y'],
            'sys_user_id'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'device_token'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'fcm_token'             => ['type' => 'TEXT'],
            'platform'              => ['type' => 'ENUM', 'constraint' => ['android', 'ios', 'web'], 'default' => 'android']
        ]);

        $this->forge->addKey('sys_user_device_id', true);
        $this->forge->createTable('sys_user_device');
    }

    public function down()
    {
        $this->forge->dropTable('sys_user_device');
    }
}
