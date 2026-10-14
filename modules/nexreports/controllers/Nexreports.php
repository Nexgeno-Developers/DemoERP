<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Nexreports Controller
 * Project Reports Module for Perfex CRM
 */
class Nexreports extends AdminController
{
    public function project_reports()
{
    redirect(admin_url('nexreports/projects'), 'location', 301);
}

    public function __construct()
{
    parent::__construct();
    
    // Load module model with full path
    $this->load->model('nexreports/nexreports_model');
    
    // Load Perfex core models
    $this->load->model('projects_model');
    $this->load->model('staff_model');
    $this->load->model('clients_model');
    
    // Load hierarchy helper
    require_once(module_dir_path('nexreports') . 'helpers/nexreports_hierarchy_helper.php');
    
    log_message('debug', 'NexReports: All models loaded successfully');
}

    /**
     * Default route - redirect to project reports
     */
    public function index()
    {
    redirect(admin_url('nexreports/projects'));  // Ã¢â€ Â Direct redirect
    }

    /**
     * Main Project Reports Page
     */
    public function projects()
{
    // Check permissions - use projects_overview permission, fallback to projects permission
    $has_permission = false;
    if (is_admin()) {
        $has_permission = true;
    } elseif (has_permission('projects_overview', '', 'view') || has_permission('projects_overview', '', 'view_own')) {
        $has_permission = true;
    } elseif (has_permission('projects', '', 'view')) {
        // Fallback to projects permission for backward compatibility
        $has_permission = true;
    }
    
    if (!$has_permission) {
        access_denied('Projects Overview');
    }

    // Get accessible project IDs based on role and hierarchy
    $accessible_project_ids = '';
    if (function_exists('nexreports_projects_get_accessible_project_ids')) {
        try {
            $accessible_project_ids = nexreports_projects_get_accessible_project_ids('projects_overview');
            log_message('debug', 'NexReports: Projects page - accessible project IDs: ' . (is_array($accessible_project_ids) ? implode(',', $accessible_project_ids) : $accessible_project_ids));
        } catch (Exception $e) {
            log_message('error', 'NexReports: Error getting accessible projects - ' . $e->getMessage());
            // SECURITY: On error, default to restrictive access
            if (is_admin() || has_permission('projects_overview', '', 'view')) {
                $accessible_project_ids = ''; // All projects for admin/view permission
            } else {
                $accessible_project_ids = []; // No projects for restricted users on error
            }
        }
    } else {
        // Helper not loaded, use safe defaults
        if (is_admin() || has_permission('projects_overview', '', 'view')) {
            $accessible_project_ids = ''; // All projects for admin/view permission
        } else {
            $accessible_project_ids = []; // No projects for restricted users
        }
    }
    $data['accessible_project_ids'] = $accessible_project_ids;

    // Get unique customers (filtered by accessible projects)
    $data['customers'] = $this->nexreports_model->get_unique_customers($accessible_project_ids);

    // Get unique tags (filtered by accessible projects)
    $data['tags'] = $this->nexreports_model->get_unique_tags($accessible_project_ids);

    // Get staff members (filtered by hierarchy for staff dropdown)
    $accessible_staff_ids = [];
    if (function_exists('nexreports_hrms_get_accessible_staff_ids')) {
        try {
            $accessible_staff_ids = nexreports_hrms_get_accessible_staff_ids('projects_overview');
        } catch (Exception $e) {
            log_message('error', 'NexReports: Error getting accessible staff - ' . $e->getMessage());
            $accessible_staff_ids = [];
        }
    }
    
    if (empty($accessible_staff_ids)) {
        $data['staff_members'] = $this->staff_model->get('', ['active' => 1]);
    } else {
        $this->db->where_in('staffid', array_map('intval', $accessible_staff_ids));
        $this->db->where('active', 1);
        $result = $this->db->get(db_prefix() . 'staff');
        $data['staff_members'] = $result ? $result->result_array() : [];
    }

    // Project statuses
    $data['project_statuses'] = [
        0 => _l('project_status_1'),
        1 => _l('project_status_2'),
        2 => _l('project_status_3'),
        3 => _l('project_status_4'),
        4 => _l('project_status_5'),
    ];

    // Date types
    $data['date_types'] = [
        'start_date'    => _l('project_start_date'),
        'deadline'      => _l('project_deadline'),
        'completion_date' => 'Completion date'
    ];

    // All projects for dropdown (filtered by accessible projects)
    $data['all_projects'] = $this->nexreports_model->get_all_project_names($accessible_project_ids);

    // Get custom fields for projects
    $data['custom_fields'] = $this->nexreports_model->get_project_custom_fields_for_filters();

    // Set page title
    $data['title'] = 'Projects Overview'; 
    
    // Load the view
    $this->load->view('nexreports/projects/index', $data);
}


    /**
     * Fetch Projects for DataTable (AJAX)
     */
 public function table()
{
    // Security check - use projects_overview permission, fallback to projects
    $has_permission = false;
    if (is_admin()) {
        $has_permission = true;
    } elseif (has_permission('projects_overview', '', 'view') || has_permission('projects_overview', '', 'view_own')) {
        $has_permission = true;
    } elseif (has_permission('projects', '', 'view')) {
        $has_permission = true;
    }
    
    if (!$has_permission) {
        ajax_access_denied();
    }
    
    // Get accessible project IDs
    $accessible_project_ids = '';
    if (function_exists('nexreports_projects_get_accessible_project_ids')) {
        try {
            $accessible_project_ids = nexreports_projects_get_accessible_project_ids('projects_overview');
            log_message('debug', 'NexReports: Got accessible project IDs: ' . (is_array($accessible_project_ids) ? implode(',', $accessible_project_ids) : $accessible_project_ids));
        } catch (Exception $e) {
            log_message('error', 'NexReports: Error getting accessible projects in table - ' . $e->getMessage());
            // SECURITY: On error, default to restrictive access
            if (is_admin() || has_permission('projects_overview', '', 'view')) {
                $accessible_project_ids = ''; // All projects for admin/view permission
            } else {
                $accessible_project_ids = []; // No projects for restricted users on error
            }
        }
    } else {
        // Helper not available - use safe defaults
        if (is_admin() || has_permission('projects_overview', '', 'view')) {
            $accessible_project_ids = ''; // All projects for admin/view permission
        } else {
            $accessible_project_ids = []; // No projects for restricted users
        }
    }

    // Get POST data
    $post_data = $this->input->post();
    
    // Debug log
    log_message('debug', '=== NexReports table() called ===');
    log_message('debug', 'POST data: ' . json_encode($post_data));
    
    // Sanitize filters
    $clean_filters = $this->sanitize_filters($post_data);
    
    log_message('debug', 'Clean filters: ' . json_encode($clean_filters));

    // DataTable parameters
    $start  = isset($post_data['start']) ? (int)$post_data['start'] : 0;
    $length = isset($post_data['length']) ? (int)$post_data['length'] : 10;
    $search_value = isset($post_data['search']['value']) ? $post_data['search']['value'] : '';

    // Get sorting parameters
    $order_column = isset($post_data['order'][0]['column']) ? (int)$post_data['order'][0]['column'] : 0;
    $order_dir = isset($post_data['order'][0]['dir']) ? $post_data['order'][0]['dir'] : 'asc';

    try {
        // Get all projects with filters and apply hierarchy filtering
        $all_projects = $this->nexreports_model->get_projects_for_export($clean_filters, $accessible_project_ids);
        
        log_message('debug', 'Total projects found: ' . count($all_projects));
        
        // SECURITY: Double-check that we have proper access control
        if (is_array($accessible_project_ids) && empty($accessible_project_ids)) {
            // User has no project access - ensure empty result
            $all_projects = [];
            log_message('debug', 'Security check: User has no project access - returning empty result');
        }
    } catch (Exception $e) {
        log_message('error', 'Error getting projects: ' . $e->getMessage());
        
        $response = [
            'draw' => (int)($post_data['draw'] ?? 1),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Error loading data: ' . $e->getMessage()
        ];
        
        $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($response));
        return;
    }

