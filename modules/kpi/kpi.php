<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: KPI
Description: Attendance KPI and performance tracking
Version: 1.0.0
Author: Nexgeno Technology
*/

// ------------------------------------------------------
// MODULE HOOK REGISTRATION
// ------------------------------------------------------
register_activation_hook('kpi', 'kpi_activate_module');

hooks()->add_action('admin_init', 'kpi_permissions');
hooks()->add_action('admin_init', 'kpi_module_init_menu_items');

hooks()->add_action('app_admin_head',          'kpi_add_module_styles');
hooks()->add_action('app_admin_footer',         'kpi_add_module_js');
hooks()->add_action('app_admin_footer',         'kpi_load_widget_js');
hooks()->add_action('after_render_top_search',  'kpi_add_notification_icon');

hooks()->add_filter('get_dashboard_widgets', 'kpi_add_dashboard_widget');

// ------------------------------------------------------
// MODULE ACTIVATION
// ------------------------------------------------------
function kpi_activate_module()
{
    $CI = &get_instance();
    require_once __DIR__ . '/install.php';
    kpi_install_permissions();
    log_activity('KPI Module Activated');
}

function kpi_install_permissions()
{
    log_activity('KPI Permissions Installed');
}

// ------------------------------------------------------
// REGISTER KPI PERMISSIONS
// ------------------------------------------------------
function kpi_permissions()
{
    register_staff_capabilities('kpi', [
        'capabilities' => [
            'view_own'        => _l('permission_view_own'),
            'view'            => _l('permission_view'),
            'approve_requests'=> _l('kpi_approve_requests'),
        ]
    ], _l('kpi_management'));
}

// ------------------------------------------------------
// ADMIN MENU SETUP
// ------------------------------------------------------
function kpi_module_init_menu_items()
{
    $CI = &get_instance();
    if (has_permission('kpi', '', 'view_own') || has_permission('kpi', '', 'view') || is_admin()) {
        $CI->app_menu->add_sidebar_menu_item('kpi', [
            'name'     => 'KPI',
            'icon'     => 'fa fa-line-chart',
            'position' => 35,
        ]);
        $CI->app_menu->add_sidebar_children_item('kpi', [
            'slug'     => 'attendance-kpi',
            'name'     => 'Attendance KPI',
            'icon'     => 'fa fa-pen-to-square',
            'href'     => admin_url('kpi'),
            'position' => 1,
        ]);
    }
}

// ------------------------------------------------------
// LOAD MODULE CSS
// ------------------------------------------------------
function kpi_add_module_styles()
{
    try {
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if (strpos($uri, '/admin/kpi') !== false) {
            echo '<link href="' . module_dir_url('kpi', 'assets/css/kpi_styles.css') . '" rel="stylesheet" />';
        }
        if (kpi_should_show_notifications()) {
            echo '<link href="' . module_dir_url('kpi', 'assets/css/kpi_notifications.css') . '" rel="stylesheet" />';
        }
    } catch (Exception $e)  { /* silent */ }
      catch (Throwable $e)  { /* silent */ }
}

// ------------------------------------------------------
// LOAD MODULE JS
// ------------------------------------------------------
function kpi_add_module_js()
{
    try {
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if (strpos($uri, '/admin/kpi') !== false) {
            echo '<script src="' . module_dir_url('kpi', 'assets/js/kpi_module.js') . '"></script>';
        }
        if (kpi_should_show_notifications()) {
            echo '<script src="' . module_dir_url('kpi', 'assets/js/kpi_notifications.js') . '"></script>';
        }
    } catch (Exception $e)  { /* silent */ }
      catch (Throwable $e)  { /* silent */ }
}

// ------------------------------------------------------
// LOAD WIDGET JS ON DASHBOARD ONLY
// ------------------------------------------------------
function kpi_load_widget_js()
{
    try {
        // Only load widget JS if user can actually see the widget
        if (!is_admin() && !has_permission('kpi', '', 'view_own')) {
            return;
        }
        $uri   = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $clean = explode('?', $uri)[0];
        if (preg_match('#/admin/?$#', $clean) || preg_match('#/admin/dashboard#', $clean)) {
            echo '<script src="' . module_dir_url('kpi', 'assets/js/kpi_widget.js') . '"></script>';
        }
    } catch (Exception $e)  { /* silent */ }
      catch (Throwable $e)  { /* silent */ }
}

// ------------------------------------------------------
// ADD KPI WIDGET TO DASHBOARD
// ------------------------------------------------------
function kpi_add_dashboard_widget($widgets)
{
    try {
        if (is_admin() || has_permission('kpi', '', 'view_own')) {
            array_unshift($widgets, [
                'path'      => 'kpi/kpi_widget',
                'container' => 'top-12',
            ]);
        }
    } catch (Exception $e)  { /* silent */ }
      catch (Throwable $e)  { /* silent */ }
    return $widgets;
}

