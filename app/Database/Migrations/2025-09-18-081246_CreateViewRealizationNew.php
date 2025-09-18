<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewRealizationNew extends Migration
{
    public function up()
    {
        $this->db->query("CREATE VIEW v_realization_new AS (
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
        CASE 
        WHEN DATE(adetail.date) <= DATE(a.submissiondate) 
            AND adetail.approve_date IS NOT NULL
            THEN add_workdays(adetail.approve_date, dt.`days_realization_mgr`)
            ELSE add_workdays(adetail.date, dt.`days_realization_mgr`)
        END AS realization_mgr,
        CASE 
        WHEN DATE(adetail.date) <= DATE(a.submissiondate) 
            AND adetail.realization_date_superior IS NOT NULL
            THEN add_workdays(adetail.realization_date_superior, dt.`days_realization_hrd`)
        WHEN DATE(adetail.date) <= DATE(a.submissiondate)
            AND adetail.approve_date IS NOT NULL
            THEN add_workdays(adetail.approve_date, dt.`days_realization_hrd`)
        ELSE add_workdays(adetail.date, dt.`days_realization_hrd`)
        END AS realization_hrd
        FROM trx_absent a
        LEFT JOIN trx_absent_detail adetail ON a.`trx_absent_id` = adetail.`trx_absent_id`
        LEFT JOIN md_employee e ON a.`md_employee_id` = e.`md_employee_id`
        LEFT JOIN md_branch b ON a.`md_branch_id` = b.`md_branch_id`
        LEFT JOIN md_division d ON a.`md_division_id` = d.`md_division_id`
        LEFT JOIN md_leavetype lt ON a.`md_leavetype_id` = lt.`md_leavetype_id`
        LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
        LEFT JOIN sys_user u ON adetail.`realization_by` = u.`sys_user_id`
        WHERE adetail.isagree IN ('M', 'S')
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
        CASE 
        WHEN DATE(adate.date) <= DATE(a.submissiondate) 
            AND adate.approve_date IS NOT NULL
            THEN add_workdays(adate.approve_date, dt.`days_realization_mgr`)
            ELSE add_workdays(adate.date, dt.`days_realization_mgr`)
        END AS realization_mgr,
        CASE 
        WHEN DATE(adate.date) <= DATE(a.submissiondate) 
            AND adate.realization_date_superior IS NOT NULL
            THEN add_workdays(adate.realization_date_superior, dt.`days_realization_hrd`)
        WHEN DATE(adate.date) <= DATE(a.submissiondate)
            AND adate.approve_date IS NOT NULL
            THEN add_workdays(adate.approve_date, dt.`days_realization_hrd`)
        ELSE add_workdays(adate.date, dt.`days_realization_hrd`)
        END AS realization_hrd
        FROM trx_assignment a
        LEFT JOIN trx_assignment_detail adetail ON a.trx_assignment_id = adetail.trx_assignment_id
        LEFT JOIN trx_assignment_date adate ON adetail.trx_assignment_detail_id = adate.trx_assignment_detail_id
        LEFT JOIN md_employee e ON adetail.`md_employee_id` = e.`md_employee_id`
        LEFT JOIN md_branch b ON a.`md_branch_id` = b.`md_branch_id`
        LEFT JOIN md_division d ON a.`md_division_id` = d.`md_division_id`
        LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
        LEFT JOIN sys_user u ON adate.`realization_by` = u.`sys_user_id`
        WHERE adate.isagree IN ('M', 'S')
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
        CASE 
        WHEN adetail.approve_date IS NOT NULL
            THEN add_workdays(adetail.approve_date, dt.`days_realization_mgr`)
            ELSE add_workdays(adetail.date, dt.`days_realization_mgr`)
        END AS realization_mgr,
        CASE 
        WHEN adetail.realization_date_superior IS NOT NULL
            THEN add_workdays(adetail.realization_date_superior, dt.`days_realization_hrd`)
        WHEN adetail.approve_date IS NOT NULL
            THEN add_workdays(adetail.approve_date, dt.`days_realization_hrd`)
        ELSE add_workdays(adetail.created_at, dt.`days_realization_hrd`)
        END AS realization_hrd
        FROM trx_submission_cancel a
        LEFT JOIN trx_submission_cancel_detail adetail ON a.trx_submission_cancel_id = adetail.trx_submission_cancel_id
        LEFT JOIN md_employee e ON adetail.`md_employee_id` = e.`md_employee_id`
        LEFT JOIN md_branch b ON a.`md_branch_id` = b.`md_branch_id`
        LEFT JOIN md_division d ON a.`md_division_id` = d.`md_division_id`
        LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
        LEFT JOIN sys_user u ON adetail.`realization_by` = u.`sys_user_id`
        WHERE adetail.isagree IN ('M', 'S')) AS realization
        )");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS v_realization_new");
    }
}