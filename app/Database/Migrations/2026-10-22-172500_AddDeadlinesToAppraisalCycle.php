<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeadlinesToAppraisalCycle extends Migration
{
    public function up()
    {
        $fields = [
            'deadline_filling' => [
                'type'  => 'DATE',
                'null'  => true,
                'after' => 'end_date',
            ],
            'deadline_review' => [
                'type'  => 'DATE',
                'null'  => true,
                'after' => 'deadline_filling',
            ],
            'deadline_reading' => [
                'type'  => 'DATE',
                'null'  => true,
                'after' => 'deadline_review',
            ],
        ];

        $this->forge->addColumn('md_appraisal_cycle', $fields);

        // Copy existing values from legacy evaluator_due_date & gm_due_date if present
        $cols = $this->db->getFieldNames('md_appraisal_cycle');
        if (in_array('evaluator_due_date', $cols) && in_array('deadline_filling', $cols)) {
            $this->db->query("UPDATE md_appraisal_cycle SET deadline_filling = evaluator_due_date WHERE evaluator_due_date IS NOT NULL AND deadline_filling IS NULL");
        }
        if (in_array('gm_due_date', $cols) && in_array('deadline_review', $cols)) {
            $this->db->query("UPDATE md_appraisal_cycle SET deadline_review = gm_due_date WHERE gm_due_date IS NOT NULL AND deadline_review IS NULL");
        }
    }

    public function down()
    {
        $this->forge->dropColumn('md_appraisal_cycle', ['deadline_filling', 'deadline_review', 'deadline_reading']);
    }
}
