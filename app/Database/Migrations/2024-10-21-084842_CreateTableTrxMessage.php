<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTrxMessage extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_message_id'            => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'                  => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'                => ['type' => 'timestamp default current_timestamp'],
            'created_by'                => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'                => ['type' => 'timestamp default current_timestamp'],
            'updated_by'                => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'author_id'                    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'subject'                   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'body'                      => ['type' => 'VARCHAR', 'constraint' => 6000, 'null' => true],
            'messagedate'               => ['type' => 'timestamp', 'null' => false],
            'recipient_id'              => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'isread'                    => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'N'],
            'isfavorite'                    => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'N']
        ]);

        $this->forge->addKey('trx_message_id', true);

        $this->forge->createTable('trx_message', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_message', true);
    }
}
