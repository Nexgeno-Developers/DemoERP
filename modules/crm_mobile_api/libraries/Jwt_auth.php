<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Jwt_auth
{
    private $secret;
    private $algorithm = 'HS256';

    public function __construct()
    {
        $key = null;
        if (defined('APP_ENC_KEY') && !empty(APP_ENC_KEY)) {
            $key = APP_ENC_KEY;
        } elseif (function_exists('config_item') && !empty(config_item('encryption_key'))) {
            $key = config_item('encryption_key');
        } elseif (function_exists('get_instance') && get_instance() !== null) {
            $key = get_instance()->config->item('encryption_key');
        }

        if (empty($key) && defined('APP_BASE_URL')) {
            $key = 'perfex_crm_mobile_jwt_key_' . md5(APP_BASE_URL);
        }

        if (empty($key)) {
            $key = 'perfex_crm_mobile_jwt_default_secret_key_12345';
        }

        $this->secret = $key;
    }

    /**
     * Generate signed JWT token
     *
     * @param array $payload
     * @param int $ttlSeconds (Default 30 days = 2592000 seconds)
     * @return string
     */
    public function encode(array $payload, int $ttlSeconds = 2592000): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => $this->algorithm,
        ];

        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $ttlSeconds;

        $base64UrlHeader  = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $this->secret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }

    /**
     * Decode and validate JWT token
     *
     * @param string $token
     * @return object|false Returns payload object on success, false on failure/expired
     */
    public function decode(string $token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;

        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $this->secret, true);
        $expectedBase64UrlSignature = $this->base64UrlEncode($expectedSignature);

        if (!hash_equals($expectedBase64UrlSignature, $base64UrlSignature)) {
            return false;
        }

        $payloadJson = $this->base64UrlDecode($base64UrlPayload);
        $payload = json_decode($payloadJson);

        if (!$payload || !isset($payload->exp)) {
            return false;
        }

        // Check token expiration
        if (time() >= $payload->exp) {
            return false;
        }

        return $payload;
    }

    private function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
