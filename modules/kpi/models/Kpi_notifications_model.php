<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kpi_notifications_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // ------------------------------------------------------------------
    // Safe table check helper — returns false without crashing if table
    // or column does not exist (Timesheets module may not be installed).
    // ------------------------------------------------------------------
    private function _table_ok($table)
    {
        try {
            return $this->db->table_exists($table);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function get_pending_ot_count()
    {
        try {
            $table = db_prefix() . 'timesheets_additional_timesheet';
            if (!$this->_table_ok($table)) return 0;
            $this->db->db_debug = false;
            return (int) $this->db->where('status', 0)->count_all_results($table);
        } catch (Throwable $e) {
            log_message('error', 'KPI get_pending_ot_count: ' . $e->getMessage());
            return 0;
        }
    }

    public function get_pending_pl_count()
    {
        try {
            $table = db_prefix() . 'timesheets_requisition_leave';
            if (!$this->_table_ok($table)) return 0;
            $this->db->db_debug = false;
            return (int) $this->db->where('status', 0)->count_all_results($table);
        } catch (Throwable $e) {
            log_message('error', 'KPI get_pending_pl_count: ' . $e->getMessage());
            return 0;
        }
    }

    public function get_pending_ot_requests($limit = 10)
    {
        try {
            $table = db_prefix() . 'timesheets_additional_timesheet';
            if (!$this->_table_ok($table)) return [];
            $this->db->db_debug = false;
            $this->db->select('r.*, s.firstname, s.lastname, s.profile_image');
            $this->db->from($table . ' r');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = r.creator', 'left');
            $this->db->where('r.status', 0);
            $this->db->order_by('r.additional_day', 'DESC');
            $this->db->limit($limit);
            $result = $this->db->get();
            return $result ? $result->result() : [];
        } catch (Throwable $e) {
            log_message('error', 'KPI get_pending_ot_requests: ' . $e->getMessage());
            return [];
        }
    }

    public function get_pending_pl_requests($limit = 10)
    {
        try {
            $table = db_prefix() . 'timesheets_requisition_leave';
            if (!$this->_table_ok($table)) return [];
            $this->db->db_debug = false;
            $this->db->select('r.*, s.firstname, s.lastname, s.profile_image');
            $this->db->from($table . ' r');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = r.staff_id', 'left');
            $this->db->where('r.status', 0);
            $this->db->order_by('r.start_time', 'DESC');
            $this->db->limit($limit);
            $result = $this->db->get();
            return $result ? $result->result() : [];
        } catch (Throwable $e) {
            log_message('error', 'KPI get_pending_pl_requests: ' . $e->getMessage());
            return [];
        }
    }

    public function get_ot_request($id)
    {
        try {
            $table = db_prefix() . 'timesheets_additional_timesheet';
            if (!$this->_table_ok($table)) return null;
            $this->db->db_debug = false;
            $this->db->select('r.*, s.firstname, s.lastname, s.email, s.phonenumber');
            $this->db->from($table . ' r');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = r.creator', 'left');
            $this->db->where('r.id', $id);
            return $this->db->get()->row();
        } catch (Throwable $e) {
            log_message('error', 'KPI get_ot_request: ' . $e->getMessage());
            return null;
        }
    }

    public function get_pl_request($id)
    {
        try {
            $table = db_prefix() . 'timesheets_requisition_leave';
            if (!$this->_table_ok($table)) return null;
            $this->db->db_debug = false;
            $this->db->select('r.*, s.firstname, s.lastname, s.email, s.phonenumber');
            $this->db->from($table . ' r');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = r.staff_id', 'left');
            $this->db->where('r.id', $id);
            return $this->db->get()->row();
        } catch (Throwable $e) {
            log_message('error', 'KPI get_pl_request: ' . $e->getMessage());
            return null;
        }
    }

    public function approve_request($type, $id, $approver_id)
    {
        try {
            $this->db->db_debug = false;
            $table = $type === 'ot' ? 'timesheets_additional_timesheet' : 'timesheets_requisition_leave';
            if (!$this->_table_ok(db_prefix() . $table)) return false;

            $this->db->where('id', $id);
            $result = $this->db->update(db_prefix() . $table, [
                'status'     => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if ($result && $this->_table_ok(db_prefix() . 'timesheets_approval_details')) {
                $this->db->insert(db_prefix() . 'timesheets_approval_details', [
                    'rel_id'   => $id,
                    'rel_type' => $type === 'ot' ? 'additional_timesheets' : 'requisition_leave',
                    'staffid'  => $approver_id,
                    'action'   => 'approved',
                    'date'     => date('Y-m-d H:i:s'),
                ]);
            }
            return $result;
        } catch (Throwable $e) {
            log_message('error', 'KPI approve_request: ' . $e->getMessage());
            return false;
        }
    }

    public function reject_request($type, $id, $approver_id, $reason = '')
    {
        try {
            $this->db->db_debug = false;
            $table = $type === 'ot' ? 'timesheets_additional_timesheet' : 'timesheets_requisition_leave';
            if (!$this->_table_ok(db_prefix() . $table)) return false;

            $data = ['status' => 2, 'updated_at' => date('Y-m-d H:i:s')];
            if ($reason) $data['rejection_reason'] = $reason;

            $this->db->where('id', $id);
            $result = $this->db->update(db_prefix() . $table, $data);

            if ($result && $this->_table_ok(db_prefix() . 'timesheets_approval_details')) {
                $this->db->insert(db_prefix() . 'timesheets_approval_details', [
                    'rel_id'   => $id,
                    'rel_type' => $type === 'ot' ? 'additional_timesheets' : 'requisition_leave',
                    'staffid'  => $approver_id,
                    'action'   => 'rejected',
                    'reason'   => $reason,
                    'date'     => date('Y-m-d H:i:s'),
                ]);
            }
            return $result;
        } catch (Throwable $e) {
            log_message('error', 'KPI reject_request: ' . $e->getMessage());
            return false;
        }
    }
}
