<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddConfigToAppraisalTemplate extends Migration
{
    public function up()
    {
        $fields = [
            'config' => [
                'type'  => 'JSON',
                'null'  => true,
                'after' => 'description',
            ],
        ];

        $cols = $this->db->getFieldNames('md_appraisal_template');
        if (! in_array('config', $cols, true)) {
            $this->forge->addColumn('md_appraisal_template', $fields);
        }
    }

    public function down()
    {
        $cols = $this->db->getFieldNames('md_appraisal_template');
        if (in_array('config', $cols, true)) {
            $this->forge->dropColumn('md_appraisal_template', ['config']);
        }
    }
}
