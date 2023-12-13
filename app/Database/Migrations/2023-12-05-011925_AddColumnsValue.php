<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnsValue extends Migration
{
    public function up()
    {
        $fields = [
            'value'     =>  [
                'type'      => 'VARCHAR',
                'after'     => 'updated_by',
                'constraint' => 20,
                'null'      => false
            ]
        ];

        $this->forge->addColumn('md_religion', $fields);
        $this->forge->addColumn('md_country', $fields);
        $this->forge->addColumn('md_bloodtype', $fields);
        $this->forge->addColumn('md_city', $fields);
        $this->forge->addColumn('md_position', $fields);
        $this->forge->addColumn('md_status', $fields);
        $this->forge->addColumn('md_province', $fields);
        $this->forge->addColumn('md_district', $fields);
        $this->forge->addColumn('md_subdistrict', $fields);
    }

    public function down()
    {
        $fields = ['value'];

        $this->forge->dropColumn('md_religion', $fields);
        $this->forge->dropColumn('md_country', $fields);
        $this->forge->dropColumn('md_bloodtype', $fields);
        $this->forge->dropColumn('md_city', $fields);
        $this->forge->dropColumn('md_position', $fields);
        $this->forge->dropColumn('md_status', $fields);
        $this->forge->dropColumn('md_province', $fields);
        $this->forge->dropColumn('md_district', $fields);
        $this->forge->dropColumn('md_subdistrict', $fields);
    }
}
