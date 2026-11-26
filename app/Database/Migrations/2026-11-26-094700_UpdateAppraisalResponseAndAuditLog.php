<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateAppraisalResponseAndAuditLog extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('hr_response', 'md_appraisal_response')) {
            $this->forge->addColumn('md_appraisal_response', [
                'hr_response' => [
                    'type'  => 'JSON',
                    'null'  => true,
                    'after' => 'reviewer_response',
                ],
            ]);
        }

        $dropAuditCols = [];
        foreach (['md_appraisal_template_item_id', 'previous_value', 'new_value'] as $col) {
            if ($this->db->fieldExists($col, 'md_appraisal_audit_log')) {
                $dropAuditCols[] = $col;
            }
        }
        if (! empty($dropAuditCols)) {
            $this->forge->dropColumn('md_appraisal_audit_log', $dropAuditCols);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('hr_response', 'md_appraisal_response')) {
            $this->forge->dropColumn('md_appraisal_response', 'hr_response');
        }

        $restoreCols = [];
        if (! $this->db->fieldExists('md_appraisal_template_item_id', 'md_appraisal_audit_log')) {
            $restoreCols['md_appraisal_template_item_id'] = ['type' => 'INT', 'constraint' => 11, 'null' => true];
        }
        if (! $this->db->fieldExists('previous_value', 'md_appraisal_audit_log')) {
            $restoreCols['previous_value'] = ['type' => 'JSON', 'null' => true];
        }
        if (! $this->db->fieldExists('new_value', 'md_appraisal_audit_log')) {
            $restoreCols['new_value'] = ['type' => 'JSON', 'null' => true];
        }
        if (! empty($restoreCols)) {
            $this->forge->addColumn('md_appraisal_audit_log', $restoreCols);
        }
    }
}
