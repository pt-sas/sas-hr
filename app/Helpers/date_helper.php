<?php

function format_idn($date)
{
    $month = array(
        1 =>   'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );

    $split = explode('-', $date);
    return $split[2] . ' ' . $month[(int)$split[1]] . ' ' . $split[0];
}

function format_dmy($date, $separator)
{
    return date('d' . $separator . 'M' . $separator . 'Y', strtotime($date));
}

function format_dmytime($date, $separator)
{
    return date('d' . $separator . 'M' . $separator . 'Y' . ' H:i', strtotime($date));
}

function format_time($date)
{
    return date('H:i', strtotime($date));
}

function convertToMinutes($date)
{
    $time    = explode(':', $date);
    $minutes = ($time[0] * 60.0 + $time[1] * 1.0);
    return $minutes;
}

function formatDay_idn($day)
{
    switch ($day) {
        case 0: {
                $day = 'Minggu'; //Sunday
            }
            break;
        case 1: {
                $day = 'Senin'; // Monday
            }
            break;
        case 2: {
                $day = 'Selasa'; //Tuesday
            }
            break;
        case 3: {
                $day = 'Rabu'; //Wednesday
            }
            break;
        case 4: {
                $day = 'Kamis'; //Thursday
            }
            break;
        case 5: {
                $day = "Jum'at"; //Friday
            }
            break;
        case 6: {
                $day = 'Sabtu';  //Saturday
            }
            break;
        default: {
                $day = 'UnKnown';
            }
            break;
    }

    return $day;
}
