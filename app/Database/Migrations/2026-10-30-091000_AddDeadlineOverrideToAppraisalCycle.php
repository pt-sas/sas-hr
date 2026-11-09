<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeadlineOverrideToAppraisalCycle extends Migration
{
    public function up()
    {
        $fields = [
            'deadline_override' => [
                'type'  => 'DATE',
                'null'  => true,
                'after' => 'deadline_reading',
            ],
        ];

        $cols = $this->db->getFieldNames('md_appraisal_cycle');
        if (!in_array('deadline_override', $cols, true)) {
            $this->forge->addColumn('md_appraisal_cycle', $fields);
        }
    }

    public function down()
    {
        $cols = $this->db->getFieldNames('md_appraisal_cycle');
        if (in_array('deadline_override', $cols, true)) {
            $this->forge->dropColumn('md_appraisal_cycle', ['deadline_override']);
        }
    }
}