    // Apply search if provided
    $filtered_projects = [];
    if (!empty($search_value)) {
        foreach ($all_projects as $p) {
            if (
                stripos($p['name'], $search_value) !== false ||
                stripos($p['customer_name'], $search_value) !== false ||
                stripos($p['status_name'], $search_value) !== false ||
                stripos($p['tags'], $search_value) !== false
            ) {
                $filtered_projects[] = $p;
            }
        }
    } else {
        $filtered_projects = $all_projects;
    }

    // Get custom fields structure
    $custom_fields = $this->nexreports_model->get_project_custom_fields();
    $custom_fields_count = count($custom_fields);

    // Column mapping for DataTables sorting - WITH DYNAMIC CUSTOM FIELDS
    $column_map = [
        0 => 'id',                // Project ID
        1 => 'name',              // Project Name
        2 => 'customer_name',     // Customer
        3 => 'tags',              // Tags
        4 => 'start_date',        // Start Date
        5 => 'deadline',          // Deadline
        6 => 'members',           // Members
        7 => 'estimated_hours',   // Estimated Hours
        8 => 'logged_hours',      // Logged Hours
    ];

    // Add custom fields dynamically to column index 9+
    $cf_start = 9;
    foreach ($custom_fields as $i => $field) {
        $column_map[$cf_start + $i] = 'custom_field_' . $field['slug'];
    }

    // Add progress & status AFTER custom fields
    $progress_index = $cf_start + $custom_fields_count;
    $status_index   = $progress_index + 1;

    $column_map[$progress_index] = 'progress';
    $column_map[$status_index]   = 'status_name';

    // Apply sorting
    if (isset($column_map[$order_column])) {
        $sort_key = $column_map[$order_column];
        
        usort($filtered_projects, function ($a, $b) use ($sort_key, $order_dir) {
            $valA = $a[$sort_key] ?? '';
            $valB = $b[$sort_key] ?? '';
            
            try {
                // Handle dates
                if (in_array($sort_key, ['start_date', 'deadline'])) {
                    $timestampA = !empty($valA) ? strtotime($valA) : 0;
                    $timestampB = !empty($valB) ? strtotime($valB) : 0;
                    return ($order_dir === 'asc') ? $timestampA <=> $timestampB : $timestampB <=> $timestampA;
                }
                
                // Handle numbers
                if (in_array($sort_key, ['estimated_hours', 'logged_hours', 'progress', 'id'])) {
                    $valA = is_numeric($valA) ? (float)$valA : 0;
                    $valB = is_numeric($valB) ? (float)$valB : 0;
                    return ($order_dir === 'asc') ? $valA <=> $valB : $valB <=> $valA;
                }
                
                // Handle STATUS with custom mapping
                if ($sort_key === 'status_name') {
                    $status_order = [
                        'not started' => 0,
                        'in progress' => 1,
                        'on hold' => 2,
                        'cancelled' => 3,
                        'finished' => 4
                    ];
                    
                    $statusA = strtolower(trim($valA));
                    $statusB = strtolower(trim($valB));
                    
                    $orderA = $status_order[$statusA] ?? 999;
                    $orderB = $status_order[$statusB] ?? 999;
                    
                    return ($order_dir === 'asc') ? $orderA <=> $orderB : $orderB <=> $orderA;
                }
                
                // Handle MEMBERS (sort by first member's name)
                if ($sort_key === 'members') {
                    $memberA = '';
                    $memberB = '';
                    
                    if (is_array($valA) && !empty($valA)) {
                        $first = reset($valA);
                        if (is_array($first)) {
                            $memberA = trim(($first['firstname'] ?? '') . ' ' . ($first['lastname'] ?? ''));
                        }
                    }
                    
                    if (is_array($valB) && !empty($valB)) {
                        $first = reset($valB);
                        if (is_array($first)) {
                            $memberB = trim(($first['firstname'] ?? '') . ' ' . ($first['lastname'] ?? ''));
                        }
                    }
                    
                    $memberA = strtolower($memberA);
                    $memberB = strtolower($memberB);
                    
                    if (empty($memberA) && empty($memberB)) return 0;
                    if (empty($memberA)) return ($order_dir === 'asc') ? 1 : -1;
                    if (empty($memberB)) return ($order_dir === 'asc') ? -1 : 1;
                    
                    $result = strcmp($memberA, $memberB);
                    return ($order_dir === 'asc') ? $result : -$result;
                }

                // Handle CUSTOM FIELDS sorting
                if (strpos($sort_key, 'custom_field_') === 0) {
                    $slug = str_replace('custom_field_', '', $sort_key);
                    $valA = isset($a['custom_fields'][$slug]) ? $a['custom_fields'][$slug] : '';
                    $valB = isset($b['custom_fields'][$slug]) ? $b['custom_fields'][$slug] : '';
                    
                    $result = strcasecmp($valA, $valB);
                    return ($order_dir === 'asc') ? $result : -$result;
                }
                
                // Handle text (default)
                $textA = strtolower(trim(strval($valA)));
                $textB = strtolower(trim(strval($valB)));
                
                if (empty($textA) && empty($textB)) return 0;
                if (empty($textA)) return ($order_dir === 'asc') ? 1 : -1;
                if (empty($textB)) return ($order_dir === 'asc') ? -1 : 1;
                
                $result = strcmp($textA, $textB);
                return ($order_dir === 'asc') ? $result : -$result;
                
            } catch (Exception $e) {
                log_message('error', 'Sorting error: ' . $e->getMessage());
                return 0;
            }
        });
    }

    // Pagination
    $projects = array_slice($filtered_projects, $start, $length);

