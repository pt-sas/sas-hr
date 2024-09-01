<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransactionTable extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'md_transaction_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'transactiondate'       => ['type' => 'timestamp', 'null' => false],
            'transactiontype'       => ['type' => 'CHAR', 'constraint' => 2, 'null' => false],
            'year'                  => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'record_id'             => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'table'                 => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'amount'                => ['type' => 'Decimal', 'constraint' => '10,2', 'null' => false, 'default' => 0.00],
            'isprocessed'           => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'N'],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]
        ]);

        $this->forge->addKey('md_transaction_id', true);
        $this->forge->createTable('md_transaction', true);
    }

    public function down()
    {
        $this->forge->dropTable('md_transaction', true);
    }
}
