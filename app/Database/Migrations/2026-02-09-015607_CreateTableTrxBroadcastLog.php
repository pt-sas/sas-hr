<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTrxBroadcastLog extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_broadcast_log_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 100000],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'Y'],
            'trx_broadcast_id'      => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'sentmethod'            => ['type' => 'TEXT', 'default' => '', 'null' => false],
            'error_message'         => ['type' => 'TEXT', 'null' => true],
        ]);

        $this->forge->addKey('trx_broadcast_log_id', true);
        $this->forge->createTable('trx_broadcast_log');
    }

    public function down()
    {
        $this->forge->dropTable('trx_broadcast_log');
    }
}