    // Format data for DataTable
    $output_data = [];
    foreach ($projects as $project) {
        $progress = (int)($project['progress'] ?? 0);
        $status = strtolower(trim($project['status_name'] ?? 'unknown'));

        // === PROGRESS BAR COLORS (based on progress percentage) ===
        if ($progress == 0) {
            $progress_color = '#e0e0e0';
        } elseif ($progress > 0 && $progress < 25) {
            $progress_color = '#dc3545';
        } elseif ($progress >= 25 && $progress < 50) {
            $progress_color = '#ffc107';
        } elseif ($progress >= 50 && $progress < 75) {
            $progress_color = '#17a2b8';
        } elseif ($progress >= 75 && $progress < 100) {
            $progress_color = '#28a745';
        } else {
            $progress_color = '#28a745';
        }

        // === STATUS COLORS (for status badge) ===
        $status_color = '#6c757d';
        $light_bg = 'rgba(108,117,125,0.05)';

        switch ($status) {
            case 'in progress':
                $status_color = '#007bff';
                $light_bg = 'rgba(0,123,255,0.05)';
                break;
            case 'on hold':
                $status_color = '#ffc107';
                $light_bg = 'rgba(255,193,7,0.08)';
                break;
            case 'cancelled':
                $status_color = '#dc3545';
                $light_bg = 'rgba(220,53,69,0.05)';
                break;
            case 'finished':
                $status_color = '#28a745';
                $light_bg = 'rgba(40,167,69,0.05)';
                break;
            case 'not started':
                $status_color = '#6c757d';
                $light_bg = 'rgba(108,117,125,0.05)';
                break;
        }

        // Progress bar HTML (uses progress_color based on percentage)
        $progress_html = '
            <div style="width:100%; min-width:100px;">
                <div style="background:#f0f0f0; height:6px; border-radius:4px; overflow:hidden;">
                    <div style="width:' . $progress . '%; height:6px; background-color:' . $progress_color . ';"></div>
                </div>
                <span style="font-size:12px; color:#555; margin-top:4px; display:inline-block;">' . $progress . '%</span>
            </div>
        ';

        // Status label HTML (uses status_color based on status)
        $status_html = '
            <span style="
                display:inline-block;
                white-space:nowrap;
                background-color:' . $light_bg . ';
                color:' . $status_color . ';
                border:1px solid ' . $status_color . ';
                font-size:13px;
                font-weight:500;
                padding:4px 10px;
                border-radius:6px;
                text-align:center;
                min-width:80px;
                line-height:18px;
            ">
                ' . ucfirst($status) . '
            </span>
        ';

        // Build row with static columns first
        $row = [
            (string)($project['id'] ?? ''),
        ];
        
        // Check if "Project Lead By" custom field exists and add it before Project Name
        $project_lead_by_added = false;
        foreach ($custom_fields as $field) {
            if (strtolower($field['name']) === 'project lead by') {
                $value = '';
                if (isset($project['custom_fields'][$field['slug']])) {
                    $value = $project['custom_fields'][$field['slug']];
                }
                
                // Format Project Lead By
                if ($field['type'] === 'select' && is_numeric($value)) {
                    $staff = $this->staff_model->get($value);
                    if ($staff) {
                        $row[] = htmlspecialchars($staff->firstname . ' ' . $staff->lastname);
                    } else {
                        $row[] = '';
                    }
                } else {
                    $row[] = htmlspecialchars($value);
                }
                $project_lead_by_added = true;
                break;
            }
        }
        
        // Continue with other static columns
        $row = array_merge($row, [
            '<a href="' . admin_url('projects/view/' . ($project['id'] ?? '')) . '" target="_blank">' . htmlspecialchars($project['name'] ?? '') . '</a>',
            '<a href="' . admin_url('clients/client/' . ($project['clientid'] ?? '')) . '" target="_blank">' . htmlspecialchars($project['customer_name'] ?? '') . '</a>',
            $this->format_tags_html($project['tags'] ?? ''),
            ($project['start_date'] ? _d($project['start_date']) : ''),
            ($project['deadline'] ? _d($project['deadline']) : ''),
            $this->format_members_html($project['members'] ?? [], $project['id']),
            number_format((float)($project['estimated_hours'] ?? 0), 2),
            number_format((float)($project['logged_hours'] ?? 0), 2),
        ]);

        // *** ADD CUSTOM FIELDS DYNAMICALLY AFTER LOGGED HOURS (excluding Project Lead By) ***
        foreach ($custom_fields as $field) {
            // Skip Project Lead By as it's already added before Project Name
            if (strtolower($field['name']) === 'project lead by') {
                continue;
            }
            
            $value = '';
            if (isset($project['custom_fields'][$field['slug']])) {
                $value = $project['custom_fields'][$field['slug']];
            }
            
            // Format based on field type
            if ($field['type'] === 'date_picker' && !empty($value)) {
                $row[] = _d($value);
            } elseif ($field['type'] === 'select' && is_numeric($value)) {
                // Get staff name for select field
                $staff = $this->staff_model->get($value);
                if ($staff) {
                    $row[] = htmlspecialchars($staff->firstname . ' ' . $staff->lastname);
                } else {
                    $row[] = htmlspecialchars($value);
                }
            } else {
                $row[] = htmlspecialchars($value);
            }
        }

        // *** ADD PROGRESS AND STATUS AT THE END (ONLY ONCE!) ***
        $row[] = $progress_html;
        $row[] = $status_html;

        $output_data[] = $row;
    }

    // Return JSON response
    $response = [
        'draw' => (int)($post_data['draw'] ?? 1),
        'recordsTotal' => count($all_projects),
        'recordsFiltered' => count($filtered_projects),
        'data' => $output_data
    ];
    
    log_message('debug', 'Returning ' . count($output_data) . ' records');
    log_message('debug', '=== End NexReports table() ===');

    // Set proper headers and output
    $this->output
        ->set_content_type('application/json')
        ->set_status_header(200)
        ->set_output(json_encode($response));
}
public function get_dynamic_filters() {
    header('Content-Type: application/json');
    
    $filters = $this->nexreports_model->get_custom_fields();
    
    // Exclude completion date field (ID 9, adjust if needed)
    $excluded_field_ids = [9]; // Change this to the actual ID of completion date
    
    $result = [];
    foreach ($filters as $filter) {
        // Skip excluded fields
        if (in_array($filter['id'], $excluded_field_ids)) {
            continue;
        }
        
        // Get unique values for this custom field
        $options = $this->nexreports_model->get_custom_field_options($filter['id']);
        
        $result[] = [
            'id' => $filter['id'],
            'name' => $filter['name'],
            'type' => $filter['type'],
            'options' => implode(',', $options)
        ];
    }
    
    echo json_encode($result);
}

// Helper method to fetch unique values for a custom field
private function get_custom_field_options($field_id)
{
    $this->db->select('value');
    $this->db->from('tblcustomfieldsvalues');
    $this->db->where('fieldid', $field_id);
    $this->db->where('value !=', '');
    $this->db->distinct();
    $this->db->order_by('value', 'ASC');
    
    $query = $this->db->get();
    $options = [];
    
    foreach ($query->result_array() as $row) {
        if (!empty($row['value'])) {
            $options[] = $row['value'];
        }
    }
    
    return $options;
}

// Helper method to format members HTML with tooltips
private function format_members_html($members, $project_id = null)
{
    if (empty($members) || !is_array($members)) {
        return '';
    }
    $html = '<div class="tw-flex tw--space-x-1">';
    
    foreach ($members as $member) {
        $staff_id = $member['staffid'];
        $full_name = htmlspecialchars($member['firstname'] . ' ' . $member['lastname']);
        
        // Get logged hours for this staff member in this project
        $logged_hours = $this->get_staff_logged_hours($staff_id, $project_id);
        
        // Format tooltip text
        $tooltip_text = $full_name . ' - Total Logged Time: ' . $logged_hours;
        
        // Use Perfex's built-in helper function
        $profile_image_url = staff_profile_image_url($staff_id, 'small');
        
        $html .= '
            <a href="' . admin_url('staff/profile/' . $staff_id) . '" 
               data-toggle="tooltip" 
               data-title="' . htmlspecialchars($tooltip_text) . '" 
               data-placement="top"
               class="tw-inline-block">
                <img src="' . $profile_image_url . '" 
                     alt="' . $full_name . '" 
                     class="staff-profile-image-small tw-rounded-full"
                     style="width: 32px; height: 32px; object-fit: cover; border: 2px solid #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">
            </a>
        ';
    }
    
    $html .= '</div>';
    
    return $html;
}

