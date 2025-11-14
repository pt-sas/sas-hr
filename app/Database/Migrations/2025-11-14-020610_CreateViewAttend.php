<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewAttend extends Migration
{
    public function up()
    {
        $this->db->query("CREATE VIEW v_attendance_series AS WITH RECURSIVE date_series AS (
    SELECT CURDATE() AS date
    UNION ALL
    SELECT date - INTERVAL 1 DAY
    FROM date_series
    WHERE date > CURDATE() - INTERVAL 999 DAY
    ),
    attendance AS (
    SELECT 
        nik,
        DATE(checktime) AS date,
        MIN(CASE WHEN TIME(checktime) < '12:00:00' THEN TIME(checktime) END) AS clock_in,
        MAX(CASE WHEN TIME(checktime) > '12:00:00' THEN TIME(checktime) END) AS clock_out
    FROM trx_attendance
    GROUP BY nik, DATE(checktime)
    )
    SELECT
        e.md_employee_id,
        e.nik,
        ds.date,
        a.clock_in,
        a.clock_out
    FROM md_employee e
    CROSS JOIN date_series ds
    LEFT JOIN attendance a
        ON a.nik = e.nik
    AND a.date = ds.date
    ORDER BY e.md_employee_id, ds.date;");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS v_attendance_series");
    }
}
