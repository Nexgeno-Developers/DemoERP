<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kpi extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        if (!has_permission('kpi', '', 'view_own') && 
            !has_permission('kpi', '', 'view') && 
            !is_admin()) {
            access_denied('KPI');
        }
        
        $this->load->model('kpi_model');
    }

    public function index()
    {
        $data['is_admin'] = is_admin();
        $data['can_view_global'] = has_permission('kpi', '', 'view') || is_admin();
        
        if ($data['is_admin'] || $data['can_view_global']) {
            $data['title'] = 'Attendance KPI - All Staff';
            $data['staff_members'] = $this->staff_model->get();
            $data['show_full_filters'] = true;
        } else {
            $staff_name = get_staff_full_name(get_staff_user_id());
            $data['title'] = 'My Attendance - ' . $staff_name;
            $data['show_full_filters'] = false;
        }
        
        $data['months'] = [];
        for ($i = 0; $i < 12; $i++) {
            $timestamp = strtotime("-$i months");
            $data['months'][] = [
                'id' => date('Y-m', $timestamp),
                'name' => date('F Y', $timestamp)
            ];
        }

        $this->load->view('kpi/attendance/manage', $data);
    }

 public function table()
{
    // Set proper JSON content type header
    header('Content-Type: application/json');
    
    // Start output buffering to prevent any accidental output
    ob_start();
    
    try {
        if (!is_staff_logged_in()) {
            ob_clean();
            echo json_encode([
                'draw' => intval($this->input->post('draw') ?: $this->input->get('draw') ?: 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Access denied'
            ]);
            ob_end_flush();
            return;
        }

        if (!has_permission('kpi', '', 'view_own') && 
            !has_permission('kpi', '', 'view') && 
            !is_admin()) {
            ob_clean();
            echo json_encode([
                'draw' => intval($this->input->post('draw') ?: $this->input->get('draw') ?: 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Access denied'
            ]);
            ob_end_flush();
            return;
        }

        // DataTables sends parameters via POST, but we check both POST and GET for flexibility
        $view = $this->input->post('view') ?: $this->input->get('view') ?: 'daily';
        $month = $this->input->post('month') ?: $this->input->get('month') ?: '';
        $staff_id = $this->input->post('staff_id') ?: $this->input->get('staff_id') ?: '';
        $from_date = $this->input->post('from_date') ?: $this->input->get('from_date') ?: '';
        $to_date = $this->input->post('to_date') ?: $this->input->get('to_date') ?: '';
        $status_filter = $this->input->post('status_filter') ?: $this->input->get('status_filter') ?: '';
        
        // Properly handle DataTables pagination parameters
        $start = (int)($this->input->post('start') ?: $this->input->get('start') ?: 0);
        $length_input = $this->input->post('length') ?: $this->input->get('length');
        $length = $length_input !== null && $length_input !== '' ? (int)$length_input : 10;
        $search_value = $this->input->post('search')['value'] ?? $this->input->get('search')['value'] ?? '';

        // Permission logic:
        // - Admin OR has 'view' permission → can view all staff data
        // - Has 'view_own' permission → can only view their own data
        $can_view_global = has_permission('kpi', '', 'view') || is_admin();
        
        if (!$can_view_global) {
            // User doesn't have global view permission
            // If they have 'view_own' permission, show only their data
            if (has_permission('kpi', '', 'view_own')) {
                $staff_id = get_staff_user_id();
            } else {
                // No permission at all
                ob_clean();
                echo json_encode([
                    'draw' => intval($this->input->post('draw') ?: $this->input->get('draw') ?: 1),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Access denied'
                ]);
                ob_end_flush();
                return;
            }
        }

        // Auto-detect view type based on filters
        if (empty($staff_id) && $view === 'daily') {
            // If all staff selected, force monthly view
            $view = 'monthly';
        }

        // Get data from model
        $data = $this->kpi_model->get_datatable_data([
            'view' => $view,
            'month' => $month,
            'staff_id' => $staff_id,
            'from_date' => $from_date,
            'to_date' => $to_date,
            'status_filter' => $status_filter,
            'start' => $start,
            'length' => $length,
            'search' => $search_value,
            'can_view_global' => $can_view_global
        ]);

        // Get counts
        $total_records = $this->kpi_model->count_all_records([
            'view' => $view,
            'month' => $month,
            'staff_id' => $staff_id,
            'from_date' => $from_date,
            'to_date' => $to_date,
            'status_filter' => $status_filter,
            'can_view_global' => $can_view_global
        ]);

        $filtered_records = $this->kpi_model->count_filtered_records([
            'view' => $view,
            'month' => $month,
            'staff_id' => $staff_id,
            'from_date' => $from_date,
            'to_date' => $to_date,
            'status_filter' => $status_filter,
            'search' => $search_value,
            'can_view_global' => $can_view_global
        ]);

        // Get draw parameter (DataTables sends via POST)
        $draw = intval($this->input->post('draw') ?: $this->input->get('draw') ?: 1);
        
        // Handle empty data
        if (empty($data)) {
            $output = [
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ];
            ob_clean();
            echo json_encode($output);
            ob_end_flush();
            return;
        }

        $output = [
            'draw' => $draw,
            'recordsTotal' => $total_records,
            'recordsFiltered' => $filtered_records,
            'data' => [],
        ];

        foreach ($data as $row) {
            $table_row = [];

            // Show staff name if user can view all staff (admin or has 'view' permission)
            if ($can_view_global) {
                $table_row[] = $row['staff_name'];
            }

            $table_row[] = _d($row['date']);                   // DATE
            $table_row[] = $row['shift_start'] ?: 'NS';        // SHIFT
            $table_row[] = $row['check_in'] ?: '--';           // CHECK-IN
            $table_row[] = '<span class="bold">'.$row['points'].'</span>'; // POINTS

            // STATUS BADGE
            $status_map = [
                'early'    => ['label' => 'Early',    'class' => 'info'],
                'on-time'  => ['label' => 'On Time',  'class' => 'success'],
                'late'     => ['label' => 'Late',     'class' => 'warning'],
                'absent'   => ['label' => 'Absent',   'class' => 'danger'],
                'summary'  => ['label' => 'Summary',  'class' => 'info'],
            ];

            $status = isset($status_map[$row['status']]) ? $status_map[$row['status']]['label'] : 'Unknown';
            $badge_class = isset($status_map[$row['status']]) ? $status_map[$row['status']]['class'] : 'default';

            $table_row[] = '<span class="badge badge-'.$badge_class.'">'.$status.'</span>';

            $output['data'][] = $table_row;
        }

        // Clear any output buffer and send JSON
        ob_clean();
        echo json_encode($output);
        
    } catch (Exception $e) {
        // Clear output buffer and return error JSON
        ob_clean();
        $output = [
            'draw' => intval($this->input->post('draw') ?: $this->input->get('draw') ?: 1),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'An error occurred while loading data.'
        ];
        echo json_encode($output);
    }
    
    // End output buffering
    ob_end_flush();
}
    public function get_monthly_average_points()
{
    if (!is_staff_logged_in()) {
        echo json_encode(['success' => false, 'average_points' => 0]);
        return;
    }

    $month = $this->input->get('month') ?: date('Y-m');
    $staff_id = get_staff_user_id();

    $monthly_data = $this->kpi_model->get_datatable_data([
        'view' => 'monthly',
        'month' => $month,
        'staff_id' => $staff_id
    ]);

    $average_points = 0;
    
    if (!empty($monthly_data)) {
        $average_points = $monthly_data[0]['points'];
    }

    echo json_encode([
        'success' => true,
        'average_points' => $average_points,
        'month' => $month
    ]);
}
    public function staff_attendance()
    {
        redirect(admin_url('kpi'));
    }

    /**
     * Get staff list for absent or on leave (AJAX)
     * @return json
     */
    public function get_staff_list()
    {
        if (!is_staff_logged_in()) {
            echo json_encode(['success' => false, 'message' => 'Access denied', 'staff' => []]);
            return;
        }

        // Check if user has role 7 or is admin
        $current_staff = get_staff();
        $has_access = false;
        if ($current_staff) {
            if (isset($current_staff->role) && $current_staff->role == 7) {
                $has_access = true;
            }
            if (is_admin()) {
                $has_access = true;
            }
        }
        if (!$has_access) {
            echo json_encode(['success' => false, 'message' => 'Access denied', 'staff' => []]);
            return;
        }

        $type = $this->input->get('type'); // 'absent' or 'on_leave'

        if ($type === 'absent') {
            $staff_list = $this->kpi_model->get_absent_staff_list();
            echo json_encode([
                'success' => true,
                'type' => 'absent',
                'title' => 'Absent Staff',
                'staff' => $staff_list
            ]);
        } elseif ($type === 'on_leave') {
            $staff_list = $this->kpi_model->get_on_leave_staff_list();
            echo json_encode([
                'success' => true,
                'type' => 'on_leave',
                'title' => 'Staff On Leave',
                'staff' => $staff_list
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid type', 'staff' => []]);
        }
    }
}