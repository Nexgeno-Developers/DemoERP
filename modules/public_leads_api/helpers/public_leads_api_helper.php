<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Default lead column list used for direct mapping.
 */
function public_leads_api_lead_fields(): array
{
    return [
        'name', 'title', 'company', 'email', 'website', 'phonenumber',
        'address', 'city', 'state', 'zip', 'country', 'description',
        'assigned', 'status', 'source', 'lead_value', 'tags', 'default_language',
    ];
}

/**
 * Keys to ignore (honeypots, CSRF, captchas, internal helpers).
 */
function public_leads_api_ignored_fields(): array
{
    return [
        // CSRF / Tokens
        'csrf_token',
        'csrf_token_name',
        'csrf_test_name',
        '_token',
        '_csrf',
        'token',
        'auth_token',
        'access_token',
        'api_key',
        'signature',
        'timestamp',
        'nonce',

        // CAPTCHA
        'g-recaptcha-response',
        'recaptcha_token',
        'h-captcha-response',
        'hcaptcha_response',
        'cf-turnstile-response',
        'turnstile_token',

        // Honeypot
        'honeypot',
        'hp_field',

        // WordPress
        '_wpnonce',
        '_wp_http_referer',
        'wp_nonce',
        'wp_verify_nonce',
        'action',
        '_wpcf7',
        '_wpcf7_version',
        '_wpcf7_locale',
        '_wpcf7_unit_tag',
        '_wpcf7_container_post',       

        // Laravel / Framework
        '_method',
        '_previous_',
        '_flash',

        // JS / Frontend
        '__NEXT_DATA__',
        '__RequestVerificationToken',

        // Form metadata
        'form_id',
        'form_name',
        'submit',
        'submit_btn',
        'submit_button',
        'reset',

        // Files
        'file',
        'files',
        'attachment',
        'attachments',
        'upload'
    ];
}

/**
 * Quick check if a key should be ignored.
 */
function public_leads_api_should_ignore(string $key): bool
{
    $lower = strtolower($key);

    if (in_array($lower, public_leads_api_ignored_fields(), true)) {
        return true;
    }

    // ignore any field starting with underscore or containing 'csrf'
    if (strpos($key, '_') === 0 || str_contains($lower, 'csrf')) {
        return true;
    }

    return false;
}

/**
 * Numeric fields that should fall back to 0 instead of "-".
 */
function public_leads_api_numeric_fields(): array
{
    return ['assigned', 'status', 'source', 'country', 'lead_value'];
}

/**
 * Return sanitized string (arrays are JSON encoded).
 */
function public_leads_api_sanitize($value)
{
    if (is_array($value) || is_object($value)) {
        return json_encode($value);
    }

    $value = trim((string) $value);
    $value = strip_tags($value); // strip HTML tags to reduce XSS vectors
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value); // drop control chars

    return $value === '' ? '-' : $value;
}

/**
 * Provide safe default for empty values.
 */
function public_leads_api_default($field, $value)
{
    $value = is_null($value) ? '' : $value;

    if ($value === '') {
        return in_array($field, public_leads_api_numeric_fields(), true) ? 0 : '';
    }

    return public_leads_api_sanitize($value);
}

/**
 * Convert a snake/slug key to Title Case label.
 */
function public_leads_api_label_from_key(string $key): string
{
    $key = str_replace(['_', '-'], ' ', $key);
    $key = preg_replace('/\s+/', ' ', $key);

    return ucwords(trim($key));
}

/**
 * Split comma/newline list into unique, slugified keys.
 */
function public_leads_api_parse_allowed(string $raw): array
{
    if ($raw === '') {
        return [];
    }

    $parts = preg_split('/[\\s,]+/', $raw);
    $keys  = [];

    foreach ($parts as $part) {
        $slug = slug_it($part, ['separator' => '_']);
        if ($slug !== '') {
            $keys[$slug] = true;
        }
    }

    return array_keys($keys);
}

/**
 * Normalize incoming payload into associative array.
 */
function public_leads_api_read_payload(): array
{
    $contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
    $raw         = file_get_contents('php://input');
    $data        = [];

    try {
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } else {
            $data = $_POST;
            if (!$data && !empty($raw)) {
                // Fallback: try JSON anyway
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
        }
    } catch (Throwable $e) {
        $data = [];
    }

    return is_array($data) ? $data : [];
}

/**
 * Build a compact preview string to store in logs.
 */
function public_leads_api_compact_payload(array $data, int $max = 1000): string
{
    $json = json_encode($data);
    if (strlen($json) <= $max) {
        return $json;
    }

    return substr($json, 0, $max) . '...';
}
