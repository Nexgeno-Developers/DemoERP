<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once dirname(__DIR__) . '/core/Api_Controller.php';

class Tickets_meta extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('tickets_model');
        $this->load->model('departments_model');
    }

    /**
     * Get all ticket metadata for form dropdowns & filters
     * GET /api/v1/tickets/meta
     */
    public function index()
    {
        $this->authenticate(); // Accessible to both staff & customer

        // Ticket Statuses
        $statuses = $this->tickets_model->get_ticket_status();
        $formattedStatuses = [];
        foreach ($statuses as $status) {
            $formattedStatuses[] = [
                'id'         => (int) $status['ticketstatusid'],
                'name'       => ticket_status_translate($status['ticketstatusid']),
                'color'      => $status['statuscolor'],
                'is_default' => (int) $status['isdefault'] === 1,
            ];
        }

        // Priorities
        $priorities = $this->tickets_model->get_priority();
        $formattedPriorities = [];
        foreach ($priorities as $priority) {
            $formattedPriorities[] = [
                'id'   => (int) $priority['priorityid'],
                'name' => ticket_priority_translate($priority['priorityid']),
            ];
        }

        // Departments
        $departments = $this->departments_model->get();
        $formattedDepartments = [];
        foreach ($departments as $dept) {
            $formattedDepartments[] = [
                'id'   => (int) $dept['departmentid'],
                'name' => $dept['name'],
            ];
        }

        // Services
        $services = $this->tickets_model->get_service();
        $formattedServices = [];
        foreach ($services as $srv) {
            $formattedServices[] = [
                'id'   => (int) $srv['serviceid'],
                'name' => $srv['name'],
            ];
        }

        $this->respond(200, [
            'status' => true,
            'data'   => [
                'statuses'    => $formattedStatuses,
                'priorities'  => $formattedPriorities,
                'departments' => $formattedDepartments,
                'services'    => $formattedServices,
            ],
        ]);
    }
}
