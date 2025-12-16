<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnWorkDateAttendance extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE trx_attendance
                            ADD COLUMN work_date DATE
                            GENERATED ALWAYS AS (DATE(checktime)) STORED;");

        $this->db->query("CREATE INDEX IDX_Att_WorkDate
                        ON trx_attendance (nik, work_date);");

        $this->db->query("CREATE INDEX IDX_Att_SerialNumber
                        ON trx_attendance (serialnumber);");

        $this->db->query("CREATE INDEX IDX_Machine_SerialNumber
                        ON md_attendance_machines (serialnumber);");
    }

    public function down()
    {
        $this->db->query("DROP INDEX IDX_Att_WorkDate ON trx_attendance;");
        $this->db->query("DROP INDEX IDX_Att_SerialNumber ON trx_attendance;");
        $this->db->query("DROP INDEX IDX_Machine_SerialNumber ON md_attendance_machines;");

        $this->db->query('ALTER TABLE trx_attendance DROP COLUMN work_date');
    }
}
