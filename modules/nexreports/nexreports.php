<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Module Name: NexReports
 * Version: 1.0
 * Description: Advanced Reports Module
 */

/* ---------------------------------------------------------
 * MODULE LIFECYCLE
 * --------------------------------------------------------- */
register_activation_hook('nexreports', 'nexreports_install');
function nexreports_install()
{
    // Auto-update Project Lead By custom field on module activation
    nexreports_update_project_lead_by_field();
    return true;
}

register_deactivation_hook('nexreports', 'nexreports_uninstall');
function nexreports_uninstall()
{
    return true;
}

/**
 * Auto-update Project Lead By custom field to dropdown with active staff
 * This runs automatically when module is activated or upgraded
 */
function nexreports_update_project_lead_by_field()
{
    $CI =& get_instance();
    $prefix = db_prefix();
    
    // Get the custom field
    $CI->db->where('fieldto', 'projects');
    $CI->db->where('name', 'Project Lead By');
    $custom_field = $CI->db->get($prefix . 'customfields')->row();
    
    if (!$custom_field) {
        // Field doesn't exist, skip
        return false;
    }
    
    // Get all active staff
    $CI->db->where('active', 1);
    $CI->db->order_by('firstname', 'ASC');
    $staff = $CI->db->get($prefix . 'staff')->result_array();
    
    if (empty($staff)) {
        return false;
    }
    
    // Build options string (format: "Full Name,Full Name,...")
    $options = [];
    foreach ($staff as $member) {
        $full_name = $member['firstname'] . ' ' . $member['lastname'];
        $options[] = $full_name;
    }
    $options_string = implode(',', $options);
    
    // Update the custom field
    $CI->db->where('id', $custom_field->id);
    $CI->db->update($prefix . 'customfields', [
        'type' => 'select',
        'options' => $options_string,
        'bs_column' => 12
    ]);
    
    return true;
}
/* ---------------------------------------------------------
 * PERMISSIONS: Projects Overview
 * --------------------------------------------------------- */
hooks()->add_action('admin_init', 'nexreports_projects_overview_permissions');

function nexreports_projects_overview_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view'      => _l('permission_view'),
        'view_own'  => _l('permission_view_own'),
    ];

    register_staff_capabilities(
        'projects_overview',
        $capabilities,
        'Projects Overview'
    );
}


/* ---------------------------------------------------------
 * PERMISSIONS: Attendance Reports
 * --------------------------------------------------------- */
/* ---------------------------------------------------------
 * PERMISSIONS: HRMS Overview
 * --------------------------------------------------------- */
hooks()->add_action('admin_init', 'nexreports_hrms_overview_permissions');

function nexreports_hrms_overview_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view'      => _l('permission_view'),
        'view_own'  => _l('permission_view_own'),
    ];

    register_staff_capabilities(
        'attendance_reports',
        $capabilities,
        'HRMS Overview'
    );
}

/* ---------------------------------------------------------
 * SIDEBAR MENU
 * --------------------------------------------------------- */
hooks()->add_action('admin_init', 'nexreports_register_menu');

function nexreports_register_menu()
{
    $CI = &get_instance();

    // Check permissions
    $can_view_projects =
    staff_can('view', 'projects_overview') ||
    staff_can('view_own', 'projects_overview') ||
    is_admin();

$can_view_attendance =
    staff_can('view', 'attendance_reports') ||
    staff_can('view_own', 'attendance_reports') ||
    is_admin();


    // Add parent menu item
    $CI->app_menu->add_sidebar_menu_item('nexreports', [
        'name'     => 'NexReports',
        'icon'     => 'fa fa-bar-chart',
        'position' => 15,
        'collapse' => true,
    ]);

    // Add Projects Reports submenu
    if ($can_view_projects) {
        $CI->app_menu->add_sidebar_children_item('nexreports', [
            'slug'     => 'nexreports-projects-reports',
            'name'     => 'Projects Overview',
            'href'     => admin_url('nexreports/projects'),
            'position' => 1,
        ]);
    }

    // Add Attendance Reports submenu
    if ($can_view_attendance) {
        $CI->app_menu->add_sidebar_children_item('nexreports', [
            'slug'     => 'nexreports-attendance-reports',
            'name'     => 'HRMS Overview',
            'href'     => admin_url('nexreports/attendance'),
            'position' => 2,
        ]);
    }
    
    // Add Leaves Overview submenu
    if ($can_view_attendance) {
        $CI->app_menu->add_sidebar_children_item('nexreports', [
            'slug'     => 'nexreports-leaves-overview',
            'name'     => 'Leaves Overview',
            'href'     => admin_url('nexreports/leaves'),
            'position' => 3,
        ]);
    }
}