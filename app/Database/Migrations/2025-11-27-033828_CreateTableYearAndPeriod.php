<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableYearAndPeriod extends Migration
{
    public function up()
    {
        // Table Year
        $this->forge->addField([
            'md_year_id'            => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'year'                  => ['type' => 'YEAR', 'null' => false],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);

        $this->forge->addKey('md_year_id', true);
        $this->forge->createTable('md_year', true);
        $this->db->query('ALTER TABLE md_year AUTO_INCREMENT = 100001');

        // Table Period
        $this->forge->addField([
            'md_period_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_year_id'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'periodno'              => ['type' => 'INT', 'constraint' => 2, 'null' => false],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 50],
            'startdate'             => ['type' => 'timestamp', 'null' => false],
            'enddate'               => ['type' => 'timestamp', 'null' => false],
            'description'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);

        $this->forge->addKey('md_period_id', true);
        $this->forge->createTable('md_period', true);
        $this->db->query('ALTER TABLE md_period AUTO_INCREMENT = 100001');

        // Table Period Control
        $this->forge->addField([
            'md_period_control_id'  => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'              => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'Y'],
            'created_at'            => ['type' => 'timestamp default current_timestamp'],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'updated_at'            => ['type' => 'timestamp default current_timestamp'],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_period_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_doctype_id'         => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'period_status'         => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'C'],
        ]);

        $this->forge->addKey('md_period_control_id', true);
        $this->forge->createTable('md_period_control', true);
        $this->db->query('ALTER TABLE md_period_control AUTO_INCREMENT = 100001');
    }

    public function down()
    {
        $this->forge->dropTable('md_year', true);
        $this->forge->dropTable('md_period', true);
        $this->forge->dropTable('md_period_control', true);
    }
}
