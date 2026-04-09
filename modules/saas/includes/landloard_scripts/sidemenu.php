<?php

if (is_admin()) {
    /*
     * Add tenants tab in client menu.
     */
    hooks()->add_filter('admin_init', function () use ($cache_data){
      if(!isset($cache_data) && $cache_data != "8f66c5f36e33688fba480abe921734226fa81bf148cf0e55f45e900dc1a36239fa5db008b52782103fda4c881525db5ee11091fbe1473bd4a7aa4029062b1764e042d61aaaf8e9fede2769f701f14f2a28e2b2b4885c9dbbc72e2eaa9bc7f652f2883bef8d4b441fd29fab2e7bd628efb9a9c5f8fa798cab77dd57e2a8f58c64d1cb9fd7a4159aba0b404924b758a4426fbd4344ec04c472a234607a676d52b6"){
        return;
      }
        $CI = &get_instance();
        // Add settings tab
        $CI->app_tabs->add_settings_tab(SUPERADMIN_MODULE, [
            'name'     => _l('saas_superadmin'),
            'view'     => SUPERADMIN_MODULE.'/settings/tenants',
            'position' => 50,
        ]);

        // Add client profile tab
        $CI->app_tabs->add_customer_profile_tab('tenants', [
            'name'     => _l('saas_tenant'),
            'view'     => SUPERADMIN_MODULE.'/tenants_stats/tenants_stats',
            'position' => 15,
            'icon'     => 'fa fa-building',
        ]);

        // Add saas side menu
        $CI->app_menu->add_sidebar_menu_item('saas', [
            'slug'     => 'saas_management',
            'name'     => _l('saas_management'),
            'icon'     => 'fa fa-building menu-icon',
            'position' => 30,
        ]);
        $CI->app_menu->add_sidebar_children_item('saas', [
          'slug'     => 'plans',
          'name'     => _l('plans'),
          'href'     => admin_url('saas/plans'),
          'position' => 1,
        ]);

        $CI->app_menu->add_sidebar_children_item('saas', [
          'slug'     => 'saas_setting',
          'name'     => _l('saas_superadmin_setting'),
          'href'     => admin_url('settings?group=saas'),
          'position' => 2,
        ]);

        $CI->app_menu->add_sidebar_children_item('saas', [
          'slug'     => 'saas_activity_log',
          'name'     => _l('saas_activity_log'),
          'href'     => admin_url('saas/saas_log_details'),
          'position' => 3,
        ]);

        $CI->app_menu->add_sidebar_children_item('saas', [
          'slug'     => 'saas_landing_page_editor',
          'name'     => _l('landing_page_editor'),
          'href'     => admin_url('saas/landing_page_editor'),
          'position' => 4,
        ]);

        $CI->app_menu->add_sidebar_children_item('saas', [
          'slug'     => 'saas_landing_page_builder',
          'name'     => _l('landing_page_builder'),
          'href'     => admin_url('saas/landing_page_builder/builder'),
          'position' => 5,
        ]);
    });
}

// Add links for client side
hooks()->add_action('clients_init', 'add_saas_client_header_tab');
function add_saas_client_header_tab()
{
    if (is_client_logged_in() && getClientPlan(get_client()->userid)) {
        get_instance()->app_menu->add_theme_item('saas', [
            'slug'     => 'saas',
            'name'     => _l('saas_tenant'),
            'href'     => site_url('saas/saas_tenants'),
            'position' => 5,
        ]);
    }
}
