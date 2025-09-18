<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnOnTableAssignmentAbsentOvertime extends Migration
{
    public function up()
    {
        $fields = [
            'approve_date' => ['type' => 'timestamp', 'null' => true],
            'realization_date_superior' => ['type' => 'timestamp', 'null' => true],
            'realization_by_superior' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'realization_date_hrd' => ['type' => 'timestamp', 'null' => true],
            'realization_by_hrd' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ];

        $this->forge->addColumn('trx_absent_detail', $fields);
        $this->forge->addColumn('trx_overtime_detail', $fields);
        $this->forge->addColumn('trx_assignment_date', $fields);
        $this->forge->addColumn('trx_submission_cancel_detail', $fields);
    }

    public function down()
    {
        $fields = [
            'approve_date',
            'realization_date_superior',
            'realization_by_superior',
            'realization_date_hrd',
            'realization_by_hrd'
        ];

        $this->forge->dropColumn('trx_absent_detail', $fields);
        $this->forge->dropColumn('trx_overtime_detail', $fields);
        $this->forge->dropColumn('trx_assignment_date', $fields);
        $this->forge->dropColumn('trx_submission_cancel_detail', $fields);
    }
}