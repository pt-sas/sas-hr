<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTrxNews extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_news_id'           => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'date'                  => ['type' => 'timestamp', 'null' => false],
            'reason'                => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);

        $this->forge->addKey('trx_news_id', true);
        $this->forge->createTable('trx_news', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_news', true);
    }
}
