<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Api_Controller extends App_Controller
{
    /**
     * Authenticated user payload
     * @var object|null
     */
    protected $current_user = null;

    public function __construct()
    {
        parent::__construct();

        $this->load->helper('admin');
        $this->load->library('crm_mobile_api/jwt_auth');

        // Setup CORS and JSON response headers
        $this->handle_cors();

        // If pre-flight OPTIONS request, exit immediately with 200
        if ($this->input->method(true) === 'OPTIONS') {
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode(['status' => true, 'message' => 'CORS preflight OK']))
                ->_display();
            exit;
        }

        $this->output->set_content_type('application/json', 'utf-8');
    }

    /**
     * Handle Cross-Origin Resource Sharing
     */
    protected function handle_cors()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With, Accept');
        header('Access-Control-Max-Age: 86400');
    }

    /**
     * Standard JSON response helper
     *
     * @param int $statusCode
     * @param array $data
     */
    protected function respond(int $statusCode, array $data)
    {
        $this->output
            ->set_status_header($statusCode)
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;
    }

    /**
     * Get request body as associative array (JSON or Form POST)
     *
     * @return array
     */
    protected function get_input_data(): array
    {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return (array) $this->input->post();
    }

    /**
     * Extract Bearer token from Authorization header or fallbacks
     *
     * @return string|null
     */
    protected function get_bearer_token(): ?string
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        if (empty($headers) && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        }

        $authHeader = null;
        foreach ($headers as $key => $val) {
            if (strcasecmp($key, 'Authorization') === 0 || strcasecmp($key, 'X-Authorization') === 0) {
                $authHeader = $val;
                break;
            }
        }

        if (!$authHeader) {
            $authHeader = $this->input->get_request_header('Authorization', true);
        }

        if (!$authHeader) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
        }

        if ($authHeader && preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            return $matches[1];
        }

        // Fallback to query parameter if needed
        $queryToken = $this->input->get('token', true);
        if (!empty($queryToken)) {
            return $queryToken;
        }

        return null;
    }

    /**
     * Authenticate request and enforce allowed roles
     *
     * @param array $allowedRoles E.g. ['staff', 'customer']
     * @return object Current user payload
     */
    protected function authenticate(array $allowedRoles = []): object
    {
        $token = $this->get_bearer_token();

        if (empty($token)) {
            $this->respond(401, [
                'status'  => false,
                'message' => 'Authorization token is missing. Please provide Authorization: Bearer <token>',
            ]);
        }

        $payload = $this->jwt_auth->decode($token);

        if (!$payload || !isset($payload->user_id) || !isset($payload->role)) {
            $this->respond(401, [
                'status'  => false,
                'message' => 'Invalid or expired authorization token.',
            ]);
        }

        // Verify active status in DB
        if ($payload->role === 'staff') {
            $this->db->select('staffid, firstname, lastname, email, active, admin, role');
            $this->db->where('staffid', (int) $payload->user_id);
            $staff = $this->db->get(db_prefix() . 'staff')->row();

            if (!$staff || (int) $staff->active !== 1) {
                $this->respond(401, [
                    'status'  => false,
                    'message' => 'Staff account is deactivated or not found.',
                ]);
            }
            $payload->is_admin = (int) $staff->admin === 1;
        } elseif ($payload->role === 'customer') {
            $this->db->select('c.id as contact_id, c.userid as client_id, c.firstname, c.lastname, c.email, c.active, cl.active as client_active');
            $this->db->from(db_prefix() . 'contacts c');
            $this->db->join(db_prefix() . 'clients cl', 'cl.userid = c.userid', 'left');
            $this->db->where('c.id', (int) $payload->user_id);
            $contact = $this->db->get()->row();

            if (!$contact || (int) $contact->active !== 1 || (int) $contact->client_active !== 1) {
                $this->respond(401, [
                    'status'  => false,
                    'message' => 'Customer account or company is deactivated.',
                ]);
            }
            $payload->client_id = (int) $contact->client_id;
        } else {
            $this->respond(401, [
                'status'  => false,
                'message' => 'Unknown user role in token.',
            ]);
        }

        // Check role permission
        if (!empty($allowedRoles) && !in_array($payload->role, $allowedRoles, true)) {
            $this->respond(403, [
                'status'  => false,
                'message' => 'Forbidden: You do not have permission to access this resource.',
            ]);
        }

        $this->current_user = $payload;
        return $payload;
    }
}
