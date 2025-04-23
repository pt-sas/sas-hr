<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldImgMedicalForAbsent extends Migration
{
    public function up()
    {
        $fields = [
            'img_medical'            =>  [
                'type'          => 'VARCHAR',
                'constraint'    => 255,
                'null'          => true
            ]
        ];

        $this->forge->addColumn('trx_absent', $fields);

        $fields = [
            'pdf'            =>  [
                'type'          => 'VARCHAR',
                'constraint'    => 255,
                'null'          => true
            ],
            'approved_by'            =>
            [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true
            ],

        ];
        $this->forge->addColumn('trx_medical_certificate', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('trx_absent', ['img_medical']);
        $this->forge->dropColumn('trx_medical_certificate', ['pdf', 'approved_by']);
    }
}
