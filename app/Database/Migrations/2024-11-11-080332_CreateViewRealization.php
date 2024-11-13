<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewRealization extends Migration
{
    public function up()
    {
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
adetail.date AS date,
dt.name AS doctype,
a.md_employee_id AS employee_id,
a.image AS image,
a.md_branch_id,
a.md_division_id
FROM trx_absent a
LEFT JOIN trx_absent_detail adetail ON a.`trx_absent_id` = adetail.`trx_absent_id`
LEFT JOIN md_employee e ON a.`md_employee_id` = e.`md_employee_id`
LEFT JOIN md_branch b ON a.`md_branch_id` = b.`md_branch_id`
LEFT JOIN md_division d ON a.`md_division_id` = d.`md_division_id`
LEFT JOIN md_leavetype lt ON a.`md_leavetype_id` = lt.`md_leavetype_id`
LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`
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
adate.date AS date,
dt.name AS doctype,
a.md_employee_id AS employee_id,
'' AS image,
a.md_branch_id,
a.md_division_id
FROM trx_assignment a
LEFT JOIN trx_assignment_detail adetail ON a.trx_assignment_id = adetail.trx_assignment_id
LEFT JOIN trx_assignment_date adate ON adetail.trx_assignment_detail_id = adate.trx_assignment_detail_id
LEFT JOIN md_employee e ON adetail.`md_employee_id` = e.`md_employee_id`
LEFT JOIN md_branch b ON a.`md_branch_id` = b.`md_branch_id`
LEFT JOIN md_division d ON a.`md_division_id` = d.`md_division_id`
LEFT JOIN md_doctype dt ON a.`submissiontype` = dt.`md_doctype_id`) AS realization
WHERE realization.isagree IS NOT NULL
)");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS v_realization");
    }
}