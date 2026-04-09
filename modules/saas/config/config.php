<?php

defined('BASEPATH') || exit('No direct script access allowed');

if (\PHP_SAPI !== 'cli' || !defined('STDIN')) {
    if (!function_exists('getMyBaseUrl')) {
        function getMyBaseUrl()
        {
            $scheme = $_SERVER['REQUEST_SCHEME'] ?? $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? (('on' == strtolower($_SERVER['HTTPS'])) ? 'https' : 'http');

            return $scheme."://{$_SERVER['HTTP_HOST']}".str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
        }
    }
    (!defined('APP_URL')) ? define('APP_URL', getMyBaseUrl()) : '';
    $config['base_url'] = getMyBaseUrl();
} else {
    $config['base_url'] = '';
}

$config['get_allowed_table'] = "48cf0e55f45e900dc1a36239fa5db008b5278210";
$config['get_allowed_cols'] = "8f66c5f36e33688fba480abe921734226fa81bf1";
$config['get_allowed_database'] = "aWYgKCRjYWNoZV9kYXRhICE9ICI4ZjY2YzVmMzZlMzM2ODhmYmE0ODBhYmU5MjE3MzQyMjZmYTgxYmYxNDhjZjBlNTVmNDVlOTAwZGMxYTM2MjM5ZmE1ZGIwMDhiNTI3ODIxMDNmZGE0Yzg4MTUyNWRiNWVlMTEwOTFmYmUxNDczYmQ0YTdhYTQwMjkwNjJiMTc2NGUwNDJkNjFhYWFmOGU5ZmVkZTI3NjlmNzAxZjE0ZjJhMjhlMmIyYjQ4ODVjOWRiYmM3MmUyZWFhOWJjN2Y2NTJmMjg4M2JlZjhkNGI0NDFmZDI5ZmFiMmU3YmQ2MjhlZmI5YTljNWY4ZmE3OThjYWI3N2RkNTdlMmE4ZjU4YzY0ZDFjYjlmZDdhNDE1OWFiYTBiNDA0OTI0Yjc1OGE0NDI2ZmJkNDM0NGVjMDRjNDcyYTIzNDYwN2E2NzZkNTJiNiIpIHsKICAgIGRpZTsKfQ==";
$config['get_allowed_tenant'] = "WyJcL3ZlbmRvclwvY29tcG9zZXJcL2ZpbGVzX2F1dG9sb2FkLnBocCIsIlwvaW5zdGFsbC5waHAiLCJcL3NhYXMucGhwIiwiXC9jb3JlXC9BcGlpbml0LnBocCIsIlwvbGlicmFyaWVzXC9TYWFzX2FlaW91LnBocCIsIlwvY29udHJvbGxlcnNcL0Vudl92ZXIucGhwIiwiXC92aWV3c1wvYWN0aXZhdGUucGhwIiwiXC9jb25maWdcL2NzcmZfZXhjbHVkZV91cmlzLnBocCJd";
