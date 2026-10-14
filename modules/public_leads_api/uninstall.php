<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'pla_api_logs`');
$CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'pla_api_keys`');

delete_option('public_leads_api_enabled');
delete_option('public_leads_api_rate_limit_per_minute');
delete_option('public_leads_api_rate_limit_window_minutes');
delete_option('public_leads_api_blocked_ips');
delete_option('public_leads_api_allowed_custom_fields');
delete_option('public_leads_api_inactive_days');
delete_option('public_leads_api_inactive_notify_emails');
delete_option('public_leads_api_last_inactive_notification_sent');

/**
 * Optionally clean up the auto-created my_routes.php if it still matches
 * the template we generated during install. If the file was edited by the
 * user, leave it intact.
 */
$myRoutesPath = APPPATH . 'config/my_routes.php';

if (file_exists($myRoutesPath)) {
    $expected = <<<'PHP'
<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Load module routes (SAFE MODE)
|--------------------------------------------------------------------------
| This file is loaded BEFORE CI_Controller exists.
| Do NOT use get_instance(), DB, or Perfex services here.
|
| Only include static route files.
*/

$modules_path = APPPATH . '../modules/';

if (is_dir($modules_path)) {
    foreach (glob($modules_path . '*/config/routes.php') as $route_file) {
        include $route_file;
    }
}

PHP;

    $current = file_get_contents($myRoutesPath);

    if (trim($current) === trim($expected)) {
        @unlink($myRoutesPath);
    }
}
