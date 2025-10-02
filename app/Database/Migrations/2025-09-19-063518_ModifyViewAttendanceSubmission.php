<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyViewAttendanceSubmission extends Migration
{
    public function up()
    {
        $fields = ['memocontent' => ['type' => 'VARCHAR', 'constraint' => 510, 'null', true]];
        $this->forge->modifyColumn('trx_hr_memo', $fields);

        $fields = [
            'is_generated_memo' => ['type' => 'CHAR', 'constraint' => 1, 'default' => 'N'],
        ];

        $this->forge->addColumn('trx_absent_detail', $fields);

        $this->db->query("DROP VIEW IF EXISTS v_attendance_submission");

        $this->db->query("CREATE VIEW v_attendance_submission AS (
                SELECT
            att.md_employee_id AS md_employee_id,
            att.type          AS type,
            att.period        AS period,
            att.total         AS total
            FROM (
            SELECT
            va.md_employee_id AS md_employee_id,
            'kehadiran_masuk' AS type,
            DATE_FORMAT(va.date, '%m-%Y') AS period,
            COUNT(va.md_employee_id) AS total
            FROM v_attendance va
            WHERE (va.clock_in = '' OR va.clock_in >= '08:01')
            and WEEKDAY(va.`date`) < 5
            AND NOT EXISTS (
            SELECT 1 
            FROM (
            SELECT startdate 
            FROM md_holiday 
            WHERE isactive = 'Y'
            UNION
            SELECT startdate 
            FROM md_massleave 
            WHERE isactive = 'Y' AND isaffect = 'Y') AS ex WHERE ex.startdate = va.`date`)
            GROUP BY
            va.md_employee_id,
            'kehadiran_masuk',
            DATE_FORMAT(va.date, '%m-%Y')
            UNION ALL
            SELECT
            va.md_employee_id AS md_employee_id,
            'kehadiran_pulang' AS type,
            DATE_FORMAT(va.date, '%m-%Y') AS period,
            COUNT(va.md_employee_id) AS total
            FROM v_attendance va
            WHERE (va.clock_out = '' OR va.clock_out < '17:00')
            and WEEKDAY(va.`date`) < 5
            AND NOT EXISTS (
            SELECT 1 
            FROM (
            SELECT startdate 
            FROM md_holiday 
            WHERE isactive = 'Y'
            UNION
            SELECT startdate 
            FROM md_massleave 
            WHERE isactive = 'Y' AND isaffect = 'Y') AS ex WHERE ex.startdate = va.`date`)
            GROUP BY
            va.md_employee_id,
            'kehadiran_pulang',
            DATE_FORMAT(va.date, '%m-%Y')
            UNION ALL
            SELECT
                ta.md_employee_id AS md_employee_id,
                'ijin'            AS type,
                DATE_FORMAT(tad.date, '%m-%Y') AS period,
                COUNT(ta.md_employee_id) AS total
            FROM trx_absent ta
            JOIN trx_absent_detail tad
                ON tad.trx_absent_id = ta.trx_absent_id
            WHERE ta.docstatus = 'CO'
            AND tad.isagree = 'Y'
            AND ta.submissiontype = 100004
            GROUP BY
                ta.md_employee_id,
                'ijin',
                DATE_FORMAT(tad.date, '%m-%Y')
            UNION ALL
            SELECT
                ta.md_employee_id AS md_employee_id,
                'alpa' AS type,
                DATE_FORMAT(tad.date, '%Y') AS period,
				count(ta.md_employee_id) as total
            	FROM trx_absent ta
            	JOIN trx_absent_detail tad
                ON tad.trx_absent_id = ta.trx_absent_id
            	WHERE ta.docstatus = 'CO'
            	and exists (select 1
            	from trx_absent ta2 
            	join trx_absent_detail tad2 on ta2.trx_absent_id = tad2.trx_absent_id
            	where tad2.is_generated_memo = 'N'
            	and date_format(tad2.date, '%Y') = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
            	and ta2.submissiontype = 100002
            	AND tad2.isagree = 'Y'
            	and ta2.md_employee_id = ta.md_employee_id)
            	AND tad.isagree = 'Y'
            	AND ta.submissiontype = 100002
            	GROUP BY
                ta.md_employee_id, 'alpa' ,DATE_FORMAT(tad.date, '%Y')
        ) att
        WHERE NOT EXISTS (
            SELECT 1
            FROM trx_hr_memo thm
            WHERE DATE_FORMAT(thm.memodate, '%m-%Y') = att.period
            AND thm.md_employee_id = att.md_employee_id
        ))");
    }

    public function down()
    {
        $fields = ['memocontent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null', true]];
        $this->forge->modifyColumn('trx_hr_memo', $fields);

        $fields = ['is_generated_memo'];

        $this->forge->dropColumn('trx_absent_detail', $fields);

        $this->db->query("DROP VIEW IF EXISTS v_attendance_submission");

        $this->db->query("CREATE VIEW v_attendance_submission AS (
        SELECT
            att.md_employee_id AS md_employee_id,
            att.type          AS type,
            att.period        AS period,
            att.total         AS total
        FROM (
            SELECT
                va.md_employee_id AS md_employee_id,
                'kehadiran'       AS type,
                DATE_FORMAT(va.date, '%m-%Y') AS period,
                COUNT(va.md_employee_id) AS total
            FROM v_attendance va
            WHERE (va.clock_in = '' OR va.clock_in > '08:30')
            GROUP BY
                va.md_employee_id,
                'kehadiran',
                DATE_FORMAT(va.date, '%m-%Y')
            UNION ALL
            SELECT
                ta.md_employee_id AS md_employee_id,
                'ijin'            AS type,
                DATE_FORMAT(tad.date, '%m-%Y') AS period,
                COUNT(ta.md_employee_id) AS total
            FROM trx_absent ta
            JOIN trx_absent_detail tad
                ON tad.trx_absent_id = ta.trx_absent_id
            WHERE ta.docstatus = 'CO'
            AND tad.isagree = 'Y'
            AND ta.submissiontype = 100004
            GROUP BY
                ta.md_employee_id,
                'ijin',
                DATE_FORMAT(tad.date, '%m-%Y')
            UNION ALL
            SELECT
                ta.md_employee_id AS md_employee_id,
                'alpa'            AS type,
                DATE_FORMAT(tad.date, '%m-%Y') AS period,
                COUNT(ta.md_employee_id) AS total
            FROM trx_absent ta
            JOIN trx_absent_detail tad
                ON tad.trx_absent_id = ta.trx_absent_id
            WHERE ta.docstatus = 'CO'
            AND tad.isagree = 'Y'
            AND ta.submissiontype = 100002
            GROUP BY
                ta.md_employee_id,
                'alpa',
                DATE_FORMAT(tad.date, '%m-%Y')
        ) att
        WHERE NOT EXISTS (
            SELECT 1
            FROM trx_hr_memo thm
            WHERE DATE_FORMAT(thm.memodate, '%m-%Y') = att.period
            AND thm.md_employee_id = att.md_employee_id
        ))");
    }
}
