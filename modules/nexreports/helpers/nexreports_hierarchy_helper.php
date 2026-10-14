<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * NexReports Hierarchy Helper Functions
 * Provides role-based hierarchy access control for HRMS Overview and Projects Overview
 */

/**
 * Get accessible staff IDs for HRMS Overview based on role and hierarchy
 * Reuses the same hierarchy logic from Timesheets → Work Shift module
 * 
 * @param string $permission Permission name (default: 'attendance_reports')
 * @return array Array of staff IDs (empty array = all staff accessible)
 */
function nexreports_hrms_get_accessible_staff_ids($permission = 'attendance_reports')
{
    try {
        $CI = &get_instance();
        
        // Super Admin / Admin - All staff
        if (is_admin() || has_permission($permission, '', 'view')) {
            return []; // Empty array means no filter (all staff)
        }
        
        // Direct Manager / HOD - Hierarchy staff (same logic as Work Shift)
        if (has_permission($permission, '', 'view_own')) {
            // Check if hr_profile module is active
            $hr_profile_active = false;
            if (function_exists('timesheet_get_status_modules')) {
                $hr_profile_active = timesheet_get_status_modules('hr_profile');
            } else {
                // Direct check if timesheet function not available
                $sql = 'SELECT * FROM ' . db_prefix() . 'modules WHERE module_name = "hr_profile" AND active = 1';
                $module = $CI->db->query($sql)->row();
                $hr_profile_active = ($module !== null);
            }
            
            if ($hr_profile_active) {
                try {
                    $CI->load->model('hr_profile/hr_profile_model');
                    $hierarchy_staff = $CI->hr_profile_model->get_staff_by_manager();
                    // If hierarchy staff found, return them; otherwise return own staff ID
                    if (!empty($hierarchy_staff) && is_array($hierarchy_staff)) {
                        return $hierarchy_staff;
                    }
                } catch (Exception $e) {
                    // If model load fails, fall back to own staff
                    log_message('error', 'NexReports: Error loading hr_profile model - ' . $e->getMessage());
                }
            }
            // Fallback: If no hierarchy, return own staff ID
            return [get_staff_user_id()];
        }
        
        // Normal Staff - Only themselves
        return [get_staff_user_id()];
    } catch (Exception $e) {
        log_message('error', 'NexReports: Error in nexreports_hrms_get_accessible_staff_ids - ' . $e->getMessage());
        // Default to own staff on error
        return [get_staff_user_id()];
    }
}

/**
 * Get accessible project IDs for Projects Overview based on role and membership
 * NO HIERARCHY LOGIC - Staff only see projects where they are personally assigned
 * 
 * @param string $permission Permission name (default: 'projects_overview')
 * @return array|string Array of project IDs (empty array = no access, empty string = all projects)
 */
function nexreports_projects_get_accessible_project_ids($permission = 'projects_overview')
{
    try {
        $CI = &get_instance();
        
        // Check permissions
        $has_view_own = has_permission($permission, '', 'view_own');
        $has_view = has_permission($permission, '', 'view');
        
        // Super Admin / Admin / User with view permission - All projects
        if (is_admin() || $has_view) {
            log_message('debug', 'NexReports: Admin/View permission - showing all projects');
            return ''; // Empty string means all projects (no filter)
        }
        
        // All other users (including HOD/Managers with view_own) - Only their assigned projects
        if ($has_view_own) {
            try {
                $current_staff_id = get_staff_user_id();
                $CI->db->distinct();
                $CI->db->select('project_id');
                $CI->db->from(db_prefix() . 'project_members');
                $CI->db->where('staff_id', $current_staff_id);
                $result = $CI->db->get()->result_array();
                
                // Return array of project IDs where user is personally assigned
                $project_ids = !empty($result) ? array_column($result, 'project_id') : [];
                
                log_message('debug', 'NexReports: Staff ' . $current_staff_id . ' has access to personally assigned projects: ' . implode(',', $project_ids));
                return $project_ids;
            } catch (Exception $e) {
                log_message('error', 'NexReports: Error querying project members for staff - ' . $e->getMessage());
                // On error, deny access (return empty array)
                return [];
            }
        }
        
        // Default: No access
        log_message('debug', 'NexReports: No permission - denying project access');
        return [];
    } catch (Exception $e) {
        log_message('error', 'NexReports: Error in nexreports_projects_get_accessible_project_ids - ' . $e->getMessage());
        // Default to no access on error
        return [];
    }
}

/**
 * Apply project filtering to query builder based on accessible project IDs
 * 
 * @param object $db_query Query builder instance
 * @param array|string $accessible_project_ids Array of IDs or empty string for all
 * @param string $project_id_column Column name for project ID (default: 'id')
 * @param string $table_prefix Table alias/prefix (default: 'p')
 */
function nexreports_apply_project_filter($db_query, $accessible_project_ids, $project_id_column = 'id', $table_prefix = 'p')
{
    if ($accessible_project_ids === '') {
        // All projects accessible - no filter needed
        return;
    }
    
    if (is_array($accessible_project_ids)) {
        if (empty($accessible_project_ids)) {
            // No access - return empty result
            $db_query->where('1 = 0', null, false); // Always false condition
            return;
        }
        
        // Filter by accessible project IDs
        $project_id_field = $table_prefix . '.' . $project_id_column;
        $db_query->where_in($project_id_field, array_map('intval', $accessible_project_ids));
    }
}

