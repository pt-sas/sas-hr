<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTrxDelegationTransferDetail extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_delegation_transfer_detail_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'trx_delegation_transfer_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'lineno'                => ['type' => 'INT', 'constraint' => 2,  'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'istransfered'            => ['type' => 'CHAR', 'constraint' => 1, 'null' => true],
        ]);

        $this->forge->addKey('trx_delegation_transfer_detail_id', true);
        $this->forge->createTable('trx_delegation_transfer_detail', true);
        $this->db->query('ALTER TABLE trx_delegation_transfer_detail AUTO_INCREMENT = 100001');
    }

    public function down()
    {
        $this->forge->dropTable('trx_delegation_transfer_detail', true);
    }
}