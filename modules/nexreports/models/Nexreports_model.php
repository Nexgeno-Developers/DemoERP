<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Nexreports_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all project names for dropdown
     */
    public function get_all_project_names($accessible_project_ids = '')
    {
        // Reset query builder to ensure clean state
        $this->db->reset_query();
        
        $this->db->select('p.id, p.name');
        $this->db->from(db_prefix() . 'projects p');
        
        // Apply hierarchy-based project filtering
        if ($accessible_project_ids !== '') {
            if (is_array($accessible_project_ids) && !empty($accessible_project_ids)) {
                $this->db->where_in('p.id', array_map('intval', $accessible_project_ids));
            } elseif (is_array($accessible_project_ids) && empty($accessible_project_ids)) {
                // No access - return empty
                return [];
            }
        }
        
        $this->db->order_by('p.name', 'ASC');
        $query = $this->db->get();
        return $query ? $query->result_array() : [];
    }

    /**
     * Get unique customers (no duplicates)
     */
   public function get_unique_customers($accessible_project_ids = '')
{
    // Reset query builder to ensure clean state
    $this->db->reset_query();
    
    $this->db->distinct();
    $this->db->select('c.userid, c.company');
    $this->db->from(db_prefix() . 'clients c');
    $this->db->join(db_prefix() . 'projects p', 'p.clientid = c.userid', 'inner');
    $this->db->where('c.company IS NOT NULL');
    
    // Apply hierarchy-based project filtering
    if ($accessible_project_ids !== '') {
        if (is_array($accessible_project_ids) && !empty($accessible_project_ids)) {
            $this->db->where_in('p.id', array_map('intval', $accessible_project_ids));
        } elseif (is_array($accessible_project_ids) && empty($accessible_project_ids)) {
            // No access - return empty
            return [];
        }
    }
    
    $this->db->order_by('c.company', 'ASC');
    $query = $this->db->get();
    return $query ? $query->result_array() : [];
}
    public function get_unique_tags($accessible_project_ids = '')
{
    // Reset query builder to ensure clean state
    $this->db->reset_query();
    
    $this->db->distinct();
    $this->db->select('t.id, t.name');
    $this->db->from(db_prefix() . 'tags t');
    $this->db->join(db_prefix() . 'taggables tg', 'tg.tag_id = t.id', 'inner');
    $this->db->where('tg.rel_type', 'project');
    
    // Apply hierarchy-based project filtering
    if ($accessible_project_ids !== '') {
        if (is_array($accessible_project_ids) && !empty($accessible_project_ids)) {
            $this->db->where_in('tg.rel_id', array_map('intval', $accessible_project_ids));
        } elseif (is_array($accessible_project_ids) && empty($accessible_project_ids)) {
            // No access - return empty
            return [];
        }
    }
    
    $this->db->order_by('t.name', 'ASC');

    $query = $this->db->get();
    return $query ? $query->result_array() : [];
}

/**
 * Get all active custom fields for projects
 */
/**
 * Get all active custom fields for projects
 */
public function get_project_custom_fields()
{
    // Reset query builder to ensure clean state
    $this->db->reset_query();
    
    $this->db->select('cf.id, cf.fieldto, cf.name, cf.slug, cf.type');
    $this->db->from(db_prefix() . 'customfields cf');
    $this->db->where('cf.fieldto', 'projects');
    $this->db->where('cf.active', 1);
    $this->db->order_by('cf.field_order', 'ASC');
    
    $query = $this->db->get();
    return $query ? $query->result_array() : [];
}
    
/**
 * Get all custom fields for projects (for dynamic filters)
 * Excludes "completion date" field from filters
 */
public function get_custom_fields()
{
    $this->db->select('cf.id, cf.name, cf.type, cf.fieldto');
    $this->db->from(db_prefix() . 'customfields cf');
    $this->db->where('cf.fieldto', 'projects');
    $this->db->where('cf.active', 1);
    // Exclude "completion date" from filters (will still show in table)
    $this->db->where('LOWER(TRIM(cf.name)) !=', 'completion date');
    $this->db->order_by('cf.field_order', 'ASC');
    
    $query = $this->db->get();
    $fields = $query ? $query->result_array() : [];
    
    // Remove duplicate "Project Lead By" fields - keep only the first one
    $seen_names = [];
    $unique_fields = [];
    
    foreach ($fields as $field) {
        $field_name_lower = strtolower(trim($field['name']));
        
        if ($field_name_lower === 'project lead by') {
            // For "Project Lead By", only keep the first occurrence
            if (!isset($seen_names[$field_name_lower])) {
                $unique_fields[] = $field;
                $seen_names[$field_name_lower] = true;
            }
            // Skip subsequent duplicates
        } else {
            // For other fields, keep all
            $unique_fields[] = $field;
        }
    }
    
    return $unique_fields;
}

/**
 * Get unique values for a specific custom field
 */
public function get_custom_field_options($field_id)
{
    $this->db->select('value');
    $this->db->from(db_prefix() . 'customfieldsvalues');
    $this->db->where('fieldid', $field_id);
    $this->db->where('value !=', '');
    $this->db->distinct();
    $this->db->order_by('value', 'ASC');
    
    $query = $this->db->get();
    $options = [];
    
    if ($query) {
        foreach ($query->result_array() as $row) {
            if (!empty($row['value'])) {
                $options[] = $row['value'];
            }
        }
    }
    
    return $options;
}

/**
 * Get custom field value for a project
 */
public function get_custom_field_value($project_id, $field_id)
{
    // Reset query builder to ensure clean state
    $this->db->reset_query();
    
    $this->db->select('cfv.value');
    $this->db->from(db_prefix() . 'customfieldsvalues cfv');
    $this->db->where('cfv.relid', $project_id);
    $this->db->where('cfv.fieldid', $field_id);
    $this->db->where('cfv.fieldto', 'projects');
    
    $query = $this->db->get();
    
    if ($query && $query->num_rows() > 0) {
        return $query->row()->value;
    }
    
    return '';
}


    /**
     * Main function to get projects for export/display
     */
