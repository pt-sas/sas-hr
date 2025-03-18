<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewAllSubmission extends Migration
{
    public function up()
    {
        $this->db->query("CREATE VIEW v_all_submission AS (
        SELECT *
        FROM 
        (
        SELECT 
a.trx_absent_id AS header_id,
'trx_absent' as 'table',
a.documentno AS documentno,
a.`docstatus` AS docstatus,
a.`isapproved` AS isapproved,
a.`submissiontype` AS submissiontype,
a.`reason` AS reason,
adetail.trx_absent_detail_id AS id,
'trx_absent_detail' as 'table_detail',
adetail.isagree AS isagree,
adetail.date AS date,
dt.name AS doctype,
a.md_employee_id AS employee_id,
a.md_branch_id,
a.md_division_id,
a.md_employee_id,
adetail.ref_absent_detail_id as ref_id,
adetail.table as ref_table
FROM trx_absent a
LEFT JOIN trx_absent_detail adetail ON a.`trx_absent_id` = adetail.`trx_absent_id`
LEFT JOIN md_leavetype lt ON a.`md_leavetype_id` = lt.`md_leavetype_id`
LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
LEFT JOIN sys_user u ON adetail.`realization_by` = u.`sys_user_id`
UNION
SELECT 
a.trx_assignment_id AS header_id,
'trx_assignment' as 'table',
a.documentno AS documentno,
a.`docstatus` AS docstatus,
a.`isapproved` AS isapproved,
a.`submissiontype` AS submissiontype,
a.`reason` AS reason,
adate.trx_assignment_date_id AS id,
'trx_assignment_date' as 'table_detail',
adate.isagree AS isagree,
adate.date AS date,
dt.name AS doctype,
a.md_employee_id AS employee_id,
a.md_branch_id,
a.md_division_id,
adetail.md_employee_id,
adate.reference_id as ref_id,
adate.table as ref_table
FROM trx_assignment a
LEFT JOIN trx_assignment_detail adetail ON a.trx_assignment_id = adetail.trx_assignment_id
LEFT JOIN trx_assignment_date adate ON adetail.trx_assignment_detail_id = adate.trx_assignment_detail_id
LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
LEFT JOIN sys_user u ON adate.`realization_by` = u.`sys_user_id`) as all_submission
WHERE isagree IS NOT NULL)");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS v_all_submission");
    }
}
