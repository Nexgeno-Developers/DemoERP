<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once dirname(__DIR__) . '/core/Api_Controller.php';

class Auth extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('authentication_model');
    }

    /**
     * Staff Login
     * POST /api/v1/auth/staff/login
     */
    public function staff_login()
    {
        $input = $this->get_input_data();

        $email    = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->respond(422, [
                'status'  => false,
                'message' => 'Email and password are required.',
            ]);
        }

        // Query staff by email
        $this->db->where('email', $email);
        $user = $this->db->get(db_prefix() . 'staff')->row();

        if (!$user) {
            $this->respond(401, [
                'status'  => false,
                'message' => 'Invalid email or password.',
            ]);
        }

        if ((int) $user->active !== 1) {
            $this->respond(403, [
                'status'  => false,
                'message' => 'Your staff account is currently inactive. Contact your administrator.',
            ]);
        }

        // Check password using Perfex native hasher
        $this->load->helper('phpass');
        $hasher = new PasswordHash(PHPASS_HASH_STRENGTH, PHPASS_HASH_PORTABLE);

        if (!$hasher->CheckPassword($password, $user->password)) {
            $this->respond(401, [
                'status'  => false,
                'message' => 'Invalid email or password.',
            ]);
        }

        // Generate token
        $payload = [
            'user_id'   => (int) $user->staffid,
            'role'      => 'staff',
            'email'     => $user->email,
            'name'      => trim($user->firstname . ' ' . $user->lastname),
            'role_id'   => (int) $user->role,
            'is_admin'  => (int) $user->admin === 1,
        ];

        $token = $this->jwt_auth->encode($payload);

        // Fetch staff profile photo if available
        $profileImage = staff_profile_image_url($user->staffid);

        $this->respond(200, [
            'status'  => true,
            'message' => 'Staff login successful.',
            'data'    => [
                'token'      => $token,
                'expires_in' => 2592000, // 30 days in seconds
                'user'       => [
                    'id'            => (int) $user->staffid,
                    'role'          => 'staff',
                    'firstname'     => $user->firstname,
                    'lastname'      => $user->lastname,
                    'email'         => $user->email,
                    'phone'         => $user->phonenumber,
                    'is_admin'      => (int) $user->admin === 1,
                    'profile_image' => $profileImage,
                ],
            ],
        ]);
    }

    /**
     * Customer Contact Login
     * POST /api/v1/auth/customer/login
     */
    public function customer_login()
    {
        $input = $this->get_input_data();

        $email    = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->respond(422, [
                'status'  => false,
                'message' => 'Email and password are required.',
            ]);
        }

        // Query contact by email
        $this->db->select('c.*, cl.company, cl.active as client_active');
        $this->db->from(db_prefix() . 'contacts c');
        $this->db->join(db_prefix() . 'clients cl', 'cl.userid = c.userid', 'left');
        $this->db->where('c.email', $email);
        $contact = $this->db->get()->row();

        if (!$contact) {
            $this->respond(401, [
                'status'  => false,
                'message' => 'Invalid email or password.',
            ]);
        }

        if ((int) $contact->active !== 1 || (int) $contact->client_active !== 1) {
            $this->respond(403, [
                'status'  => false,
                'message' => 'Your customer account is currently inactive.',
            ]);
        }

        // Verify password
        $this->load->helper('phpass');
        $hasher = new PasswordHash(PHPASS_HASH_STRENGTH, PHPASS_HASH_PORTABLE);

        if (!$hasher->CheckPassword($password, $contact->password)) {
            $this->respond(401, [
                'status'  => false,
                'message' => 'Invalid email or password.',
            ]);
        }

        // Generate token
        $payload = [
            'user_id'   => (int) $contact->id,
            'role'      => 'customer',
            'client_id' => (int) $contact->userid,
            'email'     => $contact->email,
            'name'      => trim($contact->firstname . ' ' . $contact->lastname),
        ];

        $token = $this->jwt_auth->encode($payload);

        $profileImage = contact_profile_image_url($contact->id);

        $this->respond(200, [
            'status'  => true,
            'message' => 'Customer login successful.',
            'data'    => [
                'token'      => $token,
                'expires_in' => 2592000,
                'user'       => [
                    'id'            => (int) $contact->id,
                    'client_id'     => (int) $contact->userid,
                    'company'       => $contact->company,
                    'role'          => 'customer',
                    'firstname'     => $contact->firstname,
                    'lastname'      => $contact->lastname,
                    'email'         => $contact->email,
                    'phone'         => $contact->phonenumber,
                    'title'         => $contact->title,
                    'profile_image' => $profileImage,
                ],
            ],
        ]);
    }

    /**
     * Get Current Logged-in User Info
     * GET /api/v1/auth/me
     */
    public function me()
    {
        $user = $this->authenticate();

        if ($user->role === 'staff') {
            $this->db->where('staffid', (int) $user->user_id);
            $staff = $this->db->get(db_prefix() . 'staff')->row();

            $this->respond(200, [
                'status' => true,
                'data'   => [
                    'id'            => (int) $staff->staffid,
                    'role'          => 'staff',
                    'firstname'     => $staff->firstname,
                    'lastname'      => $staff->lastname,
                    'email'         => $staff->email,
                    'phone'         => $staff->phonenumber,
                    'is_admin'      => (int) $staff->admin === 1,
                    'profile_image' => staff_profile_image_url($staff->staffid),
                ],
            ]);
        } else {
            $this->db->select('c.*, cl.company');
            $this->db->from(db_prefix() . 'contacts c');
            $this->db->join(db_prefix() . 'clients cl', 'cl.userid = c.userid', 'left');
            $this->db->where('c.id', (int) $user->user_id);
            $contact = $this->db->get()->row();

            $this->respond(200, [
                'status' => true,
                'data'   => [
                    'id'            => (int) $contact->id,
                    'client_id'     => (int) $contact->userid,
                    'company'       => $contact->company,
                    'role'          => 'customer',
                    'firstname'     => $contact->firstname,
                    'lastname'      => $contact->lastname,
                    'email'         => $contact->email,
                    'phone'         => $contact->phonenumber,
                    'title'         => $contact->title,
                    'profile_image' => contact_profile_image_url($contact->id),
                ],
            ]);
        }
    }
}
