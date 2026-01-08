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
$work_shift_detail_exists = $CI->db->table_exists(db_prefix() . 'work_shift_detail');
$shift_type_exists = $CI->db->table_exists(db_prefix() . 'shift_type');
$check_in_out_exists = $CI->db->table_exists(db_prefix() . 'check_in_out');

// Get filters
$filters = [
    'date'     => $CI->input->post('date_filter'),
    'staff_id' => $CI->input->post('staff_filter'),
    'status'   => $CI->input->post('status_filter'),
];

// Determine the date to use for shift lookup
$lookup_date = !empty($filters['date']) ? $filters['date'] : date('Y-m-d');

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

// Build JOINs
$join = [];

// Join attendance table if exists
if ($attendance_table_exists) {
    $join_condition = 'a.staff_id = s.staffid';
    if (!empty($filters['date'])) {
        $join_condition .= ' AND a.date = "' . $CI->db->escape_str($filters['date']) . '"';
    }
    $join[] = 'LEFT JOIN ' . db_prefix() . 'kpi_attendance a ON ' . $join_condition;
}

// Join work_shift_detail to get shift information
if ($work_shift_detail_exists) {
    $shift_join_condition = 'wsd.staff_id = s.staffid AND wsd.date = "' . $CI->db->escape_str($lookup_date) . '"';
    $join[] = 'LEFT JOIN ' . db_prefix() . 'work_shift_detail wsd ON ' . $shift_join_condition;
    
    // Join shift_type to get shift start time
    if ($shift_type_exists) {
        $join[] = 'LEFT JOIN ' . db_prefix() . 'shift_type st ON st.id = wsd.shift_id';
    }
}

