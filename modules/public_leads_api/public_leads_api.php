<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Leads API
Description: Exposes a secure public endpoint that accepts form submissions from external sites and stores them as Perfex leads (including dynamic custom fields).
Version: 1.0.0
Requires at least: 2.3.*
Author: Codex
*/

define('PUBLIC_LEADS_API_MODULE', 'public_leads_api');

hooks()->add_action('app_init', 'public_leads_api_init');
hooks()->add_action('admin_init', 'public_leads_api_register_menu');
hooks()->add_action('after_cron_run', 'public_leads_api_inactive_notification_cron');

register_activation_hook(PUBLIC_LEADS_API_MODULE, 'public_leads_api_activate');
register_uninstall_hook(PUBLIC_LEADS_API_MODULE, 'public_leads_api_uninstall');

/**
 * Bootstrap helper(s) so shared functions are available everywhere.
 */
function public_leads_api_init()
{
    $CI = &get_instance();
    $CI->load->helper(PUBLIC_LEADS_API_MODULE . '/public_leads_api');
}

/**
 * Install callback.
 */
function public_leads_api_activate()
{
    require_once(__DIR__ . '/install.php');
}

/**
 * Uninstall callback.
 */
function public_leads_api_uninstall()
{
    require_once(__DIR__ . '/uninstall.php');
}

/**
 * Add a Setup menu entry for managing API keys/settings.
 */
function public_leads_api_register_menu()
{
    if (!is_admin()) {
        return;
    }

    $CI = &get_instance();

    $CI->app_menu->add_setup_menu_item('public-leads-api', [
        'name'     => 'Leads API',
        'href'     => admin_url('public_leads_api'),
        'position' => 45,
        'icon'     => '',
    ]);
}

/**
 * Cron: send email when API keys have been inactive for more than X days.
 * Runs after each cron run; throttled to at most one notification per 24 hours.
 */
function public_leads_api_inactive_notification_cron()
{
    $CI = &get_instance();

    $inactiveDays = (int) get_option('public_leads_api_inactive_days', 30);
    $emailsRaw    = (string) get_option('public_leads_api_inactive_notify_emails', '');

    if ($inactiveDays <= 0 || $emailsRaw === '') {
        return;
    }

    $CI->load->model('public_leads_api/public_leads_api_model');
    $inactiveKeys = $CI->public_leads_api_model->get_inactive_keys($inactiveDays);

    if (empty($inactiveKeys)) {
        return;
    }

    // Throttle: send at most once per 24 hours
    $lastSent = get_option('public_leads_api_last_inactive_notification_sent');
    if ($lastSent && (time() - (int) $lastSent) < 86400) {
        return;
    }

    $recipients = public_leads_api_parse_notification_emails($emailsRaw);
    if (empty($recipients)) {
        return;
    }

    $parts = [];
    $parts[] = 'The following Public Leads API key(s) have not been used for more than ' . $inactiveDays . ' day(s):';
    $parts[] = '<br />';
    $url = admin_url('public_leads_api');
    foreach ($inactiveKeys as $key) {
        $lastUsed = !empty($key['last_used_at']) ? $key['last_used_at'] : 'Never';
        $parts[] = '• <strong>Label:</strong> ' . htmlspecialchars($key['label'], ENT_QUOTES, 'UTF-8') . '<br />';
        $parts[] = '&nbsp;&nbsp;<strong>Last used:</strong> ' . htmlspecialchars($lastUsed, ENT_QUOTES, 'UTF-8') . '<br />';
        $parts[] = '<br />';
    }
    $parts[] = 'You can review or revoke keys at: <a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</a>';

    $subject = 'Public Leads API – Inactive key(s) notification';
    $message = implode('', $parts);

    $CI->load->model('emails_model');
    foreach ($recipients as $email) {
        $CI->emails_model->send_simple_email($email, $subject, $message);
    }

    update_option('public_leads_api_last_inactive_notification_sent', (string) time());
}

/**
 * Parse notification emails string (comma or newline separated) and return valid addresses.
 *
 * @param string $raw
 * @return array
 */
function public_leads_api_parse_notification_emails(string $raw): array
{
    $CI = &get_instance();
    if (!function_exists('valid_email')) {
        $CI->load->helper('app_email');
    }

    $parts = preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
    $out   = [];
    foreach ($parts as $part) {
        $email = trim($part);
        if ($email !== '' && valid_email($email)) {
            $out[] = $email;
        }
    }

    return array_unique($out);
}
