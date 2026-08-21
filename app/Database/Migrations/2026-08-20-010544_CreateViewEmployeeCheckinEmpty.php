<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewEmployeeCheckinEmpty extends Migration
{
    public function up()
    {
        $this->db->query("CREATE VIEW v_employee_checkin_empty AS 
        WITH date_data as (SELECT CURRENT_DATE AS tanggal)
        select me.md_employee_id, me.telegram_id 
        from md_employee me
        CROSS JOIN date_data d
        LEFT JOIN md_employee_work mew ON me.md_employee_id = mew.md_employee_id and (mew.validfrom <= d.tanggal and mew.validto >= d.tanggal)
        LEFT JOIN md_work_detail mwd ON mew.md_work_id = mwd.md_work_id AND (WEEKDAY(d.tanggal) + 1) = mwd.md_day_id
        left join (select ad.md_employee_id, a.trx_assignment_id ,a.branch_in, date(adate.date) as date
        from trx_assignment a 
        join trx_assignment_detail ad on a.trx_assignment_id = ad.trx_assignment_id
        join trx_assignment_date adate on ad.trx_assignment_detail_id = adate.trx_assignment_detail_id
        where a.submissiontype = 100008
        and a.docstatus in ('CO', 'IP')
        and adate.isagree in ('M', 'S', 'Y')) penugasan on me.md_employee_id = penugasan.md_employee_id and penugasan.`date` = d.tanggal
        where me.isactive = 'Y'
        and me.md_status_id in (100001, 100002, 100008)
        and me.md_levelling_id != 100001
        and (CASE 
	        WHEN mwd.md_work_detail_id IS NOT NULL AND HOUR(mwd.startwork) <> HOUR(NOW()) THEN FALSE
            WHEN me.md_levelling_id <= 100003 AND mwd.md_work_detail_id IS NOT NULL THEN NOT EXISTS(SELECT 1 FROM v_attendance va WHERE va.md_employee_id = me.md_employee_id AND va.date = d.tanggal AND (va.clock_in IS NOT NULL OR va.clock_in != ''))
            WHEN penugasan.trx_assignment_id IS NOT NULL THEN NOT EXISTS (SELECT 1 FROM v_attendance_branch vab 
                            WHERE vab.md_employee_id = me.md_employee_id AND vab.md_branch_id = penugasan.branch_in AND vab.`date` = d.tanggal AND (vab.clock_in IS NOT NULL OR vab.clock_in != ''))
            WHEN mwd.md_work_detail_id IS NOT NULL THEN NOT EXISTS (SELECT 1 FROM v_attendance_branch vab 
                            JOIN md_employee_branch meb ON vab.md_employee_id = meb.md_employee_id AND vab.md_branch_id = meb.md_branch_id 
                            WHERE vab.md_employee_id = me.md_employee_id AND vab.`date` = d.tanggal AND (vab.clock_in IS NOT NULL OR vab.clock_in != ''))
            ELSE false
        END)
        and (NOT EXISTS (SELECT 1 FROM md_holiday mh2 WHERE mh2.isactive = 'Y' and mh2.startdate = d.tanggal) AND NOT EXISTS (SELECT 1 FROM md_massleave mm2 WHERE mm2.isactive = 'Y' and mm2.isaffect = 'Y' and mm2.startdate = d.tanggal))
        and NOT EXISTS (SELECT 1 FROM trx_absent a
        join trx_absent_detail ad on a.trx_absent_id = ad.trx_absent_id
        where a.submissiontype in (100001, 100003, 100004, 100005, 100007, 100009)
        and a.md_employee_id = me.md_employee_id 
        and date(ad.`date`) = d.tanggal 
        and ad.isagree in ('M', 'S', 'Y')
        and a.docstatus in ('CO', 'IP'))");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS v_employee_checkin_empty");
    }
}
