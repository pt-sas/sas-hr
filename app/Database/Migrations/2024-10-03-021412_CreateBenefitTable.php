<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBenefitTable extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'md_benefit_id'         => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'md_branch_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_division_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_levelling_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_position_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_status_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
        ]);

        $this->forge->addKey('md_benefit_id', true);
        $this->forge->createTable('md_benefit', true);
    }

    public function down()
    {
        $this->forge->dropTable('md_benefit', true);
    }
}
