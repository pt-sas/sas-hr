<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableBenefitDetail extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'md_employee_benefit_detail_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'md_employee_benefit_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'isactive'                      => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at'                    => ['type' => 'timestamp default current_timestamp'],
            'created_by'                    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'                    => ['type' => 'timestamp default current_timestamp'],
            'updated_by'                    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'benefit_detail'                => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            // 'status'                        => ['type' => 'CHAR', 'constraint' => 1, 'null' => false],
            'description'                   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]
        ]);

        $this->forge->addKey('md_employee_benefit_detail_id', true);

        $this->forge->createTable('md_employee_benefit_detail', true);
    }

    public function down()
    {
        $this->forge->dropTable('md_employee_benefit_detail', true);
    }
}