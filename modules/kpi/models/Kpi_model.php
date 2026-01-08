<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kpi_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get active staff list
     * @return array
     */
    public function get_active_staff()
    {
        $this->db->select('staffid, firstname, lastname, email');
        $this->db->from(db_prefix() . 'staff');
        $this->db->where('active', 1);
        $this->db->order_by('firstname', 'ASC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Get attendance data with filters
     * @param array $filters
     * @param bool $has_view_permission
     * @param bool $has_view_own_permission
     * @return array
     */
    public function get_attendance_data($filters = [], $has_view_permission = false, $has_view_own_permission = false)
    {
        // Check if table exists
        if (!$this->db->table_exists(db_prefix() . 'kpi_attendance')) {
            return [
                'data' => [],
                'total' => 0,
            ];
        }

        $this->db->select('a.*, CONCAT(s.firstname, " ", s.lastname) as staff_name, s.staffid as staff_id');
        $this->db->from(db_prefix() . 'kpi_attendance a');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = a.staff_id AND s.active = 1', 'left');

        // Apply permission-based filtering
        if (!$has_view_permission && $has_view_own_permission) {
            $this->db->where('a.staff_id', get_staff_user_id());
        }

        // Apply filters
        if (!empty($filters['date'])) {
            $this->db->where('a.date', $filters['date']);
        }

        if ($has_view_permission && !empty($filters['staff_id'])) {
            $this->db->where('a.staff_id', (int)$filters['staff_id']);
        }

        if (!empty($filters['status'])) {
            $this->db->where('a.status', $filters['status']);
        }

        return $this->db->get()->result_array();
    }

    /**
     * Build where clause for attendance table based on permissions and filters
     * @param bool $has_view_permission
     * @param bool $has_view_own_permission
     * @param array $filters
     * @return array
     */
    public function build_attendance_where_clause($has_view_permission = false, $has_view_own_permission = false, $filters = [])
    {
        $where = [];

        // Apply permission-based filtering
        if (!$has_view_permission && $has_view_own_permission) {
            $where[] = 'AND a.staff_id = ' . get_staff_user_id();
        }

        // Apply date filter
        if (!empty($filters['date'])) {
            $where[] = 'AND a.date = "' . $this->db->escape_str($filters['date']) . '"';
        }

        // Apply staff filter (only if user has view permission)
        if ($has_view_permission && !empty($filters['staff_id'])) {
            $where[] = 'AND a.staff_id = ' . (int)$filters['staff_id'];
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $where[] = 'AND a.status = "' . $this->db->escape_str($filters['status']) . '"';
        }

        return $where;
    }

    /**
     * Format attendance status badge
     * @param string $status
     * @return string
     */
    public function format_status_badge($status)
    {
        if (empty($status)) {
            return '<span class="label label-default">-</span>';
        }

        $status_lower = strtolower($status);
        
        switch ($status_lower) {
            case 'present':
                return '<span class="label label-success">' . _l('present') . '</span>';
            case 'absent':
                return '<span class="label label-danger">' . _l('absent') . '</span>';
            case 'late':
                return '<span class="label label-warning">' . _l('late') . '</span>';
            default:
                return '<span class="label label-default">' . $status . '</span>';
        }
    }

    /**
     * Format staff name with link
     * @param string $staff_name
     * @param int $staff_id
     * @return string
     */
    public function format_staff_name($staff_name, $staff_id = null)
    {
        if (empty($staff_name) || $staff_name == '-') {
            return '-';
        }

        if (!empty($staff_id)) {
            return '<a href="' . admin_url('staff/member/' . $staff_id) . '">' . html_escape($staff_name) . '</a>';
        }

        return html_escape($staff_name);
    }

    /**
     * Calculate points based on shift time and check-in time
     * @param string $shift_time_start Time in HH:MM format (e.g., "09:00")
     * @param string $check_in_time Time in HH:MM format or datetime (e.g., "09:05" or "2026-01-08 09:05:00")
     * @return int Points calculated
     */
    public function calculate_points($shift_time_start, $check_in_time)
    {
        // If no check-in, return -6 points (absent)
        if (empty($check_in_time) || $check_in_time == '-') {
            return -6;
        }

        // If no shift time, return 0 (can't calculate)
        if (empty($shift_time_start) || $shift_time_start == '-') {
            return 0;
        }

        // Extract time from datetime if needed
        if (strpos($check_in_time, ' ') !== false) {
            $check_in_time = date('H:i', strtotime($check_in_time));
        }

        // Parse times
        $shift_timestamp = strtotime($shift_time_start);
        $checkin_timestamp = strtotime($check_in_time);

        // Calculate difference in minutes
        $diff_minutes = round(($checkin_timestamp - $shift_timestamp) / 60);

        // Calculate points based on rules
        // T - 15 min to T → +5 points
        if ($diff_minutes >= -15 && $diff_minutes < 0) {
            return 5;
        }
        // T to T + 5 min → +4 points
        elseif ($diff_minutes >= 0 && $diff_minutes <= 5) {
            return 4;
        }
        // T + 5 to T + 15 min → +2 points
        elseif ($diff_minutes > 5 && $diff_minutes <= 15) {
            return 2;
        }
        // T + 15 to T + 29 min → 0 points
        elseif ($diff_minutes > 15 && $diff_minutes <= 29) {
            return 0;
        }
        // T + 30 to T + 59 min → -2 points
        elseif ($diff_minutes >= 30 && $diff_minutes <= 59) {
            return -2;
        }
        // T + 60 min or more → -4 points
        elseif ($diff_minutes >= 60) {
            return -4;
        }
        // Before T - 15 min (very early) → +5 points (same as on-time)
        else {
            return 5;
        }
    }

    /**
     * Store or update daily points for a staff member
     * @param int $staff_id
     * @param string $date Date in Y-m-d format
     * @param int $points
     * @param string $shift_time
     * @param string $check_in_time
     * @return bool
     */
    public function store_daily_points($staff_id, $date, $points, $shift_time = null, $check_in_time = null)
    {
        // Check if table exists
        if (!$this->db->table_exists(db_prefix() . 'kpi_daily_points')) {
            return false;
        }

        // Check if record exists
        $this->db->where('staff_id', $staff_id);
        $this->db->where('date', $date);
        $existing = $this->db->get(db_prefix() . 'kpi_daily_points')->row();

        $data = [
            'staff_id' => $staff_id,
            'date' => $date,
            'points' => $points,
            'shift_time' => $shift_time,
            'check_in_time' => $check_in_time,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            // Update existing record
            $this->db->where('id', $existing->id);
            $this->db->update(db_prefix() . 'kpi_daily_points', $data);
        } else {
            // Insert new record
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert(db_prefix() . 'kpi_daily_points', $data);
        }

        // Update monthly total
        $this->update_monthly_total($staff_id, $date);

        return true;
    }

    /**
     * Get daily points for a staff member on a specific date
     * @param int $staff_id
     * @param string $date Date in Y-m-d format
     * @return object|null
     */
    public function get_daily_points($staff_id, $date)
    {
        // Check if table exists
        if (!$this->db->table_exists(db_prefix() . 'kpi_daily_points')) {
            return null;
        }

        $this->db->where('staff_id', $staff_id);
        $this->db->where('date', $date);
        return $this->db->get(db_prefix() . 'kpi_daily_points')->row();
    }

    /**
     * Update monthly total points for a staff member
     * @param int $staff_id
     * @param string $date Date in Y-m-d format (used to determine month)
     * @return bool
     */
    public function update_monthly_total($staff_id, $date)
    {
        // Check if tables exist
        if (!$this->db->table_exists(db_prefix() . 'kpi_daily_points') || 
            !$this->db->table_exists(db_prefix() . 'kpi_monthly_points')) {
            return false;
        }

        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));

        // Calculate total points for the month
        $this->db->select_sum('points');
        $this->db->where('staff_id', $staff_id);
        $this->db->where('YEAR(date)', $year);
        $this->db->where('MONTH(date)', $month);
        $result = $this->db->get(db_prefix() . 'kpi_daily_points')->row();
        $total_points = $result->points ? (int)$result->points : 0;

        // Check if monthly record exists
        $this->db->where('staff_id', $staff_id);
        $this->db->where('year', $year);
        $this->db->where('month', $month);
        $existing = $this->db->get(db_prefix() . 'kpi_monthly_points')->row();

        $data = [
            'staff_id' => $staff_id,
            'year' => $year,
            'month' => $month,
            'total_points' => $total_points,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            // Update existing record
            $this->db->where('id', $existing->id);
            $this->db->update(db_prefix() . 'kpi_monthly_points', $data);
        } else {
            // Insert new record
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert(db_prefix() . 'kpi_monthly_points', $data);
        }

        return true;
    }

    /**
     * Get top N staff for leaderboard for current month
     * @param int $limit Number of top staff to return (default 3)
     * @param int $year Year (default current year)
     * @param int $month Month (default current month)
     * @return array
     */
    public function get_top_staff_leaderboard($limit = 3, $year = null, $month = null)
    {
        // Check if table exists
        if (!$this->db->table_exists(db_prefix() . 'kpi_monthly_points')) {
            return [];
        }

        if ($year === null) {
            $year = date('Y');
        }
        if ($month === null) {
            $month = date('m');
        }

        $this->db->select('mp.staff_id, mp.total_points, CONCAT(s.firstname, " ", s.lastname) as staff_name, s.firstname, s.lastname');
        $this->db->from(db_prefix() . 'kpi_monthly_points mp');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = mp.staff_id', 'left');
        $this->db->where('mp.year', $year);
        $this->db->where('mp.month', $month);
        $this->db->where('s.active', 1);
        $this->db->order_by('mp.total_points', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }

    /**
     * Process and store points for a staff member on a specific date
     * @param int $staff_id
     * @param string $date Date in Y-m-d format
     * @param string $shift_time Shift time in "HH:MM - HH:MM" format or just start time "HH:MM"
     * @param string $check_in_time Check-in time in "HH:MM" format or datetime
     * @return int Calculated points
     */
    public function process_and_store_points($staff_id, $date, $shift_time, $check_in_time)
    {
        // Extract start time from shift_time (format: "HH:MM - HH:MM" or just "HH:MM")
        $shift_start = null;
        if (!empty($shift_time) && $shift_time != '-') {
            if (strpos($shift_time, ' - ') !== false) {
                $parts = explode(' - ', $shift_time);
                $shift_start = trim($parts[0]);
            } else {
                $shift_start = trim($shift_time);
            }
        }

        // Calculate points
        $points = $this->calculate_points($shift_start, $check_in_time);

        // Store daily points
        $this->store_daily_points($staff_id, $date, $points, $shift_time, $check_in_time);

        return $points;
    }
}

