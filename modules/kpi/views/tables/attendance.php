<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Capture any output that might break JSON
if (ob_get_level()) {
    ob_clean();
}

$CI = &get_instance();
$CI->load->model('kpi/kpi_model');

// Check permissions
$has_view_permission = has_permission('kpi_attendance', '', 'view');
$has_view_own_permission = has_permission('kpi_attendance', '', 'view_own');

if (!$has_view_permission && !$has_view_own_permission) {
    ajax_access_denied();
}

// Check if tables exist
if (!$CI->db->table_exists(db_prefix() . 'staff')) {
    $draw = $CI->input->post('draw') ? intval($CI->input->post('draw')) : 0;
    $output = [
        'draw' => $draw,
        'aaData' => [],
        'iTotalRecords' => 0,
        'iTotalDisplayRecords' => 0,
    ];
    header('Content-Type: application/json');
    echo json_encode($output);
    exit;
}

$attendance_table_exists = $CI->db->table_exists(db_prefix() . 'kpi_attendance');

// Get filters
$filters = [
    'date'     => $CI->input->post('date_filter'),
    'staff_id' => $CI->input->post('staff_filter'),
    'status'   => $CI->input->post('status_filter'),
];

// Define columns - always include name
$aColumns = ['CONCAT(s.firstname, " ", s.lastname) as staff_name'];

// Add attendance columns only if table exists
if ($attendance_table_exists) {
    $aColumns[] = 'a.date as attendance_date';
    $aColumns[] = 'a.shift_time';
    $aColumns[] = 'a.check_in_time';
    $aColumns[] = 'a.points';
    $aColumns[] = 'a.status';
} else {
    // Even if table doesn't exist, we still need date column for display
    // We'll handle this in the row processing
}

$sIndexColumn = 'staffid';
$sTable       = db_prefix() . 'staff s';

// Build JOIN - only if attendance table exists
$join = [];
if ($attendance_table_exists) {
    $join_condition = 'a.staff_id = s.staffid';
    if (!empty($filters['date'])) {
        $join_condition .= ' AND a.date = "' . $CI->db->escape_str($filters['date']) . '"';
    }
    $join[] = 'LEFT JOIN ' . db_prefix() . 'kpi_attendance a ON ' . $join_condition;
}

// Build where clause - start with active staff filter
$where = ['AND s.active = 1'];

// Apply permission-based filtering
if (!$has_view_permission && $has_view_own_permission) {
    $where[] = 'AND s.staffid = ' . get_staff_user_id();
}

// Apply staff filter (only if user has view permission)
if ($has_view_permission && !empty($filters['staff_id'])) {
    $where[] = 'AND s.staffid = ' . (int)$filters['staff_id'];
}

// Apply status filter - only if attendance table exists
if ($attendance_table_exists && !empty($filters['status'])) {
    if (strtolower($filters['status']) == 'absent') {
        // For absent, show staff with absent status OR no attendance record
        $where[] = 'AND (a.status = "' . $CI->db->escape_str($filters['status']) . '" OR a.status IS NULL)';
    } else {
        // For other statuses, only show staff with matching status
        $where[] = 'AND a.status = "' . $CI->db->escape_str($filters['status']) . '"';
    }
}

try {
    $additionalSelect = ['s.staffid as staff_id'];
    if ($attendance_table_exists) {
        $additionalSelect[] = 'a.id as attendance_id';
    }
    
    $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalSelect);
} catch (Throwable $e) {
    // Log error and return empty result (catch both Exception and Error)
    log_message('error', 'KPI Attendance Table Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
    $draw = $CI->input->post('draw') ? intval($CI->input->post('draw')) : 0;
    $output = [
        'draw' => $draw,
        'aaData' => [],
        'iTotalRecords' => 0,
        'iTotalDisplayRecords' => 0,
    ];
    header('Content-Type: application/json');
    echo json_encode($output);
    exit;
}

$output  = $result['output'];
$rResult = isset($result['rResult']) ? $result['rResult'] : [];

// Ensure rResult is an array
if (!is_array($rResult)) {
    $rResult = [];
}

if (!isset($output['aaData'])) {
    $output['aaData'] = [];
}

foreach ($rResult as $aRow) {
    try {
        $row = [];

        // Name column - always show since we're starting from staff table
        $staff_name = !empty($aRow['staff_name']) ? trim($aRow['staff_name']) : '-';
        $staff_id = !empty($aRow['staff_id']) ? $aRow['staff_id'] : null;
        
        // Safely format staff name
        if (method_exists($CI->kpi_model, 'format_staff_name')) {
            $row[] = $CI->kpi_model->format_staff_name($staff_name, $staff_id);
        } else {
            $row[] = html_escape($staff_name);
        }

        // Attendance columns - only if table exists
        if ($attendance_table_exists) {
            // Date - show attendance date if exists, otherwise show filter date or current date
            if (!empty($aRow['attendance_date'])) {
                $date = _d($aRow['attendance_date']);
            } else {
                // If no attendance record, show filter date or current date
                if (!empty($filters['date'])) {
                    $date = _d($filters['date']);
                } else {
                    $date = _d(date('Y-m-d'));
                }
            }
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
            if (method_exists($CI->kpi_model, 'format_status_badge')) {
                $row[] = $CI->kpi_model->format_status_badge($status);
            } else {
                $row[] = html_escape($status);
            }
        } else {
            // If attendance table doesn't exist, show current date or filter date
            if (!empty($filters['date'])) {
                $date = _d($filters['date']);
            } else {
                $date = _d(date('Y-m-d'));
            }
            $row[] = $date;
            $row[] = '-';
            $row[] = '-';
            $row[] = '-';
            $row[] = '-';
        }

        $output['aaData'][] = $row;
    } catch (Exception $e) {
        // Skip this row if there's an error formatting it
        log_message('error', 'KPI Attendance Table Row Error: ' . $e->getMessage());
        continue;
    }
}

// Ensure output has all required fields
if (!isset($output['draw'])) {
    $output['draw'] = $CI->input->post('draw') ? intval($CI->input->post('draw')) : 0;
}

// Ensure all required DataTables fields are present
if (!isset($output['iTotalRecords'])) {
    $output['iTotalRecords'] = 0;
}
if (!isset($output['iTotalDisplayRecords'])) {
    $output['iTotalDisplayRecords'] = 0;
}
if (!isset($output['aaData'])) {
    $output['aaData'] = [];
}

// Ensure no output has been sent
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

// Clean any output buffer before JSON
if (ob_get_level()) {
    ob_end_clean();
}

$json_output = json_encode($output, JSON_UNESCAPED_UNICODE);

// Check for JSON encoding errors
if ($json_output === false) {
    log_message('error', 'KPI Attendance Table JSON Error: ' . json_last_error_msg() . ' | Error Code: ' . json_last_error());
    $output = [
        'draw' => $CI->input->post('draw') ? intval($CI->input->post('draw')) : 0,
        'aaData' => [],
        'iTotalRecords' => 0,
        'iTotalDisplayRecords' => 0,
    ];
    $json_output = json_encode($output, JSON_UNESCAPED_UNICODE);
}

echo $json_output;
exit;
