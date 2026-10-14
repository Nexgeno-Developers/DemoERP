<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Public_leads_api extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        // Restrict the entire controller to administrators to prevent non-admin staff from accessing it directly via URL.
        if (!is_admin()) {
            access_denied('Public Leads API');
        }
        $this->load->model('public_leads_api/public_leads_api_model');
    }

    public function index()
    {
        if ($this->input->post('action') === 'generate') {
            $label = $this->input->post('label');
            $token = $this->public_leads_api_model->create_key($label);
            set_alert('success', 'New API key created: ' . $token);
            redirect(admin_url('public_leads_api'));
        }

        if ($this->input->post('action') === 'settings') {
            $enabled    = $this->input->post('enabled') ? 1 : 0;
            $rateLimit  = (int) $this->input->post('rate_limit');
            $rateLimit  = $rateLimit < 0 ? 0 : $rateLimit;
            $rateWindow = (int) $this->input->post('rate_window');
            $rateWindow = $rateWindow <= 0 ? 1 : $rateWindow;
            $blockedIps = trim((string) $this->input->post('blocked_ips'));
            $allowedFields = trim((string) $this->input->post('allowed_custom_fields'));
            $inactiveDays = (int) $this->input->post('inactive_days');
            $inactiveDays = $inactiveDays < 0 ? 0 : $inactiveDays;
            $inactiveEmails = trim((string) $this->input->post('inactive_notify_emails'));

            update_option('public_leads_api_enabled', $enabled);
            update_option('public_leads_api_rate_limit_per_minute', $rateLimit);
            update_option('public_leads_api_rate_limit_window_minutes', $rateWindow);
            update_option('public_leads_api_blocked_ips', $blockedIps);
            update_option('public_leads_api_allowed_custom_fields', $allowedFields);
            update_option('public_leads_api_inactive_days', $inactiveDays);
            update_option('public_leads_api_inactive_notify_emails', $inactiveEmails);

            set_alert('success', 'Settings updated');
            redirect(admin_url('public_leads_api'));
        }

        $data['title']       = 'Leads API';
        $data['keys']        = $this->public_leads_api_model->get_keys();
        $data['enabled']     = (int) get_option('public_leads_api_enabled', 1);
        $data['rate_limit']  = (int) get_option('public_leads_api_rate_limit_per_minute', 60);
        $data['rate_window'] = (int) get_option('public_leads_api_rate_limit_window_minutes', 1);
        $data['blocked_ips'] = (string) get_option('public_leads_api_blocked_ips', '');
        $data['allowed_custom_fields'] = (string) get_option('public_leads_api_allowed_custom_fields', '');
        $data['inactive_days'] = (int) get_option('public_leads_api_inactive_days', 30);
        $data['inactive_notify_emails'] = (string) get_option('public_leads_api_inactive_notify_emails', '');
        $data['statuses']    = $this->public_leads_api_model->list_statuses();
        $data['sources']     = $this->public_leads_api_model->list_sources();
        $data['tags']        = $this->public_leads_api_model->list_tags();
        $data['staff']       = $this->public_leads_api_model->list_staff();

        $this->load->view(PUBLIC_LEADS_API_MODULE . '/manage', $data);
    }

    public function revoke($id)
    {
        if ($this->public_leads_api_model->deactivate_key((int) $id)) {
            set_alert('success', 'API key revoked');
        } else {
            set_alert('warning', 'Key not found or already inactive');
        }

        redirect(admin_url('public_leads_api'));
    }

    public function delete($id)
    {
        if ($this->public_leads_api_model->delete_key((int) $id)) {
            set_alert('success', 'API key deleted');
        } else {
            set_alert('warning', 'Unable to delete key');
        }

        redirect(admin_url('public_leads_api'));
    }

    /**
     * Server-side logs feed for DataTables.
     */
    public function logs()
    {
        if (!is_admin()) {
            show_404();
        }

        $draw     = (int) $this->input->post('draw');
        $start    = (int) $this->input->post('start');
        $length   = (int) $this->input->post('length');
        $length   = $length > 0 ? $length : 25;
        $search   = $this->input->post('search');
        $term     = isset($search['value']) ? trim((string) $search['value']) : '';
        $order    = $this->input->post('order');
        $orderColIndex = isset($order[0]['column']) ? (int) $order[0]['column'] : 0;
        $orderDir = isset($order[0]['dir']) ? $order[0]['dir'] : 'desc';

        // Map DataTables columns to DB columns
        $orderColumns = ['id', 'status', 'message', 'lead_id', 'ip_address', 'created_at'];
        $orderBy = $orderColumns[$orderColIndex] ?? 'id';

        $logs     = $this->public_leads_api_model->search_logs($term, $length, $start, $orderBy, $orderDir);
        $filtered = $this->public_leads_api_model->count_logs_filtered($term);
        $total    = $this->public_leads_api_model->count_logs_total();

        $data = [];
        foreach ($logs as $log) {
            $leadLink = $log['lead_id']
                ? '<a href="' . admin_url('leads/index/' . $log['lead_id']) . '">' . $log['lead_id'] . '</a>'
                : '—';

            $data[] = [
                (int) $log['id'],
                html_escape($log['status']),
                html_escape($log['message']),
                $leadLink,
                html_escape($log['ip_address']),
                html_escape($log['created_at']),
            ];
        }

        echo json_encode([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
        exit;
    }
}
