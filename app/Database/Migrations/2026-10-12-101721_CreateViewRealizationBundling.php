<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewRealizationBundling extends Migration
{
    public function up()
    {
        $this->db->query("CREATE VIEW v_realization_bundling AS 
        select
            b.trx_bundling_id,
            b.documentno,
            b.docstatus,
            md_employee.fullname AS employee_name,
            md_branch.name AS branch_name,
            md_division.name AS division_name,
            b.realization_by,
            CASE
                when b.realization_date is NOT null THEN 'realisasi_gm'
                else 'realisasi_mgr' 
            END AS need_realization_by,
            CASE
                when b.realization_date is NOT null THEN add_workdays(b.realization_date,md_doctype.days_realization_mgr)
                else add_workdays(b.enddate,md_doctype.days_realization_mgr)
            END AS realization_date,
            b.md_employee_id
        FROM trx_bundling b
        LEFT JOIN md_doctype ON b.submissiontype = md_doctype.md_doctype_id
        LEFT JOIN md_employee ON b.md_employee_id = md_employee.md_employee_id
        LEFT JOIN md_branch ON b.md_branch_id = md_branch.md_branch_id
        LEFT JOIN md_division ON b.md_division_id = md_division.md_division_id
        WHERE b.docstatus = 'IP';");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS v_realization_bundling");
    }
}
