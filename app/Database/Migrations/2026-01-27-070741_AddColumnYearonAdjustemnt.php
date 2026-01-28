<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnYearonAdjustemnt extends Migration
{
    public function up()
    {
        $fields = [
            'md_year_id'    =>  [
                'type'              => 'INT',
                'constraint'        => '10',
                'after'            => 'date'
            ]
        ];

        $this->forge->addColumn('trx_adjustment', $fields);
    }

    public function down()
    {
        $fields = ['md_year_id'];

        $this->forge->dropColumn('trx_adjustment', $fields);
    }
}
