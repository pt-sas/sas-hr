<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewReportEmpFamilyCore extends Migration
{
    public function up()
    {
        $this->db->query("CREATE VIEW v_rpt_employee_family_core AS (SELECT DISTINCT e.`value` AS `Value`,
e.`nik` AS `NIK`,
e.`fullname` AS `Nama Lengkap`,
branch.name AS `Cabang`,
divi.name AS `Divisi`,
famcore.`member` AS `keluarga`,
famcore.`name` AS `Nama`,
CASE WHEN famcore.gender = 'L' THEN 'Laki-Laki'
ELSE 'Perempuan' END AS `Jenis Kelamin`,
DATE_FORMAT(famcore.`birthdate` ,'%d-%m-%Y') AS `Tanggal Lahir`,
famcore.`education` AS `Pendidikan`,
famcore.`job` AS `Pekerjaan`,
famcore.`phone` AS `No Telp`,
famcore.`status` AS `Status`,
DATE_FORMAT(famcore.`dateofdeath`,'%d-%m-%Y')  AS `Tanggal Meninggal`,
  `e`.`md_employee_id`    AS `md_employee_id`,
  `branch`.`md_branch_id` AS `md_branch_id`,
  `divi`.`md_division_id` AS `md_division_id`,
  `e`.`md_status_id`      AS `md_status_id`,
  `e`.`isactive`      AS `isactive`
FROM md_employee e
LEFT JOIN md_employee_branch empbranch ON e.`md_employee_id` = empbranch.`md_employee_id`
LEFT JOIN md_branch branch ON empbranch.`md_branch_id` = branch.`md_branch_id`
LEFT JOIN md_employee_division empdiv ON e.`md_employee_id` = empdiv.`md_employee_id`
LEFT JOIN md_division divi ON empdiv.`md_division_id` = divi.`md_division_id` 
LEFT JOIN md_employee_family_core famcore ON e.`md_employee_id` = famcore.`md_employee_id`
ORDER BY branch.md_branch_id, divi.md_division_id,e.md_employee_id, famcore.member DESC
)");
    }

    public function down()
    {
        // SQL untuk menghapus view
        $this->db->query("DROP VIEW IF EXISTS v_rpt_employee_family_core");
    }
}