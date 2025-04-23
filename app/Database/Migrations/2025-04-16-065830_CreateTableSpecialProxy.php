<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableSpecialProxy extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_proxy_special_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'documentno'            => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'sys_user_from'         => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'sys_user_to'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'submissiontype'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'submissiondate'        => ['type' => 'date', 'null' => false],
            'startdate'             => ['type' => 'timestamp', 'null' => false],
            'enddate'               => ['type' => 'timestamp', 'null' => false],
            'reason'                => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'docstatus'             => ['type' => 'CHAR', 'constraint' => 2, 'null' => false],
            'isapproved'            => ['type' => 'CHAR', 'constraint' => 1, 'null' => true],
            'receiveddate'          => ['type' => 'timestamp', 'null' => true],
            'approveddate'          => ['type' => 'timestamp', 'null' => true],
            'sys_wfscenario_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ]);

        $this->forge->addKey('trx_proxy_special_id', true);
        $this->forge->createTable('trx_proxy_special', true);
        $this->db->query('ALTER TABLE trx_proxy_special AUTO_INCREMENT = 100001');
    }

    public function down()
    {
        $this->forge->dropTable('trx_proxy_special', true);
    }
}