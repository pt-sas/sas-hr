<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTrxAssignmentDate extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_assignment_date_id'    => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'                  => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'                => ['type' => 'timestamp default current_timestamp'],
            'created_by'                => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'                => ['type' => 'timestamp default current_timestamp'],
            'updated_by'                => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'trx_assignment_detail_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'date'                      => ['type' => 'timestamp default current_timestamp'],
            'isagree'                   => ['type' => 'CHAR', 'constraint' => 1, 'null' => false],
            'table'                     => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'reference_id'              => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'comment'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);

        $this->forge->addKey('trx_assignment_date_id', true);
        $this->forge->createTable('trx_assignment_date', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_assignment_date', true);
    }
}