// Join check_in_out to get check-in time (type_check = 1) - only the first/earliest check-in
if ($check_in_out_exists) {
    // Use a derived table to get only the earliest check-in time for each staff on the date
    // This handles cases where staff has multiple check-ins on the same day
    $checkin_join_condition = 'cio_first.staff_id = s.staffid';
    $join[] = 'LEFT JOIN (
        SELECT staff_id, MIN(date) as first_checkin_date
        FROM ' . db_prefix() . 'check_in_out 
        WHERE DATE(date) = "' . $CI->db->escape_str($lookup_date) . '" 
        AND type_check = 1
        GROUP BY staff_id
    ) cio_min ON cio_min.staff_id = s.staffid';
    $join[] = 'LEFT JOIN ' . db_prefix() . 'check_in_out cio ON cio.staff_id = cio_min.staff_id 
        AND cio.date = cio_min.first_checkin_date 
        AND cio.type_check = 1';
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
    // Add shift information to additional select
    if ($work_shift_detail_exists) {
        $additionalSelect[] = 'wsd.shift_id as shift_id';
        if ($shift_type_exists) {
            $additionalSelect[] = 'st.time_start_work as shift_start_time';
        }
    }
    // Add check-in time from check_in_out table
    if ($check_in_out_exists) {
        $additionalSelect[] = 'cio.date as check_in_datetime';
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

            // Shift Time - check work_shift_detail first, then fallback to attendance shift_time
            $shift_time = '-';
            
            if ($work_shift_detail_exists) {
                // Check if staff has a shift record for this date
                // shift_id will be NULL if no record exists in work_shift_detail (due to LEFT JOIN)
                if (isset($aRow['shift_id']) && $aRow['shift_id'] !== null && $aRow['shift_id'] !== '') {
                    // Staff has a shift, get the start time from shift_type
                    if ($shift_type_exists && !empty($aRow['shift_start_time'])) {
                        $shift_time = $aRow['shift_start_time'];
                    } else {
                        // Shift exists but no start time found in shift_type
                        $shift_time = '-';
                    }
                } else {
                    // No shift record found in work_shift_detail for this staff and date
                    $shift_time = 'NS';
                }
            } else {
                // If work_shift_detail table doesn't exist, use attendance shift_time
                $shift_time = !empty($aRow['shift_time']) ? $aRow['shift_time'] : '-';
            }
            
            $row[] = $shift_time;

            // Check-in Time - fetch from check_in_out table first, then fallback to attendance
            $check_in_time = '-';
            
            if ($check_in_out_exists && !empty($aRow['check_in_datetime'])) {
                // Get time from check_in_out table (date field contains datetime)
                $check_in_time = date('H:i', strtotime($aRow['check_in_datetime']));
            } elseif (!empty($aRow['check_in_time'])) {
                // Fallback to attendance table check_in_time if available
                $check_in_time = $aRow['check_in_time'];
            }
            
            $row[] = $check_in_time;

            // Points - calculate dynamically based on shift time and check-in time
            $points = 0;
            $date_for_points = '';
            
            // Determine the date to use for points calculation (must be Y-m-d format)
            if (!empty($aRow['attendance_date'])) {
                $date_for_points = is_numeric(strtotime($aRow['attendance_date'])) ? date('Y-m-d', strtotime($aRow['attendance_date'])) : $aRow['attendance_date'];
            } elseif (!empty($filters['date'])) {
                $date_for_points = is_numeric(strtotime($filters['date'])) ? date('Y-m-d', strtotime($filters['date'])) : $filters['date'];
            } else {
                $date_for_points = date('Y-m-d');
            }
            
            // Calculate points based on shift and check-in status
            if ($shift_time == 'NS') {
                // No shift assigned = 0 points (can't calculate without a shift)
                $points = 0;
            } elseif ($shift_time != '-' && $check_in_time != '-' && $check_in_time != 'NS') {
                // Has shift and check-in = calculate points based on timing
                if (method_exists($CI->kpi_model, 'calculate_points')) {
                    $points = $CI->kpi_model->calculate_points($shift_time, $check_in_time, $date_for_points);
                }
            } elseif ($shift_time != '-' && ($check_in_time == '-' || $check_in_time == 'NS')) {
                // Has shift but no check-in = absent = -6 points (only if date is valid)
                if (method_exists($CI->kpi_model, 'calculate_points')) {
                    $points = $CI->kpi_model->calculate_points($shift_time, '-', $date_for_points);
                } else {
                    // Fallback: only apply -6 if date is 2026 or later and not future
                    $date_year = (int)date('Y', strtotime($date_for_points));
                    $current_date = date('Y-m-d');
                    if ($date_year >= 2026 && $date_for_points <= $current_date) {
                        $points = -6;
                    }
                }
            } else {
                // No shift or invalid data = 0 points
                $points = 0;
            }
            
            // Format points display
            $points_display = number_format($points, 0);
            if ($points > 0) {
                $points_display = '+' . $points_display;
            }
            $row[] = $points_display;

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
            
            // Shift Time - check work_shift_detail
            $shift_time = '-';
            if ($work_shift_detail_exists) {
                // Check if staff has a shift record for this date
                if (isset($aRow['shift_id']) && $aRow['shift_id'] !== null && $aRow['shift_id'] !== '') {
                    // Staff has a shift, get the start time from shift_type
                    if ($shift_type_exists && !empty($aRow['shift_start_time'])) {
                        $shift_time = $aRow['shift_start_time'];
                    } else {
                        $shift_time = '-';
                    }
                } else {
                    // No shift record found, show "NS"
                    $shift_time = 'NS';
                }
            }
            $row[] = $shift_time;
            
            // Check-in Time - fetch from check_in_out table
            $check_in_time = '-';
            if ($check_in_out_exists && !empty($aRow['check_in_datetime'])) {
                // Get time from check_in_out table (date field contains datetime)
                $check_in_time = date('H:i', strtotime($aRow['check_in_datetime']));
            }
            $row[] = $check_in_time;
            
            // Points - calculate dynamically
            $points = 0;
            $date_for_points = '';
            
            // Determine the date to use for points calculation (must be Y-m-d format)
            if (!empty($filters['date'])) {
                $date_for_points = is_numeric(strtotime($filters['date'])) ? date('Y-m-d', strtotime($filters['date'])) : $filters['date'];
            } else {
                $date_for_points = date('Y-m-d');
            }
            
            // Calculate points based on shift and check-in status
            if ($shift_time == 'NS') {
                // No shift assigned = 0 points (can't calculate without a shift)
                $points = 0;
            } elseif ($shift_time != '-' && $check_in_time != '-' && $check_in_time != 'NS') {
                // Has shift and check-in = calculate points based on timing
                if (method_exists($CI->kpi_model, 'calculate_points')) {
                    $points = $CI->kpi_model->calculate_points($shift_time, $check_in_time, $date_for_points);
                }
            } elseif ($shift_time != '-' && ($check_in_time == '-' || $check_in_time == 'NS')) {
                // Has shift but no check-in = absent = -6 points (only if date is valid)
                if (method_exists($CI->kpi_model, 'calculate_points')) {
                    $points = $CI->kpi_model->calculate_points($shift_time, '-', $date_for_points);
                } else {
                    // Fallback: only apply -6 if date is 2026 or later and not future
                    $date_year = (int)date('Y', strtotime($date_for_points));
                    $current_date = date('Y-m-d');
                    if ($date_year >= 2026 && $date_for_points <= $current_date) {
                        $points = -6;
                    }
                }
            } else {
                // No shift or invalid data = 0 points
                $points = 0;
            }
            
            // Format points display
            $points_display = number_format($points, 0);
            if ($points > 0) {
                $points_display = '+' . $points_display;
            }
            $row[] = $points_display;
            
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
