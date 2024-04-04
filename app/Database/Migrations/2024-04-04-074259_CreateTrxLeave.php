<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTrxLeave extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'trx_leavebalance_id'   => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'record_id'             => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'table'                 => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'submissiondate'        => ['type' => 'timestamp', 'null' => true],
            'amount'                => ['type' => 'Decimal', 'constraint' => 10, 'null' => false],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]
        ]);

        $this->forge->addKey('trx_leavebalance_id', true);
        $this->forge->createTable('trx_leavebalance', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_leavebalance', true);
    }
}
