<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Public_api extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('public_leads_api/public_leads_api_model');
        $this->load->model('leads_model');
        $this->output->set_content_type('application/json');
    }

    public function store()
    {
        $enabled = (int) get_option('public_leads_api_enabled', 1);
        if ($enabled !== 1) {
            return $this->respond(503, [
                'status'  => false,
                'message' => 'Public API is disabled',
            ]);
        }

        $token = $this->read_token();
        if (!$token) {
            return $this->respond(401, [
                'status'  => false,
                'message' => 'Missing API key',
            ]);
        }

        $apiKey = $this->public_leads_api_model->get_active_key_by_token($token);
        if (!$apiKey) {
            $this->public_leads_api_model->log_request([
                'api_key_id' => null,
                'status'     => 'denied',
                'message'    => 'Invalid API key',
                'payload'    => [],
                'ip'         => $this->input->ip_address(),
                'code'       => 401,
            ]);

            return $this->respond(401, [
                'status'  => false,
                'message' => 'Invalid API Key',
            ]);
        }

        $rateLimit   = (int) get_option('public_leads_api_rate_limit_per_minute', 60);
        $rateWindow  = (int) get_option('public_leads_api_rate_limit_window_minutes', 1);
        $blockedIps  = $this->parse_blocked_ips((string) get_option('public_leads_api_blocked_ips', ''));
        $clientIp    = $this->input->ip_address();

        if ($this->is_ip_blocked($clientIp, $blockedIps)) {
            $this->public_leads_api_model->log_request([
                'api_key_id' => null,
                'status'     => 'blocked_ip',
                'message'    => 'IP blocked',
                'payload'    => [],
                'ip'         => $clientIp,
                'code'       => 403,
            ]);

            return $this->respond(403, [
                'status'  => false,
                'message' => 'Access denied from this IP',
            ]);
        }

        // Enforce allowed content types
        $contentType = strtolower($this->input->server('CONTENT_TYPE') ?? '');
        if ($contentType && !str_contains($contentType, 'application/json') && !str_contains($contentType, 'multipart/form-data') && !str_contains($contentType, 'application/x-www-form-urlencoded')) {
            return $this->respond(415, [
                'status'  => false,
                'message' => 'Unsupported Content-Type',
            ]);
        }

        if ($this->public_leads_api_model->is_rate_limited((int) $apiKey->id, $rateLimit, $rateWindow, $clientIp)) {
            $this->public_leads_api_model->log_request([
                'api_key_id' => $apiKey->id,
                'status'     => 'rate_limited',
                'message'    => 'Rate limit exceeded',
                'payload'    => [],
                'ip'         => $this->input->ip_address(),
                'code'       => 429,
            ]);

            return $this->respond(429, [
                'status'  => false,
                'message' => 'Rate limit exceeded',
            ]);
        }

        $payload = public_leads_api_read_payload();
        // Basic size & shape guards
        $maxBytes = 65536; // 64 KB
        $contentLength = (int) ($this->input->server('CONTENT_LENGTH') ?? 0);
        if ($contentLength > $maxBytes) {
            return $this->respond(413, [
                'status'  => false,
                'message' => 'Payload too large',
            ]);
        }

        if (count($payload) > 100) {
            return $this->respond(400, [
                'status'  => false,
                'message' => 'Too many fields in payload',
            ]);
        }

        if (empty($payload)) {
            return $this->respond(400, [
                'status'  => false,
                'message' => 'Empty payload',
            ]);
        }

        // Require lead name; don't auto-fill with defaults
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            $this->public_leads_api_model->log_request([
                'api_key_id' => $apiKey->id,
                'status'     => 'error',
                'message'    => 'Name is required',
                'payload'    => $payload,
                'ip'         => $this->input->ip_address(),
                'code'       => 422,
            ]);

            return $this->respond(422, [
                'status'  => false,
                'message' => 'The field \"name\" is required',
            ]);
        }

        try {
            $leadData = $this->public_leads_api_model->prepare_lead($payload);
            $leadId   = $this->leads_model->add($leadData);

            if (!$leadId) {
                throw new Exception('Lead creation failed');
            }

            $this->public_leads_api_model->touch_key_usage((int) $apiKey->id);
            $this->public_leads_api_model->log_request([
                'api_key_id' => $apiKey->id,
                'lead_id'    => $leadId,
                'status'     => 'success',
                'message'    => 'Lead created',
                'payload'    => $payload,
                'ip'         => $this->input->ip_address(),
                'code'       => 200,
            ]);

            return $this->respond(200, [
                'status'  => true,
                'message' => 'Lead created successfully',
                'lead_id' => $leadId,
            ]);
        } catch (Throwable $e) {
            $this->public_leads_api_model->log_request([
                'api_key_id' => $apiKey->id,
                'status'     => 'error',
                'message'    => $e->getMessage(),
                'payload'    => $payload,
                'ip'         => $this->input->ip_address(),
                'code'       => 500,
            ]);

            return $this->respond(500, [
                'status'  => false,
                'message' => 'Unable to create lead',
            ]);
        }
    }

    private function respond(int $code, array $body)
    {
        return $this->output
            ->set_status_header($code)
            ->set_output(json_encode($body));
    }

    private function read_token(): ?string
    {
        $headerKey = $this->input->get_request_header('X-API-KEY');
        if ($headerKey) {
            return $this->sanitize_token($headerKey);
        }

        $auth = $this->input->get_request_header('Authorization');
        if ($auth && stripos($auth, 'Bearer ') === 0) {
            return $this->sanitize_token(substr($auth, 7));
        }

        return null;
    }

    /**
     * Remove CRLF and disallow characters outside a safe token set.
     */
    private function sanitize_token(string $token): ?string
    {
        if (preg_match('/[\r\n]/', $token)) {
            return null;
        }

        $token = trim($token);
        $token = preg_replace('/[^A-Za-z0-9._:-]/', '', $token);

        return $token === '' ? null : $token;
    }

    /**
     * Convert blocked IP option to array.
     */
    private function parse_blocked_ips(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[\\s,]+/', $raw);

        return array_filter(array_map('trim', $parts));
    }

    private function is_ip_blocked(string $ip, array $blocked): bool
    {
        return in_array($ip, $blocked, true);
    }
}
