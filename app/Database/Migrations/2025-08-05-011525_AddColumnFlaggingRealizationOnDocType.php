<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnFlaggingRealizationOnDocType extends Migration
{
    public function up()
    {
        $fields = [
            'is_realization_mgr'    => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'N'],
            'days_realization_mgr'  => ['type' => 'INT', 'constraint' => 2, 'default' => 0],
            'is_realization_hrd'    => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'N'],
            'days_realization_hrd'  => ['type' => 'INT', 'constraint' => 2, 'default' => 0],
            'auto_not_approve_days' => ['type' => 'INT', 'constraint' => 2, 'default' => 2]
        ];

        $this->forge->addColumn('md_doctype', $fields);
    }

    public function down()
    {
        $fields = ['is_realization_mgr', 'days_realization_mgr', 'is_realization_hrd', 'days_realization_hrd', 'auto_not_approve_days'];

        $this->forge->dropColumn('md_doctype', $fields);
    }
}