<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewAllDocumentDraft extends Migration
{
    public function up()
    {
        $this->db->query("CREATE VIEW v_all_document_draft AS (
        SELECT *
        FROM 
        (
        SELECT 
        a.trx_absent_id AS header_id,
        'trx_absent' as 'table',
        a.documentno AS documentno,
        a.`docstatus` AS docstatus,
        a.`submissiontype` AS submissiontype,
        a.`submissiondate` AS submissiondate,
        dt.name AS doctype,
        u.username AS created_by,
        sm.url as url
        FROM trx_absent a
        LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
        LEFT JOIN sys_submenu sm on dt.sys_submenu_id = sm.sys_submenu_id
        LEFT JOIN sys_user u ON a.`created_by` = u.`sys_user_id`
        UNION
        SELECT 
        a.trx_assignment_id AS header_id,
        'trx_assignment' as 'table',
        a.documentno AS documentno,
        a.`docstatus` AS docstatus,
        a.`submissiontype` AS submissiontype,
        a.`submissiondate` AS submissiondate,
        dt.name AS doctype,
        u.username AS created_by,
        sm.url as url
        FROM trx_assignment a
        LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
        LEFT JOIN sys_submenu sm on dt.sys_submenu_id = sm.sys_submenu_id
        LEFT JOIN sys_user u ON a.`created_by` = u.`sys_user_id`
        UNION
        SELECT 
        a.trx_delegation_transfer_id AS header_id,
        'trx_delegation_transfer' as 'table',
        a.documentno AS documentno,
        a.`docstatus` AS docstatus,
        a.`submissiontype` AS submissiontype,
        a.`submissiondate` AS submissiondate,
        dt.name AS doctype,
        u.username AS created_by,
        sm.url as url
        FROM trx_delegation_transfer a
        LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
        LEFT JOIN sys_submenu sm on dt.sys_submenu_id = sm.sys_submenu_id
        LEFT JOIN sys_user u ON a.`created_by` = u.`sys_user_id`
        UNION
        SELECT 
        a.trx_hr_memo_id AS header_id,
        'trx_hr_memo' as 'table',
        a.documentno AS documentno,
        a.`docstatus` AS docstatus,
        a.`memotype` AS submissiontype,
        a.`submissiondate` AS submissiondate,
        dt.name AS doctype,
        u.username AS created_by,
        sm.url as url
        FROM trx_hr_memo a
        LEFT JOIN md_doctype dt ON a.`memotype` = dt.`md_doctype_id`
        LEFT JOIN sys_submenu sm on dt.sys_submenu_id = sm.sys_submenu_id
        LEFT JOIN sys_user u ON a.`created_by` = u.`sys_user_id`
        UNION
        SELECT 
        a.trx_medical_certificate_id AS header_id,
        'trx_medical_certificate' as 'table',
        a.documentno AS documentno,
        a.`docstatus` AS docstatus,
        a.`submissiontype` AS submissiontype,
        a.`submissiondate` AS submissiondate,
        dt.name AS doctype,
        u.username AS created_by,
        sm.url as url
        FROM trx_medical_certificate a
        LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
        LEFT JOIN sys_submenu sm on dt.sys_submenu_id = sm.sys_submenu_id
        LEFT JOIN sys_user u ON a.`created_by` = u.`sys_user_id`
        UNION
        SELECT 
        a.trx_overtime_id AS header_id,
        'trx_overtime' as 'table',
        a.documentno AS documentno,
        a.`docstatus` AS docstatus,
        a.`submissiontype` AS submissiontype,
        a.`submissiondate` AS submissiondate,
        dt.name AS doctype,
        u.username AS created_by,
        sm.url as url
        FROM trx_overtime a
        LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
        LEFT JOIN sys_submenu sm on dt.sys_submenu_id = sm.sys_submenu_id
        LEFT JOIN sys_user u ON a.`created_by` = u.`sys_user_id`
        UNION
        SELECT 
        a.trx_probation_id AS header_id,
        'trx_probation' as 'table',
        a.documentno AS documentno,
        a.`docstatus` AS docstatus,
        a.`submissiontype` AS submissiontype,
        a.`submissiondate` AS submissiondate,
        dt.name AS doctype,
        u.username AS created_by,
        sm.url as url
        FROM trx_probation a
        LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
        LEFT JOIN sys_submenu sm on dt.sys_submenu_id = sm.sys_submenu_id
        LEFT JOIN sys_user u ON a.`created_by` = u.`sys_user_id`
        UNION
        SELECT 
        a.trx_proxy_special_id AS header_id,
        'trx_proxy_special' as 'table',
        a.documentno AS documentno,
        a.`docstatus` AS docstatus,
        a.`submissiontype` AS submissiontype,
        a.`submissiondate` AS submissiondate,
        dt.name AS doctype,
        u.username AS created_by,
        sm.url as url
        FROM trx_proxy_special a
        LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
        LEFT JOIN sys_submenu sm on dt.sys_submenu_id = sm.sys_submenu_id
        LEFT JOIN sys_user u ON a.`created_by` = u.`sys_user_id`
        UNION
        SELECT 
        a.trx_submission_cancel_id AS header_id,
        'trx_submission_cancel' as 'table',
        a.documentno AS documentno,
        a.`docstatus` AS docstatus,
        a.`submissiontype` AS submissiontype,
        a.`submissiondate` AS submissiondate,
        dt.name AS doctype,
        u.username AS created_by,
        sm.url as url
        FROM trx_submission_cancel a
        LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
        LEFT JOIN sys_submenu sm on dt.sys_submenu_id = sm.sys_submenu_id
        LEFT JOIN sys_user u ON a.`created_by` = u.`sys_user_id`) as all_submission)");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS v_all_document_draft");
    }
}
