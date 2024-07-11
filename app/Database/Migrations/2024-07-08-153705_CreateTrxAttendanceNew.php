<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTrxAttendanceNew extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'trx_attend_id'             => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'                => ['type' => 'timestamp default current_timestamp'],
            'created_by'                => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'                => ['type' => 'timestamp default current_timestamp'],
            'updated_by'                => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'nik'                       => ['type' => 'VARCHAR', 'constraint' => 6, 'null' => false],
            'checktime'                 => ['type' => 'timestamp', 'null' => true],
            'status'                    => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'verify'                    => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'reserved'                  => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'reserved2'                 => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'serialnumber'              => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
        ]);

        $this->forge->addKey('trx_attend_id', true);
        $this->forge->createTable('trx_attend', true);
    }

    public function down()
    {
        $this->forge->dropTable('trx_attend');
    }
}
