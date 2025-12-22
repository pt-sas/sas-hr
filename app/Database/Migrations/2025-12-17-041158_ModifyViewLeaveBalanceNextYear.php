<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyViewLeaveBalanceNextYear extends Migration
{
    public function up()
    {

        $this->db->query("DROP VIEW IF EXISTS v_leavebalance_nextyear");

        $this->db->query("CREATE VIEW v_leavebalance_nextyear AS SELECT
        md_employee.md_employee_id,
        case
            WHEN YEAR(md_employee.registerdate) < YEAR(NOW()) THEN 12 - ml_year.total_massleave
            WHEN YEAR(md_employee.registerdate) = YEAR(NOW()) 
                AND MONTH(md_employee.registerdate) = 12 
                AND DAY(md_employee.registerdate) >= 6 THEN 0
            WHEN YEAR(md_employee.registerdate) = YEAR(NOW()) THEN
                (ABS(
                    PERIOD_DIFF(
                        CASE 
                            WHEN DAY(md_employee.registerdate) < 6 
                                THEN DATE_FORMAT(md_employee.registerdate, '%Y%m')
                            ELSE DATE_FORMAT(DATE_ADD(md_employee.registerdate, INTERVAL 1 MONTH), '%Y%m')
                        END,
                        CONCAT(YEAR(NOW()), '12')
                    )
                ) + 1) - ml_after.total_after_register
            ELSE 0
        END AS saldo_cuti,
        year(DATE_ADD(NOW(), interval 1 YEAR)) as year
        from md_employee
        join (
		    SELECT 
		        COUNT(*) AS total_massleave
		    FROM md_massleave
		    WHERE isaffect = 'Y'
		      AND year(startdate) = year(now())) ml_year
		LEFT JOIN (
		    SELECT 
		        e2.md_employee_id,
		        COUNT(mm.startdate) AS total_after_register
		    FROM md_employee e2
		    LEFT JOIN md_massleave mm
		        ON mm.isaffect = 'Y'
		       AND year(mm.startdate ) = year(now())
		       AND date(mm.startdate) >= date(e2.registerdate)
		    GROUP BY e2.md_employee_id
		) ml_after ON ml_after.md_employee_id = md_employee.md_employee_id
        where md_employee.md_status_id in (100001, 100002, 100008)
        and md_employee.isactive = 'Y'");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS v_leavebalance_nextyear");

        $this->db->query("CREATE VIEW v_leavebalance_nextyear AS (SELECT
        md_employee.md_employee_id,
        CASE
            WHEN YEAR(md_employee.registerdate) < YEAR(NOW()) THEN 12
            WHEN YEAR(md_employee.registerdate) = YEAR(NOW()) 
                AND MONTH(md_employee.registerdate) = 12 
                AND DAY(md_employee.registerdate) >= 6 THEN 0
            WHEN YEAR(md_employee.registerdate) = YEAR(NOW()) THEN
                ABS(
                    PERIOD_DIFF(
                        CASE 
                            WHEN DAY(md_employee.registerdate) < 6 
                                THEN DATE_FORMAT(md_employee.registerdate, '%Y%m')
                            ELSE DATE_FORMAT(DATE_ADD(md_employee.registerdate, INTERVAL 1 MONTH), '%Y%m')
                        END,
                        CONCAT(YEAR(NOW()), '12')
                    )
                ) + 1
            ELSE 0
        END AS saldo_cuti,
        year(DATE_ADD(NOW(), interval 1 YEAR)) as year
        from md_employee
        where md_employee.md_status_id in (100001, 100002, 100008)
        and md_employee.isactive = 'Y'
        )");
    }
}
