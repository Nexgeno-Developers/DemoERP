<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once dirname(__DIR__) . '/core/Api_Controller.php';

class Customer_tickets extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('tickets_model');
    }

    /**
     * List Customer Tickets
     * GET /api/v1/customer/tickets
     */
    public function index()
    {
        $user = $this->authenticate(['customer']);

        $page   = max(1, (int) $this->input->get('page'));
        $limit  = max(1, min(50, (int) ($this->input->get('limit') ?: 20)));
        $offset = ($page - 1) * $limit;

        $statusId = $this->input->get('status');
        $search   = trim($this->input->get('q') ?: '');

        $clientId = (int) $user->client_id;

        // Build query
        $this->db->start_cache();
        $this->db->from(db_prefix() . 'tickets t');
        $this->db->join(db_prefix() . 'departments d', 'd.departmentid = t.department', 'left');
        $this->db->join(db_prefix() . 'tickets_priorities p', 'p.priorityid = t.priority', 'left');
        $this->db->join(db_prefix() . 'tickets_status s', 's.ticketstatusid = t.status', 'left');
        $this->db->join(db_prefix() . 'services srv', 'srv.serviceid = t.service', 'left');
        $this->db->where('t.userid', $clientId);

        if (!empty($statusId) && is_numeric($statusId)) {
            $this->db->where('t.status', (int) $statusId);
        }

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('t.subject', $search);
            $this->db->or_like('t.ticketid', $search);
            $this->db->or_like('t.message', $search);
            $this->db->group_end();
        }
        $this->db->stop_cache();

        $total = $this->db->count_all_results();

        $this->db->select('t.ticketid as id, t.subject, t.status, s.name as status_name, s.statuscolor, t.priority, p.name as priority_name, t.department, d.name as department_name, t.service, srv.name as service_name, t.date, t.lastreply, t.assigned');
        $this->db->order_by('t.lastreply', 'DESC');
        $this->db->limit($limit, $offset);
        $tickets = $this->db->get()->result_array();
        $this->db->flush_cache();

        $formattedTickets = [];
        foreach ($tickets as $ticket) {
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
                'created_at'      => $ticket['date'],
                'last_reply'      => $ticket['lastreply'],
            ];
        }

        $this->respond(200, [
            'status' => true,
            'data'   => [
                'total'        => $total,
                'page'         => $page,
                'limit'        => $limit,
                'total_pages'  => ceil($total / $limit),
                'tickets'      => $formattedTickets,
            ],
        ]);
    }

    /**
     * Get Single Ticket Details and Conversation
     * GET /api/v1/customer/tickets/{id}
     */
    public function show($id)
    {
        $user = $this->authenticate(['customer']);

        $ticket = $this->tickets_model->get((int) $id);

        if (!$ticket || (int) $ticket->userid !== (int) $user->client_id) {
            $this->respond(404, [
                'status'  => false,
                'message' => 'Ticket not found.',
            ]);
        }

        // Ticket attachments
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

        // Replies
        $replies = $this->tickets_model->get_ticket_replies($ticket->ticketid);
        $formattedReplies = [];
        foreach ($replies as $reply) {
            $isStaff = !empty($reply['admin']);
            $senderName = $isStaff
                ? trim(($reply['staff_firstname'] ?? '') . ' ' . ($reply['staff_lastname'] ?? ''))
                : trim(($reply['user_firstname'] ?? '') . ' ' . ($reply['user_lastname'] ?? ''));

            if (empty($senderName)) {
                $senderName = $reply['from_name'] ?: ($isStaff ? 'Support Agent' : 'Customer');
            }

            $profileImage = $isStaff
                ? staff_profile_image_url($reply['admin'])
                : contact_profile_image_url($reply['contactid']);

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
                'id'           => (int) $reply['id'],
                'message'      => $reply['message'],
                'date'         => $reply['date'],
                'is_staff'     => $isStaff,
                'sender_name'  => $senderName,
                'profile_image'=> $profileImage,
                'attachments'  => $replyAttachments,
            ];
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
                'created_at'      => $ticket->date,
                'last_reply'      => $ticket->lastreply,
                'attachments'     => $formattedAttachments,
                'replies'         => $formattedReplies,
            ],
        ]);
    }

    /**
     * Create Ticket
     * POST /api/v1/customer/tickets
     */
    public function store()
    {
        $user = $this->authenticate(['customer']);
        $input = $this->get_input_data();

        $subject    = trim($input['subject'] ?? '');
        $message    = trim($input['message'] ?? '');
        $department = (int) ($input['department'] ?? 0);
        $priority   = (int) ($input['priority'] ?? 1);
        $service    = !empty($input['service']) ? (int) $input['service'] : null;

        if (empty($subject) || empty($message)) {
            $this->respond(422, [
                'status'  => false,
                'message' => 'Subject and message are required fields.',
            ]);
        }

        // If department is missing, fallback to first available department
        if ($department <= 0) {
            $defaultDept = $this->db->select('departmentid')->order_by('departmentid', 'ASC')->get(db_prefix() . 'departments')->row();
            $department = $defaultDept ? (int) $defaultDept->departmentid : 1;
        }

        $ticketData = [
            'userid'     => (int) $user->client_id,
            'contactid'  => (int) $user->user_id,
            'subject'    => $subject,
            'message'    => $message,
            'department' => $department,
            'priority'   => $priority,
            'service'    => $service,
        ];

        $ticketId = $this->tickets_model->add($ticketData);

        if (!$ticketId) {
            $this->respond(500, [
                'status'  => false,
                'message' => 'Failed to create ticket. Please try again.',
            ]);
        }

        $this->respond(201, [
            'status'  => true,
            'message' => 'Ticket created successfully.',
            'data'    => [
                'ticket_id' => (int) $ticketId,
            ],
        ]);
    }

    /**
     * Reply to Ticket
     * POST /api/v1/customer/tickets/{id}/reply
     */
    public function reply($id)
    {
        $user = $this->authenticate(['customer']);
        $input = $this->get_input_data();

        $ticket = $this->tickets_model->get((int) $id);

        if (!$ticket || (int) $ticket->userid !== (int) $user->client_id) {
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
            'message'   => $message,
            'contactid' => (int) $user->user_id,
            'userid'    => (int) $user->client_id,
        ];

        $replyId = $this->tickets_model->add_reply($replyData, (int) $id);

        if (!$replyId) {
            $this->respond(500, [
                'status'  => false,
                'message' => 'Failed to post reply.',
            ]);
        }

        $this->respond(201, [
            'status'  => true,
            'message' => 'Reply posted successfully.',
            'data'    => [
                'reply_id' => (int) $replyId,
            ],
        ]);
    }
}
