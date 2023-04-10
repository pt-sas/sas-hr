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
    return date('d' . $separator . 'm' . $separator . 'Y', strtotime($date));
}
