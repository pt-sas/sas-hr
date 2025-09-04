<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyAddRealizationDateViewRealization extends Migration
{
    public function up()
    {
        $fields = [
            'realization_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'comment'        => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ]
        ];

        $this->forge->addColumn('trx_submission_cancel_detail', $fields);

        $this->db->query("DROP VIEW IF EXISTS v_realization");

        $this->db->query("CREATE VIEW v_realization AS (
        SELECT * FROM (SELECT 
        a.trx_absent_id AS header_id,
        a.documentno AS documentno,
        a.`nik` AS nik,
        a.`docstatus` AS docstatus,
        a.`isapproved` AS isapproved,
        a.`submissiontype` AS submissiontype,
        a.`reason` AS reason,
        a.`comment` AS `comment`,
        e.value AS employee,
        e.fullname AS employee_fullname,
        b.name AS branch,
        d.name AS division,
        adetail.trx_absent_detail_id AS id,
        adetail.isagree AS isagree,
        adetail.date AS `date`,
        dt.name AS doctype,
        a.md_employee_id AS employee_id,
        CASE WHEN a.image IS NOT NULL AND a.image <> '' THEN a.image
        WHEN a.image2 IS NOT NULL AND a.image2 <> '' THEN a.image2
        WHEN a.image3 IS NOT NULL AND a.image3 <> '' THEN a.image3
        WHEN a.img_medical IS NOT NULL AND a.img_medical <> '' THEN a.img_medical
        ELSE NULL END AS image,
        a.enddate_realization,
        u.name AS realization_by,
        a.md_branch_id,
        a.md_division_id,
        e.md_employee_id,
        'trx_absent' AS 'table',
        -- CASE 
        -- WHEN DATE_FORMAT(a.submissiondate, '%Y-%m-%d') < DATE_FORMAT(a.startdate, '%Y-%m-%d') THEN 
        DATE_ADD(adetail.date, INTERVAL dt.`days_realization_mgr` DAY) AS realization_mgr,
        -- ELSE adetail.date
        -- END AS realization_mgr,
        -- CASE 
        -- WHEN DATE_FORMAT(a.submissiondate, '%Y-%m-%d') < DATE_FORMAT(a.startdate, '%Y-%m-%d') THEN 
        DATE_ADD(adetail.date, INTERVAL dt.`days_realization_hrd` DAY) AS realization_hrd
        -- ELSE adetail.date
        -- END AS realization_hrd
        FROM trx_absent a
        LEFT JOIN trx_absent_detail adetail ON a.`trx_absent_id` = adetail.`trx_absent_id`
        LEFT JOIN md_employee e ON a.`md_employee_id` = e.`md_employee_id`
        LEFT JOIN md_branch b ON a.`md_branch_id` = b.`md_branch_id`
        LEFT JOIN md_division d ON a.`md_division_id` = d.`md_division_id`
        LEFT JOIN md_leavetype lt ON a.`md_leavetype_id` = lt.`md_leavetype_id`
        LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
        LEFT JOIN sys_user u ON adetail.`realization_by` = u.`sys_user_id`
        UNION
        SELECT 
        a.trx_assignment_id AS header_id,
        a.documentno AS documentno,
        '' AS nik,
        a.`docstatus` AS docstatus,
        a.`isapproved` AS isapproved,
        a.`submissiontype` AS submissiontype,
        a.`reason` AS reason,
        adate.`comment` AS `comment`,
        e.value AS employee,
        e.fullname AS employee_fullname,
        b.name AS branch,
        d.name AS division,
        adate.trx_assignment_date_id AS id,
        adate.isagree AS isagree,
        adate.date AS `date`,
        dt.name AS doctype,
        a.md_employee_id AS employee_id,
        '' AS image,
        '0000-00-00 00:00:00' AS enddate_realization,
        u.`name` AS realization_by,
        a.md_branch_id,
        a.md_division_id,
        e.md_employee_id,
        'trx_assignment' AS 'table',
        -- CASE 
        -- WHEN DATE_FORMAT(a.submissiondate, '%Y-%m-%d') < DATE_FORMAT(a.startdate, '%Y-%m-%d') THEN 
        DATE_ADD(adate.date, INTERVAL dt.`days_realization_mgr` DAY) AS realization_mgr,
        -- ELSE adate.date 
        -- END AS realization_mgr,
        -- CASE
        -- WHEN DATE_FORMAT(a.submissiondate, '%Y-%m-%d') < DATE_FORMAT(a.startdate, '%Y-%m-%d') THEN 
        DATE_ADD(adate.date, INTERVAL dt.`days_realization_hrd` DAY) AS realization_hrd
        -- ELSE adate.date
        -- END AS realization_hrd
        FROM trx_assignment a
        LEFT JOIN trx_assignment_detail adetail ON a.trx_assignment_id = adetail.trx_assignment_id
        LEFT JOIN trx_assignment_date adate ON adetail.trx_assignment_detail_id = adate.trx_assignment_detail_id
        LEFT JOIN md_employee e ON adetail.`md_employee_id` = e.`md_employee_id`
        LEFT JOIN md_branch b ON a.`md_branch_id` = b.`md_branch_id`
        LEFT JOIN md_division d ON a.`md_division_id` = d.`md_division_id`
        LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
        LEFT JOIN sys_user u ON adate.`realization_by` = u.`sys_user_id`
        UNION
        SELECT 
        a.trx_submission_cancel_id AS header_id,
        a.documentno AS documentno,
        '' AS nik,
        a.`docstatus` AS docstatus,
        a.`isapproved` AS isapproved,
        a.`submissiontype` AS submissiontype,
        a.`reason` AS reason,
        '' AS `comment`,
        e.value AS employee,
        e.fullname AS employee_fullname,
        b.name AS branch,
        d.name AS division,
        adetail.trx_submission_cancel_detail_id AS id,
        adetail.isagree AS isagree,
        adetail.date AS `date`,
        dt.name AS doctype,
        a.md_employee_id AS employee_id,
        CASE WHEN a.image IS NOT NULL AND a.image <> '' THEN a.image
        ELSE NULL END AS image,
        '0000-00-00 00:00:00' AS enddate_realization,
        u.`name` AS realization_by,
        a.md_branch_id,
        a.md_division_id,
        e.md_employee_id,
        'trx_submission_cancel' AS 'table',
        DATE_ADD(adetail.date, INTERVAL dt.`days_realization_mgr` DAY) AS realization_mgr,
        DATE_ADD(adetail.date, INTERVAL dt.`days_realization_hrd` DAY) AS realization_hrd
        FROM trx_submission_cancel a
        LEFT JOIN trx_submission_cancel_detail adetail ON a.trx_submission_cancel_id = adetail.trx_submission_cancel_id
        LEFT JOIN md_employee e ON adetail.`md_employee_id` = e.`md_employee_id`
        LEFT JOIN md_branch b ON a.`md_branch_id` = b.`md_branch_id`
        LEFT JOIN md_division d ON a.`md_division_id` = d.`md_division_id`
        LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
        LEFT JOIN sys_user u ON adetail.`realization_by` = u.`sys_user_id`) AS realization
        WHERE realization.isagree IS NOT NULL
        )");
    }

    public function down()
    {
        $fields = ['comment', 'realization_by'];
        $this->forge->dropColumn('trx_submission_cancel_detail', $fields);

        $this->db->query("DROP VIEW IF EXISTS v_realization");

        $this->db->query("CREATE VIEW v_realization AS (
        SELECT * FROM (SELECT 
        a.trx_absent_id AS header_id,
        a.documentno AS documentno,
        a.`nik` AS nik,
        a.`docstatus` AS docstatus,
        a.`isapproved` AS isapproved,
        a.`submissiontype` AS submissiontype,
        a.`reason` AS reason,
        a.`comment` AS comment,
        e.value AS employee,
        e.fullname AS employee_fullname,
        b.name AS branch,
        d.name AS division,
        adetail.trx_absent_detail_id AS id,
        adetail.isagree AS isagree,
        adetail.date AS `date`,
        dt.name AS doctype,
        a.md_employee_id AS employee_id,
        CASE WHEN a.image IS NOT NULL AND a.image <> '' THEN a.image
        WHEN a.image2 IS NOT NULL AND a.image2 <> '' THEN a.image2
        WHEN a.image3 IS NOT NULL AND a.image3 <> '' THEN a.image3
        WHEN a.img_medical IS NOT NULL AND a.img_medical <> '' THEN a.img_medical
        ELSE null END AS image,
        a.enddate_realization,
        u.name as realization_by,
        a.md_branch_id,
        a.md_division_id,
        e.md_employee_id,
        'trx_absent' as 'table'
        FROM trx_absent a
        LEFT JOIN trx_absent_detail adetail ON a.`trx_absent_id` = adetail.`trx_absent_id`
        LEFT JOIN md_employee e ON a.`md_employee_id` = e.`md_employee_id`
        LEFT JOIN md_branch b ON a.`md_branch_id` = b.`md_branch_id`
        LEFT JOIN md_division d ON a.`md_division_id` = d.`md_division_id`
        LEFT JOIN md_leavetype lt ON a.`md_leavetype_id` = lt.`md_leavetype_id`
        LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
        LEFT JOIN sys_user u ON adetail.`realization_by` = u.`sys_user_id`
        UNION
        SELECT 
        a.trx_assignment_id AS header_id,
        a.documentno AS documentno,
        '' AS nik,
        a.`docstatus` AS docstatus,
        a.`isapproved` AS isapproved,
        a.`submissiontype` AS submissiontype,
        a.`reason` AS reason,
        adate.`comment` AS comment,
        e.value AS employee,
        e.fullname AS employee_fullname,
        b.name AS branch,
        d.name AS division,
        adate.trx_assignment_date_id AS id,
        adate.isagree AS isagree,
        adate.date AS `date`,
        dt.name AS doctype,
        a.md_employee_id AS employee_id,
        '' AS image,
        '0000-00-00 00:00:00' as enddate_realization,
        u.`name` AS realization_by,
        a.md_branch_id,
        a.md_division_id,
        e.md_employee_id,
        'trx_assignment' as 'table'
        FROM trx_assignment a
        LEFT JOIN trx_assignment_detail adetail ON a.trx_assignment_id = adetail.trx_assignment_id
        LEFT JOIN trx_assignment_date adate ON adetail.trx_assignment_detail_id = adate.trx_assignment_detail_id
        LEFT JOIN md_employee e ON adetail.`md_employee_id` = e.`md_employee_id`
        LEFT JOIN md_branch b ON a.`md_branch_id` = b.`md_branch_id`
        LEFT JOIN md_division d ON a.`md_division_id` = d.`md_division_id`
        LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
        LEFT JOIN sys_user u ON adate.`realization_by` = u.`sys_user_id`) AS realization
        WHERE realization.isagree IS NOT NULL
        )");
    }
}