<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFunctionAddWorkDaysonSQL extends Migration
{
    public function up()
    {
        $this->db->query("CREATE FUNCTION add_workdays(
            start_date DATE,
            days_to_add INT
        ) RETURNS DATE
        DETERMINISTIC
        BEGIN
            DECLARE counter INT DEFAULT 0;
            DECLARE `current_date` DATE;

            SET `current_date` = start_date;

            WHILE counter < days_to_add DO
                SET `current_date` = DATE_ADD(`current_date`, INTERVAL 1 DAY);

                IF WEEKDAY(`current_date`) < 5
           AND NOT EXISTS (
                SELECT 1 
                FROM (
                    SELECT startdate 
                    FROM md_holiday 
                    WHERE isactive = 'Y'
                    UNION
                    SELECT startdate 
                    FROM md_massleave 
                    WHERE isactive = 'Y' AND isaffect = 'Y'
                ) AS ex
                WHERE ex.startdate = `current_date`
                   ) THEN
                    SET counter = counter + 1;
                END IF;
            END WHILE;

            RETURN `current_date`;
        END");
    }

    public function down()
    {
        $this->db->query("DROP FUNCTION IF EXISTS add_workdays;");
    }
}