// Updated helper method for project-specific logged hours
private function get_staff_logged_hours($staff_id, $project_id = null)
{
    // Use proper table names with prefix
    $timers_table = db_prefix() . 'taskstimers';
    $tasks_table = db_prefix() . 'tasks';
    
    // Start building query
    $this->db->select('SUM(' . $timers_table . '.end_time - ' . $timers_table . '.start_time) as total_seconds', FALSE);
    $this->db->from($timers_table);
    $this->db->where($timers_table . '.staff_id', $staff_id);
    
    if ($project_id) {
        // Join with tasks to filter by project
        $this->db->join($tasks_table, $tasks_table . '.id = ' . $timers_table . '.task_id', 'inner');
        $this->db->where($tasks_table . '.rel_type', 'project');
        $this->db->where($tasks_table . '.rel_id', $project_id);
    }
    
    $query = $this->db->get();
    
    if ($query && $query->num_rows() > 0) {
        $result = $query->row();
        $total_seconds = (int)$result->total_seconds;
        
        // Handle NULL or 0 values
        if ($total_seconds <= 0) {
            return '00:00';
        }
        
        // Convert seconds to hours and minutes
        $hours = floor($total_seconds / 3600);
        $minutes = floor(($total_seconds % 3600) / 60);
        
        return sprintf('%02d:%02d', $hours, $minutes);
    }
    
    return '00:00';
}
// Format tags as HTML with simple gray boxes
private function format_tags_html($tags_string)
{
    if (empty($tags_string)) {
        return '';
    }
    
    // Split tags by comma
    $tags_array = explode(',', $tags_string);
    
    $html = '<div style="display: flex; flex-wrap: wrap; gap: 4px;">';
    
    foreach ($tags_array as $tag) {
        $tag = trim($tag);
        if (empty($tag)) {
            continue;
        }
        
        $html .= '<span style="
            display: inline-block;
            background-color: #f5f5f5;
            color: #333;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 12px;
            white-space: nowrap;
            border: 1px solid #e0e0e0;
        ">' . htmlspecialchars($tag) . '</span>';
    }
    
    $html .= '</div>';
    
    return $html;
}
    /**
     * Summary Data (AJAX)
     */
    public function summary()
    {
        if (!has_permission('projects', '', 'view')) {
            ajax_access_denied();
        }

        $summary = $this->nexreports_model->get_project_status_counts();
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($summary));
    }

    /**
     * Sanitize filters from POST data
     */
private function sanitize_filters($post_data)
{
    $clean = [];

    // Keys we allow (normalized names, without trailing [])
    $allowed_keys = [
        'project_name',
        'customer',
        'date_type',
        'from_date',
        'to_date',
        'members',
        'status',
        'tags',
        'custom_fields'  // Add this
    ];

    // DataTables params we want to ignore
    $datatables_params = [
        'draw', 'columns', 'order', 'start', 'length', 'search',
        'last_order_identifier', '_',
    ];

    // Helper: try parsing common input date formats into MySQL Y-m-d
    $parse_date_to_mysql = function ($datestr) {
        $datestr = trim((string)$datestr);
        if ($datestr === '') {
            return '';
        }
        // Try common formats (add more if your users use different formats)
        $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'Y/m/d'];
        foreach ($formats as $f) {
            $d = DateTime::createFromFormat($f, $datestr);
            if ($d !== false) {
                return $d->format('Y-m-d');
            }
        }
        // last fallback Ã¢â‚¬â€œ try strtotime (may work for many cases)
        $ts = strtotime($datestr);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }
        return '';
    };

    // Normalize incoming array structure
    foreach ($post_data as $raw_key => $value) {
        // ignore datatables params
        if (in_array($raw_key, $datatables_params, true)) {
            continue;
        }

        // Normalize key: remove any trailing [] for multi-selects
        $key = preg_replace('/\[\]$/', '', $raw_key);

        // skip anything not allowed
        if (!in_array($key, $allowed_keys, true)) {
            continue;
        }

        // Handle custom_fields specially
        if ($key === 'custom_fields') {
            if (is_array($value) && !empty($value)) {
                $clean['custom_fields'] = [];
                foreach ($value as $field_id => $field_value) {
                    if ($field_value !== '' && $field_value !== null) {
                        $clean['custom_fields'][$field_id] = $field_value;
                    }
                }
            }
            continue;
        }

        // If value is array already (CI will give arrays for name[]), keep as array
        if (is_array($value)) {
            // Remove empty entries
            $value = array_filter($value, function ($v) {
                return !(is_null($v) || $v === '');
            });
            if (empty($value)) {
                continue;
            }
            $clean[$key] = array_values($value);
            continue;
        }

        // If value is a string with commas and this key is supposed to be array-like,
        // convert to array (covers cases where select multiple was serialized as "1,2,3")
        if (is_string($value) && in_array($key, ['members', 'status', 'tags'], true) && strpos($value, ',') !== false) {
            $parts = array_filter(array_map('trim', explode(',', $value)));
            if (!empty($parts)) {
                $clean[$key] = array_values($parts);
            }
            continue;
        }

        // For date fields, convert to mysql format YYYY-MM-DD
        if (in_array($key, ['from_date', 'to_date'], true)) {
            $mysql = $parse_date_to_mysql($value);
            if ($mysql !== '') {
                $clean[$key] = $mysql;
            }
            continue;
        }

        // Normal single value (incl. project_name and customer)
        if ($value === '' || is_null($value)) {
            continue;
        }

        $clean[$key] = $value;
    }

    log_message('debug', 'SANITIZED FILTERS: ' . print_r($clean, true));
    
    return $clean;
    }
    /**
 * Save filter template
 * FIXED VERSION - Add this to your Nexreports.php controller
 */
public function save_filter_template()
{
    // Set JSON header
    header('Content-Type: application/json');
    
    if (!$this->input->post()) {
        echo json_encode([
            'success' => false,
            'message' => 'No data received'
        ]);
        return;
    }
    
    $template_name = $this->input->post('template_name');
    $filters = $this->input->post('filters');
    $set_as_default = $this->input->post('set_as_default') == '1' ? 1 : 0;
    $staff_id = get_staff_user_id();
    
    // Validate
    if (empty($template_name)) {
        echo json_encode([
            'success' => false,
            'message' => 'Template name is required'
        ]);
        return;
    }
    
    // Log for debugging
    log_message('debug', '=== SAVING FILTER TEMPLATE ===');
    log_message('debug', 'Template Name: ' . $template_name);
    log_message('debug', 'Filters: ' . print_r($filters, true));
    log_message('debug', 'Set as Default: ' . $set_as_default);
    log_message('debug', 'Staff ID: ' . $staff_id);
    
    // If set as default, unset all other defaults first
    if ($set_as_default) {
        $this->db->where('staff_id', $staff_id);
        $this->db->update(db_prefix() . 'nexreports_filter_templates', [
            'is_default' => 0
        ]);
        log_message('debug', 'Cleared other default templates');
    }
    
    // Prepare data
    $data = [
        'staff_id' => $staff_id,
        'name' => $template_name,
        'filters' => json_encode($filters),
        'is_default' => $set_as_default,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    log_message('debug', 'Inserting data: ' . print_r($data, true));
    
    // Insert new template
    $this->db->insert(db_prefix() . 'nexreports_filter_templates', $data);
    
    if ($this->db->affected_rows() > 0) {
        $insert_id = $this->db->insert_id();
        log_message('debug', 'Template saved successfully with ID: ' . $insert_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Filter template saved successfully!',
            'template_id' => $insert_id
        ]);
    } else {
        log_message('error', 'Failed to insert template. DB Error: ' . $this->db->error());
        
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save filter template.'
        ]);
    }
}

