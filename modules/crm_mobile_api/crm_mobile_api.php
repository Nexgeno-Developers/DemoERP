<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: CRM Mobile App API
Description: Secure RESTful API for Flutter Mobile Application (Staff & Customer Auth, Support & Tickets)
Version: 1.0.0
Requires at least: 2.3.*
*/

define('CRM_MOBILE_API_MODULE_NAME', 'crm_mobile_api');

register_activation_hook(CRM_MOBILE_API_MODULE_NAME, 'crm_mobile_api_activation_hook');
register_deactivation_hook(CRM_MOBILE_API_MODULE_NAME, 'crm_mobile_api_deactivation_hook');

function crm_mobile_api_activation_hook()
{
    // Module activation logic if needed in future
}

function crm_mobile_api_deactivation_hook()
{
    // Module deactivation logic if needed in future
}

hooks()->add_action('app_init', 'crm_mobile_api_init');

function crm_mobile_api_init()
{
    $CI = &get_instance();
    // Preload library for API use
    $CI->load->library(CRM_MOBILE_API_MODULE_NAME . '/jwt_auth');
}
