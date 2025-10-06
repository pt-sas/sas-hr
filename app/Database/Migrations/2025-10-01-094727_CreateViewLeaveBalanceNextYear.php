<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewLeaveBalanceNextYear extends Migration
{
    public function up()
    {
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

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS v_leavebalance_nextyear");
    }
}
