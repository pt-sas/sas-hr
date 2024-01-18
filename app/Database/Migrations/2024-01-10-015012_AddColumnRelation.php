<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnRelation extends Migration
{
    public function up()
    {
        $md_branch = [
            'md_branch_id' => ['type' => 'INT', 'constraint' => 6, 'null' => false]
        ];

        $this->forge->addColumn('md_division', $md_branch);
        $this->forge->addKey('md_branch_id', false);
    }

    public function down()
    {
        $md_branch = 'md_branch_id';
        $this->forge->dropColumn('md_division', $md_branch);
    }
}
