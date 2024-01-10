<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTrxTkh extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'trx_allow_attendance_id'   => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'record_id'             => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'table'                 => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'submissiondate'        => ['type' => 'timestamp default current_timestamp'],
            'submissiontype'        => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'md_employee_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'amount'                => ['type' => 'FLOAT'],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false]
        ]);
        $this->forge->addKey('trx_allow_attendance_id', true);
        $this->forge->createTable('trx_allow_attendance', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_allow_attendance', true);
    }
}
