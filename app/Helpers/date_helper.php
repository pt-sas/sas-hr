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
    $time = date('H:i', strtotime($date));
    $time = explode(':', $time);
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
                $day = "Jumat"; //Friday
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

function convertDay_idn($day)
{
    switch (ucfirst(strtolower($day))) {
        case 'Minggu': {
                $day = 0; //Sunday
            }
            break;
        case 'Senin': {
                $day = 1; // Monday
            }
            break;
        case 'Selasa': {
                $day = 2; //Tuesday
            }
            break;
        case 'Rabu': {
                $day = 3; //Wednesday
            }
            break;
        case 'Kamis': {
                $day = 4; //Thursday
            }
            break;
        case 'Jumat': {
                $day = 5; //Friday
            }
            break;
        case 'Sabtu': {
                $day = 6; //Saturday
            }
            break;
    }

    return $day;
}

function convertMinutesToHour($minutes)
{
    return floor($minutes / 60) . ':' . ($minutes -   floor($minutes / 60) * 60);
}
