<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddtabletrxOvertimeDetail extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_overtime_detail_id'=> ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'trx_overtime_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'startdate'             => ['type' => 'timestamp', 'null' => false, 'default' => '0000-00-00 00:00:00'],
            'enddate'               => ['type' => 'timestamp', 'null' => false, 'default' => '0000-00-00 00:00:00'],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]
        ]);

        $this->forge->addKey('trx_overtime_detail_id', true);
        $this->forge->createTable('trx_overtime_detail', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_overtime_detail', true);
    }
}