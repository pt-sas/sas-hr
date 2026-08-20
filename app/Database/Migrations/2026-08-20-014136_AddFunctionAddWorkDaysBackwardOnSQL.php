<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFunctionAddWorkDaysBackwardOnSQL extends Migration
{
    public function up()
    {
        $sql = "CREATE FUNCTION add_workdays_backwards(
                start_date DATE,
                days_to_subtract INT
            )
            RETURNS DATE
            DETERMINISTIC
            BEGIN
                DECLARE counter INT DEFAULT 0;
                DECLARE v_current_date DATE;

                SET v_current_date = start_date;

                WHILE counter < days_to_subtract DO

                    SET v_current_date = DATE_SUB(
                        v_current_date,
                        INTERVAL 1 DAY
                    );

                    IF WEEKDAY(v_current_date) < 5
                       AND NOT EXISTS (
                            SELECT 1
                            FROM (
                                SELECT startdate
                                FROM md_holiday
                                WHERE isactive = 'Y'

                                UNION

                                SELECT startdate
                                FROM md_massleave
                                WHERE isactive = 'Y'
                                  AND isaffect = 'Y'
                            ) AS ex
                            WHERE ex.startdate = v_current_date
                       )
                    THEN
                        SET counter = counter + 1;
                    END IF;

                END WHILE;

                RETURN v_current_date;
            END
        ";

        $this->db->query($sql);
    }

    public function down()
    {
        $this->db->query("DROP FUNCTION IF EXISTS add_workdays_backwards;");
    }
}
