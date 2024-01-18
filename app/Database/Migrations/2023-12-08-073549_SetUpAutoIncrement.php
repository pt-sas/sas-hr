<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SetUpAutoIncrement extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE md_leveling AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_bloodtype AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_leveling AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_branch AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_city AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_country AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_courses AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_day AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_district AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_division AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_driverlicence AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_education AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_employee AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_family AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_family_core AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_holiday AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_job_history AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_leavetype AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_massleave AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_position AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_province AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_rekammedis AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_religion AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_skill AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_social_media AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_status AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_subdistrict AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_work AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE md_workdetail AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE trx_absent AUTO_INCREMENT = 100001;');
        $this->db->query('ALTER TABLE trx_overtime AUTO_INCREMENT = 100001;');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE md_leveling AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_bloodtype AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_leveling AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_branch AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_city AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_country AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_courses AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_day AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_district AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_division AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_driverlicence AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_education AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_employee AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_family AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_family_core AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_holiday AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_job_history AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_leavetype AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_massleave AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_position AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_province AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_rekammedis AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_religion AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_skill AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_social_media AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_status AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_subdistrict AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_work AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE md_workdetail AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE trx_absent AUTO_INCREMENT = 1;');
        $this->db->query('ALTER TABLE trx_overtime AUTO_INCREMENT = 1;');
    }
}
