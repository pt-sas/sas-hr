<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableSpecialProxyDetail extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_proxy_special_detail_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'trx_proxy_special_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'lineno'                => ['type' => 'INT', 'constraint' => 2,  'null' => false],
            'sys_role_id'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],
        ]);

        $this->forge->addKey('trx_proxy_special_detail_id', true);
        $this->forge->createTable('trx_proxy_special_detail', true);
        $this->db->query('ALTER TABLE trx_proxy_special_detail AUTO_INCREMENT = 100001');
    }

    public function down()
    {
        $this->forge->dropTable('trx_proxy_special_detail', true);
    }
}