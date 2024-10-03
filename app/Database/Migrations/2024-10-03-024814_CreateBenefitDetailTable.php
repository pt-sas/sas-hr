<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBenefitDetailTable extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'md_benefit_detail_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_benefit_id'         => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'benefit'               => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'status'                => ['type' => 'CHAR', 'constraint' => 1, 'null' => false],
            'isdetail'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'N'],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]
        ]);

        $this->forge->addKey('md_benefit_detail_id', true);
        $this->forge->createTable('md_benefit_detail', true);
    }

    public function down()
    {
        $this->forge->dropTable('md_benefit_detail', true);
    }
}
