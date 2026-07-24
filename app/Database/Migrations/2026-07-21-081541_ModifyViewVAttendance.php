<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyViewVAttendance extends Migration
{
    public function up()
    {
        $this->db->query("DROP VIEW IF EXISTS v_attendance_branch");

        $this->db->query("CREATE VIEW v_attendance_branch AS 
        SELECT e.md_employee_id,
        t.nik,
        t.work_date AS date,
        MIN(CASE WHEN TIME(t.checktime) < COALESCE(mwd.max_time_late, '12:00:00') 
                THEN TIME(t.checktime) END) AS clock_in,
        MAX(CASE WHEN TIME(t.checktime) > COALESCE(mwd.max_time_late, '12:00:00') 
                THEN TIME(t.checktime) END) AS clock_out,
        m.md_branch_id
        FROM trx_attendance t
        JOIN md_employee e ON t.nik = e.nik
        LEFT JOIN md_attendance_machines m ON m.serialnumber = t.serialnumber
        LEFT JOIN md_employee_work mew ON e.md_employee_id = mew.md_employee_id and (mew.validfrom <= t.work_date and mew.validto >= t.work_date)
        LEFT JOIN (
            SELECT DATE_ADD(startwork, INTERVAL 240 MINUTE) AS max_time_late, md_work_id, md_day_id
            FROM md_work_detail
        ) mwd ON mew.md_work_id = mwd.md_work_id 
            AND (WEEKDAY(t.checktime) + 1) = mwd.md_day_id
        GROUP BY e.md_employee_id, t.nik, t.work_date, m.md_branch_id
        ORDER BY t.work_date DESC;");

        $this->db->query("DROP VIEW IF EXISTS v_attendance");

        $this->db->query("CREATE VIEW v_attendance AS 
        SELECT e.md_employee_id,
        t.nik,
        t.work_date AS date,
        MIN(CASE WHEN TIME(t.checktime) < COALESCE(mwd.max_time_late, '12:00:00') 
                THEN TIME(t.checktime) END) AS clock_in,
        MAX(CASE WHEN TIME(t.checktime) > COALESCE(mwd.max_time_late, '12:00:00') 
                THEN TIME(t.checktime) END) AS clock_out
        FROM trx_attendance t
        JOIN md_employee e ON t.nik = e.nik
        LEFT JOIN md_employee_work mew ON e.md_employee_id = mew.md_employee_id and (mew.validfrom <= t.work_date and mew.validto >= t.work_date)
        LEFT JOIN (
            SELECT DATE_ADD(startwork, INTERVAL 240 MINUTE) AS max_time_late, md_work_id, md_day_id
            FROM md_work_detail
        ) mwd ON mew.md_work_id = mwd.md_work_id 
            AND (WEEKDAY(t.checktime) + 1) = mwd.md_day_id
        GROUP BY e.md_employee_id, t.nik, t.work_date
        ORDER BY t.work_date DESC;");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS v_attendance_branch");

        $this->db->query("CREATE VIEW v_attendance_branch AS
        Select e.md_employee_id, 
        t.nik,
        t.work_date  AS date,
        MIN(CASE WHEN TIME(t.checktime) < '12:00:00' THEN TIME(t.checktime) END) AS clock_in,
        MAX(CASE WHEN TIME(t.checktime) > '12:00:00' THEN TIME(t.checktime) END) AS clock_out,
        m.md_branch_id 
        FROM trx_attendance t
        LEFT JOIN md_attendance_machines m ON m.serialnumber = t.serialnumber
        join md_employee e on t.nik = e.nik
        GROUP by e.md_employee_id, t.nik, t.work_date , m.md_branch_id
        ");

        $this->db->query("DROP VIEW IF EXISTS v_attendance");

        $this->db->query("CREATE VIEW v_attendance AS 
        SELECT e.md_employee_id,
        t.nik,
        t.work_date AS date,
        MIN(CASE WHEN TIME(t.checktime) < '12:00:00'
                    THEN TIME(t.checktime) END) AS clock_in,
        MAX(CASE WHEN TIME(t.checktime) > '12:00:00'
                    THEN TIME(t.checktime) END) AS clock_out
        FROM trx_attendance t
        JOIN md_employee e ON t.nik = e.nik
        GROUP BY e.md_employee_id, t.nik, t.work_date
        ORDER BY t.work_date DESC;");
    }
}