/**
 * Apply staff filtering to query builder based on accessible staff IDs
 * 
 * @param object $db_query Query builder instance
 * @param array $accessible_staff_ids Array of staff IDs (empty array = all staff)
 * @param string $staff_id_column Column name for staff ID (default: 'staffid')
 * @param string $table_prefix Table alias/prefix (default: 's')
 */
function nexreports_apply_staff_filter($db_query, $accessible_staff_ids, $staff_id_column = 'staffid', $table_prefix = 's')
{
    if (empty($accessible_staff_ids)) {
        // All staff accessible - no filter needed
        return;
    }
    
    // Filter by accessible staff IDs
    $staff_id_field = $table_prefix . '.' . $staff_id_column;
    if (count($accessible_staff_ids) == 1) {
        $db_query->where($staff_id_field, (int)$accessible_staff_ids[0]);
    } else {
        $db_query->where_in($staff_id_field, array_map('intval', $accessible_staff_ids));
    }
}

/**
 * Get accessible staff for dropdown/list based on hierarchy
 * Enhanced version with role-based filtering and status support
 * 
 * @param string $permission Permission name
 * @param string $status_filter Filter by active status: 'all', 'active', 'inactive'
 * @return array Array of staff data for dropdown
 */
function nexreports_get_accessible_staff_for_dropdown($permission = 'attendance_reports', $status_filter = 'active')
{
    try {
        $CI = &get_instance();
        
        $accessible_staff_ids = nexreports_hrms_get_accessible_staff_ids($permission);
        
        // Build base query
        $CI->db->select('staffid, firstname, lastname, email, active');
        $CI->db->from(db_prefix() . 'staff');
        
        // Apply staff ID filtering based on role
        if (!empty($accessible_staff_ids)) {
            if (count($accessible_staff_ids) == 1) {
                $CI->db->where('staffid', (int)$accessible_staff_ids[0]);
            } else {
                $CI->db->where_in('staffid', array_map('intval', $accessible_staff_ids));
            }
        }
        // If empty array, no additional filtering (all staff accessible)
        
        // Apply status filtering
        if ($status_filter === 'active') {
            $CI->db->where('active', 1);
        } elseif ($status_filter === 'inactive') {
            $CI->db->where('active', 0);
        }
        // 'all' status - no additional where clause
        
        $CI->db->order_by('firstname', 'ASC');
        $result = $CI->db->get();
        return $result ? $result->result_array() : [];
        
    } catch (Exception $e) {
        log_message('error', 'NexReports: Error in nexreports_get_accessible_staff_for_dropdown - ' . $e->getMessage());
        
        // Fallback - return all staff with status filter
        try {
            $CI = &get_instance();
            $CI->db->select('staffid, firstname, lastname, email, active');
            $CI->db->from(db_prefix() . 'staff');
            
            if ($status_filter === 'active') {
                $CI->db->where('active', 1);
            } elseif ($status_filter === 'inactive') {
                $CI->db->where('active', 0);
            }
            
            $CI->db->order_by('firstname', 'ASC');
            $result = $CI->db->get();
            return $result ? $result->result_array() : [];
        } catch (Exception $fallback_error) {
            log_message('error', 'NexReports: Fallback query also failed - ' . $fallback_error->getMessage());
            return [];
        }
    }
}

/**
 * Determine user's role and permissions for HRMS filtering
 * 
 * @param string $permission Permission name
 * @return array Role information with flags and accessible staff IDs
 */
function nexreports_get_user_role_info($permission = 'attendance_reports')
{
    try {
        $CI = &get_instance();
        
        // Check permissions
        $is_admin = is_admin();
        $has_view = staff_can('view', $permission);
        $has_view_own = staff_can('view_own', $permission);
        
        // Determine role type
        $role_type = 'none';
        $can_manage_filters = false;
        $can_view_all = false;
        
        if ($is_admin || $has_view) {
            $role_type = 'admin_hr';
            $can_manage_filters = true;
            $can_view_all = true;
        } elseif ($has_view_own) {
            // Check if user has hierarchy (HOD/Manager) or is normal employee
            $accessible_staff_ids = nexreports_hrms_get_accessible_staff_ids($permission);
            
            if (!empty($accessible_staff_ids) && count($accessible_staff_ids) > 1) {
                $role_type = 'hod_manager';
                $can_manage_filters = true;
                $can_view_all = false;
            } else {
                $role_type = 'normal_employee';
                $can_manage_filters = false;
                $can_view_all = false;
            }
        }
        
        // Get accessible staff IDs
        $accessible_staff_ids = [];
        if ($role_type === 'admin_hr') {
            $accessible_staff_ids = []; // Empty array means all staff
        } else {
            $accessible_staff_ids = nexreports_hrms_get_accessible_staff_ids($permission);
        }
        
        return [
            'role_type' => $role_type,
            'is_admin' => $is_admin,
            'has_view' => $has_view,
            'has_view_own' => $has_view_own,
            'can_manage_filters' => $can_manage_filters,
            'can_view_all' => $can_view_all,
            'accessible_staff_ids' => $accessible_staff_ids,
            'current_staff_id' => get_staff_user_id()
        ];
        
    } catch (Exception $e) {
        log_message('error', 'NexReports: Error in nexreports_get_user_role_info - ' . $e->getMessage());
        
        // Safe fallback - normal employee access
        return [
            'role_type' => 'normal_employee',
            'is_admin' => false,
            'has_view' => false,
            'has_view_own' => true,
            'can_manage_filters' => false,
            'can_view_all' => false,
            'accessible_staff_ids' => [get_staff_user_id()],
            'current_staff_id' => get_staff_user_id()
        ];
    }
}
