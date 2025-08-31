<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnTelegramOnTableEmployee extends Migration
{
    public function up()
    {
        $fields = [
            'telegram_username' =>  ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'telegram_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
        ];

        $this->forge->addColumn('md_employee', $fields);
    }

    public function down()
    {
        $fields = ["telegram_username", 'telegram_id'];

        $this->forge->dropColumn('md_employee', $fields);
    }
}