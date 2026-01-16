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
     * Get status title based on points
     * Status mapping:
     * - 15 min early to before shift: +5 → Early Check-in
     * - On shift time to +5 min: +4 → Punctual
     * - +6 to +15 min: +2 → Delayed
     * - +16 to +29 min: 0 → Grace Window
     * - +30 to +59 min: -2 → Late
     * - 60 min or more: -4 → Very Late
     * - No check-in: -6 → Absent
     * - No shift assigned: 0 → No Shift
     * - Date before 2026 or future: 0 → Not Applicable
     * 
     * @param int $points
     * @param string $shift_time
     * @param string $date
     * @return string
     */
    public function get_status_from_points($points, $shift_time = null, $date = null)
    {
        // Check if date is before 2026 or future - returns "Not Applicable"
        if (!empty($date)) {
            $date_year = (int)date('Y', strtotime($date));
            $current_date = date('Y-m-d');
            
            if ($date_year < 2026 || $date > $current_date) {
                return 'Not Applicable';
            }
        }
        
        // Check if no shift assigned - returns "No Shift"
        if ($shift_time == 'NS' || $shift_time == '-' || empty($shift_time)) {
            return 'No Shift';
        }
        
        // Map points to status
        switch ($points) {
            case 5:
                return 'Early Check-in';  // 15 min early to before shift
            case 4:
                return 'Punctual';        // On shift time to +5 min
            case 2:
                return 'Delayed';         // +6 to +15 min
            case 0:
                return 'Grace Window';    // +16 to +29 min
            case -2:
                return 'Late';            // +30 to +59 min
            case -4:
                return 'Very Late';       // 60 min or more
            case -6:
                return 'Absent';          // No check-in
            default:
                return 'Not Applicable';  // Unknown points value
        }
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
        
        // New status badges
        switch ($status_lower) {
            case 'early check-in':
                return '<span class="label label-success">Early Check-in</span>';
            case 'punctual':
                return '<span class="label label-success">Punctual</span>';
            case 'delayed':
                return '<span class="label label-info">Delayed</span>';
            case 'grace window':
                return '<span class="label label-default">Grace Window</span>';
            case 'late':
                return '<span class="label label-warning">Late</span>';
            case 'very late':
                return '<span class="label label-danger">Very Late</span>';
            case 'absent':
                return '<span class="label label-danger">Absent</span>';
            case 'no shift':
                return '<span class="label label-default">No Shift</span>';
            case 'not applicable':
                return '<span class="label label-default">Not Applicable</span>';
            // Legacy statuses
            case 'present':
                return '<span class="label label-success">' . _l('present') . '</span>';
            default:
                return '<span class="label label-default">' . html_escape($status) . '</span>';
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
     * Based on new rules:
     * - 09:45 – 10:00: +5 points (Early buffer)
     * - 10:00 – 10:05: +4 points (On-time grace period)
     * - 10:05 – 10:15: +2 points (Minor delay)
     * - 10:15 – 10:29: 0 points (Late but acceptable)
     * - 10:30 – 10:59: -2 points (Moderately late)
     * - After 11:00: -4 points (Very late)
     * - No Check-in / Absent: -6 points
     * 
     * @param string $shift_time_start Time in HH:MM format (e.g., "10:00")
     * @param string $check_in_time Time in HH:MM format or datetime (e.g., "10:05" or "2026-01-08 10:05:00")
     * @param string $date Date in Y-m-d format (for validation)
     * @return int Points calculated
     */
    public function calculate_points($shift_time_start, $check_in_time, $date = null)
    {
        // Validate date - only calculate points for 2026 onwards
        if (!empty($date)) {
            $date_year = (int)date('Y', strtotime($date));
            $current_date = date('Y-m-d');
            
            // Don't calculate points for years before 2026
            if ($date_year < 2026) {
                return 0;
            }
            
            // Don't calculate points for future dates
            if ($date > $current_date) {
                return 0;
            }
        }

        // If no shift time or shift is "NS" (No Shift), return 0 (can't calculate points without a shift)
        if (empty($shift_time_start) || $shift_time_start == '-' || $shift_time_start == 'NS') {
            return 0;
        }

        // If no check-in, return -6 points (absent) - but only if there's a shift assigned
        if (empty($check_in_time) || $check_in_time == '-' || $check_in_time == 'NS') {
            return -6;
        }

        // Extract time from datetime if needed
        if (strpos($check_in_time, ' ') !== false) {
            $check_in_time = date('H:i', strtotime($check_in_time));
        }

        // Parse times
        $shift_timestamp = strtotime($shift_time_start);
        $checkin_timestamp = strtotime($check_in_time);

        // Calculate difference in minutes from shift start time
        $diff_minutes = round(($checkin_timestamp - $shift_timestamp) / 60);

        // Calculate points based on new rules
        // 09:45 – 10:00 (T - 15 min to T): +5 points
        if ($diff_minutes >= -15 && $diff_minutes < 0) {
            return 5;
        }
        // 10:00 – 10:05 (T to T + 5 min): +4 points
        elseif ($diff_minutes >= 0 && $diff_minutes <= 5) {
            return 4;
        }
        // 10:05 – 10:15 (T + 5 to T + 15 min): +2 points
        elseif ($diff_minutes > 5 && $diff_minutes <= 15) {
            return 2;
        }
        // 10:15 – 10:29 (T + 15 to T + 29 min): 0 points
        elseif ($diff_minutes > 15 && $diff_minutes <= 29) {
            return 0;
        }
        // 10:30 – 10:59 (T + 30 to T + 59 min): -2 points
        elseif ($diff_minutes >= 30 && $diff_minutes <= 59) {
            return -2;
        }
        // After 11:00 (T + 60 min or more): -4 points
        elseif ($diff_minutes >= 60) {
            return -4;
        }
        // Before 09:45 (very early, more than 15 min before): +5 points (same as early buffer)
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
        if (!empty($shift_time) && $shift_time != '-' && $shift_time != 'NS') {
            if (strpos($shift_time, ' - ') !== false) {
                $parts = explode(' - ', $shift_time);
                $shift_start = trim($parts[0]);
            } else {
                $shift_start = trim($shift_time);
            }
        }

        // Calculate points with date validation
        $points = $this->calculate_points($shift_start, $check_in_time, $date);

        // Only store points if date is valid (2026 onwards and not future)
        if ($points != 0 || ($points == 0 && !empty($shift_start) && !empty($check_in_time))) {
            // Store daily points
            $this->store_daily_points($staff_id, $date, $points, $shift_time, $check_in_time);
        }

        return $points;
    }
}

