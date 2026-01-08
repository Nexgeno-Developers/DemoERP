<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('kpi/kpi_model');

// Check permissions
$has_view_permission = has_permission('kpi_attendance', '', 'view');
$has_view_own_permission = has_permission('kpi_attendance', '', 'view_own');

if (!$has_view_permission && !$has_view_own_permission) {
    ajax_access_denied();
}

// Define columns - Name will be conditionally included
$aColumns = [];

// Add Name column only if user has view permission
if ($has_view_permission) {
    $aColumns[] = 'CONCAT(s.firstname, " ", s.lastname) as staff_name';
}

$aColumns[] = 'a.date as attendance_date';
$aColumns[] = 'a.shift_time';
$aColumns[] = 'a.check_in_time';
$aColumns[] = 'a.points';
$aColumns[] = 'a.status';

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'kpi_attendance a';
$join         = [
    'LEFT JOIN ' . db_prefix() . 'staff s ON s.staffid = a.staff_id AND s.active = 1',
];

// Get filters
$filters = [
    'date'     => $CI->input->post('date_filter'),
    'staff_id' => $CI->input->post('staff_filter'),
    'status'   => $CI->input->post('status_filter'),
];

// Build where clause using model method
$where = $CI->kpi_model->build_attendance_where_clause($has_view_permission, $has_view_own_permission, $filters);

// For demo purposes, if table doesn't exist, return empty data
if (!$CI->db->table_exists(db_prefix() . 'kpi_attendance')) {
    $output = [
        'aaData' => [],
        'iTotalRecords' => 0,
        'iTotalDisplayRecords' => 0,
    ];
    echo json_encode($output);
    exit;
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['a.id as id', 'a.staff_id as staff_id']);

$output  = $result['output'];
$rResult = $result['rResult'];

if (!isset($output['aaData'])) {
    $output['aaData'] = [];
}

foreach ($rResult as $aRow) {
    $row = [];

    // Name column - only add if user has view permission
    if ($has_view_permission) {
        $staff_name = !empty($aRow['staff_name']) ? trim($aRow['staff_name']) : '-';
        $staff_id = !empty($aRow['staff_id']) ? $aRow['staff_id'] : null;
        $row[] = $CI->kpi_model->format_staff_name($staff_name, $staff_id);
    }

    // Date
    $date = !empty($aRow['attendance_date']) ? _d($aRow['attendance_date']) : '-';
    $row[] = $date;

    // Shift Time
    $shift_time = !empty($aRow['shift_time']) ? $aRow['shift_time'] : '-';
    $row[] = $shift_time;

    // Check-in Time
    $check_in_time = !empty($aRow['check_in_time']) ? $aRow['check_in_time'] : '-';
    $row[] = $check_in_time;

    // Points
    $points = !empty($aRow['points']) ? number_format($aRow['points'], 0) : '0';
    $row[] = $points;

    // Status - use model method to format badge
    $status = !empty($aRow['status']) ? $aRow['status'] : '-';
    $row[] = $CI->kpi_model->format_status_badge($status);

    $output['aaData'][] = $row;
}

echo json_encode($output);