/**
 * Get all filter templates for current user
 * FIXED VERSION
 */
public function get_filter_templates()
{
    header('Content-Type: application/json');
    
    $staff_id = get_staff_user_id();
    
    log_message('debug', 'Loading templates for staff ID: ' . $staff_id);
    
    $this->db->select('id, name, filters, is_default, created_at');
    $this->db->from(db_prefix() . 'nexreports_filter_templates');
    $this->db->where('staff_id', $staff_id);
    $this->db->order_by('is_default', 'DESC');
    $this->db->order_by('created_at', 'DESC');
    
    $templates = $this->db->get()->result_array();
    
    log_message('debug', 'Found ' . count($templates) . ' templates');
    
    echo json_encode($templates);
}

/**
 * Get default filter template
 * FIXED VERSION
 */
public function get_default_filter()
{
    header('Content-Type: application/json');
    
    $staff_id = get_staff_user_id();
    
    log_message('debug', 'Loading default filter for staff ID: ' . $staff_id);
    
    $this->db->select('filters');
    $this->db->from(db_prefix() . 'nexreports_filter_templates');
    $this->db->where('staff_id', $staff_id);
    $this->db->where('is_default', 1);
    
    $result = $this->db->get()->row();
    
    if ($result) {
        log_message('debug', 'Default filter found');
        
        echo json_encode([
            'success' => true,
            'filters' => json_decode($result->filters, true)
        ]);
    } else {
        log_message('debug', 'No default filter found');
        
        echo json_encode([
            'success' => false
        ]);
    }
}

/**
 * Delete filter template
 * FIXED VERSION
 */
public function delete_filter_template($id)
{
    header('Content-Type: application/json');
    
    $staff_id = get_staff_user_id();
    
    log_message('debug', 'Deleting template ID: ' . $id . ' for staff ID: ' . $staff_id);
    
    $this->db->where('id', $id);
    $this->db->where('staff_id', $staff_id);
    $this->db->delete(db_prefix() . 'nexreports_filter_templates');
    
    if ($this->db->affected_rows() > 0) {
        log_message('debug', 'Template deleted successfully');
        
        echo json_encode([
            'success' => true,
            'message' => 'Filter template deleted successfully!'
        ]);
    } else {
        log_message('error', 'Failed to delete template or not found');
        
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete filter template.'
        ]);
    }
}


                                                    /**
                                                     * Attendance Overview Page
                                                     * Enhanced with comprehensive role-based access control
                                                     */
