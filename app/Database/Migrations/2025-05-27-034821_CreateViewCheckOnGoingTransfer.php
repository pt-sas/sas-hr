<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewCheckOnGoingTransfer extends Migration
{
    public function up()
    {
        $this->db->query("
        CREATE OR REPLACE VIEW v_intransition_delegation AS
        SELECT 
            tdt.employee_from,
            us.sys_user_id as user_from,
            tdt.employee_to,
            su.sys_user_id AS user_to,
            tdtd.md_employee_id
        FROM 
            trx_delegation_transfer tdt
        JOIN 
            trx_delegation_transfer_detail tdtd 
            ON tdt.trx_delegation_transfer_id = tdtd.trx_delegation_transfer_id
        JOIN 
            sys_user su 
            ON su.md_employee_id = tdt.employee_to
        JOIN 
            sys_user us 
            ON us.md_employee_id = tdt.employee_from
        WHERE 
            DATE_FORMAT(tdt.startdate , '%Y-%m-%d') <= CURDATE()
            AND DATE_FORMAT(tdt.enddate, '%Y-%m-%d') >= CURDATE()
            AND tdtd.istransfered = 'IP'
    ");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS v_intransition_delegation");
    }
}
