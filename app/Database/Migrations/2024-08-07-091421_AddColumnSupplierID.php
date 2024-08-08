<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnSupplierID extends Migration
{
    public function up()
    {
        $field = [
            'md_supplier_id' => ['type' => 'INT', 'constraint' => 10, 'null' => true, 'default' => 0]
        ];

        $this->forge->addColumn('md_employee', $field);
    }

    public function down()
    {
        $field = ['md_supplier_id'];

        $this->forge->dropColumn('md_employee', $field);
    }
}