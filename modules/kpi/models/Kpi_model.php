<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kpi_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // =========================================================
    // MAIN – used by controller table()
    // =========================================================
    public function get_datatable_data($params = [])
    {
        $view           = $params['view']           ?? 'daily';
        $month          = $params['month']          ?? null;
        $staff_id       = $params['staff_id']       ?? null;
        $from_date      = $params['from_date']      ?? null;
        $to_date        = $params['to_date']        ?? null;
        $status_filter  = $params['status_filter']  ?? null;
        $search         = $params['search']         ?? '';
        $start          = isset($params['start']) ? (int)$params['start'] : 0;
        $length         = isset($params['length']) && $params['length'] !== '' ? (int)$params['length'] : 10;
        $can_view_global = $params['can_view_global'] ?? false;

        // If all staff + daily view → use monthly summary style
        if (empty($staff_id) && $view === 'daily') {
            $view = 'monthly';
        }

        // --- Decide which dataset to build ---
        if ($view === 'monthly' && $month) {

            // Monthly view for a given month
            $data = $this->get_monthly_data($month, $staff_id, $status_filter, $search, $can_view_global);

        } elseif (empty($staff_id) && !empty($from_date) && $from_date === $to_date) {

            // All staff, single day
            $data = $this->get_daily_single_data($from_date, $staff_id, $status_filter, $search, $can_view_global);

        } elseif (empty($staff_id) && !empty($from_date) && $from_date !== $to_date) {

            // All staff, date range → summary per staff
            $data = $this->get_range_total_data($from_date, $to_date, $staff_id, $status_filter, $search, $can_view_global);

        } elseif (!empty($staff_id) && $view === 'daily' && !empty($from_date) && $from_date !== $to_date) {

            // Specific staff, daily breakdown for date range
            $data = $this->get_daily_range_data($from_date, $to_date, $staff_id, $status_filter, $search, $can_view_global);

        } elseif (!empty($staff_id) && $view === 'daily' && !empty($from_date) && $from_date === $to_date) {

            // Specific staff, single day
            $data = $this->get_daily_single_data($from_date, $staff_id, $status_filter, $search, $can_view_global);

        } elseif (!empty($staff_id) && $view === 'daily') {

            // Specific staff, no date filter → today only
            $today = date('Y-m-d');
            $data  = $this->get_daily_single_data($today, $staff_id, $status_filter, $search, $can_view_global);

        } else {
            // Fallback: summary between start_date and end_date
            if ($month) {
                $start_date = $month . '-01';
                $end_date   = ($month == date('Y-m'))
                    ? date('Y-m-d')
                    : date('Y-m-t', strtotime($start_date));
            } elseif ($from_date && $to_date) {
                $start_date = $from_date;
                $end_date   = $to_date;
            } else {
                $start_date = date('Y-m-d');
                $end_date   = date('Y-m-d');
            }

            $data = $this->get_range_total_data($start_date, $end_date, $staff_id, $status_filter, $search, $can_view_global);
        }

        // --- Server-side pagination (Perfex style) ---
        if ($length != -1) {
            $data = array_slice($data, (int)$start, (int)$length);
        }

        return $data ?: [];
    }

    // =========================================================
    // DAILY: SINGLE DAY (per staff row, including absents)
    // =========================================================
    private function get_daily_single_data($date, $staff_id = null, $status_filter = null, $search = '', $can_view_global = false)
    {
        $params          = [];
        $staff_condition = '';
        $search_condition = '';

        // Staff restriction
        if ($staff_id) {
            $staff_condition = "AND s.staffid = ?";
            $params[]        = $staff_id;
        } elseif (!$can_view_global) {
            $staff_condition = "AND s.staffid = ?";
            $params[]        = get_staff_user_id();
        } else {
            $staff_condition = "AND s.active = 1";
        }

        // Search name
        if (!empty($search)) {
            $search_condition = "AND (s.firstname LIKE ? OR s.lastname LIKE ?)";
            $params[]         = "%{$search}%";
            $params[]         = "%{$search}%";
        }

        // Escape date once; this returns e.g. '2025-12-10'
        $safe_date = $this->db->escape($date);

        $sql = "
            SELECT
                s.staffid,
                s.firstname,
                s.lastname,
                $safe_date AS att_date,
                cio.first_checkin
            FROM " . db_prefix() . "staff s
            LEFT JOIN (
                SELECT 
                    staff_id,
                    MIN(date) AS first_checkin
                FROM " . db_prefix() . "check_in_out
                WHERE DATE(date) = $safe_date
                  AND type_check = 1
                GROUP BY staff_id
            ) AS cio ON cio.staff_id = s.staffid
            WHERE 1=1
            {$staff_condition}
            {$search_condition}
            ORDER BY s.firstname ASC
        ";

        $rows = $this->db->query($sql, $params)->result_array();
        if (!$rows) {
            return [];
        }

        $result = [];

        foreach ($rows as $r) {
            $check_in = $r['first_checkin'] ? date('H:i', strtotime($r['first_checkin'])) : null;

            $shift       = $this->get_shift_for_staff($r['staffid'], $date);
            $shift_start = $shift['time_start_work'] ?? 'NS';

            $status_data = $this->calculate_daily_status($shift_start, $check_in);
            $color       = $this->get_color_from_status($status_data['status'], $status_data['points']);

            // Apply textual status filter if requested
            if ($status_filter && $status_filter !== 'all' && $status_filter !== $status_data['status']) {
                continue;
            }

            $result[] = [
                'staff_name'  => trim($r['firstname'] . ' ' . $r['lastname']),
                'date'        => $date,
                'check_in'    => $check_in ?: '--',
                'shift_start' => $shift_start,
                'status'      => $status_data['status'],
                'points'      => $status_data['points'],
                'color'       => $color,
            ];
        }

        return $result;
    }

    // =========================================================
    // DAILY: DATE RANGE (per staff per day)
    // =========================================================
    private function get_daily_range_data($start_date, $end_date, $staff_id = null, $status_filter = null, $search = '', $can_view_global = false)
    {
        $params          = [];
        $staff_condition = '';
        $search_condition = '';

        if ($staff_id) {
            $staff_condition = "AND s.staffid = ?";
            $params[]        = $staff_id;
        } elseif (!$can_view_global) {
            $staff_condition = "AND s.staffid = ?";
            $params[]        = get_staff_user_id();
        } else {
            $staff_condition = "AND s.active = 1";
        }

        if (!empty($search)) {
            $search_condition = "AND (s.firstname LIKE ? OR s.lastname LIKE ? OR dates.date LIKE ?)";
            $params[]         = "%{$search}%";
            $params[]         = "%{$search}%";
            $params[]         = "%{$search}%";
        }

        $sd = $this->db->escape($start_date);
        $ed = $this->db->escape($end_date);

        // Simple calendar generator 0–99 days (enough for typical ranges)
        $sql = "
            SELECT
                s.staffid,
                s.firstname,
                s.lastname,
                dates.date AS att_date,
                cio.first_checkin
            FROM " . db_prefix() . "staff s
            CROSS JOIN (
                SELECT DATE($sd) + INTERVAL (a.a + 10*b.a) DAY AS date
                FROM 
                    (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
                     UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
                CROSS JOIN 
                    (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
                     UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS b
            ) AS dates
            LEFT JOIN (
                SELECT 
                    staff_id,
                    DATE(date) AS att_date,
                    MIN(CASE WHEN type_check = 1 THEN date END) AS first_checkin
                FROM " . db_prefix() . "check_in_out
                WHERE DATE(date) >= $sd AND DATE(date) <= $ed
                GROUP BY staff_id, DATE(date)
            ) AS cio ON cio.staff_id = s.staffid AND cio.att_date = dates.date
            WHERE dates.date BETWEEN $sd AND $ed
              AND dates.date <= CURDATE()
            {$staff_condition}
            {$search_condition}
            ORDER BY s.firstname ASC, dates.date DESC
        ";

        $rows = $this->db->query($sql, $params)->result_array();
        if (!$rows) {
            return [];
        }

        $result = [];

        foreach ($rows as $r) {
            $check_in = $r['first_checkin'] ? date('H:i', strtotime($r['first_checkin'])) : null;

            $shift       = $this->get_shift_for_staff($r['staffid'], $r['att_date']);
            $shift_start = $shift['time_start_work'] ?? 'NS';

            $status_data = $this->calculate_daily_status($shift_start, $check_in);
            $color       = $this->get_color_from_status($status_data['status'], $status_data['points']);

            if ($status_filter && $status_filter !== 'all' && $status_filter !== $status_data['status']) {
                continue;
            }

            $result[] = [
                'staff_name'  => trim($r['firstname'] . ' ' . $r['lastname']),
                'date'        => $r['att_date'],
                'check_in'    => $check_in ?: '--',
                'shift_start' => $shift_start,
                'status'      => $status_data['status'],
                'points'      => $status_data['points'],
                'color'       => $color,
            ];
        }

        return $result;
    }

    // =========================================================
    // SUMMARY: RANGE TOTAL (per staff average points)
    // =========================================================
    private function get_range_total_data($start_date, $end_date, $staff_id = null, $status_filter = null, $search = '', $can_view_global = false)
    {
        $params          = [];
        $staff_condition = '';
        $search_condition = '';

        if ($staff_id) {
            $staff_condition = "AND s.staffid = ?";
            $params[]        = $staff_id;
        } elseif (!$can_view_global) {
            $staff_condition = "AND s.staffid = ?";
            $params[]        = get_staff_user_id();
        } else {
            $staff_condition = "AND s.active = 1";
        }

        if (!empty($search)) {
            $search_condition = "AND (s.firstname LIKE ? OR s.lastname LIKE ? OR dates.date LIKE ?)";
            $params[]         = "%{$search}%";
            $params[]         = "%{$search}%";
            $params[]         = "%{$search}%";
        }

        $sd = $this->db->escape($start_date);
        $ed = $this->db->escape($end_date);

        $sql = "
            SELECT
                s.staffid,
                s.firstname,
                s.lastname,
                dates.date AS att_date,
                cio.first_checkin
            FROM " . db_prefix() . "staff s
            CROSS JOIN (
                SELECT DATE($sd) + INTERVAL (a.a + 10*b.a) DAY AS date
                FROM 
                    (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
                     UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
                CROSS JOIN 
                    (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
                     UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS b
            ) AS dates
            LEFT JOIN (
                SELECT 
                    staff_id,
                    DATE(date) AS att_date,
                    MIN(CASE WHEN type_check = 1 THEN date END) AS first_checkin
                FROM " . db_prefix() . "check_in_out
                WHERE DATE(date) >= $sd AND DATE(date) <= $ed
                GROUP BY staff_id, DATE(date)
            ) AS cio ON cio.staff_id = s.staffid AND cio.att_date = dates.date
            WHERE dates.date BETWEEN $sd AND $ed
              AND dates.date <= CURDATE()
            {$staff_condition}
            {$search_condition}
            ORDER BY s.firstname ASC, dates.date DESC
        ";

        $rows = $this->db->query($sql, $params)->result_array();
        if (!$rows) {
            return [];
        }

        $staff_data = [];

        foreach ($rows as $r) {
            $sid = $r['staffid'];

            if (!isset($staff_data[$sid])) {
                $shift = $this->get_shift_for_staff($sid, $r['att_date']);

                $staff_data[$sid] = [
                    'staff_name'  => trim($r['firstname'] . ' ' . $r['lastname']),
                    'shift_start' => $shift['time_start_work'] ?? 'NS',
                    'points_sum'  => 0,
                    'days_count'  => 0,
                ];
            }

            $check_in = $r['first_checkin'] ? date('H:i', strtotime($r['first_checkin'])) : null;

            if ($check_in && $staff_data[$sid]['shift_start'] !== 'NS') {
                $daily = $this->calculate_daily_status($staff_data[$sid]['shift_start'], $check_in);

                // Only count present days (not absent)
                if ($daily['status'] !== 'absent') {
                    $staff_data[$sid]['points_sum'] += $daily['points'];
                    $staff_data[$sid]['days_count']++;
                }
            }
        }

        $result = [];

        foreach ($staff_data as $sid => $data) {
            $avg_points = $data['days_count'] > 0
                ? round($data['points_sum'] / $data['days_count'], 2)
                : 0;

            $color = $this->get_color_from_average($avg_points);

            // NOTE: status_filter is mainly for daily views; we ignore it here
            // so that summary rows are not unexpectedly filtered out.

            $result[] = [
                'staff_name'  => $data['staff_name'],
                'date'        => _d($start_date) . ' to ' . _d($end_date),
                'check_in'    => '--',
                'shift_start' => $data['shift_start'],
                'status'      => 'summary',
                'points'      => $avg_points,
                'color'       => $color,
            ];
        }

        return $result;
    }

    // =========================================================
    // MONTHLY WRAPPER (just uses range total)
    // =========================================================
    private function get_monthly_data($month, $staff_id = null, $status_filter = null, $search = '', $can_view_global = false)
    {
        $start_date = $month . '-01';
        if ($month == date('Y-m')) {
            $end_date = date('Y-m-d');
        } else {
            $end_date = date('Y-m-t', strtotime($start_date));
        }

        return $this->get_range_total_data($start_date, $end_date, $staff_id, $status_filter, $search, $can_view_global);
    }

    // =========================================================
    // DAILY STATUS ENGINE
    // =========================================================
    private function calculate_daily_status($shift_start, $check_in)
    {
        // No check-in or no shift → absent
        if (!$check_in || !$shift_start || $shift_start === 'NS') {
            return ['status' => 'absent', 'points' => -6];
        }

        $shift_ts = strtotime($shift_start);
        $check_ts = strtotime($check_in);

        if ($shift_ts === false || $check_ts === false) {
            return ['status' => 'absent', 'points' => -6];
        }

        $minutes = ($check_ts - $shift_ts) / 60;

        // EARLY
        if ($minutes <= 0 && $minutes >= -15) {
            return ['status' => 'early', 'points' => 5];
        }

        // ON-TIME
        if ($minutes <= 5) {
            return ['status' => 'on-time', 'points' => 4];
        }

        if ($minutes <= 15) {
            return ['status' => 'on-time', 'points' => 2];
        }

        // LATE
        if ($minutes <= 29) {
            return ['status' => 'late', 'points' => 0];
        }

        if ($minutes <= 59) {
            return ['status' => 'late', 'points' => -2];
        }

        // 60+ minutes late
        return ['status' => 'late', 'points' => -4];
    }

    // Map daily status/points to color for the badge
    private function get_color_from_status($status, $points)
    {
        if ($status === 'absent') {
            return 'gray';
        }

        if ($status === 'early') {
            return 'green';
        }

        if ($status === 'on-time') {
            // If slightly lower points (2) you *could* make this yellow,
            // but keeping it green keeps the UI simpler.
            return 'green';
        }

        // late
        return 'red';
    }

    // Monthly / summary color from average points
    private function get_color_from_average($avg_points)
    {
        // With scale: 5,4,2,0,-2,-4,-6
        if ($avg_points >= 4) {
            return 'green';
        }

        if ($avg_points >= 2) {
            return 'yellow';
        }

        return 'red';
    }

    // =========================================================
    // SHIFT LOOKUP (dynamic per day)
    // =========================================================
    private function get_shift_for_staff($staff_id, $date)
    {
        // First: Exact shift assignment for that date
        $sql = "
            SELECT st.time_start_work, st.time_end_work
            FROM " . db_prefix() . "work_shift_detail AS wsd
            LEFT JOIN " . db_prefix() . "shift_type AS st ON st.id = wsd.shift_id
            WHERE wsd.staff_id = ?
              AND wsd.date     = ?
            LIMIT 1
        ";

        $row = $this->db->query($sql, [$staff_id, $date])->row_array();

        if ($row && !empty($row['time_start_work'])) {
            return $row;
        }

        // Fallback – no shift → mark as NS
        return [
            'time_start_work' => 'NS',
            'time_end_work'   => 'NS',
        ];
    }

    // =========================================================
    // COUNTERS for DataTables
    // =========================================================
    public function count_all_records($params = [])
    {
        // Ignore search & status filters for total count
        $params['search']        = '';
        $params['status_filter'] = null;
        $params['start']         = 0;
        $params['length']        = -1;

        $rows = $this->get_datatable_data($params);
        return count($rows);
    }

    public function count_filtered_records($params = [])
    {
        // Apply search & status filters, but ignore pagination
        $params['start']  = 0;
        $params['length'] = -1;

        $rows = $this->get_datatable_data($params);
        return count($rows);
    }

    public function get_today_checkin_status($staff_id = null)
    {
        if (!$staff_id) {
            $staff_id = get_staff_user_id();
        }

        $today = date('Y-m-d');

        // Find first check-in of the day
        $sql = "
            SELECT MIN(date) AS first_checkin
            FROM " . db_prefix() . "check_in_out
            WHERE staff_id = ?
            AND type_check = 1
            AND DATE(date) = ?
        ";

        $row = $this->db->query($sql, [$staff_id, $today])->row_array();

        if (!$row || empty($row['first_checkin'])) {
            return [
                'checked_in' => false,
                'time'       => '--',
                'msg'        => 'Not checked in today',
            ];
        }

        return [
            'checked_in' => true,
            'time'       => date('H:i', strtotime($row['first_checkin'])),
            'msg'        => 'Checked in at ' . date('H:i', strtotime($row['first_checkin'])),
        ];
    }

    /**
     * Get staff statistics for today
     * Returns total active staff, present staff, and absent staff
     * @return array
     */
    public function get_staff_statistics()
    {
        $today = date('Y-m-d');

        // Get all active staff
        $sql_total = "
            SELECT COUNT(*) as total
            FROM " . db_prefix() . "staff
            WHERE active = 1
        ";
        $result_total = $this->db->query($sql_total)->row_array();
        $total_active = $result_total['total'] ?? 0;

        // Get staff who checked in today
        $sql_present = "
            SELECT COUNT(DISTINCT cio.staff_id) as present
            FROM " . db_prefix() . "check_in_out cio
            INNER JOIN " . db_prefix() . "staff s ON s.staffid = cio.staff_id
            WHERE DATE(cio.date) = ?
            AND cio.type_check = 1
            AND s.active = 1
        ";
        $result_present = $this->db->query($sql_present, [$today])->row_array();
        $present = $result_present['present'] ?? 0;

        // Calculate absent (active staff who didn't check in today)
        $absent = $total_active - $present;

        return [
            'total_active' => (int)$total_active,
            'present'      => (int)$present,
            'absent'      => (int)$absent,
        ];
    }
}