public function attendance()
{
    // Check permissions with fallback
    $has_permission = false;
    if (is_admin()) {
        $has_permission = true;
    } elseif (staff_can('view', 'attendance_reports') || staff_can('view_own', 'attendance_reports')) {
        $has_permission = true;
    }
    
    if (!$has_permission) {
        access_denied('Attendance Reports');
    }

    // Get comprehensive role information
    $role_info = [];
    if (function_exists('nexreports_get_user_role_info')) {
        try {
            $role_info = nexreports_get_user_role_info('attendance_reports');
        } catch (Exception $e) {
            log_message('error', 'NexReports: Error getting role info - ' . $e->getMessage());
            // Fallback to basic role detection
            $role_info = [
                'role_type' => 'normal_employee',
                'can_manage_filters' => false,
                'can_view_all' => false,
                'accessible_staff_ids' => [get_staff_user_id()]
            ];
        }
    } else {
        // Fallback if helper not available
        $role_info = [
            'role_type' => is_admin() || staff_can('view', 'attendance_reports') ? 'admin_hr' : 'normal_employee',
            'can_manage_filters' => is_admin() || staff_can('view', 'attendance_reports'),
            'can_view_all' => is_admin() || staff_can('view', 'attendance_reports'),
            'accessible_staff_ids' => is_admin() || staff_can('view', 'attendance_reports') ? [] : [get_staff_user_id()]
        ];
    }
    
    // Pass role information to view
    $data['role_type'] = $role_info['role_type'];
    $data['can_manage_filters'] = $role_info['can_manage_filters'];
    $data['can_view_all'] = $role_info['can_view_all'];
    $data['accessible_staff_ids'] = $role_info['accessible_staff_ids'];
    $data['current_staff_id'] = get_staff_user_id();
    
    // Load staff members for all status types (will be filtered dynamically)
    $data['staff_members'] = [];
    $data['all_staff_members'] = [];
    
    // Get staff for each status type if user can manage filters
    if ($data['can_manage_filters']) {
        try {
            if (function_exists('nexreports_get_accessible_staff_for_dropdown')) {
                // Get staff for all status types
                $data['staff_active'] = nexreports_get_accessible_staff_for_dropdown('attendance_reports', 'active');
                $data['staff_inactive'] = nexreports_get_accessible_staff_for_dropdown('attendance_reports', 'inactive');
                $data['staff_all'] = nexreports_get_accessible_staff_for_dropdown('attendance_reports', 'all');
                
                // Default to active staff
                $data['staff_members'] = $data['staff_active'];
                
                // Combine all for JavaScript dynamic filtering
                $data['all_staff_members'] = $data['staff_all'];
                
                log_message('debug', 'NexReports: Loaded staff - Active: ' . count($data['staff_active']) . 
                           ', Inactive: ' . count($data['staff_inactive']) . ', All: ' . count($data['staff_all']));
            }
        } catch (Exception $e) {
            log_message('error', 'NexReports: Error loading staff for filters - ' . $e->getMessage());
        }
    } else {
        // For restricted users (normal employees), load only their own data
        try {
            $current_staff = $this->db->query("
                SELECT staffid, firstname, lastname, email, active 
                FROM " . db_prefix() . "staff 
                WHERE staffid = " . get_staff_user_id()
            )->row_array();
            
            if ($current_staff) {
                $data['staff_members'] = [$current_staff];
                $data['all_staff_members'] = [$current_staff];
                $data['staff_active'] = $current_staff['active'] == 1 ? [$current_staff] : [];
                $data['staff_inactive'] = $current_staff['active'] == 0 ? [$current_staff] : [];
                $data['staff_all'] = [$current_staff];
            }
        } catch (Exception $e) {
            log_message('error', 'NexReports: Error loading current staff data - ' . $e->getMessage());
            $data['staff_members'] = [];
            $data['all_staff_members'] = [];
        }
    }
    
    // Fallback if no staff data loaded
    if (empty($data['all_staff_members'])) {
        log_message('debug', 'NexReports: Using direct database fallback for staff dropdown');
        try {
            $query = $this->db->query("
                SELECT staffid, firstname, lastname, email, active 
                FROM " . db_prefix() . "staff 
                WHERE active = 1 
                ORDER BY firstname ASC
            ");
            $all_staff = $query->result_array();
            
            // Apply role-based filtering to fallback data
            if (!$data['can_view_all'] && !empty($data['accessible_staff_ids'])) {
                $accessible_ids = array_map('intval', $data['accessible_staff_ids']);
                $all_staff = array_filter($all_staff, function($staff) use ($accessible_ids) {
                    return in_array((int)$staff['staffid'], $accessible_ids);
                });
            }
            
            $data['staff_members'] = $all_staff;
            $data['all_staff_members'] = $all_staff;
            
            log_message('debug', 'NexReports: Fallback loaded ' . count($all_staff) . ' staff members');
        } catch (Exception $e) {
            log_message('error', 'NexReports: Fallback database query failed - ' . $e->getMessage());
            $data['staff_members'] = [];
            $data['all_staff_members'] = [];
        }
    }
    
    // Set default values based on role
    $data['default_active_status'] = 'active';
    $data['title'] = 'HRMS Overview';
    
    // Summary data (optional)
    $data['ot_summary'] = $this->db->query("
        SELECT 
            SUM(status IN ('1', '2')) AS approved,
            COUNT(*) AS total
        FROM " . db_prefix() . "timesheets_additional_timesheet
        WHERE timekeeping_type IS NOT NULL
    ")->row();

    $data['leave_summary'] = $this->db->query("
        SELECT 
            SUM(status = '1') AS approved,
            COUNT(*) AS total
        FROM " . db_prefix() . "timesheets_requisition_leave
    ")->row();

    // Debug logging
    log_message('debug', 'NexReports Attendance: Role=' . $data['role_type'] . 
               ', CanManageFilters=' . ($data['can_manage_filters'] ? 'Yes' : 'No') . 
               ', StaffCount=' . count($data['all_staff_members']));

    $this->load->view('nexreports/attendance/index', $data);
}
public function attendance_table()
{
    if (
        !staff_can('view', 'attendance_reports') &&
        !staff_can('view_own', 'attendance_reports') &&
        !is_admin()
    ) {
        ajax_access_denied();
    }

    $can_view_all = staff_can('view', 'attendance_reports') || is_admin();
    
    // Get accessible staff IDs based on hierarchy
    $accessible_staff_ids = [];
    if (function_exists('nexreports_hrms_get_accessible_staff_ids')) {
        try {
            $accessible_staff_ids = nexreports_hrms_get_accessible_staff_ids('attendance_reports');
        } catch (Exception $e) {
            log_message('error', 'NexReports: Error getting accessible staff in table - ' . $e->getMessage());
            $accessible_staff_ids = $can_view_all ? [] : [get_staff_user_id()];
        }
    } else {
        $accessible_staff_ids = $can_view_all ? [] : [get_staff_user_id()];
    }

    $post_data = $this->input->post();
    $clean_filters = $this->sanitize_attendance_filters($post_data);
    
    // Set default filter to 'active' if no active_status filter is provided
    if (!isset($clean_filters['active_status']) || $clean_filters['active_status'] === '') {
        $clean_filters['active_status'] = 'active';
    }

    $start  = isset($post_data['start']) ? (int)$post_data['start'] : 0;
    $length = isset($post_data['length']) ? (int)$post_data['length'] : 10;
    $search_value = isset($post_data['search']['value']) ? $post_data['search']['value'] : '';

    // Log filter state for debugging
    log_message('debug', 'HRMS Filters Applied: ' . json_encode($clean_filters));
    log_message('debug', 'HRMS Pagination: start=' . $start . ', length=' . $length . ', search=' . $search_value);

    try {
        $all_records = $this->nexreports_model
            ->get_attendance_summary_by_staff($clean_filters, $can_view_all, $accessible_staff_ids);
    } catch (Exception $e) {
        log_message('error', 'Attendance Error: ' . $e->getMessage());

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'draw' => (int)($post_data['draw'] ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ]));
        return;
    }

    // Search
    $filtered_records = [];
    if (!empty($search_value)) {
        foreach ($all_records as $record) {
            if (stripos($record['staff_name'], $search_value) !== false) {
                $filtered_records[] = $record;
            }
        }
    } else {
        $filtered_records = $all_records;
    }

    // ✅ Sorting - Handle DataTables order parameter
    if (isset($post_data['order']) && is_array($post_data['order']) && !empty($post_data['order'])) {
        $order_column_index = (int)$post_data['order'][0]['column'];
        $order_direction = strtoupper($post_data['order'][0]['dir']) === 'DESC' ? 'DESC' : 'ASC';
        
        // Map column index to field name
        $column_field_map = [
            0 => 'staff_name',           // Staff Name (string)
            1 => 'timesheet_hours',       // Timesheet Hours (numeric)
            2 => 'checkin_hours',         // Check-in/out Hours (numeric)
            3 => 'ot_hours',              // OT Hours (numeric)
            4 => 'paid_leave_hours',      // Paid Leave Hours (numeric)
            5 => 'tickets_count',         // Tickets (numeric)
            6 => 'payable_hours',         // Payable Hours (numeric)
            7 => 'actual_work_hours',     // Actual Work Hours (numeric)
            8 => 'base_salary',           // Salary (numeric)
            9 => 'reflectable_salary'     // Reflectable Salary (numeric)
        ];
        
        if (isset($column_field_map[$order_column_index])) {
            $sort_field = $column_field_map[$order_column_index];
            
            usort($filtered_records, function($a, $b) use ($sort_field, $order_direction) {
                $val_a = isset($a[$sort_field]) ? $a[$sort_field] : ($sort_field === 'staff_name' ? '' : 0);
                $val_b = isset($b[$sort_field]) ? $b[$sort_field] : ($sort_field === 'staff_name' ? '' : 0);
                
                // Handle numeric sorting for all fields except staff_name
                if ($sort_field === 'staff_name') {
                    // String sorting (case-insensitive)
                    $result = strcasecmp((string)$val_a, (string)$val_b);
                } else {
                    // Numeric sorting
                    $val_a = (float)$val_a;
                    $val_b = (float)$val_b;
                    $result = $val_a <=> $val_b;
                }
                
                return $order_direction === 'DESC' ? -$result : $result;
            });
        }
    }

    // Calculate sums for all filtered records (not just current page)
    $total_base_salary = 0;
    $total_reflectable_salary = 0;
    foreach ($filtered_records as $record) {
        $total_base_salary += (float)$record['base_salary'];
        $total_reflectable_salary += (float)$record['reflectable_salary'];
    }

    // Pagination
    $records = array_slice($filtered_records, $start, $length);

 // Format data
$output_data = [];
foreach ($records as $record) {
    // ✅ Staff name 
$staff_name = htmlspecialchars($record['staff_name']);
$staff_id = isset($record['staff_id']) ? (int)$record['staff_id'] : 0;

if ($staff_id > 0) {
    $staff_name_html = '<a href="' . admin_url('staff/profile/' . $staff_id) . '" target="_blank" style="color: #333; text-decoration: none;">' . $staff_name . '</a>';
} else {
    $staff_name_html = $staff_name;
}
    
    // Format hours (for sorting, use numeric values)
    $timesheet_hours = $record['timesheet_hours'];
    $timesheet_hours_html = number_format($timesheet_hours, 2);
    $checkin_hours = $record['checkin_hours'];
    $checkin_hours_html = number_format($checkin_hours, 2);
    $payable_hours = $record['payable_hours'];
    $payable_hours_html = number_format($payable_hours, 2);
    $actual_work_hours = $record['actual_work_hours'];
    // Format to show whole numbers when appropriate (200 instead of 200.00)
    // But keep decimals if needed (200.5 instead of 200)
    $actual_work_hours_html = ($actual_work_hours == floor($actual_work_hours)) 
        ? number_format($actual_work_hours, 0) 
        : number_format($actual_work_hours, 2);
    
    // ✅ OT Hours with approved badge (clickable for any records)
    $ot_hours = $record['ot_hours'];
    $ot_hours_formatted = number_format($ot_hours, 2);
    $ot_statistics = isset($record['ot_statistics']) ? $record['ot_statistics'] : '-';
    $staff_id = isset($record['staff_id']) ? $record['staff_id'] : 0;
    
    // Show "Approved" badge if there are ANY OT records (approved, pending, or rejected)
    if ($ot_statistics !== '-' && $staff_id > 0) {
        $ot_hours_html = '<span data-order="' . $ot_hours . '">' . $ot_hours_formatted . '<br><span class="label label-success ot-approved-badge" data-staff-id="' . $staff_id . '" data-type="ot" style="cursor: pointer;">Approved (' . $ot_statistics . ')</span></span>';
    } else {
        $ot_hours_html = '<span data-order="' . $ot_hours . '">' . $ot_hours_formatted . '</span>';
    }
    
    // ✅ Paid Leave Hours with approved badge (clickable for any records)
    $paid_leave_hours = $record['paid_leave_hours'];
    $paid_leave_hours_formatted = ($paid_leave_hours > 0) 
        ? number_format($paid_leave_hours, 2) 
        : '0.00';
    
    $leave_statistics = isset($record['leave_statistics']) ? $record['leave_statistics'] : '-';
    
    // Show "Approved" badge if there are ANY Leave records (approved, pending, or rejected)
    if ($leave_statistics !== '-' && $staff_id > 0) {
        $paid_leave_html = '<span data-order="' . $paid_leave_hours . '">' . $paid_leave_hours_formatted . '<br><span class="label label-success leave-approved-badge" data-staff-id="' . $staff_id . '" data-type="leave" style="cursor: pointer;">Approved (' . $leave_statistics . ')</span></span>';
    } else {
        $paid_leave_html = '<span data-order="' . $paid_leave_hours . '">' . $paid_leave_hours_formatted . '</span>';
    }
    
    // ✅ Tickets count - plain clickable number only
$tickets_count = isset($record['tickets_count']) ? (int)$record['tickets_count'] : 0;

if ($tickets_count > 0 && $staff_id > 0) {
    // Plain number that's clickable - NO badge
    $tickets_html = '<span data-order="' . $tickets_count . '"><span class="tickets-badge" data-staff-id="' . $staff_id . '" data-type="tickets" style="cursor: pointer;">' . $tickets_count . '</span></span>';
} else {
    // Just show 0
    $tickets_html = '<span data-order="' . $tickets_count . '">' . ($tickets_count > 0 ? $tickets_count : '0') . '</span>';
}
    
    // Currency (extract numeric value for sorting)
    $base_salary = $record['base_salary'];
    $base_salary_html = app_format_money($base_salary, get_base_currency());
    $reflectable_salary = round($record['reflectable_salary'], 0); // ✅ Round to whole number
    $reflectable_salary_html = app_format_money($reflectable_salary, get_base_currency());
    
    $row = [
        $staff_name_html,           // 1. Staff Name (no badge)
        '<span data-order="' . $timesheet_hours . '">' . $timesheet_hours_html . '</span>',      // 2. Timesheet Hrs
        '<span data-order="' . $checkin_hours . '">' . $checkin_hours_html . '</span>',             // 3. Checkin-out Hrs
        $ot_hours_html,             // 4. OT hrs (with approved count below)
        $paid_leave_html,           // 5. Paid Leave Hrs (with approved count below)
        $tickets_html,              // 6. Tickets (with open count badge)
        '<span data-order="' . $payable_hours . '">' . $payable_hours_html . '</span>',             // 7. Payable Hrs
        '<span data-order="' . $actual_work_hours . '">' . $actual_work_hours_html . '</span>',         // 8. Actual Wrk Hrs
        '<span data-order="' . $base_salary . '">' . $base_salary_html . '</span>',               // 9. Salary
        '<span data-order="' . $reflectable_salary . '">' . $reflectable_salary_html . '</span>'         // 10. Reflectable Salary
    ];
    
    $output_data[] = $row;
}
    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode([
            'draw' => (int)($post_data['draw'] ?? 1),
            'recordsTotal' => count($all_records),
            'recordsFiltered' => count($filtered_records),
            'data' => $output_data,
            'totals' => [
                'base_salary' => round($total_base_salary, 2),
                'reflectable_salary' => round($total_reflectable_salary, 2)
            ]
        ]));
    return;

}
/**
 * Sanitize attendance filters
 */
