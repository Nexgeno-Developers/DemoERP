<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once dirname(__DIR__) . '/core/Api_Controller.php';

class Staff_tickets extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('tickets_model');
        $this->load->helper('upload');
    }

    /**
     * List Tickets for Staff
     * GET /api/v1/staff/tickets
     */
    public function index()
    {
        try {
            $user = $this->authenticate(['staff']);

        $page   = max(1, (int) $this->input->get('page'));
        $limit  = max(1, min(50, (int) ($this->input->get('limit') ?: 20)));
        $offset = ($page - 1) * $limit;

        $statusId   = $this->input->get('status');
        $priorityId = $this->input->get('priority');
        $deptId     = $this->input->get('department');
        $assigned   = $this->input->get('assigned'); // 'me', 'all', or specific staffid
        $search     = trim($this->input->get('q') ?: '');

        $staffId = (int) $user->user_id;
        $isAdmin = !empty($user->is_admin);

        $this->db->start_cache();
        $this->db->from(db_prefix() . 'tickets t');
        $this->db->join(db_prefix() . 'departments d', 'd.departmentid = t.department', 'left');
        $this->db->join(db_prefix() . 'tickets_priorities p', 'p.priorityid = t.priority', 'left');
        $this->db->join(db_prefix() . 'tickets_status s', 's.ticketstatusid = t.status', 'left');
        $this->db->join(db_prefix() . 'services srv', 'srv.serviceid = t.service', 'left');
        $this->db->join(db_prefix() . 'clients cl', 'cl.userid = t.userid', 'left');
        $this->db->join(db_prefix() . 'contacts ct', 'ct.id = t.contactid', 'left');
        $this->db->join(db_prefix() . 'staff st', 'st.staffid = t.assigned', 'left');

        // Assignment filtering
        if ($assigned === 'me') {
            $this->db->where('t.assigned', $staffId);
        } elseif (is_numeric($assigned) && $assigned > 0) {
            $this->db->where('t.assigned', (int) $assigned);
        } elseif (!$isAdmin && !has_permission('tickets', $staffId, 'view')) {
            // If staff does not have global view tickets permission, restrict to assigned
            $this->db->where('t.assigned', $staffId);
        }

        if (!empty($statusId) && is_numeric($statusId)) {
            $this->db->where('t.status', (int) $statusId);
        }

        if (!empty($priorityId) && is_numeric($priorityId)) {
            $this->db->where('t.priority', (int) $priorityId);
        }

        if (!empty($deptId) && is_numeric($deptId)) {
            $this->db->where('t.department', (int) $deptId);
        }

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('t.subject', $search);
            $this->db->or_like('t.ticketid', $search);
            $this->db->or_like('cl.company', $search);
            $this->db->or_like('ct.firstname', $search);
            $this->db->or_like('ct.lastname', $search);
            $this->db->group_end();
        }
        $this->db->stop_cache();

        $total = $this->db->count_all_results();

        $this->db->select('t.ticketid as id, t.subject, t.status, s.name as status_name, s.statuscolor, t.priority, p.name as priority_name, t.department, d.name as department_name, t.service, srv.name as service_name, t.date, t.lastreply, t.assigned, t.userid as client_id, cl.company, ct.firstname as contact_firstname, ct.lastname as contact_lastname, st.firstname as assigned_firstname, st.lastname as assigned_lastname');
        $this->db->order_by('t.lastreply', 'DESC');
        $this->db->limit($limit, $offset);
        $tickets = $this->db->get()->result_array();
        $this->db->flush_cache();

        $formattedTickets = [];
        foreach ($tickets as $ticket) {
            $contactName = trim(($ticket['contact_firstname'] ?? '') . ' ' . ($ticket['contact_lastname'] ?? ''));
            $assignedName = trim(($ticket['assigned_firstname'] ?? '') . ' ' . ($ticket['assigned_lastname'] ?? ''));

            $formattedTickets[] = [
                'id'              => (int) $ticket['id'],
                'subject'         => $ticket['subject'],
                'status_id'       => (int) $ticket['status'],
                'status_name'     => ticket_status_translate($ticket['status']),
                'status_color'    => $ticket['statuscolor'],
                'priority_id'     => (int) $ticket['priority'],
                'priority_name'   => ticket_priority_translate($ticket['priority']),
                'department_name' => $ticket['department_name'],
                'service_name'    => $ticket['service_name'],
                'company'         => $ticket['company'] ?: $contactName,
                'contact_name'    => $contactName,
                'assigned_id'     => (int) $ticket['assigned'],
                'assigned_name'   => $assignedName ?: 'Unassigned',
                'created_at'      => $ticket['date'],
                'last_reply'      => $ticket['lastreply'],
            ];
        }

            $this->respond(200, [
                'status' => true,
                'data'   => [
                    'total'       => $total,
                    'page'        => $page,
                    'limit'       => $limit,
                    'total_pages' => ceil($total / $limit),
                    'tickets'     => $formattedTickets,
                ],
            ]);
        } catch (Throwable $e) {
            $this->respond(500, [
                'status'  => false,
                'message' => $e->getMessage(),
                'file'    => basename($e->getFile()),
                'line'    => $e->getLine(),
            ]);
        }
    }

    /**
     * Get Single Ticket Details for Staff
     * GET /api/v1/staff/tickets/{id}
     */
    public function show($id)
    {
        $user = $this->authenticate(['staff']);
        $ticket = $this->tickets_model->get((int) $id);

        if (!$ticket) {
            $this->respond(404, [
                'status'  => false,
                'message' => 'Ticket not found.',
            ]);
        }

        // Attachments
        $ticketAttachments = $this->tickets_model->get_ticket_attachments($ticket->ticketid);
        $formattedAttachments = [];
        foreach ($ticketAttachments as $att) {
            $formattedAttachments[] = [
                'id'       => (int) $att['id'],
                'filename' => $att['file_name'],
                'filetype' => $att['filetype'],
                'url'      => site_url('download/file/ticket/' . $att['id']),
            ];
        }

        // Replies & Notes
        $replies = $this->tickets_model->get_ticket_replies($ticket->ticketid);
        $formattedReplies = [];
        foreach ($replies as $reply) {
            $isStaff = !empty($reply['admin']);
            $senderName = $isStaff
                ? trim(($reply['staff_firstname'] ?? '') . ' ' . ($reply['staff_lastname'] ?? ''))
                : trim(($reply['user_firstname'] ?? '') . ' ' . ($reply['user_lastname'] ?? ''));

            if (empty($senderName)) {
                $senderName = $reply['from_name'] ?: ($isStaff ? 'Staff' : 'Customer');
            }

            $replyAttachments = [];
            if (!empty($reply['attachments'])) {
                foreach ($reply['attachments'] as $att) {
                    $replyAttachments[] = [
                        'id'       => (int) $att['id'],
                        'filename' => $att['file_name'],
                        'filetype' => $att['filetype'],
                        'url'      => site_url('download/file/ticket/' . $att['id']),
                    ];
                }
            }

            $formattedReplies[] = [
                'id'            => (int) $reply['id'],
                'message'       => $reply['message'],
                'date'          => $reply['date'],
                'is_staff'      => $isStaff,
                'sender_name'   => $senderName,
                'profile_image' => $isStaff ? staff_profile_image_url($reply['admin']) : contact_profile_image_url($reply['contactid']),
                'attachments'   => $replyAttachments,
            ];
        }

        $assignedName = '';
        if (!empty($ticket->assigned)) {
            $assignedStaff = $this->db->select('firstname, lastname')->where('staffid', (int) $ticket->assigned)->get(db_prefix() . 'staff')->row();
            if ($assignedStaff) {
                $assignedName = trim($assignedStaff->firstname . ' ' . $assignedStaff->lastname);
            }
        }

        $this->respond(200, [
            'status' => true,
            'data'   => [
                'id'              => (int) $ticket->ticketid,
                'subject'         => $ticket->subject,
                'message'         => $ticket->message,
                'status_id'       => (int) $ticket->status,
                'status_name'     => ticket_status_translate($ticket->status),
                'status_color'    => $ticket->statuscolor,
                'priority_id'     => (int) $ticket->priority,
                'priority_name'   => ticket_priority_translate($ticket->priority),
                'department_name' => $ticket->department_name,
                'service_name'    => $ticket->service_name,
                'client_id'       => (int) $ticket->userid,
                'company'         => $ticket->company,
                'contact_name'    => trim(($ticket->contact_firstname ?? '') . ' ' . ($ticket->contact_lastname ?? '')),
                'contact_email'   => $ticket->email,
                'assigned_id'     => (int) $ticket->assigned,
                'assigned_name'   => $assignedName ?: 'Unassigned',
                'created_at'      => $ticket->date,
                'last_reply'      => $ticket->lastreply,
                'attachments'     => $formattedAttachments,
                'replies'         => $formattedReplies,
            ],
        ]);
    }

    /**
     * Change Ticket Status
     * PUT or POST /api/v1/staff/tickets/{id}/status
     */
    public function update_status($id)
    {
        $user = $this->authenticate(['staff']);
        $input = $this->get_input_data();

        $ticket = $this->tickets_model->get((int) $id);
        if (!$ticket) {
            $this->respond(404, [
                'status'  => false,
                'message' => 'Ticket not found.',
            ]);
        }

        $statusId = (int) ($input['status_id'] ?? $input['status'] ?? 0);
        if ($statusId <= 0) {
            $this->respond(422, [
                'status'  => false,
                'message' => 'Valid status_id is required.',
            ]);
        }

        $success = $this->tickets_model->change_ticket_status((int) $id, $statusId);

        if (!$success) {
            $this->respond(500, [
                'status'  => false,
                'message' => 'Failed to update ticket status.',
            ]);
        }

        $this->respond(200, [
            'status'  => true,
            'message' => 'Ticket status updated successfully.',
            'data'    => [
                'ticket_id'   => (int) $id,
                'status_id'   => $statusId,
                'status_name' => ticket_status_translate($statusId),
            ],
        ]);
    }

    /**
     * Staff Post Reply to Ticket
     * POST /api/v1/staff/tickets/{id}/reply
     */
    public function reply($id)
    {
        $user = $this->authenticate(['staff']);
        $input = $this->get_input_data();

        $ticket = $this->tickets_model->get((int) $id);
        if (!$ticket) {
            $this->respond(404, [
                'status'  => false,
                'message' => 'Ticket not found.',
            ]);
        }

        $message = trim($input['message'] ?? '');
        if (empty($message)) {
            $this->respond(422, [
                'status'  => false,
                'message' => 'Reply message cannot be empty.',
            ]);
        }

        $replyData = [
            'message' => $message,
            'admin'   => (int) $user->user_id,
        ];

        $replyId = $this->tickets_model->add_reply($replyData, (int) $id, (int) $user->user_id);

        if (!$replyId) {
            $this->respond(500, [
                'status'  => false,
                'message' => 'Failed to add reply.',
            ]);
        }

        // Handle attachments if sent with reply
        if (!empty($_FILES['attachments'])) {
            handle_ticket_attachments((int) $id, 'attachments');
        }

        $this->respond(201, [
            'status'  => true,
            'message' => 'Reply posted successfully.',
            'data'    => [
                'reply_id' => (int) $replyId,
            ],
        ]);
    }

    /**
     * Upload Attachment / Service Report to Ticket
     * POST /api/v1/staff/tickets/{id}/attachments
     */
    public function upload_attachment($id)
    {
        $user = $this->authenticate(['staff']);
        $ticket = $this->tickets_model->get((int) $id);

        if (!$ticket) {
            $this->respond(404, [
                'status'  => false,
                'message' => 'Ticket not found.',
            ]);
        }

        if (empty($_FILES)) {
            $this->respond(422, [
                'status'  => false,
                'message' => 'No file was uploaded. Provide file in multipart/form-data with field name "file" or "attachments".',
            ]);
        }

        $field = isset($_FILES['file']) ? 'file' : 'attachments';
        $uploaded = handle_ticket_attachments((int) $id, $field);

        if (!$uploaded) {
            $this->respond(500, [
                'status'  => false,
                'message' => 'Failed to upload attachment. Check file type and size permissions.',
            ]);
        }

        // Return the latest attachments list
        $freshAttachments = $this->tickets_model->get_ticket_attachments((int) $id);
        $formatted = [];
        foreach ($freshAttachments as $att) {
            $formatted[] = [
                'id'       => (int) $att['id'],
                'filename' => $att['file_name'],
                'filetype' => $att['filetype'],
                'url'      => site_url('download/file/ticket/' . $att['id']),
            ];
        }

        $this->respond(200, [
            'status'  => true,
            'message' => 'Attachment uploaded successfully.',
            'data'    => [
                'attachments' => $formatted,
            ],
        ]);
    }
}