// ------------------------------------------------------
// ADD NOTIFICATION ICON TO NAVBAR
// ------------------------------------------------------
function kpi_add_notification_icon()
{
    $output = '';
    try {
        if (!kpi_should_show_notifications()) {
            return;
        }

        $CI = &get_instance();
        $CI->load->model('kpi/kpi_notifications_model');

        $old_debug = $CI->db->db_debug;
        $CI->db->db_debug = false;

        $pending_ot_count    = 0;
        $pending_pl_count    = 0;
        $pending_ot_requests = [];
        $pending_pl_requests = [];

        try { $pending_ot_count    = (int)$CI->kpi_notifications_model->get_pending_ot_count();     } catch (Throwable $e) { log_message('error','KPI OT count: '.$e->getMessage()); }
        try { $pending_pl_count    = (int)$CI->kpi_notifications_model->get_pending_pl_count();     } catch (Throwable $e) { log_message('error','KPI PL count: '.$e->getMessage()); }
        try { $pending_ot_requests = $CI->kpi_notifications_model->get_pending_ot_requests(5) ?: []; } catch (Throwable $e) { log_message('error','KPI OT list: '.$e->getMessage()); }
        try { $pending_pl_requests = $CI->kpi_notifications_model->get_pending_pl_requests(5) ?: []; } catch (Throwable $e) { log_message('error','KPI PL list: '.$e->getMessage()); }

        $CI->db->db_debug = $old_debug;

        $data = [
            'pending_ot_count'       => $pending_ot_count,
            'pending_pl_count'       => $pending_pl_count,
            'total_pending_requests' => $pending_ot_count + $pending_pl_count,
            'pending_ot_requests'    => $pending_ot_requests,
            'pending_pl_requests'    => $pending_pl_requests,
        ];

        ob_start();
        try {
            $CI->load->view('kpi/notifications/notification_icon', $data);
            $output = ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            log_message('error', 'KPI notification view error: ' . $e->getMessage());
            return;
        }

    } catch (Exception $e)  { if (ob_get_level()) ob_end_clean(); log_message('error','KPI icon error: '.$e->getMessage()); return; }
      catch (Throwable $e)  { if (ob_get_level()) ob_end_clean(); log_message('error','KPI icon throwable: '.$e->getMessage()); return; }

    if (!empty($output)) {
        echo $output;
    }
}

// ------------------------------------------------------
// HELPER: CHECK IF USER SHOULD SEE NOTIFICATIONS
// Result is cached per request so we only run queries once.
// ------------------------------------------------------
function kpi_should_show_notifications()
{
    // Static cache — computed once per page request
    static $cached_result = null;
    if ($cached_result !== null) {
        return $cached_result;
    }

    $cached_result = false; // default

    try {
        if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
            return false;
        }

        $CI = &get_instance();

        if (!isset($CI->db) || !is_object($CI->db)) {
            return false;
        }

        // Use conn_id carefully — it's a resource on mysqli, object on pdo
        if (isset($CI->db->conn_id) && empty($CI->db->conn_id) && $CI->db->conn_id !== 0) {
            return false;
        }

        $current_staff_id = get_staff_user_id();
        if (!$current_staff_id) {
            return false;
        }

        // Fast path — admin or has explicit permission
        if (is_admin() || has_permission('kpi', '', 'approve_requests')) {
            $cached_result = true;
            return true;
        }

        // -------------------------------------------------------
        // All remaining checks require DB queries against tables
        // that belong to the Timesheets module. We MUST verify
        // each table exists before querying it, otherwise CI's
        // show_error() kills the page before jQuery loads.
        // -------------------------------------------------------
        $old_debug = $CI->db->db_debug;
        $CI->db->db_debug = false; // suppress ALL db errors globally for these checks

        // Check 2: Is user listed in timesheets_approval_details?
        if ($CI->db->table_exists(db_prefix() . 'timesheets_approval_details')) {
            $q = $CI->db->select('staffid')
                        ->from(db_prefix() . 'timesheets_approval_details')
                        ->where('staffid', $current_staff_id)
                        ->limit(1)
                        ->get();
            if ($q && $q->num_rows() > 0) {
                $CI->db->db_debug = $old_debug;
                $cached_result = true;
                return true;
            }
        }

        // Check 3: Is user a direct manager via team_manage column?
        // Must verify the column exists first — it's a custom column.
        $has_team_manage_col = false;
        try {
            $cols = $CI->db->list_fields(db_prefix() . 'staff');
            $has_team_manage_col = is_array($cols) && in_array('team_manage', $cols);
        } catch (Throwable $e) { /* column check failed, skip */ }

        if ($has_team_manage_col) {
            $q = $CI->db->select('staffid')
                        ->from(db_prefix() . 'staff')
                        ->where('team_manage', $current_staff_id)
                        ->limit(1)
                        ->get();
            if ($q && $q->num_rows() > 0) {
                $CI->db->db_debug = $old_debug;
                $cached_result = true;
                return true;
            }
        }

        // Check 4: Is user a department manager?
        if ($CI->db->table_exists(db_prefix() . 'staff_departments')) {
            $cols2 = [];
            try { $cols2 = $CI->db->list_fields(db_prefix() . 'staff_departments'); } catch (Throwable $e) {}
            if (in_array('manager_id', (array)$cols2)) {
                $q = $CI->db->select('departmentid')
                            ->from(db_prefix() . 'staff_departments')
                            ->where('manager_id', $current_staff_id)
                            ->limit(1)
                            ->get();
                if ($q && $q->num_rows() > 0) {
                    $CI->db->db_debug = $old_debug;
                    $cached_result = true;
                    return true;
                }
            }
        }

        $CI->db->db_debug = $old_debug;
        $cached_result = false;
        return false;

    } catch (Exception $e) {
        log_message('error', 'KPI kpi_should_show_notifications error: ' . $e->getMessage());
        $cached_result = false;
        return false;
    } catch (Throwable $e) {
        log_message('error', 'KPI kpi_should_show_notifications throwable: ' . $e->getMessage());
        $cached_result = false;
        return false;
    }
}
