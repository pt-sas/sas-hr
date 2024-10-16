<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewReportEmpSkills extends Migration
{
    public function up()
    {
        $this->db->query("CREATE VIEW v_rpt_employee_skills AS (SELECT e.`value` AS `Value`,
e.`fullname` AS `Nama Lengkap`,
branch.name AS `Cabang`,
divi.name AS `Divisi`,
skill.name AS `Keterampilan`,
skill.description AS `Keterangan`,
skill.ability AS `Kemampuan`,
skill.skilltype AS `Tipe Skill`,
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
LEFT JOIN md_employee_skills skill ON skill.`md_employee_id` = e.`md_employee_id`
WHERE skill.skilltype = 'K'
ORDER BY branch.md_branch_id, divi.md_division_id,e.md_employee_id DESC
)");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS v_rpt_employee_skills");
    }
}