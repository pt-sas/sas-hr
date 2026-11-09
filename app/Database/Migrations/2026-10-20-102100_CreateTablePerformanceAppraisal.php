<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTablePerformanceAppraisal extends Migration
{
    protected $DBGroup = 'default';

    /** Required for foreign keys and transactional integrity. */
    private array $tableAttr = ['ENGINE' => 'InnoDB'];

    /**
     * Common housekeeping columns, shared across master & transaction tables.
     */
    private function baseFields(string $pk): array
    {
        return [
            $pk          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'auto_increment' => true],
            'isactive'   => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'created_at' => ['type' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
            'updated_at' => ['type' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
        ];
    }

    public function up()
    {
        /* ---------------------------------------------------------------
         * 1. md_appraisal_template (Master Template Header - Versioned)
         * ------------------------------------------------------------- */
        $this->forge->addField($this->baseFields('md_appraisal_template_id'));
        $this->forge->addField([
            'template_code' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'version'       => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 1],
            'title'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'description'   => ['type' => 'TEXT', 'null' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'default' => 'DRAFT'], // DRAFT | PUBLISHED | ARCHIVED
            'published_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('md_appraisal_template_id', true);
        $this->forge->addUniqueKey(['template_code', 'version'], 'uq_templates_code_version');
        $this->forge->addKey('status', false, false, 'idx_templates_status');
        $this->forge->createTable('md_appraisal_template', true, $this->tableAttr);

        /* ---------------------------------------------------------------
         * 2. Target Scoping - Branch Mapping (md_template_branch)
         * ------------------------------------------------------------- */
        $this->forge->addField($this->baseFields('md_template_branch_id'));
        $this->forge->addField([
            'md_appraisal_template_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'md_branch_id'             => ['type' => 'INT', 'constraint' => 11, 'null' => false],
        ]);
        $this->forge->addKey('md_template_branch_id', true);
        $this->forge->addUniqueKey(['md_appraisal_template_id', 'md_branch_id'], 'uq_md_tpl_branch');
        $this->forge->addKey('md_branch_id', false, false, 'idx_md_tpl_branch');
        $this->forge->addForeignKey('md_appraisal_template_id', 'md_appraisal_template', 'md_appraisal_template_id', 'CASCADE', 'CASCADE', 'fk_md_branch_template');
        $this->forge->createTable('md_template_branch', true, $this->tableAttr);

        /* ---------------------------------------------------------------
         * 3. Target Scoping - Division Mapping (md_template_division)
         * ------------------------------------------------------------- */
        $this->forge->addField($this->baseFields('md_template_division_id'));
        $this->forge->addField([
            'md_appraisal_template_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'md_division_id'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],
        ]);
        $this->forge->addKey('md_template_division_id', true);
        $this->forge->addUniqueKey(['md_appraisal_template_id', 'md_division_id'], 'uq_md_tpl_division');
        $this->forge->addKey('md_division_id', false, false, 'idx_md_tpl_division');
        $this->forge->addForeignKey('md_appraisal_template_id', 'md_appraisal_template', 'md_appraisal_template_id', 'CASCADE', 'CASCADE', 'fk_md_division_template');
        $this->forge->createTable('md_template_division', true, $this->tableAttr);
        
        /* ---------------------------------------------------------------
         * 4. Target Scoping - Levelling Mapping (md_template_levelling)
         * ------------------------------------------------------------- */
        $this->forge->addField($this->baseFields('md_template_levelling_id'));
        $this->forge->addField([
            'md_appraisal_template_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'md_levelling_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
        ]);
        $this->forge->addKey('md_template_levelling_id', true);
        $this->forge->addUniqueKey(['md_appraisal_template_id', 'md_levelling_id'], 'uq_md_tpl_levelling');
        $this->forge->addKey('md_levelling_id', false, false, 'idx_md_tpl_levelling');
        
        // Foreign keys
        $this->forge->addForeignKey('md_appraisal_template_id', 'md_appraisal_template', 'md_appraisal_template_id', 'CASCADE', 'CASCADE', 'fk_md_tpl_lvl_template');
        $this->forge->addForeignKey('md_levelling_id', 'md_levelling', 'md_levelling_id', 'CASCADE', 'CASCADE', 'fk_md_tpl_lvl_levelling');

        $this->forge->createTable('md_template_levelling', true, $this->tableAttr);

        /* ---------------------------------------------------------------
         * 5. md_appraisal_section (Logical Sections: Checklist, Skala, etc.)
         * ------------------------------------------------------------- */
        $this->forge->addField($this->baseFields('md_appraisal_section_id'));
        $this->forge->addField([
            'md_appraisal_template_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'name'                     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'section_type'             => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'default' => 'SCORE'], // BOOLEAN | SCORE | KPI | OBJECTIVE
            'weight'                   => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false, 'default' => 0.00],
            'sequence_order'           => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 1],
            'description'              => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('md_appraisal_section_id', true);
        $this->forge->addKey(['md_appraisal_template_id', 'sequence_order'], false, false, 'idx_sections_template_seq');
        $this->forge->addForeignKey('md_appraisal_template_id', 'md_appraisal_template', 'md_appraisal_template_id', 'CASCADE', 'CASCADE', 'fk_sections_template');
        $this->forge->createTable('md_appraisal_section', true, $this->tableAttr);

        /* ---------------------------------------------------------------
         * 6. md_appraisal_template_item (Items with Dynamic JSON Config)
         * ------------------------------------------------------------- */
        $this->forge->addField($this->baseFields('md_appraisal_template_item_id'));
        $this->forge->addField([
            'md_appraisal_section_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'input_type'              => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false], // SCORE | BOOLEAN | TEXT | CURRENCY | PERCENTAGE
            'title'                   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'description'             => ['type' => 'TEXT', 'null' => true],
            'weight'                  => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false, 'default' => 0.00],
            'sequence_order'          => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 1],
            'is_required'             => ['type' => 'CHAR', 'constraint' => 1, 'null' => false, 'default' => 'Y'],
            'config'                  => ['type' => 'JSON', 'null' => true], // Bundles min/max scores, passing rules, key_actions list, etc.
        ]);
        $this->forge->addKey('md_appraisal_template_item_id', true);
        $this->forge->addKey(['md_appraisal_section_id', 'sequence_order'], false, false, 'idx_items_section_seq');
        $this->forge->addForeignKey('md_appraisal_section_id', 'md_appraisal_section', 'md_appraisal_section_id', 'CASCADE', 'CASCADE', 'fk_items_section');
        $this->forge->createTable('md_appraisal_template_item', true, $this->tableAttr);

        /* ---------------------------------------------------------------
         * 7. md_appraisal_cycle (Evaluation Period Definition)
         * ------------------------------------------------------------- */
        $this->forge->addField($this->baseFields('md_appraisal_cycle_id'));
        $this->forge->addField([
            'title'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'start_date'         => ['type' => 'DATE', 'null' => false],
            'end_date'           => ['type' => 'DATE', 'null' => false],
            'evaluator_due_date' => ['type' => 'DATE', 'null' => true],
            'gm_due_date'        => ['type' => 'DATE', 'null' => true],
            'status'             => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'default' => 'DRAFT'], // DRAFT | OPEN | CLOSED
        ]);
        $this->forge->addKey('md_appraisal_cycle_id', true);
        $this->forge->addKey('status', false, false, 'idx_cycles_status');
        $this->forge->createTable('md_appraisal_cycle', true, $this->tableAttr);

        $cycles = $this->db->prefixTable('md_appraisal_cycle');
        $check = $this->db->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND CONSTRAINT_NAME = 'chk_cycles_dates'
        ")->getRow();

        if (! $check) {
            try {
                $this->db->query("ALTER TABLE {$cycles} ADD CONSTRAINT chk_cycles_dates CHECK (end_date >= start_date)");
            } catch (\Throwable $e) {
                // Ignore if constraint already exists
            }
        }

        /* ---------------------------------------------------------------
         * 8. md_appraisal (Transaction Header with Immutable Template Snapshot)
         * ------------------------------------------------------------- */
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

            // Final Evaluation Scores & Ratings
            'performance_score'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'final_score'              => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'rating'                   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],

            // Qualitative Feedback
            'evaluator_comment'        => ['type' => 'TEXT', 'null' => true],
            'gm_comment'               => ['type' => 'TEXT', 'null' => true],
            'development_plan'         => ['type' => 'TEXT', 'null' => true],
            'employee_comment'         => ['type' => 'TEXT', 'null' => true],

            // Workflow Status & Timestamps
            'status'                   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'default' => 'DRAFT_EVALUATOR'],
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

        /* ---------------------------------------------------------------
         * 9. md_appraisal_response (Line Responses with JSON Payloads)
         * ------------------------------------------------------------- */
        $this->forge->addField($this->baseFields('md_appraisal_response_id'));
        $this->forge->addField([
            'md_appraisal_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'md_appraisal_template_item_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'score'                         => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true], // Normalized score for SQL indexing & aggregation
            'evaluator_response'            => ['type' => 'JSON', 'null' => true], // Full response payload (score, ticks, remarks, key actions)
            'gm_response'                   => ['type' => 'JSON', 'null' => true], // GM override or review feedback
        ]);
        $this->forge->addKey('md_appraisal_response_id', true);
        $this->forge->addUniqueKey(['md_appraisal_id', 'md_appraisal_template_item_id'], 'uq_responses_appraisal_item');
        $this->forge->addKey('md_appraisal_template_item_id', false, false, 'idx_responses_item');
        $this->forge->addKey('score', false, false, 'idx_responses_score');
        $this->forge->addForeignKey('md_appraisal_id', 'md_appraisal', 'md_appraisal_id', 'CASCADE', 'CASCADE', 'fk_responses_appraisal');
        $this->forge->addForeignKey('md_appraisal_template_item_id', 'md_appraisal_template_item', 'md_appraisal_template_item_id', 'RESTRICT', 'RESTRICT', 'fk_responses_item');
        $this->forge->createTable('md_appraisal_response', true, $this->tableAttr);

        /* ---------------------------------------------------------------
         * 10. md_appraisal_audit_log (Append-only Audit Trail)
         * ------------------------------------------------------------- */
        $this->forge->addField([
            'md_appraisal_audit_log_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'auto_increment' => true],
            'created_at'                    => ['type' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],
            'md_appraisal_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'actor_id'                      => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
            'action'                        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'from_status'                   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'to_status'                     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'md_appraisal_template_item_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'previous_value'                => ['type' => 'JSON', 'null' => true],
            'new_value'                     => ['type' => 'JSON', 'null' => true],
            'notes'                         => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('md_appraisal_audit_log_id', true);
        $this->forge->addKey(['md_appraisal_id', 'created_at'], false, false, 'idx_audit_appraisal_created');
        $this->forge->addKey('actor_id', false, false, 'idx_audit_actor');
        $this->forge->addForeignKey('md_appraisal_id', 'md_appraisal', 'md_appraisal_id', 'CASCADE', 'CASCADE', 'fk_audit_appraisal');
        $this->forge->createTable('md_appraisal_audit_log', true, $this->tableAttr);
    }

    public function down()
    {
        $this->forge->dropTable('md_appraisal_audit_log', true);
        $this->forge->dropTable('md_appraisal_response', true);
        $this->forge->dropTable('md_appraisal', true);
        $this->forge->dropTable('md_appraisal_cycle', true);
        $this->forge->dropTable('md_appraisal_template_item', true);
        $this->forge->dropTable('md_appraisal_section', true);
        $this->forge->dropTable('md_template_levelling', true);
        $this->forge->dropTable('md_template_division', true);
        $this->forge->dropTable('md_template_branch', true);
        $this->forge->dropTable('md_appraisal_template', true);
    }
}
