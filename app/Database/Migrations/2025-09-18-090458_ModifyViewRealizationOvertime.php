<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyViewRealizationOvertime extends Migration
{
    public function up()
    {
        $this->db->query("DROP VIEW IF EXISTS v_realization_overtime");

        $this->db->query("CREATE VIEW v_realization_overtime AS (
        SELECT *
        FROM (
        SELECT trx_overtime_detail.`trx_overtime_detail_id`,
        trx_overtime.`documentno`,
        trx_overtime.`docstatus`,
        md_employee.`fullname` AS employee_name,
        md_branch.`name` AS branch_name,
        md_division.`name` AS division_name,
        trx_overtime_detail.startdate AS startdate_line,
        trx_overtime_detail.enddate AS enddate_line,
        trx_overtime_detail.`md_employee_id`,
        trx_overtime_detail.`isagree`,
        CASE 
        WHEN trx_overtime_detail.approve_date IS NOT NULL
            THEN add_workdays(trx_overtime_detail.approve_date, md_doctype.`days_realization_mgr`)
            ELSE add_workdays(trx_overtime_detail.startdate, md_doctype.`days_realization_mgr`)
        END AS realization_date
        FROM trx_overtime
        JOIN trx_overtime_detail ON trx_overtime.`trx_overtime_id` = trx_overtime_detail.`trx_overtime_id`
        LEFT JOIN md_doctype ON trx_overtime.`submissiontype` = md_doctype.`md_doctype_id`
        LEFT JOIN md_employee ON md_employee.`md_employee_id` = trx_overtime_detail.`md_employee_id`
        LEFT JOIN md_branch ON trx_overtime.md_branch_id = md_branch.`md_branch_id`
        LEFT JOIN md_division ON trx_overtime.`md_division_id` = md_division.`md_division_id`
        WHERE trx_overtime_detail.`isagree` = 'M'
        ) AS realization_overtime
        )");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS v_realization_overtime");

        $this->db->query("CREATE VIEW v_realization_overtime AS (
        SELECT *
        FROM (
        SELECT trx_overtime_detail.`trx_overtime_detail_id`,
        trx_overtime.`documentno`,
        trx_overtime.`docstatus`,
        md_employee.`fullname` AS employee_name,
        md_branch.`name` AS branch_name,
        md_division.`name` AS division_name,
        trx_overtime_detail.startdate AS startdate_line,
        trx_overtime_detail.enddate AS enddate_line,
        trx_overtime_detail.`md_employee_id`,
        trx_overtime_detail.`isagree`,
        DATE_ADD(trx_overtime_detail.startdate, INTERVAL md_doctype.`days_realization_mgr` DAY) AS realization_date
        FROM trx_overtime
        JOIN trx_overtime_detail ON trx_overtime.`trx_overtime_id` = trx_overtime_detail.`trx_overtime_id`
        LEFT JOIN md_doctype ON trx_overtime.`submissiontype` = md_doctype.`md_doctype_id`
        LEFT JOIN md_employee ON md_employee.`md_employee_id` = trx_overtime_detail.`md_employee_id`
        LEFT JOIN md_branch ON trx_overtime.md_branch_id = md_branch.`md_branch_id`
        LEFT JOIN md_division ON trx_overtime.`md_division_id` = md_division.`md_division_id`
        WHERE trx_overtime_detail.`isagree` = 'M'
        ) AS realization_overtime
        )");
    }
}