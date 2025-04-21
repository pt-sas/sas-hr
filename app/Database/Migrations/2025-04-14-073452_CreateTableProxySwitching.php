<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\MySQLi\Forge;

class CreateTableProxySwtiching extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_proxy_switching_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'Y', 'null' => false],
            'trx_proxy_special_detail_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'proxytype'             => ['type' => 'CHAR', 'constraint' => 15, 'null' => false],
            'sys_role_id'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'sys_user_from'         => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'sys_user_to'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'startdate'             => ['type' => 'timestamp', 'null' => false],
            'enddate'               => ['type' => 'timestamp', 'null' => true],
            'state'                 => ['type' => 'CHAR', 'constraint' => 2, 'null' => false],
        ]);

        $this->forge->addKey('trx_proxy_switching_id', true);

        $this->forge->createTable('trx_proxy_switching', true);
        $this->db->query('ALTER TABLE trx_proxy_switching AUTO_INCREMENT = 100001');
    }

    public function down()
    {
        $this->forge->dropTable('trx_proxy_switching', true);
    }
}