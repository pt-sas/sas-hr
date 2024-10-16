<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateViewReportEmployee extends Migration
{
  public function up()
  {
    // SQL untuk membuat view
    $this->db->query("CREATE VIEW v_rpt_employee AS 
                          (SELECT DISTINCT
  `e`.`value`             AS `Value`,
  `e`.`nik`               AS `NIK`,
  `e`.`fullname`          AS `Nama Lengkap`,
  `e`.`nickname`          AS `Nama Panggilan`,
  `e`.`pob`               AS `Tempat Lahir`,
  DATE_FORMAT(`e`.`birthday`,'%d-%m-%Y') AS `Tanggal Lahir`,
  `bt`.`name`             AS `Golongan Darah`,
  `e`.`rhesus`            AS `Rhesus`,
  (CASE WHEN (`e`.`gender` = 'L') THEN 'Laki-Laki' ELSE 'Perempuan' END) AS `Jenis Kelamin`,
  `e`.`nationality`       AS `Kewarganegaraan`,
  `r`.`name`              AS `Agama`,
  `e`.`marital_status`    AS `Status Menikah`,
  `e`.`email`             AS `Email`,
  `e`.`phone`             AS `No HP Pribadi`,
  `e`.`phone2`            AS `No HP Pribadi 2`,
  `e`.`homestatus`        AS `Status Rumah`,
  `branch`.`name`         AS `Cabang`,
  `divi`.`name`           AS `Divisi`,
  `sup`.`fullname`        AS `Superior`,
  `p`.`name`              AS `Jabatan`,
  `l`.`name`              AS `Level`,
  `e`.`officephone`       AS `No HP Kantor`,
  DATE_FORMAT(`e`.`registerdate`,'%d-%m-%Y') AS `Tanggal Bergabung`,
  `s`.`name`              AS `Status Karyawan`,
  `e`.`address_dom`       AS `Alamat Domisili`,
  `cd`.`name`             AS `Negara`,
  `pd`.`name`             AS `Provinsi`,
  `citd`.`name`           AS `Kota`,
  `dd`.`name`             AS `Kecamatan`,
  `sd`.`name`             AS `Kelurahan`,
  `e`.`postalcode_dom`    AS `Kode POS`,
  `e`.`issameaddress`     AS `Sama dengan alamat domisili`,
  `e`.`address`           AS `Alamat KTP`,
  `country`.`name`        AS `Negara KTP`,
  `province`.`name`       AS `Provinsi KTP`,
  `city`.`name`           AS `Kota KTP`,
  `district`.`name`       AS `Kecamatan KTP`,
  `subdistrict`.`name`    AS `Kelurahan KTP`,
  `e`.`postalcode`        AS `Kode POS KTP`,
  `e`.`card_id` AS `No KTP`,
  `e`.`npwp_id`           AS `No NPWP`,
  `e`.`ptkp_status`       AS `Status PTKP`,
  `e`.`bpjs_kes_no`       AS `BPJS Kesehatan`,
  DATE_FORMAT(`e`.`bpjs_kes_period`,'%d-%m-%Y') AS `BPJS Kesehatan Periode`,
  `e`.`bpjs_tenaga_no`    AS `BPJS Tenaga Kerja`,
  DATE_FORMAT(`e`.`bpjs_tenaga_period`,'%d-%m-%Y') AS `BPJS Tenaga Kerja Periode`,
  `e`.`bank`              AS `Nama Bank`,
  `e`.`bank_branch`       AS `Cabang Bank`,
  `e`.`bank_account`      AS `No Rekening`,
  `e`.`md_employee_id`    AS `md_employee_id`,
  `branch`.`md_branch_id` AS `md_branch_id`,
  `divi`.`md_division_id` AS `md_division_id`,
  `e`.`md_status_id`      AS `md_status_id`,
  `e`.`isactive`      AS `isactive`
FROM ((((((((((((((((((((`md_employee` `e`
                      LEFT JOIN `md_religion` `r`
                        ON ((`e`.`md_religion_id` = `r`.`md_religion_id`)))
                     LEFT JOIN `md_bloodtype` `bt`
                       ON ((`e`.`md_bloodtype_id` = `bt`.`md_bloodtype_id`)))
                    LEFT JOIN `md_levelling` `l`
                      ON ((`e`.`md_levelling_id` = `l`.`md_levelling_id`)))
                   LEFT JOIN `md_position` `p`
                     ON ((`e`.`md_position_id` = `p`.`md_position_id`)))
                  LEFT JOIN `md_status` `s`
                    ON ((`e`.`md_status_id` = `s`.`md_status_id`)))
                 LEFT JOIN `md_country` `cd`
                   ON ((`e`.`md_country_dom_id` = `cd`.`md_country_id`)))
                LEFT JOIN `md_province` `pd`
                  ON ((`e`.`md_province_dom_id` = `pd`.`md_province_id`)))
               LEFT JOIN `md_city` `citd`
                 ON ((`e`.`md_city_dom_id` = `citd`.`md_city_id`)))
              LEFT JOIN `md_district` `dd`
                ON ((`e`.`md_district_dom_id` = `dd`.`md_district_id`)))
             LEFT JOIN `md_subdistrict` `sd`
               ON ((`e`.`md_subdistrict_dom_id` = `sd`.`md_subdistrict_id`)))
            LEFT JOIN `md_country` `country`
              ON ((`e`.`md_country_dom_id` = `country`.`md_country_id`)))
           LEFT JOIN `md_province` `province`
             ON ((`e`.`md_province_dom_id` = `province`.`md_province_id`)))
          LEFT JOIN `md_city` `city`
            ON ((`e`.`md_city_dom_id` = `city`.`md_city_id`)))
         LEFT JOIN `md_district` `district`
           ON ((`e`.`md_district_dom_id` = `district`.`md_district_id`)))
        LEFT JOIN `md_subdistrict` `subdistrict`
          ON ((`e`.`md_subdistrict_dom_id` = `subdistrict`.`md_subdistrict_id`)))
       LEFT JOIN `md_employee` `sup`
         ON ((`e`.`superior_id` = `sup`.`md_employee_id`)))
      LEFT JOIN `md_employee_branch` `empbranch`
        ON ((`e`.`md_employee_id` = `empbranch`.`md_employee_id`)))
     LEFT JOIN `md_branch` `branch`
       ON ((`empbranch`.`md_branch_id` = `branch`.`md_branch_id`)))
    LEFT JOIN `md_employee_division` `empdiv`
      ON ((`e`.`md_employee_id` = `empdiv`.`md_employee_id`)))
   LEFT JOIN `md_division` `divi`
     ON ((`empdiv`.`md_division_id` = `divi`.`md_division_id`)))
ORDER BY `branch`.`md_branch_id`,`divi`.`md_division_id`)");
  }

  public function down()
  {
    // SQL untuk menghapus view
    $this->db->query("DROP VIEW IF EXISTS v_rpt_employee");
  }
}