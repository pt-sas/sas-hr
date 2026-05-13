<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Tablebroadcastqueue extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_broadcast_queue_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 100000],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'Y'],
            'trx_broadcast_id'      => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'status'                => ['type' => 'CHAR', 'constraint' => 2, 'null' => false],
            'starttime'             => ['type' => 'timestamp', 'null' => true],
            'endtime'               => ['type' => 'timestamp', 'null' => true],
        ]);

        $this->forge->addKey('trx_broadcast_queue_id', true);
        $this->forge->createTable('trx_broadcast_queue');
    }

    public function down()
    {
        $this->forge->dropTable('trx_broadcast_queue');
    }
}
