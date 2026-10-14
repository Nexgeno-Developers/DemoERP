<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kpi_notifications_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // ------------------------------------------------------------------
    // Safe table check helper
    // ------------------------------------------------------------------
    private function _table_ok($table)
    {
        try {
            return $this->db->table_exists($table);
        } catch (Throwable $e) {
            return false;
        }
    }

    // ------------------------------------------------------------------
    // ROLE-BASED FILTERING
    //
    //   Admin / has approve_requests permission  → NULL  (see ALL)
    //   Department manager / Team manager        → array of staff IDs
    //   Empty array                              → manages nobody, show nothing
    // ------------------------------------------------------------------
    public function get_managed_staff_ids()
    {
        // Admin or explicit permission → no filter (sees everything)
        if (is_admin() || has_permission('kpi', '', 'approve_requests')) {
            return null;
        }

        $current_staff_id = get_staff_user_id();
        $staff_ids        = [];

        try {
            $this->db->db_debug = false;

            // -- Team Manager: fetch staff whose team_manage = current user --
            $has_team_manage_col = false;
            try {
                $cols                = $this->db->list_fields(db_prefix() . 'staff');
                $has_team_manage_col = is_array($cols) && in_array('team_manage', $cols);
            } catch (Throwable $e) {}

            if ($has_team_manage_col) {
                $q = $this->db->select('staffid')
                              ->from(db_prefix() . 'staff')
                              ->where('team_manage', $current_staff_id)
                              ->get();
                if ($q) {
                    foreach ($q->result() as $row) {
                        $staff_ids[] = (int) $row->staffid;
                    }
                }
            }

            // -- Department Manager: fetch all staff in managed departments --
            if ($this->_table_ok(db_prefix() . 'staff_departments')) {
                $cols2 = [];
                try { $cols2 = $this->db->list_fields(db_prefix() . 'staff_departments'); } catch (Throwable $e) {}

                if (in_array('manager_id', (array) $cols2)) {
                    // Departments this user manages
                    $dept_q = $this->db->select('departmentid')
                                       ->from(db_prefix() . 'staff_departments')
                                       ->where('manager_id', $current_staff_id)
                                       ->get();

                    if ($dept_q && $dept_q->num_rows() > 0) {
                        $dept_ids = array_column($dept_q->result_array(), 'departmentid');

                        // All staff members in those departments
                        $members_q = $this->db->select('staffid')
                                              ->from(db_prefix() . 'staff_departments')
                                              ->where_in('departmentid', $dept_ids)
                                              ->get();
                        if ($members_q) {
                            foreach ($members_q->result() as $row) {
                                $staff_ids[] = (int) $row->staffid;
                            }
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'KPI get_managed_staff_ids: ' . $e->getMessage());
        }

        // De-duplicate and remove the manager themselves
        $staff_ids = array_unique($staff_ids);
        $staff_ids = array_values(array_filter($staff_ids, function ($id) use ($current_staff_id) {
            return $id !== (int) $current_staff_id;
        }));

        return $staff_ids;
    }

    // ------------------------------------------------------------------
    // COUNT HELPERS
    // ------------------------------------------------------------------

    public function get_pending_ot_count()
    {
        try {
            $table = db_prefix() . 'timesheets_additional_timesheet';
            if (!$this->_table_ok($table)) return 0;
            $this->db->db_debug = false;

            $managed_ids = $this->get_managed_staff_ids();
            if ($managed_ids !== null && empty($managed_ids)) return 0;

            $this->db->where('status', 0);
            if ($managed_ids !== null) {
                $this->db->where_in('creator', $managed_ids);
            }

            return (int) $this->db->count_all_results($table);
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

            $managed_ids = $this->get_managed_staff_ids();
            if ($managed_ids !== null && empty($managed_ids)) return 0;

            $this->db->where('status', 0);
            if ($managed_ids !== null) {
                $this->db->where_in('staff_id', $managed_ids);
            }

            return (int) $this->db->count_all_results($table);
        } catch (Throwable $e) {
            log_message('error', 'KPI get_pending_pl_count: ' . $e->getMessage());
            return 0;
        }
    }

    // ------------------------------------------------------------------
    // LIST HELPERS
    // ------------------------------------------------------------------

    public function get_pending_ot_requests($limit = 10)
    {
        try {
            $table = db_prefix() . 'timesheets_additional_timesheet';
            if (!$this->_table_ok($table)) return [];
            $this->db->db_debug = false;

            $managed_ids = $this->get_managed_staff_ids();
            if ($managed_ids !== null && empty($managed_ids)) return [];

            $this->db->select('r.*, s.firstname, s.lastname, s.profile_image');
            $this->db->from($table . ' r');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = r.creator', 'left');
            $this->db->where('r.status', 0);
            if ($managed_ids !== null) {
                $this->db->where_in('r.creator', $managed_ids);
            }
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

            $managed_ids = $this->get_managed_staff_ids();
            if ($managed_ids !== null && empty($managed_ids)) return [];

            $this->db->select('r.*, s.firstname, s.lastname, s.profile_image');
            $this->db->from($table . ' r');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = r.staff_id', 'left');
            $this->db->where('r.status', 0);
            if ($managed_ids !== null) {
                $this->db->where_in('r.staff_id', $managed_ids);
            }
            $this->db->order_by('r.start_time', 'DESC');
            $this->db->limit($limit);
            $result = $this->db->get();
            return $result ? $result->result() : [];
        } catch (Throwable $e) {
            log_message('error', 'KPI get_pending_pl_requests: ' . $e->getMessage());
            return [];
        }
    }

    // ------------------------------------------------------------------
    // TICKETS
    // ------------------------------------------------------------------

    /**
     * Get the status ID for "Open" tickets
     */
    private function get_open_ticket_status_id()
    {
        try {
            $this->db->select('ticketstatusid');
            $this->db->from(db_prefix() . 'tickets_status');
            $this->db->where("LOWER(name) = 'open'");
            $this->db->limit(1);
            $result = $this->db->get()->row_array();
            return !empty($result) ? (int) $result['ticketstatusid'] : null;
        } catch (Throwable $e) {
            log_message('error', 'KPI get_open_ticket_status_id: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Count of open tickets belonging to managed team members
     * Same role-based filtering as OT/PL — only managers/HODs/admins get a non-zero result
     */
    public function get_open_tickets_count()
    {
        try {
            $table = db_prefix() . 'tickets';
            if (!$this->_table_ok($table)) return 0;
            $this->db->db_debug = false;

            $managed_ids    = $this->get_managed_staff_ids();
            if ($managed_ids !== null && empty($managed_ids)) return 0;

            $open_status_id = $this->get_open_ticket_status_id();

            $this->db->from($table);
            if ($managed_ids !== null) {
                $this->db->where_in('admin', $managed_ids);
            }
            if ($open_status_id !== null) {
                $this->db->where('status', $open_status_id);
            }
            return (int) $this->db->count_all_results();
        } catch (Throwable $e) {
            log_message('error', 'KPI get_open_tickets_count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Open tickets for team members managed by the current user
     * Same role-based filtering as OT/PL — only managers/HODs/admins see results
     */
    public function get_open_tickets_for_staff($limit = 5)
    {
        try {
            $table = db_prefix() . 'tickets';
            if (!$this->_table_ok($table)) return [];
            $this->db->db_debug = false;

            $managed_ids    = $this->get_managed_staff_ids();
            if ($managed_ids !== null && empty($managed_ids)) return [];

            $open_status_id = $this->get_open_ticket_status_id();

            $this->db->select(
                db_prefix() . 'tickets.ticketid, ' .
                db_prefix() . 'tickets.subject, ' .
                db_prefix() . 'tickets.status, ' .
                db_prefix() . 'tickets_status.name as status_name, ' .
                db_prefix() . 'tickets_status.statuscolor, ' .
                db_prefix() . 'tickets.priority, ' .
                db_prefix() . 'tickets_priorities.name as priority_name, ' .
                db_prefix() . 'tickets.lastreply, ' .
                db_prefix() . 'tickets.date, ' .
                db_prefix() . 'departments.name as department_name, ' .
                's.firstname, s.lastname'
            );
            $this->db->from($table);
            $this->db->join(db_prefix() . 'tickets_status',
                db_prefix() . 'tickets_status.ticketstatusid = ' . db_prefix() . 'tickets.status', 'left');
            $this->db->join(db_prefix() . 'tickets_priorities',
                db_prefix() . 'tickets_priorities.priorityid = ' . db_prefix() . 'tickets.priority', 'left');
            $this->db->join(db_prefix() . 'departments',
                db_prefix() . 'departments.departmentid = ' . db_prefix() . 'tickets.department', 'left');
            $this->db->join(db_prefix() . 'staff s',
                's.staffid = ' . db_prefix() . 'tickets.admin', 'left');

            if ($managed_ids !== null) {
                $this->db->where_in(db_prefix() . 'tickets.admin', $managed_ids);
            }
            if ($open_status_id !== null) {
                $this->db->where(db_prefix() . 'tickets.status', $open_status_id);
            }
            $this->db->order_by(db_prefix() . 'tickets.lastreply', 'DESC');
            $this->db->order_by(db_prefix() . 'tickets.date', 'DESC');
            $this->db->limit($limit);

            return $this->db->get()->result_array();
        } catch (Throwable $e) {
            log_message('error', 'KPI get_open_tickets_for_staff: ' . $e->getMessage());
            return [];
        }
    }

    // ------------------------------------------------------------------
    // SINGLE REQUEST GETTERS
    // ------------------------------------------------------------------

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

    // ------------------------------------------------------------------
    // APPROVE / REJECT
    // ------------------------------------------------------------------

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