public function get_projects_for_export($filters = [], $accessible_project_ids = '')
    {
        log_message('debug', 'EXPORT FILTERS DEBUG: ' . print_r($filters, true));

        $this->db->select('
            p.id,
            p.name,
            p.clientid,
            c.company AS customer_name,
            p.start_date,
            p.deadline,
            p.status,
            p.estimated_hours,
            p.progress
        ', false);

        $this->db->from(db_prefix() . 'projects p');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = p.clientid', 'left');

        // Apply hierarchy-based project filtering FIRST (before other filters)
        // CRITICAL SECURITY: Always apply project access control
        if (is_array($accessible_project_ids)) {
            if (empty($accessible_project_ids)) {
                // No access - return empty result
                log_message('debug', 'NexReports: No accessible projects - returning empty result');
                $this->db->where('1 = 0', null, false); // Always false condition
            } else {
                // Filter by accessible project IDs
                log_message('debug', 'NexReports: Filtering by accessible projects: ' . implode(',', $accessible_project_ids));
                $this->db->where_in('p.id', array_map('intval', $accessible_project_ids));
            }
        } elseif ($accessible_project_ids === '') {
            // Empty string means all projects (admin access)
            log_message('debug', 'NexReports: Admin access - showing all projects');
            // No filtering needed
        } else {
            // Invalid value - deny access for security
            log_message('warning', 'NexReports: Invalid accessible_project_ids value - denying access');
            $this->db->where('1 = 0', null, false); // Always false condition
        }
        
        // Apply filters (custom field logic happens here)
        if (!empty($filters)) {
            $this->apply_filters($filters, $accessible_project_ids);
        }

        $this->db->group_by('p.id');
        $this->db->order_by('p.id', 'ASC');

        $query = $this->db->get();

        log_message('debug', 'SQL Query: ' . $this->db->last_query());

        if (!$query) {
            log_message('error', 'Query failed: ' . $this->db->error()['message']);
            return [];
        }

        $projects = $query->result_array();

        // Enrich data
        $custom_fields = $this->get_project_custom_fields();

        foreach ($projects as &$project) {
            $project['status_id'] = isset($project['status']) ? (int)$project['status'] : 1;
            $project['status_name'] = $this->get_status_name($project['status_id']);
            $project['tags'] = $this->get_project_tags($project['id']);
            $project['members'] = $this->get_project_members($project['id']);
            $project['logged_hours'] = $this->get_project_logged_hours($project['id']);

            // Custom fields
            $project['custom_fields'] = [];
            foreach ($custom_fields as $field) {
                $project['custom_fields'][$field['slug']] = $this->get_custom_field_value($project['id'], $field['id']);
            }
        }

        log_message('debug', 'Returning ' . count($projects) . ' projects');
        return $projects;
    }

/**
 * Apply filters to the query
 */
private function apply_filters($filters, $accessible_project_ids = '')
{
    log_message('debug', 'FILTER DEBUG: ' . print_r($filters, true));

    if (empty($filters)) {
        return;
    }

    /**
     * ===============================
     * 1Ã¯Â¸ÂÃ¢Æ’Â£ CUSTOM FIELD FILTERS
     * ===============================
     */
    if (isset($filters['custom_fields']) && !empty($filters['custom_fields'])) {
        log_message('debug', 'Custom fields found: ' . print_r($filters['custom_fields'], true));
        
        foreach ($filters['custom_fields'] as $field_id => $value) {
            if ($value === '' || $value === null) {
                continue;
            }

            log_message('debug', "Applying custom field filter: Field ID = {$field_id}, Value = {$value}");
            
            $alias = 'cfv_' . $field_id;
            $this->db->join(
                db_prefix() . 'customfieldsvalues as ' . $alias,
                $alias . '.relid = p.id AND ' . 
                $alias . '.fieldid = ' . $this->db->escape((int)$field_id) . ' AND ' . 
                $alias . '.fieldto = "projects"',
                'inner'
            );
            $this->db->where($alias . '.value', $value);
            
            log_message('debug', "Custom field join added for field {$field_id}");
        }
    }
    
    // Ã°Å¸Ââ€”Ã¯Â¸Â Project name filter
    if (!empty($filters['project_name'])) {
        $this->db->where('p.id', (int)$filters['project_name']);
    }

    // Ã°Å¸Ââ€”Ã¯Â¸Â Customer filter
    if (!empty($filters['customer'])) {
        $this->db->where('p.clientid', (int)$filters['customer']);
    }

    // ---------- CLEAN & ACCURATE DATE FILTER ----------
    if (!empty($filters['date_type']) && (!empty($filters['from_date']) || !empty($filters['to_date']))) {
        $allowed_fields = ['start_date', 'deadline', 'completion_date'];
        $date_field = in_array($filters['date_type'], $allowed_fields) ? $filters['date_type'] : 'start_date';

        // Convert user date → SQL date
        $normalize_date = function ($date) {
            if (empty($date)) {
                return null;
            }

            if (function_exists('to_sql_date')) {
                return to_sql_date($date);
            }

            $ts = strtotime($date);
            return $ts ? date('Y-m-d', $ts) : null;
        };

        $from_date = $normalize_date($filters['from_date'] ?? null);
        $to_date   = $normalize_date($filters['to_date'] ?? null);

        // ✅ FIXED: Handle completion_date as custom field
        if ($date_field === 'completion_date') {
            // Get completion date custom field ID
            $completion_field_query = $this->db->query("
                SELECT id FROM " . db_prefix() . "customfields 
                WHERE fieldto = 'projects' 
                AND active = 1 
                AND LOWER(TRIM(name)) = 'completion date'
                LIMIT 1
            ");
            
            if ($completion_field_query && $completion_field_query->num_rows() > 0) {
                $completion_field_id = $completion_field_query->row()->id;
                $alias = 'cfv_completion_date';
                
                // Join with custom field values table
                $this->db->join(
                    db_prefix() . 'customfieldsvalues as ' . $alias,
                    $alias . '.relid = p.id AND ' . 
                    $alias . '.fieldid = ' . $completion_field_id . ' AND ' . 
                    $alias . '.fieldto = "projects"',
                    'inner'
                );
                
                // Apply date filtering on custom field value
                if ($from_date && !$to_date) {
                    $this->db->where("DATE(" . $alias . ".value)", $from_date);
                } elseif (!$from_date && $to_date) {
                    $this->db->where("DATE(" . $alias . ".value)", $to_date);
                } elseif ($from_date && $to_date) {
                    $this->db->where("DATE(" . $alias . ".value) BETWEEN " . $this->db->escape($from_date) . " AND " . $this->db->escape($to_date), null, false);
                }
                
                // Ensure custom field has a value
                $this->db->where($alias . ".value IS NOT NULL", null, false);
                $this->db->where($alias . ".value != ''", null, false);
                
                log_message('debug', "COMPLETION DATE CUSTOM FIELD FILTER APPLIED: Field ID={$completion_field_id}, From={$from_date}, To={$to_date}");
            } else {
                log_message('error', 'Completion date custom field not found');
            }
        } else {
            // Handle regular database fields (start_date, deadline)
            $db_field_map = [
                'start_date' => 'start_date',
                'deadline' => 'deadline'
            ];
            
            $actual_db_field = $db_field_map[$date_field] ?? 'start_date';
            $db_field = "DATE(p.`{$actual_db_field}`)";
            
            // Apply date filtering on regular fields
            if ($from_date && !$to_date) {
                $this->db->where($db_field, $from_date);
            } elseif (!$from_date && $to_date) {
                $this->db->where($db_field, $to_date);
            } elseif ($from_date && $to_date) {
                $this->db->where("$db_field BETWEEN " . $this->db->escape($from_date) . " AND " . $this->db->escape($to_date), null, false);
            }

            // If using deadline, ignore nulls
            if ($date_field === 'deadline') {
                $this->db->where("p.`deadline` IS NOT NULL", null, false);
            }
            
            log_message('debug', "REGULAR DATE FILTER APPLIED: Field={$date_field}, From={$from_date}, To={$to_date}");
        }

        log_message('debug', "DATE FILTER APPLIED (STRICT): Field={$date_field}, From={$from_date}, To={$to_date}");
    }
    // ---------- END DATE FILTER ----------

    // Ã°Å¸Å½Å¡Ã¯Â¸Â Normalize status IDs (fix reversed mapping)
    if (!empty($filters['status'])) {
        // Convert string or mixed input to array
        $filters['status'] = is_array($filters['status']) ? $filters['status'] : [$filters['status']];

        foreach ($filters['status'] as &$status_id) {
            // Fix reversed mapping (if any frontend confusion)
            if ($status_id == 3) {
                // In case frontend sends 3 for Finished
                $status_id = 4;
            } elseif ($status_id == 4) {
                // In case frontend sends 4 for Cancelled
                $status_id = 3;
            }
        }
        unset($status_id); // break reference
    }

    // Ã°Å¸Ââ€”Ã¯Â¸Â Status filter (0Ã¢â‚¬â€œ4 mapping)
    if (!empty($filters['status'])) {
        // Ensure array
        $status_ids = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
        $status_ids = array_map('intval', $status_ids);

        // Validate valid range 0Ã¢â‚¬â€œ4
        $valid_status = array_intersect($status_ids, [0, 1, 2, 3, 4]);
        if (!empty($valid_status)) {
            $this->db->where_in('p.status', $valid_status);
        }
    }

    // Ã°Å¸Ââ€”Ã¯Â¸Â Members filter
    if (!empty($filters['members']) && is_array($filters['members'])) {
        $member_ids = array_map('intval', $filters['members']);
        if (!empty($member_ids)) {
            $member_count = count($member_ids);
            $member_ids_str = implode(',', $member_ids);
            
            if ($member_count === 1) {
                // Single member: Show all projects where this member exists
                $this->db->where("p.id IN (
                    SELECT project_id 
                    FROM " . db_prefix() . "project_members 
                    WHERE staff_id = " . $member_ids[0] . "
                )");
            } else {
                // Multiple members: Show projects that have ALL selected members
                $this->db->where("p.id IN (
                    SELECT project_id 
                    FROM " . db_prefix() . "project_members 
                    WHERE staff_id IN (" . $member_ids_str . ")
                    GROUP BY project_id
                    HAVING COUNT(DISTINCT staff_id) = " . $member_count . "
                )");
            }
        }
    }

    // Ã°Å¸Ââ€”Ã¯Â¸Â Tags filter
    if (!empty($filters['tags']) && is_array($filters['tags'])) {
        $tag_ids = array_map('intval', $filters['tags']);
        if (!empty($tag_ids)) {
            $this->db->where('p.id IN (
                SELECT rel_id 
                FROM ' . db_prefix() . 'taggables 
                WHERE rel_type = "project" AND tag_id IN (' . implode(',', $tag_ids) . ')
            )', null, false);
        }
    }
}
    /**
     * Get status name by ID
     */
    private function get_status_name($status_id)
    {
        $statuses = [
            0 => 'Not Started',
            1 => 'In Progress',
            2 => 'On Hold',
            3 => 'Cancelled',
            4 => 'Finished'
        ];
        return isset($statuses[$status_id]) ? $statuses[$status_id] : 'Unknown';
    }

    /**
     * Get project tags
     */
    private function get_project_tags($project_id)
{
    $this->db->select('GROUP_CONCAT(t.name SEPARATOR ", ") as tags', false);
    $this->db->from(db_prefix() . 'taggables tg');
    $this->db->join(db_prefix() . 'tags t', 't.id = tg.tag_id');
    $this->db->where('tg.rel_id', $project_id);
    $this->db->where('tg.rel_type', 'project');
    
    $query = $this->db->get();
    
    if ($query && $query->num_rows() > 0) {
        return $query->row()->tags ?? '';
    }
    
    return '';
}

    /**
     * Get project members
     */
    private function get_project_members($project_id)
{
    $this->db->select('st.staffid, st.firstname, st.lastname, st.profile_image');
    $this->db->from(db_prefix() . 'project_members pm');
    $this->db->join(db_prefix() . 'staff st', 'st.staffid = pm.staff_id', 'inner');
    $this->db->where('pm.project_id', $project_id);
    $query = $this->db->get();
    
    if (!$query) {
        return [];
    }

    return $query->result_array();
}

    /**
     * Get logged hours for a project
     */
    private function get_project_logged_hours($project_id)
{
    // Perfex stores timestamps as Unix timestamps (integers), not datetime
    $this->db->select('SUM((tt.end_time - tt.start_time) / 3600) AS total_hours', false);
    $this->db->from(db_prefix() . 'taskstimers tt');
    $this->db->join(db_prefix() . 'tasks t', 't.id = tt.task_id', 'inner');
    $this->db->where('t.rel_id', $project_id);
    $this->db->where('t.rel_type', 'project');
    $this->db->where('tt.end_time IS NOT NULL');
    $this->db->where('tt.start_time IS NOT NULL');
    $this->db->where('tt.end_time >', 0);
    
    $query = $this->db->get();
    
    if (!$query || $query->num_rows() == 0) {
        return 0;
    }

    $result = $query->row_array();
    $hours = !empty($result['total_hours']) ? (float)$result['total_hours'] : 0;
    
    return $hours;
}

    /**
     * Get project status counts for summary
     */
    public function get_project_status_counts()
    {
        $statuses = [
            0 => 'not_started',
            1 => 'in_progress',
            2 => 'on_hold',
            3 => 'cancelled',
            4 => 'finished',
        ];

        $counts = [];
        foreach ($statuses as $id => $name) {
            $this->db->where('status', $id);
            $counts[$name] = $this->db->count_all_results(db_prefix() . 'projects');
        }

        return $counts;
    }
    // Ã¢Å“â€¦ Get all active custom fields for 'projects' to show as filters
public function get_project_custom_fields_for_filters()
{
    // Reset query builder to ensure clean state
    $this->db->reset_query();
    
    $this->db->select('cf.id, cf.name, cf.type, cf.options');
    $this->db->from(db_prefix() . 'customfields cf');
    $this->db->where('cf.fieldto', 'projects');
    $this->db->where('cf.active', 1);
    $this->db->order_by('cf.field_order', 'ASC');
    
    $query = $this->db->get();
    
    if (!$query) {
        log_message('error', 'Failed to get project custom fields');
        return [];
    }
    
    return $query->result_array();
}
                                         //attendance report functions

/**
 * Get attendance summary by staff member
 */
public function get_attendance_summary_by_staff($filters = [], $can_view_all = false, $accessible_staff_ids = [])
{
    $this->db->select('
    s.staffid as staff_id,
    CONCAT(s.firstname, " ", s.lastname) as staff_name,
    s.email as staff_email,
    s.active as is_active
    ');
    
    $this->db->from(db_prefix() . 'staff s');
    
    // Apply hierarchy-based staff filtering
    // If accessible_staff_ids is empty array, show all (admin/view permission)
    // Otherwise filter by accessible staff IDs
    if (!empty($accessible_staff_ids)) {
        if (count($accessible_staff_ids) == 1) {
            $this->db->where('s.staffid', (int)$accessible_staff_ids[0]);
        } else {
            $this->db->where_in('s.staffid', array_map('intval', $accessible_staff_ids));
        }
    }
    // Legacy fallback: If accessible_staff_ids not provided but can_view_all is false
    elseif (!$can_view_all) {
        $this->db->where('s.staffid', get_staff_user_id());
    }

    // Apply active status filter
    if (isset($filters['active_status']) && $filters['active_status'] !== 'all') {
        $this->db->where('s.active', $filters['active_status'] === 'active' ? 1 : 0);
    }
    
    // Apply staff filter from dropdown (allow for both can_view_all and restricted users)
    if (!empty($filters['staff_member'])) {
        $staff_filter_ids = is_array($filters['staff_member']) 
            ? $filters['staff_member'] 
            : [$filters['staff_member']];
        
        // For restricted users, ensure they can only filter within their accessible staff
        if (!empty($accessible_staff_ids)) {
            // Intersect with accessible staff IDs to maintain security
            $staff_filter_ids = array_intersect($staff_filter_ids, $accessible_staff_ids);
        }
        
        if (!empty($staff_filter_ids)) {
            $this->db->where_in('s.staffid', array_map('intval', $staff_filter_ids));
        }
    }

    
    $this->db->order_by('staff_name', 'ASC');
    $staff_list = $this->db->get()->result_array();
    
    $results = [];
    
    foreach ($staff_list as $staff) {
        $attendance = $this->get_staff_attendance_data($staff['staff_id'], $filters);
        
        $results[] = array_merge([
            'staff_id' => $staff['staff_id'],
            'staff_name' => $staff['staff_name'],
            'staff_email' => $staff['staff_email'],
            'is_active' => (int)$staff['is_active']
        ], $attendance);
    }
    
    return $results;
}
/**
 * Get complete attendance data for a staff member
 */
private function get_staff_attendance_data($staff_id, $filters = [])
{
    $date_range = $this->get_date_range_from_filters($filters);
    
    // ✅ Get ONLY task timer data - this is what shows in Timesheet Hours column
    $task_timer_data = $this->get_task_timer_hours_detailed($staff_id, $date_range);
    
    // Get check-in/out hours (try to mirror payroll's timesheet calculation first)
    $checkin_hours = $this->get_payroll_timesheet_hours($staff_id, $date_range);
    if ($checkin_hours === null) {
        // Fallback to legacy check-in/out calculation
        $checkin_hours = $this->get_checkin_checkout_hours($staff_id, $date_range);
    }
    
    // Get OT data
    $ot_data = $this->get_ot_hours_and_status($staff_id, $date_range);
    $ot_stats = $this->get_ot_statistics($staff_id, $date_range);
    
    $leave_data = $this->get_leave_data_from_requisition($staff_id, $date_range);
    // If payroll/timesheets integration is on, use payroll's paid-leave hours (from timesheets_timesheet) to mirror payslip
    $payroll_leave_hours = $this->get_payroll_paid_leave_hours($staff_id, $date_range);
    if ($payroll_leave_hours !== null) {
        $leave_data = [
            'leave_hours' => round($payroll_leave_hours, 2),
            'status'      => $payroll_leave_hours > 0 ? 'approved' : 'none'
        ];
    }
    // ✅ get year from selected period (VERY IMPORTANT)
    $selected_year = date('Y', strtotime($date_range['from']));
    $leave_stats = $this->get_leave_statistics($staff_id, $date_range, $selected_year);

    
    // Calculate working days (for reference/fallback)
    $working_days_in_period = $this->calculate_working_days_in_period($date_range);
    $total_hours_in_period = $working_days_in_period * 8;
    
    // Actual work hours = from shift assignments in tblwork_shift_detail and tblshift_type
    // This fetches actual shift data and calculates: (work_end - work_start) - (lunch_end - lunch_start)
    $actual_work_hours = $this->get_actual_work_hours_from_shifts($staff_id, $date_range);
    
    // ✅ FIXED: If no shift assignments found, show 0 instead of fallback calculation
    // This ensures employees without shift data show 0 actual work hours
    if ($actual_work_hours == 0) {
        // Keep as 0 - don't use working days fallback
        // $actual_work_hours = $working_days_in_period * 8; // REMOVED
        log_message('debug', "No shifts found for staff {$staff_id}, showing 0 actual work hours");
    }
    
    // Safety check: Cap actual work hours to reasonable maximum based on period
    // Calculate days in period
    $days_in_period = (strtotime($date_range['to']) - strtotime($date_range['from'])) / 86400 + 1;
    // Maximum reasonable: 12 hours per day (for very long shifts), but cap at 300 hours for monthly periods
    $max_reasonable_hours = min($days_in_period * 12, 300);
    
    // If calculated hours exceed reasonable maximum, use checkin hours or set to 0
    if ($actual_work_hours > $max_reasonable_hours) {
        // Use checkin hours if available and reasonable, otherwise set to 0
        if ($checkin_hours > 0 && $checkin_hours <= $max_reasonable_hours) {
            $actual_work_hours = $checkin_hours;
            log_message('debug', "Using checkin hours as fallback for staff {$staff_id}: {$actual_work_hours}");
        } else {
            // ✅ FIXED: Set to 0 instead of working days calculation
            $actual_work_hours = 0;
            log_message('debug', "Setting actual work hours to 0 for staff {$staff_id} due to no valid shift data");
        }
    }
    
    // ✅ Remove OT from check-in hours so we don't double count (check-in logs often include OT duration)
    $regular_checkin_hours = max(0, $checkin_hours - $ot_data['ot_hours']);

    // ✅ Payable hours = regular checkin hours + OT + leave
    $payable_hours = $regular_checkin_hours;

    // Add approved OT
    if ($ot_data['status'] === 'approved') {
        $payable_hours += $ot_data['ot_hours'];
    } elseif ($ot_data['status'] === 'has_records') {
        // Mixed statuses but ot_hours already contains only approved portion
        $payable_hours += $ot_data['ot_hours'];
    }

    // Add approved paid leave
    if ($leave_data['status'] === 'approved') {
        $payable_hours += $leave_data['leave_hours'];
    }
    
    // Get salary data
    $monthly_salary = $this->get_staff_contract_salary($staff_id);
    
    // ✅ Calculate reflectable salary: (Base Salary / Actual Work Hours) × Payable Hours
    $reflectable_salary = 0;
    if ($actual_work_hours > 0) {
        $per_hour_salary = $monthly_salary / $actual_work_hours;
        $reflectable_salary = $per_hour_salary * $payable_hours;
    }
    
    return [
        // ✅ Timesheet hours = ONLY task timer hours (converted from Unix timestamps)
        'timesheet_hours'        => round($task_timer_data['total_hours'], 1),
        'task_timer_hours'       => round($task_timer_data['total_hours'], 1),
        'task_timer_count'       => $task_timer_data['count'],
        'task_timer_breakdown'   => $task_timer_data['breakdown'],
        // Show regular hours only (OT removed)
        'checkin_hours'          => round($regular_checkin_hours, 1),
        'ot_hours'               => round($ot_data['ot_hours'], 1),
        'ot_status'              => $ot_data['status'],
        'ot_statistics'          => $ot_stats['display'],
        'paid_leave_hours'       => round($leave_data['leave_hours'], 1),
        'leave_status'           => $leave_data['status'],
        'leave_statistics'       => $leave_stats['display'],
        'tickets_count'          => $this->get_open_tickets_count($staff_id, $date_range),
        'tickets_statistics'     => $this->get_tickets_statistics($staff_id, $date_range),
        'payable_hours'          => round($payable_hours, 1),
        'actual_work_hours'      => round($actual_work_hours, 1),
        'base_salary'            => round($monthly_salary, 2),
        'reflectable_salary'     => round($reflectable_salary, 2),
        'working_days_in_period' => $working_days_in_period,            
        'total_hours_in_period'  => $total_hours_in_period
    ];
}

/**
 * Get task timer hours from tbltaskstimers ONLY
 * Converts Unix timestamps to hours
 */
private function get_task_timer_hours_detailed($staff_id, $date_range)
{
    $from_ts = strtotime($date_range['from'] . ' 00:00:00');
    $to_ts = strtotime($date_range['to'] . ' 23:59:59');
    
    $this->db->select('id, task_id, start_time, end_time, note');
    $this->db->from(db_prefix() . 'taskstimers');
    $this->db->where('staff_id', $staff_id);
    $this->db->where('end_time IS NOT NULL');
    $this->db->where('end_time !=', '');
    $this->db->where('start_time >=', $from_ts);
    $this->db->where('start_time <=', $to_ts);
    $this->db->order_by('start_time', 'DESC');
    
    $timers = $this->db->get()->result_array();
    
    $breakdown = [];
    $total_seconds = 0;
    
    foreach ($timers as $timer) {
        $start = (int)$timer['start_time'];
        $end = (int)$timer['end_time'];
        
        if ($end > $start) {
            $duration_seconds = $end - $start;
            $duration_hours = round($duration_seconds / 3600, 2);
            
            $total_seconds += $duration_seconds;
            
            $breakdown[] = [
                'id' => $timer['id'],
                'task_id' => $timer['task_id'],
                'start_time' => $start,
                'end_time' => $end,
                'start_datetime' => date('Y-m-d H:i:s', $start),
                'end_datetime' => date('Y-m-d H:i:s', $end),
                'duration_seconds' => $duration_seconds,
                'duration_hours' => $duration_hours,
                'duration_formatted' => $this->seconds_to_time_format($duration_seconds),
                'note' => $timer['note']
            ];
        }
    }
    
    $total_hours = round($total_seconds / 3600, 2);
    
    return [
        'total_hours' => $total_hours,
        'total_formatted' => $this->seconds_to_time_format($total_seconds),
        'breakdown' => $breakdown,
        'count' => count($breakdown)
    ];
}

/**
 * Convert seconds to HH:MM:SS format
 */
private function seconds_to_time_format($seconds)
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}

/**
 * Get check-in/out hours from tblcheck_in_out
 * Uses same logic as Timesheets module: subtracts lunch break and caps hours within shift boundaries
 */
private function get_checkin_checkout_hours($staff_id, $date_range)
{
    $prefix = db_prefix();
    
    // Get all check-in/out records for the period
    $this->db->select('DATE(date) as work_date, type_check, date');
    $this->db->from($prefix . 'check_in_out');
    $this->db->where('staff_id', $staff_id);
    $this->db->where('DATE(date) >=', $date_range['from']);
    $this->db->where('DATE(date) <=', $date_range['to']);
    $this->db->order_by('date', 'ASC');
    
    $rows = $this->db->get()->result_array();
    
    // Group by date
    $daily = [];
    foreach ($rows as $row) {
        $day = $row['work_date'];
        if (!isset($daily[$day])) {
            $daily[$day] = ['in' => null, 'out' => null, 'in_time' => null, 'out_time' => null];
        }
        
        if ($row['type_check'] == 1) {
            $daily[$day]['in'] = strtotime($row['date']);
            $daily[$day]['in_time'] = $row['date'];
        }
        
        if ($row['type_check'] == 2) {
            $daily[$day]['out'] = strtotime($row['date']);
            $daily[$day]['out_time'] = $row['date'];
        }
    }
    
    $total_hours = 0;
    
    // Calculate hours for each day
    foreach ($daily as $date => $times) {
        if (!$times['in'] || !$times['out'] || $times['out'] <= $times['in']) {
            continue;
        }
        
        // Get shift assignments for this staff on this date
        $this->db->select('wsd.shift_id');
        $this->db->from($prefix . 'work_shift_detail wsd');
        $this->db->where('wsd.staff_id', $staff_id);
        $this->db->where('wsd.date', $date);
        $shifts = $this->db->get()->result_array();
        
        if (empty($shifts)) {
            // No shift assigned - use simple calculation (check-out minus check-in)
            $hours = ($times['out'] - $times['in']) / 3600;
            $total_hours += $hours;
            continue;
        }
        
        // Process each shift (staff might have multiple shifts in a day)
        $day_hours = 0;
        
        foreach ($shifts as $shift) {
            // Get shift type details
            $this->db->select('time_start_work, time_end_work, start_lunch_break_time, end_lunch_break_time');
            $this->db->from($prefix . 'shift_type');
            $this->db->where('id', $shift['shift_id']);
            $shift_type = $this->db->get()->row();
            
            if (!$shift_type) {
                continue;
            }
            
            // Extract time components only (ignore date part)
            $time_in = strtotime(date('H:i:s', $times['in']));
            $time_out = strtotime(date('H:i:s', $times['out']));
            
            $start_work = strtotime(date('H:i:s', strtotime($shift_type->time_start_work)));
            $end_work = strtotime(date('H:i:s', strtotime($shift_type->time_end_work)));
            $start_lunch = strtotime(date('H:i:s', strtotime($shift_type->start_lunch_break_time)));
            $end_lunch = strtotime(date('H:i:s', strtotime($shift_type->end_lunch_break_time)));
            
            // Cap check-in time to shift start (if checked in early)
            if ($time_in < $start_work && $time_out > $start_work) {
                $time_in = $start_work;
            }
            
            // Cap check-out time to shift end (if checked out late)
            if ($time_out > $end_work && $time_in < $end_work) {
                $time_out = $end_work;
            }
            
            // Skip if check-out is before shift start
            if ($time_out < $start_work) {
                continue;
            }
            
            // Calculate work hours for this shift
            $shift_hours = ($time_out - $time_in) / 3600;

            // Deduct only the overlapping portion of lunch (avoids over‑deduction when staff arrives after lunch)
            $lunch_overlap_seconds = 0;
            if ($time_out > $start_lunch && $time_in < $end_lunch) {
                $lunch_overlap_seconds = min($time_out, $end_lunch) - max($time_in, $start_lunch);
            }

            $shift_hours -= ($lunch_overlap_seconds / 3600);
            $day_hours += max(0, $shift_hours);
        }
        
        // Subtracted lunch inside shift loop; just accumulate
        $total_hours += $day_hours;
    }
    
    return round($total_hours, 2);
}

/**
 * Mirror HR Payroll timesheet hour calculation (used for payslips)
 * Returns null if payroll/timesheets integration is not available.
 */
private function get_payroll_timesheet_hours($staff_id, $date_range)
{
    // Payroll helpers may not exist in all installs
    if (!function_exists('hr_payroll_get_status_modules') || !function_exists('get_hr_payroll_option')) {
        return null;
    }

    // Ensure timesheets module is integrated with payroll
    if (!hr_payroll_get_status_modules('timesheets') || (int)get_hr_payroll_option('integrated_timesheets') !== 1) {
        return null;
    }

    // Types that payroll counts as actual workday
    $actual_workday_types = new_explode(',', get_hr_payroll_option('integration_actual_workday'));
    if (empty($actual_workday_types)) {
        return null;
    }

    // Sum value field (stored in hours) for the period
    $this->db->select_sum('value', 'total_hours');
    $this->db->from(db_prefix() . 'timesheets_timesheet');
    $this->db->where('staff_id', $staff_id);
    $this->db->where_in('type', $actual_workday_types);
    $this->db->where('date_work >=', $date_range['from']);
    $this->db->where('date_work <=', $date_range['to']);

    $row = $this->db->get()->row();

    if (!$row) {
        return null;
    }

    $hours = (float)($row->total_hours ?? 0);

    // If no data, return null to allow fallback
    if ($hours == 0) {
        return null;
    }

    return round($hours, 2);
}

/**
 * Mirror payroll paid leave hours from timesheets_timesheet (types in integration_paid_leave)
 * Returns null if payroll integration not active.
 */
private function get_payroll_paid_leave_hours($staff_id, $date_range)
{
    if (!function_exists('hr_payroll_get_status_modules') || !function_exists('get_hr_payroll_option')) {
        return null;
    }
    if (!hr_payroll_get_status_modules('timesheets') || (int)get_hr_payroll_option('integrated_timesheets') !== 1) {
        return null;
    }

    $paid_leave_types = new_explode(',', get_hr_payroll_option('integration_paid_leave'));
    if (empty($paid_leave_types)) {
        return null;
    }

    $this->db->select_sum('value', 'total_paid_leave');
    $this->db->from(db_prefix() . 'timesheets_timesheet');
    $this->db->where('staff_id', $staff_id);
    $this->db->where_in('type', $paid_leave_types);
    $this->db->where('date_work >=', $date_range['from']);
    $this->db->where('date_work <=', $date_range['to']);

    $row = $this->db->get()->row();
    if (!$row) {
        return null;
    }

    return (float) ($row->total_paid_leave ?? 0);
}

/**
 * Calculate working days in the selected period
 */
private function calculate_working_days_in_period($date_range)
{
    $start = strtotime($date_range['from']);
    $end = strtotime($date_range['to']);
    
    $working_days = 0;
    
    for ($date = $start; $date <= $end; $date = strtotime('+1 day', $date)) {
        $day_of_week = date('N', $date);
        $day_of_month = date('j', $date);
        $week_of_month = ceil($day_of_month / 7);
        
        // Skip Sundays
        if ($day_of_week == 7) {
            continue;
        }
        
        // Skip 2nd and 4th Saturdays
        if ($day_of_week == 6) {
            if ($week_of_month == 2 || $week_of_month == 4) {
                continue;
            }
        }
        
        $working_days++;
    }
    
    return $working_days;
}

/**
 * Get OT hours and status
 */
private function get_ot_hours_and_status($staff_id, $date_range)
{
    $this->db->select('status, timekeeping_value');
    $this->db->from(db_prefix() . 'timesheets_additional_timesheet');
    $this->db->where('creator', $staff_id);
    $this->db->where('additional_day >=', $date_range['from']);
    $this->db->where('additional_day <=', $date_range['to']);
    
    $query = $this->db->get();
    
    if ($query->num_rows() == 0) {
        return ['ot_hours' => 0, 'status' => 'no_ot'];
    }
    
    $total_ot_hours = 0;
    $approved_count = 0;
    $pending_count = 0;
    $rejected_count = 0;
    
    foreach ($query->result_array() as $row) {
        $status = (int)$row['status'];
        
        if ($status === 1) {
            // Approved - add hours
            $total_ot_hours += (float)$row['timekeeping_value'];
            $approved_count++;
        } elseif ($status === 2) {
            // Rejected
            $rejected_count++;
        } else {
            // Pending (0 or other)
            $pending_count++;
        }
    }
    
    // Determine overall status - return 'has_records' if there are any records
    $overall_status = 'has_records'; // This ensures badges will show
    if ($approved_count > 0 && $pending_count == 0 && $rejected_count == 0) {
        $overall_status = 'approved';
    } elseif ($pending_count > 0 && $approved_count == 0 && $rejected_count == 0) {
        $overall_status = 'pending';
    } elseif ($rejected_count > 0 && $approved_count == 0 && $pending_count == 0) {
        $overall_status = 'rejected';
    }
    
    return [
        'ot_hours' => $total_ot_hours, 
        'status' => $overall_status
    ];
}

/**
 * Get OT statistics (approved/total)
 */
private function get_ot_statistics($staff_id, $date_range)
{
    $this->db->select('status, timekeeping_value'); // ✅ CORRECT COLUMN
    $this->db->from(db_prefix() . 'timesheets_additional_timesheet');
    $this->db->where('creator', $staff_id);
    $this->db->where('additional_day >=', $date_range['from']);
    $this->db->where('additional_day <=', $date_range['to']);

    $records = $this->db->get()->result_array();

    $approved = 0;
    $total = count($records); // ✅ FIXED: Count ALL records (approved, pending, rejected)
    $total_hours = 0;

    foreach ($records as $record) {
        // only approved adds hours
        if ((int)$record['status'] === 1) {
            $approved++;
            $total_hours += (float) ($record['timekeeping_value'] ?? 0);
        }
    }

    return [
        'hours'   => round($total_hours, 2),
        'display' => $total > 0 ? "{$approved}/{$total}" : '-',
    ];
}
private function get_shift_work_hours($shift_id)
{
    $this->db->select('time_start_work, time_end_work, start_lunch_break_time, end_lunch_break_time');
    $this->db->from(db_prefix() . 'shift_type');
    $this->db->where('id', $shift_id);

    $shift = $this->db->get()->row();

    if (!$shift) {
        return 8; // fallback
    }

    $start = strtotime($shift->time_start_work);
    $end   = strtotime($shift->time_end_work);

    if ($end < $start) {
        $end += 24 * 3600; // night shift
    }

    $seconds = $end - $start;

    if ($shift->start_lunch_break_time && $shift->end_lunch_break_time) {
        $lunch_start = strtotime($shift->start_lunch_break_time);
        $lunch_end   = strtotime($shift->end_lunch_break_time);

        if ($lunch_end < $lunch_start) {
            $lunch_end += 24 * 3600;
        }

        $seconds -= ($lunch_end - $lunch_start);
    }

    return round($seconds / 3600, 2);
}

/**
 * Get leave data from requisition table
 */
// IMPORTANT: PL is calculated based on start_time month,
// not overlap with date range
private function get_leave_data_from_requisition($staff_id, $date_range)
{
    $this->db->select('
        l.status,
        l.start_time,
        l.number_of_leaving_day,
        wsd.shift_id
    ');
    $this->db->from(db_prefix() . 'timesheets_requisition_leave l');

    // ✅ JOIN shift detail to get shift_id
    $this->db->join(
        db_prefix() . 'work_shift_detail wsd',
        'wsd.staff_id = l.staff_id 
         AND wsd.date = DATE(l.start_time)',
        'left'
    );

    $this->db->where('l.staff_id', $staff_id);

    // ✅ month / period decided ONLY by start_time
    $this->db->where('DATE(l.start_time) >=', $date_range['from']);
    $this->db->where('DATE(l.start_time) <=', $date_range['to']);

    $query = $this->db->get();

    if ($query->num_rows() === 0) {
        return ['leave_hours' => 0, 'status' => 'none'];
    }

    $total_hours = 0;
    $approved_count = 0;
    $pending_count = 0;
    $rejected_count = 0;

    foreach ($query->result_array() as $row) {
        $status = (int)$row['status'];

        if ($status === 1) {
            // Approved - calculate hours
            $shift_hours = !empty($row['shift_id'])
                ? $this->get_shift_work_hours($row['shift_id'])
                : 8; // fallback

            $days = (float)$row['number_of_leaving_day'];
            $total_hours += $shift_hours * $days;
            $approved_count++;
        } elseif ($status === 2) {
            // Rejected
            $rejected_count++;
        } else {
            // Pending (0 or other)
            $pending_count++;
        }
    }

    // Determine overall status - return 'has_records' if there are any records
    $overall_status = 'has_records'; // This ensures badges will show
    if ($approved_count > 0 && $pending_count == 0 && $rejected_count == 0) {
        $overall_status = 'approved';
    } elseif ($pending_count > 0 && $approved_count == 0 && $rejected_count == 0) {
        $overall_status = 'pending';
    } elseif ($rejected_count > 0 && $approved_count == 0 && $pending_count == 0) {
        $overall_status = 'rejected';
    }

    return [
        'leave_hours' => round($total_hours, 2),
        'status' => $overall_status
    ];
}

/**
 * Get leave statistics (approved/total)
 */
private function get_leave_statistics($staff_id, $date_range, $year)
{
    $this->db->select('status');
    $this->db->from(db_prefix() . 'timesheets_requisition_leave');
    $this->db->where('staff_id', $staff_id);
    // ✅ FIXED: Use same date range logic as get_leave_data_from_requisition
    $this->db->where('DATE(start_time) >=', $date_range['from']);
    $this->db->where('DATE(start_time) <=', $date_range['to']);

    $records = $this->db->get()->result_array();

    $approved = 0;
    $total = count($records);

    foreach ($records as $record) {
        if ($record['status'] == '1') {
            $approved++;
        }
    }
    return [
        'approved' => $approved,
        'total' => $total,
        'display' => $total > 0 ? "{$approved}/{$total}" : '-',
    ];
}

/**
 * Get staff hourly rate
 */
private function get_staff_hourly_rate($staff_id)
{
    $this->db->select('hourly_rate');
    $this->db->from(db_prefix() . 'staff');
    $this->db->where('staffid', $staff_id);
    $staff = $this->db->get()->row();
    
    if ($staff && $staff->hourly_rate > 0) {
        return (float)$staff->hourly_rate;
    }
    
    $monthly_salary = $this->get_staff_contract_salary($staff_id);
    return round($monthly_salary / 176, 2);
}

/**
 * Get staff salary from HR contract
 */
private function get_staff_contract_salary($staff_id)
{
    $this->db->select('id_contract');
    $this->db->from(db_prefix() . 'hr_staff_contract');
    $this->db->where('staff', $staff_id);
    $this->db->where('contract_status', 'valid');
    $this->db->order_by('start_valid', 'DESC');
    $this->db->limit(1);

    $contract = $this->db->get()->row();

    if (!$contract) {
        // fallback
        $this->db->select('hourly_rate');
        $this->db->from(db_prefix() . 'staff');
        $this->db->where('staffid', $staff_id);
        $staff = $this->db->get()->row();

        return $staff ? ((float)$staff->hourly_rate * 160) : 0;
    }

    // ✅ Salary + Allowances
    $this->db->select_sum('rel_value', 'total_salary');
    $this->db->from(db_prefix() . 'hr_staff_contract_detail');
    $this->db->where('staff_contract_id', $contract->id_contract);
    $this->db->where_in('type', ['salary', 'allowance']);

    $salary_data = $this->db->get()->row();

    return $salary_data ? (float)$salary_data->total_salary : 0;
}

/**
 * Convert period to date range
 */
private function period_to_date_range($period)
{
    $today = date('Y-m-d');
    
    switch ($period) {
        case 'today':
            return ['from' => $today, 'to' => $today];
        case 'this_week':
            return [
                'from' => date('Y-m-d', strtotime('monday this week')),
                'to' => date('Y-m-d', strtotime('sunday this week'))
            ];
        case 'last_week':
            return [
                'from' => date('Y-m-d', strtotime('monday last week')),
                'to' => date('Y-m-d', strtotime('sunday last week'))
            ];
        case 'this_month':
            return ['from' => date('Y-m-01'), 'to' => date('Y-m-t')];
        case 'last_month':
            return [
                'from' => date('Y-m-01', strtotime('first day of last month')),
                'to' => date('Y-m-t', strtotime('last day of last month'))
            ];
        case 'this_year':
            return ['from' => date('Y-01-01'), 'to' => date('Y-12-31')];
        case 'last_year':
            $year = date('Y') - 1;
            return ['from' => "{$year}-01-01", 'to' => "{$year}-12-31"];
        case 'last_3_months':
            return ['from' => date('Y-m-01', strtotime('-3 months')), 'to' => $today];
        case 'last_6_months':
            return ['from' => date('Y-m-01', strtotime('-6 months')), 'to' => $today];
        case 'last_12_months':
            return ['from' => date('Y-m-01', strtotime('-12 months')), 'to' => $today];
        default:
            return ['from' => date('Y-m-01'), 'to' => date('Y-m-t')];
    }
}
/**
 * Get actual work hours from shift assignments
 * Fetches data from tblwork_shift_detail and tblshift_type
 * Calculates: (time_end_work - time_start_work) - (end_lunch_break_time - start_lunch_break_time)
 * Sums all hours for the selected month/period
 */
private function get_actual_work_hours_from_shifts($staff_id, $date_range)
{
    $prefix = db_prefix();
    
    // Fetch shift assignments with shift type details
    // Join tblwork_shift_detail with tblshift_type to get shift timing details
    // Use DISTINCT on date+shift_id to prevent double counting if there are duplicate entries
    $this->db->select('
        DISTINCT wsd.date,
        wsd.shift_id,
        st.time_start_work,
        st.time_end_work,
        st.start_lunch_break_time,
        st.end_lunch_break_time
    ', false);
    $this->db->from($prefix . 'work_shift_detail wsd');
    $this->db->join($prefix . 'shift_type st', 'st.id = wsd.shift_id', 'left');
    $this->db->where('wsd.staff_id', $staff_id);
    $this->db->where('wsd.date >=', $date_range['from']);
    $this->db->where('wsd.date <=', $date_range['to']);
    $this->db->where('wsd.shift_id IS NOT NULL');
    $this->db->where('wsd.shift_id >', 0);
    $this->db->order_by('wsd.date', 'ASC');
    
    $query = $this->db->get();
    $shift_assignments = $query->result_array();
    
    if (empty($shift_assignments)) {
        // If no shift assignments found, return 0
        return 0;
    }
    
    // Work entirely in seconds (integers) to avoid floating point precision errors
    // This ensures accurate summation across all shifts
    $total_seconds = 0;
    $processed_dates = []; // Track dates to prevent double counting same day
    
    // Calculate seconds for each shift assignment
    foreach ($shift_assignments as $shift) {
        // Skip if shift type data is missing
        if (empty($shift['time_start_work']) || empty($shift['time_end_work'])) {
            continue;
        }
        
        // Prevent double counting: if same date+shift_id already processed, skip
        $date_key = $shift['date'] . '_' . $shift['shift_id'];
        if (isset($processed_dates[$date_key])) {
            continue; // Already counted this shift for this date
        }
        $processed_dates[$date_key] = true;
        
        // Parse work start and end times
        $work_start = strtotime($shift['time_start_work']);
        $work_end = strtotime($shift['time_end_work']);
        
        // Validate parsed times
        if ($work_start === false || $work_end === false) {
            continue; // Skip invalid time formats
        }
        
        // Handle overnight shifts (e.g., Night Shift 00:00 - 09:30)
        if ($work_end < $work_start) {
            $work_end += (24 * 3600); // Add 24 hours
        }
        
        // Calculate total work time in seconds
        $total_work_seconds = $work_end - $work_start;
        
        // Safety check: if shift is more than 24 hours, something is wrong
        if ($total_work_seconds > (24 * 3600)) {
            continue; // Skip invalid shift data
        }
        
        // Calculate lunch break duration if lunch times are provided
        $lunch_duration_seconds = 0;
        if (!empty($shift['start_lunch_break_time']) && !empty($shift['end_lunch_break_time'])) {
            $lunch_start = strtotime($shift['start_lunch_break_time']);
            $lunch_end = strtotime($shift['end_lunch_break_time']);
            
            // Validate lunch times
            if ($lunch_start !== false && $lunch_end !== false) {
                // Handle overnight lunch breaks
                if ($lunch_end < $lunch_start) {
                    $lunch_end += (24 * 3600); // Add 24 hours
                }
                
                $lunch_duration_seconds = $lunch_end - $lunch_start;
                
                // Safety check: lunch break shouldn't be more than 4 hours
                if ($lunch_duration_seconds > (4 * 3600)) {
                    $lunch_duration_seconds = 0; // Ignore invalid lunch break
                }
            }
        }
        
        // Net work seconds = Total work time - Lunch break
        $net_work_seconds = $total_work_seconds - $lunch_duration_seconds;
        
        // Safety check: net work should be positive and reasonable (max 16 hours per day)
        if ($net_work_seconds > 0 && $net_work_seconds <= (16 * 3600)) {
            // Accumulate in seconds (integer arithmetic - no precision loss)
            $total_seconds += $net_work_seconds;
        }
    }
    
    // Convert to hours only at the very end
    // Use round() to handle any remaining precision issues and ensure clean output
    $total_hours = round($total_seconds / 3600, 2);
    
    return $total_hours;
}

/**
 * Get shift hours from shift_type table
 * Calculates: (work_end - work_start) - (lunch_end - lunch_start)
 */
private function get_shift_hours_from_type($shift_id)
{
    $prefix = db_prefix();
    
    // Get shift type details
    $this->db->select('time_start_work, time_end_work, start_lunch_break_time, end_lunch_break_time');
    $this->db->from($prefix . 'shift_type');
    $this->db->where('id', $shift_id);
    
    $shift_type = $this->db->get()->row();
    
    if (!$shift_type) {
        // Default to 8 hours if shift type not found
        return 8;
    }
    
    // Calculate work hours
    $work_start = strtotime($shift_type->time_start_work);
    $work_end = strtotime($shift_type->time_end_work);
    
    // Handle overnight shifts (e.g., Night Shift 00:00 - 09:30)
    if ($work_end < $work_start) {
        $work_end += (24 * 3600); // Add 24 hours
    }
    
    $total_work_seconds = $work_end - $work_start;
    
    // Calculate lunch break duration
    $lunch_start = strtotime($shift_type->start_lunch_break_time);
    $lunch_end = strtotime($shift_type->end_lunch_break_time);
    
    if ($lunch_end < $lunch_start) {
        $lunch_end += (24 * 3600); // Add 24 hours for overnight lunch
    }
    
    $lunch_duration_seconds = $lunch_end - $lunch_start;
    
    // Net work hours = Total work time - Lunch break
    $net_work_seconds = $total_work_seconds - $lunch_duration_seconds;
    $net_work_hours = $net_work_seconds / 3600;
    
    return round($net_work_hours, 2);
}
/**
 * Get date range from filters
 */
public function get_date_range_from_filters($filters = [])
{
    // If custom date range is provided
    if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
        return [
            'from' => $filters['from_date'],
            'to' => $filters['to_date']
        ];
    }
    
    // If period is provided
    if (!empty($filters['period'])) {
        return $this->period_to_date_range($filters['period']);
    }
    
    // Default to current month
    return [
        'from' => date('Y-m-01'),
        'to' => date('Y-m-t')
    ];
}

/**
 * Get leave details for modal popup
 */
public function get_leave_details_for_modal($staff_id, $from_date = null, $to_date = null)
{
    $this->db->select('
        ' . db_prefix() . 'timesheets_requisition_leave.id,
        ' . db_prefix() . 'timesheets_requisition_leave.subject,
        ' . db_prefix() . 'timesheets_requisition_leave.start_time,
        ' . db_prefix() . 'timesheets_requisition_leave.end_time,
        ' . db_prefix() . 'timesheets_requisition_leave.status,
        ' . db_prefix() . 'timesheets_requisition_leave.type_of_leave,
        ' . db_prefix() . 'timesheets_requisition_leave.type_of_leave_text,
        (SELECT GROUP_CONCAT(CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) SEPARATOR ", ") 
         FROM ' . db_prefix() . 'timesheets_approval_details 
         LEFT JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'staff.staffid = ' . db_prefix() . 'timesheets_approval_details.staffid
         WHERE ' . db_prefix() . 'timesheets_approval_details.rel_id = ' . db_prefix() . 'timesheets_requisition_leave.id 
         AND ' . db_prefix() . 'timesheets_approval_details.rel_type <> "additional_timesheets") as approver_names,
        (SELECT GROUP_CONCAT(' . db_prefix() . 'timesheets_approval_details.staffid SEPARATOR ",") 
         FROM ' . db_prefix() . 'timesheets_approval_details 
         WHERE ' . db_prefix() . 'timesheets_approval_details.rel_id = ' . db_prefix() . 'timesheets_requisition_leave.id 
         AND ' . db_prefix() . 'timesheets_approval_details.rel_type <> "additional_timesheets") as approver_ids
    ');
    $this->db->from(db_prefix() . 'timesheets_requisition_leave');
    $this->db->where('staff_id', $staff_id);
    
    if ($from_date && $to_date) {
        $this->db->where("(
            (DATE(start_time) <= '" . $to_date . "' AND DATE(end_time) >= '" . $from_date . "')
        )");
    }
    
    $this->db->order_by('start_time', 'DESC');
    
    return $this->db->get()->result_array();
}

/**
 * Get OT details for modal popup - FIXED VERSION
 * Shows all records (approved, pending, rejected) with proper status labels
 */
public function get_ot_details_for_modal($staff_id, $from_date = null, $to_date = null)
{
    $this->db->select('
        ' . db_prefix() . 'timesheets_additional_timesheet.id,
        ' . db_prefix() . 'timesheets_additional_timesheet.additional_day,
        ' . db_prefix() . 'timesheets_additional_timesheet.status,
        ' . db_prefix() . 'timesheets_additional_timesheet.time_in,
        ' . db_prefix() . 'timesheets_additional_timesheet.time_out,
        ' . db_prefix() . 'timesheets_additional_timesheet.timekeeping_value
    ');
    $this->db->from(db_prefix() . 'timesheets_additional_timesheet');
    $this->db->where('creator', $staff_id);
    
    if ($from_date && $to_date) {
        $this->db->where("DATE(additional_day) BETWEEN '" . $from_date . "' AND '" . $to_date . "'");
    }
    
    $this->db->order_by('additional_day', 'DESC');
    
    $records = $this->db->get()->result_array();
    
    // ✅ Add status labels and ensure all records are visible
    foreach ($records as &$record) {
        $status = (int)$record['status'];
        
        if ($status === 1) {
            $record['status_label'] = 'Approved';
            $record['status_class'] = 'label-success';
        } elseif ($status === 2) {
            $record['status_label'] = 'Rejected';
            $record['status_class'] = 'label-danger';
        } else {
            $record['status_label'] = 'Pending';
            $record['status_class'] = 'label-warning';
        }
    }
    
    return $records;
}

/**
 * Get count of open tickets created by staff member
 * @param int $staff_id Staff ID
 * @param array $date_range Date range filter (optional)
 * @return int Count of open tickets
 */
private function get_open_tickets_count($staff_id, $date_range = [])
{
    // Get closed status IDs
    $closed_status_ids = $this->get_closed_ticket_status_ids();
    
    $this->db->from(db_prefix() . 'tickets');
    $this->db->where('admin', $staff_id); // admin field = staff who created the ticket
    
    // Exclude closed tickets
    if (!empty($closed_status_ids)) {
        $this->db->where_not_in('status', $closed_status_ids);
    }
    
    // Apply date range filter (by creation date)
    if (!empty($date_range) && isset($date_range['from']) && isset($date_range['to'])) {
        $this->db->where("DATE(date) BETWEEN '" . $date_range['from'] . "' AND '" . $date_range['to'] . "'");
    }
    
    return $this->db->count_all_results();
}

/**
 * Get open tickets statistics (for display)
 * @param int $staff_id Staff ID
 * @param array $date_range Date range filter
 * @return array Statistics array with display format
 */
private function get_tickets_statistics($staff_id, $date_range = [])
{
    $count = $this->get_open_tickets_count($staff_id, $date_range);
    return [
        'count' => $count,
        'display' => $count > 0 ? (string)$count : '-',
    ];
}

/**
 * Get closed ticket status IDs
 * @return array Array of closed status IDs
 */
private function get_closed_ticket_status_ids()
{
    $this->db->select('ticketstatusid');
    $this->db->from(db_prefix() . 'tickets_status');
    // Status names that indicate closed: Closed, Resolved, Solved, Completed
    $this->db->where("LOWER(name) IN ('closed', 'resolved', 'solved', 'completed')");
    $result = $this->db->get()->result_array();
    
    return array_column($result, 'ticketstatusid');
}

/**
 * Get open tickets details for staff member
 * @param int $staff_id Staff ID
 * @param array $date_range Date range filter (optional)
 * @return array Array of ticket records with required fields
 */
public function get_open_tickets_for_staff($staff_id, $date_range = [])
{
    // Get closed status IDs
    $closed_status_ids = $this->get_closed_ticket_status_ids();
    
    $this->db->select('
        ' . db_prefix() . 'tickets.ticketid,
        ' . db_prefix() . 'tickets.subject,
        ' . db_prefix() . 'tickets.department,
        ' . db_prefix() . 'departments.name as department_name,
        ' . db_prefix() . 'tickets.status,
        ' . db_prefix() . 'tickets_status.name as status_name,
        ' . db_prefix() . 'tickets_status.statuscolor,
        ' . db_prefix() . 'tickets.priority,
        ' . db_prefix() . 'tickets_priorities.name as priority_name,
        ' . db_prefix() . 'tickets.lastreply,
        ' . db_prefix() . 'tickets.date
    ');
    
    $this->db->from(db_prefix() . 'tickets');
    $this->db->join(db_prefix() . 'departments', db_prefix() . 'departments.departmentid = ' . db_prefix() . 'tickets.department', 'left');
    $this->db->join(db_prefix() . 'tickets_status', db_prefix() . 'tickets_status.ticketstatusid = ' . db_prefix() . 'tickets.status', 'left');
    $this->db->join(db_prefix() . 'tickets_priorities', db_prefix() . 'tickets_priorities.priorityid = ' . db_prefix() . 'tickets.priority', 'left');
    
    $this->db->where(db_prefix() . 'tickets.admin', $staff_id);
    
    // Exclude closed tickets
    if (!empty($closed_status_ids)) {
        $this->db->where_not_in(db_prefix() . 'tickets.status', $closed_status_ids);
    }
    
    // Apply date range filter (by creation date)
    if (!empty($date_range) && isset($date_range['from']) && isset($date_range['to'])) {
        $this->db->where("DATE(" . db_prefix() . "tickets.date) BETWEEN '" . $date_range['from'] . "' AND '" . $date_range['to'] . "'");
    }
    
    $this->db->order_by(db_prefix() . 'tickets.lastreply', 'DESC');
    $this->db->order_by(db_prefix() . 'tickets.date', 'DESC');
    
    return $this->db->get()->result_array();
}

/**
 * Get OT count summary for modal header - FIXED VERSION
 * Returns proper "Approved (X/Total)" format
 */
public function get_ot_count_summary($staff_id, $from_date = null, $to_date = null)
{
    $this->db->select('status');
    $this->db->from(db_prefix() . 'timesheets_additional_timesheet');
    $this->db->where('creator', $staff_id);
    
    if ($from_date && $to_date) {
        $this->db->where("DATE(additional_day) BETWEEN '" . $from_date . "' AND '" . $to_date . "'");
    }
    
    $records = $this->db->get()->result_array();
    
    $approved = 0;
    $pending = 0;
    $rejected = 0;
    
    foreach ($records as $record) {
        $status = (int)$record['status'];
        
        if ($status === 1) {
            $approved++;
        } elseif ($status === 2) {
            $rejected++;
        } else {
            $pending++;
        }
    }
    
    $total = $approved + $pending + $rejected;
    
    return [
        'approved' => $approved,
        'pending' => $pending,
        'rejected' => $rejected,
        'total' => $total,
        'display' => $total > 0 ? "Approved ({$approved}/{$total})" : 'No OT Records'
    ];
}

/**
 * Get Leave count summary for modal header - FIXED VERSION
 * Returns proper "Approved (X/Total)" format
 */
public function get_leave_count_summary($staff_id, $from_date = null, $to_date = null)
{
    $this->db->select('status');
    $this->db->from(db_prefix() . 'timesheets_requisition_leave');
    $this->db->where('staff_id', $staff_id);
    
    if ($from_date && $to_date) {
        $this->db->where("(
            (DATE(start_time) <= '" . $to_date . "' AND DATE(end_time) >= '" . $from_date . "')
        )");
    }
    
    $records = $this->db->get()->result_array();
    
    $approved = 0;
    $pending = 0;
    $rejected = 0;
    
    foreach ($records as $record) {
        $status = (int)$record['status'];
        
        if ($status === 1) {
            $approved++;
        } elseif ($status === 2) {
            $rejected++;
        } else {
            $pending++;
        }
    }
    
    $total = $approved + $pending + $rejected;
    
    return [
        'approved' => $approved,
        'pending' => $pending,
        'rejected' => $rejected,
        'total' => $total,
        'display' => $total > 0 ? "Approved ({$approved}/{$total})" : 'No Leave Records'
    ];
}


/**
 * ========================================
 * LEAVES OVERVIEW METHODS
 * ========================================
 */

/**
 * Get leaves overview data - FULLY DYNAMIC
 */
public function get_leaves_overview($year = null, $staff_filter = null, $status_filter = 'active', $accessible_staff_ids = '')
{
    if (!$year) {
        $year = date('Y');
    }
    
    $prefix = db_prefix();
    
    // Get ALL leave types dynamically from timesheets_type_of_leave table
    $this->db->select('id, type_name, slug, symbol');
    $this->db->from($prefix . 'timesheets_type_of_leave');
    $this->db->order_by('type_name', 'ASC');
    $custom_leave_types = $this->db->get()->result_array();
    
    // Hardcoded leave type names (for backward compatibility with numeric IDs)
    $hardcoded_type_names = [
        0 => 'Other',
        1 => 'Sick Leave',
        2 => 'Maternity Leave',
        3 => 'Marriage Leave',
        4 => 'Private Work Without Pay',
        5 => 'Funeral Leave',
        6 => 'Paternity Leave',
        7 => 'Unpaid Leave',
        8 => 'Annual Leave'
    ];
    
    // Get staff list
    $this->db->select('staffid, CONCAT(firstname, " ", lastname) as staff_name');
    $this->db->from($prefix . 'staff');
    
    // Apply staff filter if provided
    if ($staff_filter) {
        $this->db->where('staffid', $staff_filter);
    }
    
    // Apply status filter
    if ($status_filter === 'active') {
        $this->db->where('active', 1);
    } elseif ($status_filter === 'inactive') {
        $this->db->where('active', 0);
    }
    
    $this->db->order_by('firstname', 'ASC');
    $staff_list = $this->db->get()->result_array();
    
    if (empty($staff_list)) {
        return [];
    }
    
    $results = [];
    
    foreach ($staff_list as $staff) {
        // First, check which hardcoded leave types this staff has assigned
        $this->db->distinct();
        $this->db->select('type_of_leave');
        $this->db->from($prefix . 'timesheets_day_off');
        $this->db->where('staffid', $staff['staffid']);
        $this->db->where_in('year', [$year, $year - 1]);
        $this->db->where('type_of_leave REGEXP', '^[0-9]+$'); // Only numeric IDs
        $assigned_hardcoded_types = $this->db->get()->result_array();
        
        // Process hardcoded leave types that have assignments
        foreach ($assigned_hardcoded_types as $type_row) {
            $type_id = (int)$type_row['type_of_leave'];
            
            // Skip if not in our hardcoded list
            if (!isset($hardcoded_type_names[$type_id])) {
                continue;
            }
            
            // Get assigned leaves
            $this->db->select('total');
            $this->db->from($prefix . 'timesheets_day_off');
            $this->db->where('staffid', $staff['staffid']);
            $this->db->where('type_of_leave', $type_id);
            $this->db->where_in('year', [$year, $year - 1]);
            $this->db->order_by('year', 'DESC');
            $this->db->limit(1);
            $day_off = $this->db->get()->row();
            
            $assigned = $day_off ? (float)$day_off->total : 0;
            
            // Get applied, approved, rejected counts
            $this->db->select('
                COUNT(*) as applied,
                SUM(CASE WHEN status = 1 THEN number_of_leaving_day ELSE 0 END) as approved,
                SUM(CASE WHEN status = 2 THEN number_of_leaving_day ELSE 0 END) as rejected
            ');
            $this->db->from($prefix . 'timesheets_requisition_leave');
            $this->db->where('staff_id', $staff['staffid']);
            $this->db->where('YEAR(start_time)', $year);
            $this->db->where('rel_type', 1);
            $this->db->where('type_of_leave', $type_id);
            
            $leave_stats = $this->db->get()->row();
            
            $applied = $leave_stats ? (int)$leave_stats->applied : 0;
            $approved = $leave_stats ? (float)$leave_stats->approved : 0;
            $rejected = $leave_stats ? (float)$leave_stats->rejected : 0;
            
            // Calculate balance
            $balance = $assigned - $approved;
            
            // Only add if there's some activity
            if ($assigned > 0 || $applied > 0 || $approved > 0 || $rejected > 0) {
                $results[] = [
                    'staff_id' => $staff['staffid'],
                    'staff_name' => $staff['staff_name'],
                    'leave_type' => $hardcoded_type_names[$type_id],
                    'assigned' => $assigned,
                    'applied' => $applied,
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'balance' => $balance
                ];
            }
        }
        
        // Process custom leave types
        foreach ($custom_leave_types as $custom_type) {
            $slug = $custom_type['slug'];
            
            // Get assigned leaves from day_off table using slug
            $this->db->select('total');
            $this->db->from($prefix . 'timesheets_day_off');
            $this->db->where('staffid', $staff['staffid']);
            $this->db->where('type_of_leave', $slug);
            $this->db->where_in('year', [$year, $year - 1]);
            $this->db->order_by('year', 'DESC');
            $this->db->limit(1);
            $day_off = $this->db->get()->row();
            
            $assigned = $day_off ? (float)$day_off->total : 0;
            
            // Get applied, approved, rejected counts using type_of_leave_text field
            $this->db->select('
                COUNT(*) as applied,
                SUM(CASE WHEN status = 1 THEN number_of_leaving_day ELSE 0 END) as approved,
                SUM(CASE WHEN status = 2 THEN number_of_leaving_day ELSE 0 END) as rejected
            ');
            $this->db->from($prefix . 'timesheets_requisition_leave');
            $this->db->where('staff_id', $staff['staffid']);
            $this->db->where('YEAR(start_time)', $year);
            $this->db->where('rel_type', 1);
            $this->db->where('type_of_leave_text', $slug);
            
            $leave_stats = $this->db->get()->row();
            
            $applied = $leave_stats ? (int)$leave_stats->applied : 0;
            $approved = $leave_stats ? (float)$leave_stats->approved : 0;
            $rejected = $leave_stats ? (float)$leave_stats->rejected : 0;
            
            // Calculate balance
            $balance = $assigned - $approved;
            
            // Only add if there's some activity
            if ($assigned > 0 || $applied > 0 || $approved > 0 || $rejected > 0) {
                $results[] = [
                    'staff_id' => $staff['staffid'],
                    'staff_name' => $staff['staff_name'],
                    'leave_type' => $custom_type['type_name'],
                    'assigned' => $assigned,
                    'applied' => $applied,
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'balance' => $balance
                ];
            }
        }
    }
    
    return $results;
}

/**
 * Get available years from requisition_leave table (Financial Year format)
 */
public function get_available_years()
{
    $prefix = db_prefix();
    
    $this->db->select('DISTINCT YEAR(start_time) as year');
    $this->db->from($prefix . 'timesheets_requisition_leave');
    $this->db->order_by('year', 'DESC');
    $years = $this->db->get()->result_array();
    
    $year_list = [];
    foreach ($years as $y) {
        if ($y['year']) {
            // Format as financial year: 2025-2026
            $year_list[] = $y['year'] . '-' . ($y['year'] + 1);
        }
    }
    
    // Add current financial year if not in list
    $current_year = date('Y');
    $current_fy = $current_year . '-' . ($current_year + 1);
    if (!in_array($current_fy, $year_list)) {
        array_unshift($year_list, $current_fy);
    }
    
    return $year_list;
}

}
