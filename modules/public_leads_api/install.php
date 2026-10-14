<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$keysTable = db_prefix() . 'pla_api_keys';
$logsTable = db_prefix() . 'pla_api_logs';

$CI->db->query("
    CREATE TABLE IF NOT EXISTS `{$keysTable}` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `label` VARCHAR(191) NOT NULL,
        `api_key` VARCHAR(64) NOT NULL,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `usage_count` INT(11) NOT NULL DEFAULT 0,
        `last_used_at` DATETIME NULL DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `api_key_unique` (`api_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$CI->db->query("
    CREATE TABLE IF NOT EXISTS `{$logsTable}` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `api_key_id` INT(11) UNSIGNED NULL,
        `lead_id` INT(11) UNSIGNED NULL,
        `status` VARCHAR(50) NOT NULL,
        `message` VARCHAR(255) NOT NULL,
        `payload` LONGTEXT NULL,
        `ip_address` VARCHAR(64) NULL,
        `response_code` SMALLINT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `api_key_idx` (`api_key_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

add_option('public_leads_api_enabled', 1);
add_option('public_leads_api_rate_limit_per_minute', 60);
add_option('public_leads_api_allowed_custom_fields', '');
add_option('public_leads_api_inactive_days', 30);
add_option('public_leads_api_inactive_notify_emails', '');

/**
 * Ensure application/config/my_routes.php exists so module routes
 * (including this module) are loaded automatically without touching
 * core routes.php. Only create if missing.
 */
$myRoutesPath = APPPATH . 'config/my_routes.php';

if (!file_exists($myRoutesPath)) {
    $loader = <<<'PHP'
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

    file_put_contents($myRoutesPath, $loader);
}
