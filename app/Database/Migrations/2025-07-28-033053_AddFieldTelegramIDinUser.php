<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldTelegramIDinUser extends Migration
{
    public function up()
    {
        $fields = [
            'telegram_username' =>  ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'telegram_id' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
        ];

        $this->forge->addColumn('sys_user', $fields);
    }

    public function down()
    {
        $fields = ["telegram_username", 'telegram_id'];

        $this->forge->dropColumn('sys_user', $fields);
    }
}