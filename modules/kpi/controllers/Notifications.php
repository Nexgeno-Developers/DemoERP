<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Notifications extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        
        // Load model first
        $this->load->model('kpi/kpi_notifications_model');
        
        // Check if user should have access - same logic as notification icon
        if (!$this->_check_access()) {
            // For AJAX requests, return JSON error instead of HTML access denied page
            if ($this->input->is_ajax_request()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Access denied. You do not have permission to view notifications.'
                ]);
                exit;
            } else {
                access_denied('KPI Notifications');
            }
        }
    }
    
    /**
     * Check if current user has access to notifications
     * Same 4-level check as in kpi.php
     */
    private function _check_access()
    {
        $has_access = false;
        $current_staff_id = get_staff_user_id();
        
        try {
            // Check 1: Admin or has permission
            if (is_admin() || has_permission('kpi', '', 'approve_requests')) {
                $has_access = true;
            }
            
            // Check 2: Is user an approver in timesheets?
            if (!$has_access) {
                $this->db->select('staffid');
                $this->db->from(db_prefix() . 'timesheets_approval_details');
                $this->db->where('staffid', $current_staff_id);
                $this->db->limit(1);
                $query = $this->db->get();
                if ($query && $query->num_rows() > 0) {
                    $has_access = true;
                }
            }
            
            // Check 3: Is user a direct manager?
            if (!$has_access) {
                $this->db->select('staffid');
                $this->db->from(db_prefix() . 'staff');
                $this->db->where('team_manage', $current_staff_id);
                $this->db->limit(1);
                $query = $this->db->get();
                if ($query && $query->num_rows() > 0) {
                    $has_access = true;
                }
            }
            
            // Check 4: Is user a department manager?
            if (!$has_access) {
                $this->db->select('departmentid');
                $this->db->from(db_prefix() . 'staff_departments');
                $this->db->where('manager_id', $current_staff_id);
                $this->db->limit(1);
                $query = $this->db->get();
                if ($query && $query->num_rows() > 0) {
                    $has_access = true;
                }
            }
        } catch (Exception $e) {
            log_message('error', 'KPI Notifications access check error: ' . $e->getMessage());
            $has_access = false;
        }
        
        return $has_access;
    }
    
    /**
     * Get notification count for AJAX requests
     */
    public function get_notification_count()
    {
        header('Content-Type: application/json');
        
        try {
            $ot_count = $this->kpi_notifications_model->get_pending_ot_count();
            $pl_count = $this->kpi_notifications_model->get_pending_pl_count();
            
            echo json_encode([
                'success' => true,
                'ot_count' => $ot_count,
                'pl_count' => $pl_count,
                'total' => $ot_count + $pl_count
            ]);
        } catch (Exception $e) {
            log_message('error', 'KPI get_notification_count error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Failed to get notification count',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * View request details page (redirects to existing timesheets pages)
     */
    public function view_request($type, $id)
    {
        if (!in_array($type, ['ot', 'pl'])) {
            show_404();
        }
        
        if ($type === 'ot') {
            // Redirect to existing timesheets additional timesheet page
            redirect(admin_url('timesheets/requisition_manage?tab=additional_timesheets&additional_timesheets_id=' . $id));
        } elseif ($type === 'pl') {
            // Redirect to existing timesheets requisition detail page
            redirect(admin_url('timesheets/requisition_detail/' . $id));
        }
    }
    
    /**
     * Approve a request
     */
    public function approve_request()
    {
        header('Content-Type: application/json');
        
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        try {
            $type = $this->input->post('type');
            $id = $this->input->post('id');
            
            if (!in_array($type, ['ot', 'pl']) || !$id) {
                echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
                return;
            }
            
            $result = $this->kpi_notifications_model->approve_request($type, $id, get_staff_user_id());
            
            if ($result) {
                // Log activity
                $request_type = $type === 'ot' ? 'Overtime' : 'Personal Leave';
                log_activity($request_type . ' Request Approved [ID: ' . $id . ']');
                
                echo json_encode(['success' => true, 'message' => 'Request approved successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to approve request']);
            }
        } catch (Exception $e) {
            log_message('error', 'KPI approve_request error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error approving request', 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Reject a request
     */
    public function reject_request()
    {
        header('Content-Type: application/json');
        
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        try {
            $type = $this->input->post('type');
            $id = $this->input->post('id');
            $reason = $this->input->post('reason', true);
            
            if (!in_array($type, ['ot', 'pl']) || !$id) {
                echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
                return;
            }
            
            $result = $this->kpi_notifications_model->reject_request($type, $id, get_staff_user_id(), $reason);
            
            if ($result) {
                // Log activity
                $request_type = $type === 'ot' ? 'Overtime' : 'Personal Leave';
                log_activity($request_type . ' Request Rejected [ID: ' . $id . ']');
                
                echo json_encode(['success' => true, 'message' => 'Request rejected successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to reject request']);
            }
        } catch (Exception $e) {
            log_message('error', 'KPI reject_request error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error rejecting request', 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get requests for dropdown tabs
     */
    public function get_requests($type)
    {
        if (!in_array($type, ['ot', 'pl'])) {
            show_404();
        }
        
        try {
            if ($type === 'ot') {
                $requests = $this->kpi_notifications_model->get_pending_ot_requests(10);
                $data['ot_requests'] = $requests;
                $this->load->view('kpi/notifications/ot_requests', $data);
            } else {
                $requests = $this->kpi_notifications_model->get_pending_pl_requests(10);
                $data['pl_requests'] = $requests;
                $this->load->view('kpi/notifications/pl_requests', $data);
            }
        } catch (Exception $e) {
            log_message('error', 'KPI get_requests error: ' . $e->getMessage());
            echo '<div class="text-center text-danger p-3">Error loading requests</div>';
        }
    }
}
