<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyViewAttendanceSubmission extends Migration
{
    public function up()
    {
        $this->db->query("DROP VIEW IF EXISTS v_attendance_submission");

        $this->db->query("CREATE VIEW v_attendance_submission AS 
            WITH excluded_dates AS (
                SELECT startdate
                FROM md_holiday
                WHERE isactive = 'Y'
                UNION
                SELECT startdate
                FROM md_massleave
                WHERE isactive = 'Y' AND isaffect = 'Y'
            ),
            officeduties_submission as (
            select a.md_employee_id, a.trx_absent_id,DATE_FORMAT(ad.date,'%Y-%m-%d') as date
            from trx_absent a
            join trx_absent_detail ad on a.trx_absent_id = ad.trx_absent_id 
            where a.submissiontype in (100007, 100009)
            and a.docstatus IN ('CO', 'IP')
            and ad.isagree = 'Y')
            SELECT
                att.md_employee_id,
                att.type,
                att.period,
                att.total
            FROM (
                SELECT
                    va.md_employee_id                AS md_employee_id,
                    'kehadiran_masuk'                 AS type,
                    DATE_FORMAT(va.date, '%m-%Y')     AS period,
                    COUNT(*)                          AS total
                FROM v_attendance va
                JOIN md_employee_work mew
                    ON mew.md_employee_id = va.md_employee_id
                AND va.date BETWEEN mew.validfrom AND mew.validto
                JOIN (
                    SELECT startwork, md_work_id, md_day_id
                    FROM md_work_detail
                ) mwd
                    ON mwd.md_work_id = mew.md_work_id
                AND mwd.md_day_id  = WEEKDAY(va.date) + 1
                LEFT JOIN excluded_dates ex ON ex.startdate = va.date
                left join officeduties_submission os on os.md_employee_id = va.md_employee_id and va.date = os.date
                WHERE (va.clock_in is null OR va.clock_in >= ADDTIME(mwd.startwork, '00:01:00'))
                AND ex.startdate IS null
                and os.trx_absent_id is null
                GROUP BY va.md_employee_id, DATE_FORMAT(va.date, '%m-%Y')
                UNION ALL
                SELECT
                    va.md_employee_id                AS md_employee_id,
                    'kehadiran_pulang'                AS type,
                    DATE_FORMAT(va.date, '%m-%Y')     AS period,
                    COUNT(*)                          AS total
                FROM v_attendance va
                JOIN md_employee_work mew
                    ON mew.md_employee_id = va.md_employee_id
                AND va.date BETWEEN mew.validfrom AND mew.validto
                JOIN (
                    SELECT endwork, md_work_id, md_day_id
                    FROM md_work_detail
                ) mwd
                    ON mwd.md_work_id = mew.md_work_id
                AND mwd.md_day_id  = WEEKDAY(va.date) + 1
                LEFT JOIN excluded_dates ex ON ex.startdate = va.date
                left join officeduties_submission os on os.md_employee_id = va.md_employee_id and va.date = os.date
                WHERE (va.clock_out is null OR va.clock_out < mwd.endwork)
                AND ex.startdate IS null
                and os.trx_absent_id is null
                GROUP BY va.md_employee_id, DATE_FORMAT(va.date, '%m-%Y')
                UNION ALL
                SELECT
                    ta.md_employee_id                 AS md_employee_id,
                    'ijin'                             AS type,
                    DATE_FORMAT(tad.date, '%m-%Y')     AS period,
                    COUNT(*)                           AS total
                FROM trx_absent ta
                JOIN trx_absent_detail tad ON tad.trx_absent_id = ta.trx_absent_id
                WHERE ta.docstatus = 'CO'
                AND tad.isagree = 'Y'
                AND ta.submissiontype = 100004
                GROUP BY ta.md_employee_id, DATE_FORMAT(tad.date, '%m-%Y')
                UNION ALL
                SELECT
                    ta.md_employee_id             AS md_employee_id,
                    'alpa'                         AS type,
                    DATE_FORMAT(tad.date, '%Y')    AS period,
                    COUNT(*)                       AS total
                FROM trx_absent ta
                JOIN trx_absent_detail tad ON tad.trx_absent_id = ta.trx_absent_id
                WHERE ta.docstatus = 'CO'
                AND tad.isagree = 'Y'
                AND ta.submissiontype = 100002
                AND EXISTS (
                        SELECT 1
                        FROM trx_absent ta2
                        JOIN trx_absent_detail tad2 ON ta2.trx_absent_id = tad2.trx_absent_id
                        WHERE ta2.md_employee_id = ta.md_employee_id
                        AND ta2.submissiontype = 100002
                        AND tad2.isagree = 'Y'
                        AND tad2.is_generated_memo = 'N'
                        AND DATE_FORMAT(tad2.date, '%Y') = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                )
                GROUP BY ta.md_employee_id, DATE_FORMAT(tad.date, '%Y')
            ) att
            WHERE NOT EXISTS (
                SELECT 1
                FROM trx_hr_memo thm
                WHERE thm.md_employee_id = att.md_employee_id
                AND (
                        (att.type <> 'alpa' AND DATE_FORMAT(thm.memodate, '%m-%Y') = att.period)
                    OR (att.type =  'alpa' AND DATE_FORMAT(thm.memodate, '%Y')    = att.period)
                )
            )");
    }

    public function down()
    {
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
}
