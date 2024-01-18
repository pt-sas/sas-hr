<?php

function message($param, $value, $message)
{
    if (strtolower($param) == 'success') {
        return [
            [
                strtolower($param)      => $value,
                'message'               => $message
            ]
        ];
    } else if (strtolower($param) == 'error') {
        return [
            [
                strtolower($param)      => $value,
                'message'               => $message
            ]
        ];
    } else {
        return [
            [
                'parameter'             => $param,
                'message'               => 'Undefined message'
            ]
        ];
    }
}

function active($string)
{
    return $string === 'Y' ? '<center><span class="badge badge-success">Yes</span></center>' :
        '<center><span class="badge badge-danger">No</span></center>';
}

function gender(string $str)
{
    if ($str === 'L') {
        $msg = '<span>Laki-Laki</span>';
    } else if ($str === 'P') {
        $msg = '<span>Perempuan</span>';
    } else {
        $msg = '<span>Semua</span>';
    }

    return $msg;
}

function durationtype(string $str)
{
    if ($str === 'D') {
        $msg = '<span>Hari</span>';
    } else {
        $msg = '<span>Bulan</span>';
    }

    return $msg;
}

function formatyesno(string $str)
{
    if ($str === 'Y') {
        $msg = '<span>Ya</span>';
    } else {
        $msg = '<span>Tidak</span>';
    }

    return $msg;
}

function truncate($string, $length = 50, $append = "...")
{
    $string = trim($string);

    if (strlen($string) > $length) {
        $string = wordwrap($string, $length);
        $string = explode("\n", $string, 2);
        $string = $string[0] . $append;
    }

    return $string;
}

function setCheckbox($string)
{
    return $string ? 'Y' : 'N';
}

/**
 * To set and get length data from table line
 *
 * @param [type] $table
 * @return void
 */
function countLine($count)
{
    return count($count) == 0 ? "" : $count;
}

/**
 * Remove special character on the format rupiah
 *
 * @param [type] $rupiah
 * @return int
 */
function replaceFormat(string $rupiah)
{
    return preg_replace("/\./", "", $rupiah);
}

/**
 * Convert format number to rupiah
 *
 * @param [type] $numeric
 * @return float
 */
function formatRupiah(int $numeric)
{
    return number_format($numeric, 0, '', ',');
}

/**
 * Populate array table
 *
 * @param array $table
 * @return array
 */
function arrTableLine(array $table, string $str = null)
{
    $result = [];

    if (empty($str))
        $str = "line";

    foreach ($table as $value) :
        foreach ($value as $key => $val) :
            $row = [];
            $row[$key . '_' . $str] = $val;

            $result[] = $row;
        endforeach;
    endforeach;

    return $result;
}

function array_duplicates(array $array)
{
    return array_diff_assoc($array, array_unique($array));
}

function notification($param)
{
    $msg = '';

    if (strtolower($param) == 'insert')
        $msg = 'Your data has been inserted successfully !';
    else
        $msg = 'Your data has been updated successfully !';

    return $msg;
}

/**
 * Return badge info document status table
 *
 * @param string $str
 * @return void
 */
function docStatus(string $str, string $type = null, ?int $total = 0, ?int $available = 0)
{
    if ($str === "IP" && (is_null($type) || strtoupper($type) !== "TERIMA")) {
        $msg = '<center><span class="badge badge-info">In Progress</span></center>';
    } else if ($str === "VO") {
        $msg = '<center><span class="badge badge-secondary">Voided</span></center>';
    } else if ($str === "IN") {
        $msg = '<center><span class="badge badge-danger">Invalid</span></center>';
    } else if ($str === "AP") {
        $msg = '<center><span class="badge badge-info">Approved</span></center>';
    } else if ($str === "NA") {
        $msg = '<center><span class="badge badge-black">Not Approved</span></center>';
    } else if ($str === "DR") {
        $msg = '<center><span class="badge badge-warning">Drafted</span></center>';
    } else {
        if (strtoupper($type) === "TERIMA") {
            if ($total == 0 && $available == 0)
                return 0;

            $calculation = ($available / $total) * 100;

            if ($calculation == 100) {
                $msg = '<center><span class="badge badge-success">Completed</span></center>';
            } else {
                $detail = $calculation > 50 ? "$available from $total</div>" : "<span>$available from $total</span></div>";

                $msg = '<center><div class="progress progress-lg">
                        <div class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: ' . $calculation . '%" aria-valuenow="' . $available . '" aria-valuemin="0" aria-valuemax="' . $total . '">
                        ' . $detail . '
                    </div></center>';
            }
        } else {
            $msg = '<center><span class="badge badge-success">Completed</span></center>';
        }
    }

    return $msg;
}

function addYear($date, string $value)
{
    return strtotime("+" . $value . " years", strtotime($date));
}

/**
 *  Array sum based on data
 *
 * @param string $field Column data
 * @param array $data Data
 * @return void
 */
function arrSumField(string $field, array $data)
{
    $arr = [];

    foreach ($data as $value) :
        $arr[] = $value->{$field};
    endforeach;

    return array_sum($arr);
}

/**
 * Associative Array sort by
 *
 * @return void
 */
function array_orderby()
{
    $args = func_get_args();
    $data = array_shift($args);
    foreach ($args as $n => $field) {
        if (is_string($field)) {
            $tmp = array();
            foreach ($data as $key => $row)
                $tmp[$key] = $row[$field];
            $args[$n] = $tmp;
        }
    }
    $args[] = &$data;
    call_user_func_array('array_multisort', $args);
    return array_pop($args);
}

/**
 * Remove string in the bracket
 *
 * @param [type] $rupiah
 * @return int
 */
function replaceStrBracket(string $str)
{
    return trim(preg_replace("[\(.*?\)]", "", $str));
}

/**
 * Function to get date list from range date
 *
 * @param [type] $start
 * @param [type] $end
 * @param string $format
 * @return array
 */
function getDatesFromRange($start, $end, $format = 'Y-m-d H:i:s'): array
{
    $array = [];

    // Variable that store the date interval 
    // of period 1 day 
    $interval = new DateInterval('P1D');

    $realEnd = new DateTime($end);
    $realEnd->add($interval);

    $period = new DatePeriod(new DateTime($start), $interval, $realEnd);

    foreach ($period as $date) {
        $array[] = $date->format($format);
    }

    return $array;
}
