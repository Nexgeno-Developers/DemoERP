<?php
# modules/timesheets_api/timesheets_api.php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: timesheets_api
Description: Create a simple authentication API module. Accepts a POST request with username and password.
Version: 1.0.1
Requires at least: 2.3.*
*/

register_activation_hook('timesheets_api', 'attendancecore_module_activation');

function attendancecore_module_activation() {
    // Activation code here (if needed)
}

register_deactivation_hook('timesheets_api', 'attendancecore_module_deactivation');

function attendancecore_module_deactivation() {
    // Deactivation code here (if needed)
}

hooks()->add_action('app_init', 'attendancecore_init');

function attendancecore_init() {
    $CI = &get_instance();
    $CI->load->helper('timesheets_api/attendancecore');
    $CI->load->library('timesheets_api/AttendanceCoreAuth');
}
