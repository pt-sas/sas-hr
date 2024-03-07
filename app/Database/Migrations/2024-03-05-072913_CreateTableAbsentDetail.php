<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableAbsentDetail extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'trx_absent_detail_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'trx_absent_id'         => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'lineno'                => ['type' => 'DECIMAL', 'constraint' => 10, 'null' => false],
            'date'                  => ['type' => 'TIMESTAMP', 'null' => false],
            'isagree'               => ['type' => 'CHAR', 'constraint' => 1, 'null' => true],
            'ref_absent_detail_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ]);

        $this->forge->addKey('trx_absent_detail_id', true);
        $this->forge->createTable('trx_absent_detail', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_absent_detail', true);
    }
}
