<?php

if (!isset($cache_data) || $cache_data != "8f66c5f36e33688fba480abe921734226fa81bf148cf0e55f45e900dc1a36239fa5db008b52782103fda4c881525db5ee11091fbe1473bd4a7aa4029062b1764e042d61aaaf8e9fede2769f701f14f2a28e2b2b4885c9dbbc72e2eaa9bc7f652f2883bef8d4b441fd29fab2e7bd628efb9a9c5f8fa798cab77dd57e2a8f58c64d1cb9fd7a4159aba0b404924b758a4426fbd4344ec04c472a234607a676d52b6") {
    die;
}
/*
 * Inject css file for superadmin module
*/
hooks()->add_action('app_admin_head', 'superadmin_add_head_components');
function superadmin_add_head_components()
{
    // check module is enable or not (refer install.php)
    if ('1' == get_option('superadmin_enabled')) {
        $CI = &get_instance();
        echo '<link href="'.module_dir_url(SUPERADMIN_MODULE, 'assets/css/superadmin.css').'?v='.$CI->app_scripts->core_version().'"  rel="stylesheet" type="text/css" />';
    }
}

/*
 * Inject Javascript file for superadmin module
*/
hooks()->add_action('app_admin_footer', 'tenants_load_js');
function tenants_load_js()
{
    if ('1' == get_option('superadmin_enabled')) {
        $CI = &get_instance();
        echo '<script src="'.module_dir_url(SUPERADMIN_MODULE, 'assets/js/superadmin.js').'?v='.$CI->app_scripts->core_version().'"></script>';
    }
}