private function sanitize_attendance_filters($post_data)
{
    $clean = [];
    
    $allowed_keys = ['staff_member', 'from_date', 'to_date', 'period', 'active_status'];
    
    foreach ($post_data as $key => $value) {
        $normalized_key = str_replace('[]', '', $key);
        
        if (in_array($normalized_key, $allowed_keys)) {
            // Allow empty values for active_status to support "all" option
            if ($normalized_key === 'active_status' || ($value !== '' && $value !== null)) {
                $clean[$normalized_key] = $value;
            }
        }
    }
    
    // Ensure active_status has a default value if not provided
    if (!isset($clean['active_status'])) {
        $clean['active_status'] = 'active';
    }
    
    return $clean;
}

/**
 * Export attendance to CSV - ADMIN ONLY
 */
public function export_attendance()
{
    if (
        !staff_can('view', 'attendance_reports') &&
        !staff_can('view_own', 'attendance_reports') &&
        !is_admin()
    ) {
        access_denied('Attendance Reports');
    }

    $can_view_all = staff_can('view', 'attendance_reports') || is_admin();

    $filters = $this->sanitize_attendance_filters($this->input->get());

    $records = $this->nexreports_model
        ->get_attendance_summary_by_staff($filters, $can_view_all);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=attendance_report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, [
    'Staff Name',
    'Timesheet Hours',
    'Check-in/out Hours',
    'OT Hours',
    'Paid Leave Hours',
    'Payable Hours',
    'Actual Work Hours',
    'Base Salary',
    'Reflectable Salary'
    ]);
    foreach ($records as $record) {
    $ot_hours = number_format($record['ot_hours'], 2);
    $paid_leave_hours = number_format($record['paid_leave_hours'], 2);
    fputcsv($output, [
        $record['staff_name'],
        number_format($record['timesheet_hours'], 2),
        number_format($record['checkin_hours'], 2),
        $ot_hours,
        $paid_leave_hours,
        number_format($record['payable_hours'], 2),
        ($record['actual_work_hours'] == floor($record['actual_work_hours'])) 
            ? number_format($record['actual_work_hours'], 0) 
            : number_format($record['actual_work_hours'], 2),
        number_format($record['base_salary'], 2),
        number_format(ceil($record['reflectable_salary']), 0) // Round up to whole number
    ]);
}
    
    fclose($output);
    exit;
}

