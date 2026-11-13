<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePerformanceAppraisalSchema extends Migration
{
    protected $DBGroup = 'default';

    /** Required for foreign keys, full Unicode JSON, and transactional integrity. */
    private array $tableAttr = [
        'ENGINE'  => 'InnoDB',
        'CHARSET' => 'utf8mb4',
        'COLLATE' => 'utf8mb4_unicode_ci',
    ];

    /**
     * Common housekeeping columns shared across master & transaction tables.
     */
    private function baseFields(string $pk): array
    {
        return [
            $pk          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'auto_increment' => true],
            'isactive'   => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at' => ['type' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
            'updated_at' => ['type' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
        ];
    }

    public function up()
    {
        $db = $this->db;

        /* ---------------------------------------------------------------
         * 1. md_appraisal_template (Master Template Header - Versioned + JSON Schema)
         * ------------------------------------------------------------- */
        if (! $db->tableExists('md_appraisal_template')) {
            $this->forge->addField($this->baseFields('md_appraisal_template_id'));
            $this->forge->addField([
                'template_code' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
                'version'       => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 1],
                'title'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'description'   => ['type' => 'TEXT', 'null' => true],
                'target_group'  => ['type' => 'ENUM', 'constraint' => ['STAFF_SPV', 'OPERATOR'], 'null' => false, 'default' => 'STAFF_SPV'],
                'status'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'default' => 'DRAFT'], // DRAFT | PUBLISHED | ARCHIVED
                'published_at'  => ['type' => 'DATETIME', 'null' => true],
                'schema_json'   => ['type' => 'JSON', 'null' => true],
            ]);
            $this->forge->addKey('md_appraisal_template_id', true);
            $this->forge->addUniqueKey(['template_code', 'version'], 'uq_templates_code_version');
            $this->forge->addKey('status', false, false, 'idx_templates_status');
            $this->forge->createTable('md_appraisal_template', true, $this->tableAttr);

            $tplTable = $db->prefixTable('md_appraisal_template');
            $idxExists = $db->query(
                "SHOW INDEX FROM {$tplTable} WHERE Key_name = 'idx_template_lookup'"
            )->getResultArray();
            if (empty($idxExists)) {
                $db->query("ALTER TABLE {$tplTable} ADD INDEX idx_template_lookup (status, isactive, version DESC)");
            }
        }

        /* ---------------------------------------------------------------
         * 2. md_template_scope (Unified Target Scoping: BRANCH, DIVISION, LEVELLING, POSITION)
         * ------------------------------------------------------------- */
        if (! $db->tableExists('md_template_scope')) {
            $this->forge->addField([
                'md_template_scope_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'auto_increment' => true],
                'md_appraisal_template_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'scope_type'               => ['type' => 'ENUM', 'constraint' => ['BRANCH', 'DIVISION', 'LEVELLING', 'POSITION'], 'null' => false],
                'scope_id'                 => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'isactive'                 => ['type' => 'ENUM', 'constraint' => ['Y', 'N'], 'null' => false, 'default' => 'Y'],
                'created_at'               => ['type' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'],
                'created_by'               => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
                'updated_at'               => ['type' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
                'updated_by'               => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
            ]);
            $this->forge->addKey('md_template_scope_id', true);
            $this->forge->addUniqueKey(['md_appraisal_template_id', 'scope_type', 'scope_id'], 'uq_template_scope');
            $this->forge->addKey(['scope_type', 'scope_id', 'isactive', 'md_appraisal_template_id'], false, false, 'idx_scope_resolve');
            $this->forge->addForeignKey('md_appraisal_template_id', 'md_appraisal_template', 'md_appraisal_template_id', 'CASCADE', 'CASCADE', 'fk_scope_template');
            $this->forge->createTable('md_template_scope', true, $this->tableAttr);
        }

        /* ---------------------------------------------------------------
         * 3. md_appraisal_cycle (Evaluation Period Definition & Deadlines)
         * ------------------------------------------------------------- */
        if (! $db->tableExists('md_appraisal_cycle')) {
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
                'status'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'default' => 'DRAFT'], // DRAFT | OPEN | CLOSED
            ]);
            $this->forge->addKey('md_appraisal_cycle_id', true);
            $this->forge->addKey('status', false, false, 'idx_cycles_status');
            $this->forge->createTable('md_appraisal_cycle', true, $this->tableAttr);

            $cycles = $db->prefixTable('md_appraisal_cycle');
            $check = $db->query("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'md_appraisal_cycle'
                  AND CONSTRAINT_NAME = 'chk_cycles_dates'
            ")->getRow();

            if (! $check) {
                try {
                    $db->query("ALTER TABLE {$cycles} ADD CONSTRAINT chk_cycles_dates CHECK (end_date >= start_date)");
                } catch (\Throwable $e) {
                    // Ignore if constraint already exists
                }
            }
        }

        /* ---------------------------------------------------------------
         * 4. md_appraisal (Transaction Header with Immutable Template Snapshot)
         * ------------------------------------------------------------- */
        if (! $db->tableExists('md_appraisal')) {
            $this->forge->addField($this->baseFields('md_appraisal_id'));
            $this->forge->addField([
                'md_appraisal_cycle_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'md_appraisal_template_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'md_employee_id'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],

                // Scoping Snapshots
                'snapshot_division_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'snapshot_position_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'snapshot_branch_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'snapshot_evaluator_id'    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'snapshot_gm_id'           => ['type' => 'INT', 'constraint' => 11, 'null' => true],

                // Immutable Transactional Snapshot of the complete template structure
                'template_snapshot'        => ['type' => 'JSON', 'null' => true],

                // Final Evaluation Score & Rating
                'final_score'              => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
                'rating'                   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],

                // Qualitative Feedback
                'evaluator_comment'        => ['type' => 'MEDIUMTEXT', 'null' => true],
                'gm_comment'               => ['type' => 'MEDIUMTEXT', 'null' => true],
                'development_plan'         => ['type' => 'MEDIUMTEXT', 'null' => true],
                'employee_comment'         => ['type' => 'MEDIUMTEXT', 'null' => true],

                // Workflow Status & Timestamps
                'status'                   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'default' => 'PENILAIAN'],
                'evaluator_submitted_at'   => ['type' => 'DATETIME', 'null' => true],
                'gm_reviewed_at'           => ['type' => 'DATETIME', 'null' => true],
                'employee_acknowledged_at' => ['type' => 'DATETIME', 'null' => true],
                'finalized_at'             => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('md_appraisal_id', true);
            $this->forge->addUniqueKey(['md_appraisal_cycle_id', 'md_employee_id'], 'uq_appraisals_cycle_employee');
            $this->forge->addKey('md_appraisal_template_id', false, false, 'idx_appraisals_template');
            $this->forge->addKey(['snapshot_evaluator_id', 'status'], false, false, 'idx_appraisals_evaluator_status');
            $this->forge->addKey(['snapshot_gm_id', 'status'], false, false, 'idx_appraisals_gm_status');
            $this->forge->addKey('md_employee_id', false, false, 'idx_appraisals_employee');
            $this->forge->addForeignKey('md_appraisal_cycle_id', 'md_appraisal_cycle', 'md_appraisal_cycle_id', 'RESTRICT', 'RESTRICT', 'fk_appraisals_cycle');
            $this->forge->addForeignKey('md_appraisal_template_id', 'md_appraisal_template', 'md_appraisal_template_id', 'RESTRICT', 'RESTRICT', 'fk_appraisals_template');
            $this->forge->createTable('md_appraisal', true, $this->tableAttr);
        }

        /* ---------------------------------------------------------------
         * 5. md_appraisal_response (Line Responses with JSON Payloads)
         * ------------------------------------------------------------- */
        if (! $db->tableExists('md_appraisal_response')) {
            $this->forge->addField($this->baseFields('md_appraisal_response_id'));
            $this->forge->addField([
                'md_appraisal_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'md_appraisal_template_item_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'score'                         => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
                'evaluator_response'            => ['type' => 'JSON', 'null' => true],
                'gm_response'                   => ['type' => 'JSON', 'null' => true],
            ]);
            $this->forge->addKey('md_appraisal_response_id', true);
            $this->forge->addUniqueKey(['md_appraisal_id', 'md_appraisal_template_item_id'], 'uq_responses_appraisal_item');
            $this->forge->addKey('md_appraisal_template_item_id', false, false, 'idx_responses_item');
            $this->forge->addKey('score', false, false, 'idx_responses_score');
            $this->forge->addForeignKey('md_appraisal_id', 'md_appraisal', 'md_appraisal_id', 'CASCADE', 'CASCADE', 'fk_responses_appraisal');
            $this->forge->createTable('md_appraisal_response', true, $this->tableAttr);
        }

        /* ---------------------------------------------------------------
         * 6. md_appraisal_audit_log (Append-only Audit Trail)
         * ------------------------------------------------------------- */
        if (! $db->tableExists('md_appraisal_audit_log')) {
            $this->forge->addField([
                'md_appraisal_audit_log_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'auto_increment' => true],
                'created_at'                    => ['type' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'],
                'md_appraisal_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'actor_id'                      => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
                'action'                        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
                'from_status'                   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'to_status'                     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'md_appraisal_template_item_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'previous_value'                => ['type' => 'JSON', 'null' => true],
                'new_value'                     => ['type' => 'JSON', 'null' => true],
                'notes'                         => ['type' => 'MEDIUMTEXT', 'null' => true],
            ]);
            $this->forge->addKey('md_appraisal_audit_log_id', true);
            $this->forge->addKey(['md_appraisal_id', 'created_at'], false, false, 'idx_audit_appraisal_created');
            $this->forge->addKey('actor_id', false, false, 'idx_audit_actor');
            $this->forge->addForeignKey('md_appraisal_id', 'md_appraisal', 'md_appraisal_id', 'CASCADE', 'CASCADE', 'fk_audit_appraisal');
            $this->forge->createTable('md_appraisal_audit_log', true, $this->tableAttr);
        }
    }

    public function down()
    {
        $this->forge->dropTable('md_appraisal_audit_log', true);
        $this->forge->dropTable('md_appraisal_response', true);
        $this->forge->dropTable('md_appraisal', true);
        $this->forge->dropTable('md_appraisal_cycle', true);
        $this->forge->dropTable('md_template_scope', true);
        $this->forge->dropTable('md_appraisal_template', true);
    }
}
