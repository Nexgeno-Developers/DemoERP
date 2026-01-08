<?php
defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'CONCAT(co.firstname, " ", co.lastname) as contact_name',
    'co.title as position',
    'co.email as email',
    'co.phonenumber as phonenumber',
    'anniversary_cf.value as anniversary_date',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'contacts co';
$join         = [
    'INNER JOIN ' . db_prefix() . 'clients c ON c.userid = co.userid',
    'INNER JOIN ' . db_prefix() . 'customfieldsvalues anniversary_cf ON anniversary_cf.relid = co.id AND anniversary_cf.fieldid = 14 AND anniversary_cf.fieldto = "contacts" AND anniversary_cf.value IS NOT NULL AND anniversary_cf.value != ""',
];
$where        = ['AND c.active = 1', 'AND co.active = 1'];

// Get filters
$CI = &get_instance();
$period = $CI->input->post('period_filter');
$date_from = $CI->input->post('date_from_filter');
$date_to = $CI->input->post('date_to_filter');

// Handle period filter
if (!empty($period)) {
    $today = date('Y-m-d');
    switch ($period) {
        case 'this_week':
        case 'next_week':
            // Week filters are handled by date_from and date_to, so we'll let the custom date range handle it
            // But we need to ensure dates are set, so this case falls through to custom date range
            break;
        case 'this_month':
            $month = date('m');
            $where[] = 'AND MONTH(CAST(anniversary_cf.value AS DATE)) = ' . (int)$month;
            break;
        case 'last_month':
            $last_month = date('m', strtotime('first day of last month'));
            $where[] = 'AND MONTH(CAST(anniversary_cf.value AS DATE)) = ' . (int)$last_month;
            break;
        case 'next_month':
            $next_month = date('m', strtotime('first day of next month'));
            $where[] = 'AND MONTH(CAST(anniversary_cf.value AS DATE)) = ' . (int)$next_month;
            break;
    }
}

// Handle custom date range filter
if (!empty($date_from) && !empty($date_to)) {
    // Extract month and day from date range
    $from_month = date('m', strtotime($date_from));
    $from_day = date('d', strtotime($date_from));
    $to_month = date('m', strtotime($date_to));
    $to_day = date('d', strtotime($date_to));
    
    // Build condition to match month/day within range (ignoring year)
    if ($from_month == $to_month) {
        // Same month - filter by day range
        $where[] = 'AND MONTH(CAST(anniversary_cf.value AS DATE)) = ' . (int)$from_month . 
                   ' AND DAY(CAST(anniversary_cf.value AS DATE)) BETWEEN ' . (int)$from_day . ' AND ' . (int)$to_day;
    } else {
        // Different months - need to handle month/day range
        $where[] = 'AND (
            (MONTH(CAST(anniversary_cf.value AS DATE)) = ' . (int)$from_month . ' AND DAY(CAST(anniversary_cf.value AS DATE)) >= ' . (int)$from_day . ') OR
            (MONTH(CAST(anniversary_cf.value AS DATE)) = ' . (int)$to_month . ' AND DAY(CAST(anniversary_cf.value AS DATE)) <= ' . (int)$to_day . ') OR
            (MONTH(CAST(anniversary_cf.value AS DATE)) > ' . (int)$from_month . ' AND MONTH(CAST(anniversary_cf.value AS DATE)) < ' . (int)$to_month . ')
        )';
    }
} elseif (!empty($date_from)) {
    $from_month = date('m', strtotime($date_from));
    $from_day = date('d', strtotime($date_from));
    $where[] = 'AND (
        MONTH(CAST(anniversary_cf.value AS DATE)) > ' . (int)$from_month . ' OR
        (MONTH(CAST(anniversary_cf.value AS DATE)) = ' . (int)$from_month . ' AND DAY(CAST(anniversary_cf.value AS DATE)) >= ' . (int)$from_day . ')
    )';
} elseif (!empty($date_to)) {
    $to_month = date('m', strtotime($date_to));
    $to_day = date('d', strtotime($date_to));
    $where[] = 'AND (
        MONTH(CAST(anniversary_cf.value AS DATE)) < ' . (int)$to_month . ' OR
        (MONTH(CAST(anniversary_cf.value AS DATE)) = ' . (int)$to_month . ' AND DAY(CAST(anniversary_cf.value AS DATE)) <= ' . (int)$to_day . ')
    )';
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['co.id as id', 'co.userid as userid']);

$output  = $result['output'];
$rResult = $result['rResult'];

if (!isset($output['aaData'])) {
    $output['aaData'] = [];
}

foreach ($rResult as $aRow) {
    $row = [];

    // Contact Name
    $contact_name = !empty($aRow['contact_name']) ? trim($aRow['contact_name']) : '-';
    if ($contact_name != '-' && !empty($aRow['userid'])) {
        $contact_name_link = '<a href="' . admin_url('clients/client/' . $aRow['userid'] . '?contactid=' . $aRow['id']) . '" class="tw-font-medium">' . $contact_name . '</a>';
        $row[] = $contact_name_link;
    } else {
        $row[] = $contact_name;
    }

    // Position
    $position = !empty($aRow['position']) ? $aRow['position'] : '-';
    $row[] = $position;

    // Email
    $email = !empty($aRow['email']) ? $aRow['email'] : '-';
    if ($email != '-') {
        $email_link = '<a href="mailto:' . $email . '">' . $email . '</a>';
        $row[] = $email_link;
    } else {
        $row[] = $email;
    }

    // Phone Number
    $phonenumber = !empty($aRow['phonenumber']) ? $aRow['phonenumber'] : '-';
    if ($phonenumber != '-') {
        $phone_link = '<a href="tel:' . $phonenumber . '">' . $phonenumber . '</a>';
        $row[] = $phone_link;
    } else {
        $row[] = $phonenumber;
    }

    // Anniversary Date
    $anniversary_date = !empty($aRow['anniversary_date']) ? $aRow['anniversary_date'] : '-';
    if ($anniversary_date != '-') {
        // Format as month-day (ignoring year for display)
        $date_parts = explode('-', $anniversary_date);
        if (count($date_parts) >= 2) {
            $anniversary_display = date('M d', strtotime($anniversary_date));
        } else {
            $anniversary_display = _d($anniversary_date);
        }
        $row[] = $anniversary_display;
    } else {
        $row[] = $anniversary_date;
    }

    $output['aaData'][] = $row;
}

if (!isset($output['aaData'])) {
    $output['aaData'] = [];
}
