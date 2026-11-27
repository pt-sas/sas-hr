<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTablePerformanceAppraisal extends Migration
{
    private function baseFields(string $pk): array
    {
        return [
            $pk          => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'isactive'   => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at' => ['type' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
            'updated_at' => ['type' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
        ];
    }

    public function up()
    {
        // 1. md_appraisal_template
        $this->forge->addField($this->baseFields('md_appraisal_template_id'));
        $this->forge->addField([
            'template_code' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'version'       => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 1],
            'title'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'description'   => ['type' => 'TEXT', 'null' => true],
            'target_group'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'default' => 'DRAFT'],
            'published_at'  => ['type' => 'DATETIME', 'null' => true],
            'schema_json'   => ['type' => 'JSON', 'null' => true],
        ]);
        $this->forge->addKey('md_appraisal_template_id', true);
        $this->forge->addUniqueKey(['template_code', 'version'], 'uq_templates_code_version');
        $this->forge->addKey('status', false, false, 'idx_templates_status');
        $this->forge->addKey(['status', 'isactive', 'version'], false, false, 'idx_template_lookup');
        $this->forge->createTable('md_appraisal_template', true);

        // 2. Template mapping tables (branch, division, levelling, position)
        foreach (['branch', 'division', 'levelling', 'position'] as $scope) {
            $table = "md_template_{$scope}";
            $pk    = "{$table}_id";
            $fk    = "md_{$scope}_id";

            $this->forge->addField($this->baseFields($pk));
            $this->forge->addField([
                'md_appraisal_template_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                $fk                        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            ]);
            $this->forge->addKey($pk, true);
            $this->forge->addUniqueKey(['md_appraisal_template_id', $fk], "uq_tpl_{$scope}");
            $this->forge->addKey([$fk, 'isactive', 'md_appraisal_template_id'], false, false, "idx_tpl_{$scope}_resolve");
            $this->forge->addForeignKey('md_appraisal_template_id', 'md_appraisal_template', 'md_appraisal_template_id', 'CASCADE', 'CASCADE', "fk_tpl_{$scope}_template");
            $this->forge->createTable($table, true);
        }

        // 3. md_appraisal_cycle
        $this->forge->addField($this->baseFields('md_appraisal_cycle_id'));
        $this->forge->addField([
            'title'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'semester'          => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true, 'default' => '1'],
            'start_date'        => ['type' => 'DATE', 'null' => false],
            'end_date'          => ['type' => 'DATE', 'null' => false],
            'deadline_filling'  => ['type' => 'DATE', 'null' => true],
            'deadline_review'   => ['type' => 'DATE', 'null' => true],
            'deadline_reading'  => ['type' => 'DATE', 'null' => true],
            'deadline_override' => ['type' => 'DATE', 'null' => true],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'default' => 'DRAFT'],
        ]);
        $this->forge->addKey('md_appraisal_cycle_id', true);
        $this->forge->addKey('status', false, false, 'idx_cycles_status');
        $this->forge->createTable('md_appraisal_cycle', true);

        // 4. md_appraisal
        $this->forge->addField($this->baseFields('md_appraisal_id'));
        $this->forge->addField([
            'md_appraisal_cycle_id'    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_appraisal_template_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_employee_id'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'snapshot_division_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'snapshot_position_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'snapshot_branch_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'snapshot_evaluator_id'    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'snapshot_reviewer_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'template_snapshot'        => ['type' => 'JSON', 'null' => true],
            'final_score'              => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'rating'                   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'evaluator_comment'        => ['type' => 'MEDIUMTEXT', 'null' => true],
            'reviewer_comment'         => ['type' => 'MEDIUMTEXT', 'null' => true],
            'development_plan'         => ['type' => 'MEDIUMTEXT', 'null' => true],
            'employee_comment'         => ['type' => 'MEDIUMTEXT', 'null' => true],
            'status'                   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'default' => 'PENILAIAN'],
            'evaluator_submitted_at'   => ['type' => 'DATETIME', 'null' => true],
            'reviewer_reviewed_at'     => ['type' => 'DATETIME', 'null' => true],
            'employee_acknowledged_at' => ['type' => 'DATETIME', 'null' => true],
            'finalized_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('md_appraisal_id', true);
        $this->forge->addUniqueKey(['md_appraisal_cycle_id', 'md_employee_id'], 'uq_appraisals_cycle_employee');
        $this->forge->addKey('md_appraisal_template_id', false, false, 'idx_appraisals_template');
        $this->forge->addKey(['snapshot_evaluator_id', 'status'], false, false, 'idx_appraisals_evaluator_status');
        $this->forge->addKey(['snapshot_reviewer_id', 'status'], false, false, 'idx_appraisals_reviewer_status');
        $this->forge->addKey('md_employee_id', false, false, 'idx_appraisals_employee');
        $this->forge->addForeignKey('md_appraisal_cycle_id', 'md_appraisal_cycle', 'md_appraisal_cycle_id', 'RESTRICT', 'RESTRICT', 'fk_appraisals_cycle');
        $this->forge->addForeignKey('md_appraisal_template_id', 'md_appraisal_template', 'md_appraisal_template_id', 'RESTRICT', 'RESTRICT', 'fk_appraisals_template');
        $this->forge->createTable('md_appraisal', true);

        // 5. md_appraisal_response
        $this->forge->addField($this->baseFields('md_appraisal_response_id'));
        $this->forge->addField([
            'md_appraisal_id'               => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'md_appraisal_template_item_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'score'                         => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'evaluator_response'            => ['type' => 'JSON', 'null' => true],
            'reviewer_response'             => ['type' => 'JSON', 'null' => true],
        ]);
        $this->forge->addKey('md_appraisal_response_id', true);
        $this->forge->addUniqueKey(['md_appraisal_id', 'md_appraisal_template_item_id'], 'uq_responses_appraisal_item');
        $this->forge->addKey('md_appraisal_template_item_id', false, false, 'idx_responses_item');
        $this->forge->addKey('score', false, false, 'idx_responses_score');
        $this->forge->addForeignKey('md_appraisal_id', 'md_appraisal', 'md_appraisal_id', 'CASCADE', 'CASCADE', 'fk_responses_appraisal');
        $this->forge->createTable('md_appraisal_response', true);

        // 6. md_appraisal_audit_log
        $this->forge->addField([
            'md_appraisal_audit_log_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'auto_increment' => true],
            'created_at'                    => ['type' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'],
            'md_appraisal_id'               => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'actor_id'                      => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
            'action'                        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'from_status'                   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'to_status'                     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'md_appraisal_template_item_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'previous_value'                => ['type' => 'JSON', 'null' => true],
            'new_value'                     => ['type' => 'JSON', 'null' => true],
            'notes'                         => ['type' => 'MEDIUMTEXT', 'null' => true],
        ]);
        $this->forge->addKey('md_appraisal_audit_log_id', true);
        $this->forge->addKey(['md_appraisal_id', 'created_at'], false, false, 'idx_audit_appraisal_created');
        $this->forge->addKey('actor_id', false, false, 'idx_audit_actor');
        $this->forge->addForeignKey('md_appraisal_id', 'md_appraisal', 'md_appraisal_id', 'CASCADE', 'CASCADE', 'fk_audit_appraisal');
        $this->forge->createTable('md_appraisal_audit_log', true);
    }

    public function down()
    {
        $this->forge->dropTable('md_appraisal_audit_log', true);
        $this->forge->dropTable('md_appraisal_response', true);
        $this->forge->dropTable('md_appraisal', true);
        $this->forge->dropTable('md_appraisal_cycle', true);
        $this->forge->dropTable('md_template_position', true);
        $this->forge->dropTable('md_template_levelling', true);
        $this->forge->dropTable('md_template_division', true);
        $this->forge->dropTable('md_template_branch', true);
        $this->forge->dropTable('md_appraisal_template', true);
    }
}
