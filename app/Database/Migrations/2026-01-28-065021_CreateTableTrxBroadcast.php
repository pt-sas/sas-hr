<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTrxBroadcast extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_broadcast_id'   => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'title'                  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'message'                => ['type' => 'TEXT'],
            'attachment'                  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'attachment2'                  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'attachment3'                  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_branch_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_division_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'sentmethod'            => ['type' => 'VARCHAR', 'constraint' => 5, 'default' => '', 'null' => false],
            'effective_date'            => ['type' => 'TIMESTAMP', 'null' => true],
            'lastupdate'            => ['type' => 'TIMESTAMP', 'null' => true, 'default' => null],
            'is_sent'               => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'N'],
        ]);

        $this->forge->addKey('trx_broadcast_id', true);
        $this->forge->createTable('trx_broadcast');
    }

    public function down()
    {
        $this->forge->dropTable('trx_broadcast');
    }
}