/**
 * Get leave details for modal (AJAX)
 */
public function get_leave_details()
{
    if (
        !staff_can('view', 'attendance_reports') &&
        !staff_can('view_own', 'attendance_reports') &&
        !is_admin()
    ) {
        ajax_access_denied();
    }

    $staff_id = $this->input->post('staff_id');
    $from_date = $this->input->post('from_date');
    $to_date = $this->input->post('to_date');

    if (!$staff_id) {
        $this->output->set_output(json_encode(['success' => false, 'message' => 'Staff ID required']));
        return;
    }

    $data['leaves'] = $this->nexreports_model->get_leave_details_for_modal($staff_id, $from_date, $to_date);
    $data['staff_name'] = get_staff_full_name($staff_id);
    
    // ✅ Get count summary for modal header
    $data['leave_summary'] = $this->nexreports_model->get_leave_count_summary($staff_id, $from_date, $to_date);

    $this->load->view('nexreports/attendance/modal_leave_details', $data);
}

/**
 * Get OT details for modal (AJAX)
 */
public function get_ot_details()
{
    if (
        !staff_can('view', 'attendance_reports') &&
        !staff_can('view_own', 'attendance_reports') &&
        !is_admin()
    ) {
        ajax_access_denied();
    }

    $staff_id = $this->input->post('staff_id');
    $from_date = $this->input->post('from_date');
    $to_date = $this->input->post('to_date');

    if (!$staff_id) {
        $this->output->set_output(json_encode(['success' => false, 'message' => 'Staff ID required']));
        return;
    }

    $data['ot_records'] = $this->nexreports_model->get_ot_details_for_modal($staff_id, $from_date, $to_date);
    $data['staff_name'] = get_staff_full_name($staff_id);
    
    // ✅ Get count summary for modal header
    $data['ot_summary'] = $this->nexreports_model->get_ot_count_summary($staff_id, $from_date, $to_date);

    $this->load->view('nexreports/attendance/modal_ot_details', $data);
}

/**
 * Get tickets details for modal (AJAX)
 */
public function get_tickets_details()
{
    if (
        !staff_can('view', 'attendance_reports') &&
        !staff_can('view_own', 'attendance_reports') &&
        !is_admin()
    ) {
        ajax_access_denied();
    }

    $staff_id = $this->input->post('staff_id');
    $from_date = $this->input->post('from_date');
    $to_date = $this->input->post('to_date');

    if (!$staff_id) {
        $this->output->set_output(json_encode(['success' => false, 'message' => 'Staff ID required']));
        return;
    }

    // Build date range array for the method
    $date_range = [];
    if ($from_date && $to_date) {
        $date_range = ['from' => $from_date, 'to' => $to_date];
    }

    $data['tickets'] = $this->nexreports_model->get_open_tickets_for_staff($staff_id, $date_range);
    $data['staff_name'] = get_staff_full_name($staff_id);

    $this->load->view('nexreports/attendance/modal_tickets_details', $data);
}

    /**
     * Leaves Overview Page
     */
    public function leaves()
    {
        // Check permissions - reuse attendance_reports permission
        if (!is_admin() && !has_permission('attendance_reports', '', 'view') && !has_permission('attendance_reports', '', 'view_own')) {
            access_denied('Leaves Overview');
        }

        // Get filters
        $year_param = $this->input->get('year') ?: (date('Y') . '-' . (date('Y') + 1));
        $staff_filter = $this->input->get('staff');
        $status_filter = $this->input->get('status') ?: 'active'; // Default to active
        
        // Parse financial year (e.g., "2025-2026" -> 2025)
        $year = (int)explode('-', $year_param)[0];
        
        // For leaves overview, ignore hierarchy - show all staff if user has permission
        $accessible_staff_ids = ''; // Empty string means all staff

        // Get leaves data
        $data['leaves_data'] = $this->nexreports_model->get_leaves_overview($year, $staff_filter, $status_filter, $accessible_staff_ids);
        
        // Get staff list for filter (all staff for dropdown)
        $data['staff_members'] = nexreports_get_accessible_staff_for_dropdown('attendance_reports', 'all');
        
        // Get available years
        $data['years'] = $this->nexreports_model->get_available_years();
        $data['selected_year'] = $year_param;
        $data['selected_staff'] = $staff_filter;
        $data['selected_status'] = $status_filter;
        
        $data['title'] = 'Leaves Overview';
        $this->load->view('nexreports/leaves/index', $data);
    }
    
    /**
     * Update Project Lead By custom field to dropdown with active staff
     * Access this once: yourdomain.com/admin/nexreports/update_project_lead_by_field
     */
    public function update_project_lead_by_field()
    {
        if (!is_admin()) {
            access_denied('Update Custom Field');
        }
        
        $prefix = db_prefix();
        
        // Get the custom field
        $this->db->where('fieldto', 'projects');
        $this->db->where('name', 'Project Lead By');
        $custom_field = $this->db->get($prefix . 'customfields')->row();
        
        if (!$custom_field) {
            echo '<h2>Error!</h2>';
            echo '<p>Custom field "Project Lead By" not found!</p>';
            echo '<p>Please create it first in Setup → Custom Fields → Projects</p>';
            return;
        }
        
        // Get all active staff
        $this->db->where('active', 1);
        $this->db->order_by('firstname', 'ASC');
        $staff = $this->db->get($prefix . 'staff')->result_array();
        
        // Build options string (format: "Full Name,Full Name,...")
        // We'll store the staff name as both value and label to hide the ID
        $options = [];
        $staff_mapping = []; // Keep track of name to ID mapping for reference
        foreach ($staff as $member) {
            $full_name = $member['firstname'] . ' ' . $member['lastname'];
            $options[] = $full_name;
            $staff_mapping[$full_name] = $member['staffid'];
        }
        $options_string = implode(',', $options);
        
        // Update the custom field
        $this->db->where('id', $custom_field->id);
        $this->db->update($prefix . 'customfields', [
            'type' => 'select',
            'options' => $options_string,
            'bs_column' => 12 // Full width
        ]);
        
        if ($this->db->affected_rows() > 0) {
            echo '<h2 style="color: green;">✓ Success!</h2>';
            echo '<p>The "Project Lead By" custom field has been updated to a dropdown with <strong>' . count($staff) . ' active staff members</strong>.</p>';
            echo '<p>Now go to <strong>Projects → Edit any project</strong> and you will see a dropdown with live search for "Project Lead By".</p>';
            echo '<hr>';
            echo '<p><strong>Staff members added:</strong></p>';
            echo '<ul>';
            foreach ($staff as $member) {
                echo '<li>' . $member['firstname'] . ' ' . $member['lastname'] . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<h2>No changes made</h2>';
            echo '<p>The field might already be configured correctly.</p>';
        }
    }
}
