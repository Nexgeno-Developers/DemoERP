<?php
defined('BASEPATH') or exit('No direct script access allowed');

// ALL db calls wrapped - never let widget crash the dashboard
$status = ['checked_in' => false, 'time' => '--'];
$has_role_9 = false;
$stats = ['total_active' => 0, 'present' => 0, 'absent' => 0];

try {
    $CI =& get_instance();
    $CI->load->model('kpi/Kpi_model');

    $old_debug = $CI->db->db_debug;
    $CI->db->db_debug = false;

    try { $status = $CI->Kpi_model->get_today_checkin_status(); } catch (Throwable $e) { log_message('error', 'KPI widget checkin: ' . $e->getMessage()); }

    $current_staff = get_staff();
    if ($current_staff && isset($current_staff->role) && $current_staff->role == 9) {
        $has_role_9 = true;
        try { $stats = $CI->Kpi_model->get_staff_statistics(); } catch (Throwable $e) { log_message('error', 'KPI widget stats: ' . $e->getMessage()); }
    }

    $CI->db->db_debug = $old_debug;

} catch (Throwable $e) {
    log_message('error', 'KPI widget error: ' . $e->getMessage());
}
?>

<div class="widget" id="widget-kpi_widget" data-name="KPI Widget">
    <div class="panel_s user-data">
        <div class="panel-body">
            <div class="widget-dragger"></div>
            <h4 class="no-margin font-bold">
                <i class="fa fa-line-chart"></i>
                KPI – Today Check-in Status
            </h4>
            <hr>
            <div class="text-center">
                <?php if (!empty($status['checked_in'])) { ?>
                    <h2 style="color: green;">✔ Checked in at <?= htmlspecialchars($status['time']); ?></h2>
                    <button class="btn btn-warning btn-sm hide-kpi-widget">Dismiss</button>
                <?php } else { ?>
                    <h2 style="color: red;">✖ Not checked in today</h2>
                <?php } ?>
            </div>

            <?php if ($has_role_9) { ?>
                <hr>
                <h5 class="text-center" style="margin-bottom: 15px;"><strong>Staff Attendance Statistics</strong></h5>
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="widget-stat-box">
                            <h3 class="text-primary" style="font-size:32px;margin:10px 0;"><?= (int)$stats['total_active']; ?></h3>
                            <p class="text-muted" style="margin:0;"><strong>Active Staff</strong></p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="widget-stat-box">
                            <h3 class="text-success" style="font-size:32px;margin:10px 0;"><?= (int)$stats['present']; ?></h3>
                            <p class="text-muted" style="margin:0;"><strong>Present</strong></p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="widget-stat-box">
                            <h3 class="text-danger" style="font-size:32px;margin:10px 0;"><?= (int)$stats['absent']; ?></h3>
                            <p class="text-muted" style="margin:0;"><strong>Absent</strong></p>
                        </div>
                    </div>
                </div>
                <hr>
                <p class="text-center text-muted" style="margin:0;font-size:12px;">
                    <i class="fa fa-calendar"></i> <?= date('F d, Y'); ?>
                </p>
            <?php } ?>
        </div>
    </div>
</div>
