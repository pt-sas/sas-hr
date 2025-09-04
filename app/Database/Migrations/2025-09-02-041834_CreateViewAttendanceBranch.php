<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewAttendanceBranch extends Migration
{
    public function up()
    {
        $this->db->query("CREATE VIEW v_attendance_branch AS (SELECT
        `e`.`md_employee_id`        AS `md_employee_id`,
        `attendance`.`nik`          AS `nik`,
        `attendance`.`date`         AS `date`,
        `attendance`.`clock_in`     AS `clock_in`,
        `attendance`.`clock_out`    AS `clock_out`,
        `attendance`.`md_branch_id` AS `md_branch_id`
        FROM ((SELECT
                `trx_attendance`.`nik`          AS `nik`,
                DATE_FORMAT(`trx_attendance`.`checktime`,'%Y-%m-%d') AS `date`,
                IF(DATE_FORMAT(MIN(`trx_attendance`.`checktime`),'%T') < '12:00:00',DATE_FORMAT(MIN(`trx_attendance`.`checktime`),'%T'),'') AS `clock_in`,
                IF(DATE_FORMAT(MAX(`trx_attendance`.`checktime`),'%T') > '12:00:00',DATE_FORMAT(MAX(`trx_attendance`.`checktime`),'%T'),'') AS `clock_out`,
                `md_attendance_machines`.`md_branch_id` AS md_branch_id
            FROM `trx_attendance`
            LEFT JOIN `md_attendance_machines` ON `md_attendance_machines`.`serialnumber` = trx_attendance.`serialnumber`
            GROUP BY `trx_attendance`.`nik`,DATE_FORMAT(`trx_attendance`.`checktime`,'%Y-%m-%d'),`md_attendance_machines`.`md_branch_id`) `attendance`
        LEFT JOIN `md_employee` `e`
            ON (`e`.`nik` = `attendance`.`nik`)))");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS v_attendance_branch");
    }
}
