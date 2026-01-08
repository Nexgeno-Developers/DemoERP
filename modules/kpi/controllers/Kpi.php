<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kpi extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('kpi/kpi_model');
    }

    /**
     * Dashboard page
     */
    public function dashboard()
    {
        if (!has_permission('kpi_dashboard', '', 'view') && !has_permission('kpi_dashboard', '', 'view_own')) {
            access_denied('kpi_dashboard');
        }

        $data['title'] = _l('dashboard');
        $this->load->view('kpi/dashboard', $data);
    }

    /**
     * Attendance page
     */
    public function attendance()
    {
        if (!has_permission('kpi_attendance', '', 'view') && !has_permission('kpi_attendance', '', 'view_own')) {
            access_denied('kpi_attendance');
        }

        $data['title'] = _l('attendance');
        
        // Get active staff for filter (only if user has view permission)
        if (has_permission('kpi_attendance', '', 'view')) {
            $data['staff'] = $this->kpi_model->get_active_staff();
        } else {
            $data['staff'] = [];
        }
        
        // Get top 3 staff for leaderboard (current month)
        $data['leaderboard'] = $this->kpi_model->get_top_staff_leaderboard(3);
        
        $this->load->view('kpi/attendance', $data);
    }

    /**
     * Attendance datatable
     */
    public function attendance_table()
    {
        if (!has_permission('kpi_attendance', '', 'view') && !has_permission('kpi_attendance', '', 'view_own')) {
            ajax_access_denied();
        }

        $this->app->get_table_data(module_views_path('kpi', 'tables/attendance'));
    }
}

