<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: KPI
Description: KPI module for Dashboard and Attendance management
Version: 1.0.0
Requires at least: 2.3.*
Author: Nexgeno Technologies
*/

define('KPI_MODULE_NAME', 'kpi');
define('KPI_MODULE_VERSION', '1.0.0');

hooks()->add_action('admin_init', 'kpi_module_init_menu_items');
hooks()->add_action('admin_init', 'kpi_permissions');
hooks()->add_action('app_admin_head', 'kpi_add_head_components');
hooks()->add_action('app_admin_footer', 'kpi_add_footer_components');

/**
 * Register activation module hook
 */
register_activation_hook(KPI_MODULE_NAME, 'kpi_module_activation_hook');

/**
 * Register language files
 */
register_language_files(KPI_MODULE_NAME, [KPI_MODULE_NAME]);

/**
 * Module activation hook
 */
function kpi_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Init KPI module menu items in setup in admin_init hook
 */
function kpi_module_init_menu_items()
{
    $CI = &get_instance();
    
    // Show main menu if user has at least one permission
    if (has_permission('kpi_dashboard', '', 'view') || 
        has_permission('kpi_dashboard', '', 'view_own') ||
        has_permission('kpi_attendance', '', 'view') || 
        has_permission('kpi_attendance', '', 'view_own')) {
        
        $CI->app_menu->add_sidebar_menu_item('kpi', [
            'name'     => _l('kpi'),
            'collapse' => true,
            'icon'     => 'fa fa-chart-line',
            'position' => 50
        ]);

        if (has_permission('kpi_dashboard', '', 'view') || has_permission('kpi_dashboard', '', 'view_own')) {
            $CI->app_menu->add_sidebar_children_item('kpi', [
                'slug'     => 'kpi-dashboard',
                'name'     => _l('dashboard'),
                'icon'     => 'fa fa-dashboard',
                'href'     => admin_url('kpi/dashboard'),
                'position' => 1,
            ]);
        }

        if (has_permission('kpi_attendance', '', 'view') || has_permission('kpi_attendance', '', 'view_own')) {
            $CI->app_menu->add_sidebar_children_item('kpi', [
                'slug'     => 'kpi-attendance',
                'name'     => _l('attendance'),
                'icon'     => 'fa fa-calendar-check',
                'href'     => admin_url('kpi/attendance'),
                'position' => 2,
            ]);
        }
    }
}

/**
 * Init KPI module permissions in setup in admin_init hook
 */
function kpi_permissions()
{
    // Dashboard permissions - view and view_own
    $capabilities = [];
    $capabilities['capabilities'] = [
        'view_own' => _l('permission_view'),
        'view'     => _l('permission_view') . '(' . _l('permission_global') . ')',
    ];
    register_staff_capabilities('kpi_dashboard', $capabilities, _l('kpi') . ' - ' . _l('dashboard'));
    
    // Attendance permissions - view and view_own
    $capabilities_attendance = [];
    $capabilities_attendance['capabilities'] = [
        'view_own' => _l('permission_view'),
        'view'     => _l('permission_view') . '(' . _l('permission_global') . ')',
    ];
    register_staff_capabilities('kpi_attendance', $capabilities_attendance, _l('kpi') . ' - ' . _l('attendance'));
}

/**
 * Add CSS files to admin head
 */
function kpi_add_head_components()
{
    $CI = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];

    // Load CSS for KPI module pages
    if (strpos($viewuri, 'admin/kpi/') !== false) {
        echo '<link href="' . module_dir_url(KPI_MODULE_NAME, 'assets/css/kpi.css') . '?v=' . KPI_MODULE_VERSION . '" rel="stylesheet" type="text/css" />' . PHP_EOL;
    }
}

/**
 * Add JavaScript files to admin footer
 */
function kpi_add_footer_components()
{
    $CI = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];

    // Load JavaScript for attendance page
    if (strpos($viewuri, 'admin/kpi/attendance') !== false) {
        echo '<script src="' . module_dir_url(KPI_MODULE_NAME, 'assets/js/attendance.js') . '?v=' . KPI_MODULE_VERSION . '"></script>' . PHP_EOL;
    }
}


