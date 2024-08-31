<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldIsRealizationDocType extends Migration
{
    public function up()
    {
        $fields = [
            'isrealization'     =>  [
                'type'          => 'CHAR',
                'after'         => 'name',
                'null'          => false,
                'default'       => 'N'
            ],
            'isapprovedline'    =>  [
                'type'          => 'CHAR',
                'after'         => 'isrealization',
                'null'          => false,
                'default'       => 'N'
            ]
        ];

        $this->forge->addColumn('md_doctype', $fields);
    }

    public function down()
    {
        $fields = ['isrealization', 'isapprovedline'];

        $this->forge->dropColumn('md_doctype', $fields);
    }
}
