<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Reminder
Description: AMC, Anniversary, and Birthday reminder management module
Version: 1.0.0
Requires at least: 2.3.*
Author: Nexgeno Technologies
*/

define('REMINDER_MODULE_NAME', 'reminder');

hooks()->add_action('admin_init', 'reminder_module_init_menu_items');
hooks()->add_action('admin_init', 'reminder_permissions');

/**
 * Register activation module hook
 */
register_activation_hook(REMINDER_MODULE_NAME, 'reminder_module_activation_hook');

/**
 * Register language files
 */
register_language_files(REMINDER_MODULE_NAME, [REMINDER_MODULE_NAME]);

/**
 * Module activation hook
 */
function reminder_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Init reminder module menu items in setup in admin_init hook
 */
function reminder_module_init_menu_items()
{
    $CI = &get_instance();
    
    // Show main menu if user has at least one permission
    if (has_permission('reminder_amc', '', 'view') || 
        has_permission('reminder_anniversary', '', 'view') || 
        has_permission('reminder_birthday', '', 'view') || 
        has_permission('reminder_settings', '', 'view')) {
        
        $CI->app_menu->add_sidebar_menu_item('reminder', [
            'name'     => _l('reminder'),
            'href'     => admin_url('reminder/amc'),
            'icon'     => 'fa fa-bell',
            'position' => 40
        ]);

        if (has_permission('reminder_amc', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('reminder', [
                'slug'     => 'reminder-amc',
                'name'     => _l('amc'),
                'icon'     => 'fa fa-calendar-check',
                'href'     => admin_url('reminder/amc'),
                'position' => 1,
            ]);
        }

        if (has_permission('reminder_anniversary', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('reminder', [
                'slug'     => 'reminder-anniversary',
                'name'     => _l('anniversary'),
                'icon'     => 'fa fa-gift',
                'href'     => admin_url('reminder/anniversary'),
                'position' => 2,
            ]);
        }

        if (has_permission('reminder_birthday', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('reminder', [
                'slug'     => 'reminder-birthday',
                'name'     => _l('birthday'),
                'icon'     => 'fa fa-birthday-cake',
                'href'     => admin_url('reminder/birthday'),
                'position' => 3,
            ]);
        }

        if (has_permission('reminder_settings', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item('reminder', [
                'slug'     => 'reminder-settings',
                'name'     => _l('settings'),
                'icon'     => 'fa fa-cog',
                'href'     => admin_url('reminder/settings'),
                'position' => 4,
            ]);
        }
    }
}

/**
 * Init reminder module permissions in setup in admin_init hook
 */
function reminder_permissions()
{
    // AMC permissions - view, create (includes renew), edit, delete
    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];
    register_staff_capabilities('reminder_amc', $capabilities, _l('reminder') . ' - ' . _l('amc'));
    
    // Anniversary permissions - view only
    $capabilities_anniversary = [];
    $capabilities_anniversary['capabilities'] = [
        'view'   => _l('permission_view'),
    ];
    register_staff_capabilities('reminder_anniversary', $capabilities_anniversary, _l('reminder') . ' - ' . _l('anniversary'));
    
    // Birthday permissions - view only
    $capabilities_birthday = [];
    $capabilities_birthday['capabilities'] = [
        'view'   => _l('permission_view'),
    ];
    register_staff_capabilities('reminder_birthday', $capabilities_birthday, _l('reminder') . ' - ' . _l('birthday'));
    
    // Settings permissions - view, create, edit, delete
    $capabilities_settings = [];
    $capabilities_settings['capabilities'] = [
        'view'   => _l('permission_view'),
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];
    register_staff_capabilities('reminder_settings', $capabilities_settings, _l('reminder') . ' - ' . _l('settings'));
}

