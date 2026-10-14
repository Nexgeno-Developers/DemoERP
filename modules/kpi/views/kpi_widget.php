<?php
defined('BASEPATH') or exit('No direct script access allowed');

// ALL db calls wrapped - never let widget crash the dashboard
$status = ['checked_in' => false, 'time' => '--'];
$has_role_9 = false;
$stats = ['total_active' => 0, 'present' => 0, 'on_leave' => 0, 'absent' => 0];

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
                    <div class="col-md-3 text-center">
                        <div class="widget-stat-box">
                            <h3 class="text-primary" style="font-size:32px;margin:10px 0;"><?= (int)$stats['total_active']; ?></h3>
                            <p class="text-muted" style="margin:0;"><strong>Active Staff</strong></p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="widget-stat-box">
                            <h3 class="text-success" style="font-size:32px;margin:10px 0;"><?= (int)$stats['present']; ?></h3>
                            <p class="text-muted" style="margin:0;"><strong>Present</strong></p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="widget-stat-box kpi-clickable-stat" data-type="on_leave" onclick="showKpiStaffListModal('on_leave'); return false;" style="cursor: pointer;" title="Click to view staff list">
                            <h3 class="text-warning" style="font-size:32px;margin:10px 0;"><?= (int)$stats['on_leave']; ?></h3>
                            <p class="text-muted" style="margin:0;"><strong>On Leave</strong></p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="widget-stat-box kpi-clickable-stat" data-type="absent" onclick="showKpiStaffListModal('absent'); return false;" style="cursor: pointer;" title="Click to view staff list">
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
            
            <?php if ($has_role_9) { ?>
            <script>
// Define function immediately for onclick handlers
// This must be defined before the onclick handlers try to use it
(function() {
    'use strict';
    
    // Define the function immediately
    window.showKpiStaffListModal = function(type) {
        // Check if jQuery is available, wait if not
        if (typeof jQuery === 'undefined' && typeof $ === 'undefined') {
            // Wait a bit and try again
            setTimeout(function() {
                window.showKpiStaffListModal(type);
            }, 200);
            return;
        }
        
        // Use jQuery if $ is not defined
        var $jq = typeof $ !== 'undefined' ? $ : (typeof jQuery !== 'undefined' ? jQuery : null);
        if (!$jq) {
            alert('Error: jQuery not loaded. Please refresh the page.');
            return;
        }
        
        if (typeof admin_url === 'undefined') {
            alert('Error: admin_url not defined. Please refresh the page.');
            return;
        }
        
        // Use jQuery for all operations
        var modal = $jq('#kpi_staff_list_modal');
        if (modal.length === 0) {
            alert('Error: Modal not found. Please refresh the page.');
            return;
        }

        // Move modal to body if needed
        var parent = modal.parent();
        if (!parent.is('body')) {
            modal.detach().appendTo('body');
        }
        
        // Reset modal content
        $jq('#kpi_staff_list_loading').show();
        $jq('#kpi_staff_list_content').hide();
        $jq('#kpi_staff_list_empty').hide();
        $jq('#kpi_staff_list_tbody').empty();
        
        // Set title
        var title = type === 'absent' ? 'Absent Staff' : 'Staff On Leave';
        $jq('#kpi_modal_title').text(title);
        
        // Show/hide leave dates column for on_leave
        if (type === 'on_leave') {
            $jq('#kpi_leave_dates_header').show();
        } else {
            $jq('#kpi_leave_dates_header').hide();
        }
        
        // Show modal using Bootstrap
        modal.modal('show');
        
        // Fetch staff list via AJAX
        $jq.ajax({
            url: admin_url + 'kpi/get_staff_list',
            type: 'GET',
            data: { type: type },
            dataType: 'json',
            success: function(response) {
                $jq('#kpi_staff_list_loading').hide();
                
                if (response.success && response.staff && response.staff.length > 0) {
                    var tbody = $jq('#kpi_staff_list_tbody');
                    tbody.empty();
                    
                    $jq.each(response.staff, function(index, staff) {
                        var row = '<tr>';
                        row += '<td>' + (index + 1) + '</td>';
                        row += '<td><strong>' + (staff.full_name || staff.firstname + ' ' + staff.lastname) + '</strong></td>';
                        row += '<td>' + (staff.email || '-') + '</td>';
                        
                        if (type === 'on_leave') {
                            var leavePeriod = '-';
                            if (staff.start_time && staff.end_time) {
                                var startDate = new Date(staff.start_time);
                                var endDate = new Date(staff.end_time);
                                var startFormatted = startDate.toLocaleDateString();
                                var endFormatted = endDate.toLocaleDateString();
                                leavePeriod = startFormatted + ' to ' + endFormatted;
                            }
                            var statusBadge = '';
                            if (staff.status === 0 || staff.status === '0') {
                                statusBadge = ' <span class="badge badge-warning">Pending</span>';
                            } else if (staff.status === 1 || staff.status === '1') {
                                statusBadge = ' <span class="badge badge-success">Approved</span>';
                            }
                            row += '<td>' + leavePeriod + statusBadge + '</td>';
                        }
                        
                        row += '</tr>';
                        tbody.append(row);
                    });
                    
                    $jq('#kpi_staff_list_content').show();
                } else {
                    $jq('#kpi_staff_list_empty').show();
                }
            },
            error: function(xhr, status, error) {
                $jq('#kpi_staff_list_loading').hide();
                $jq('#kpi_staff_list_empty').html('<p class="text-danger">Error loading staff list. Please try again.</p>').show();
            }
        });
    };
})();
            </script>
            
            <!-- Modal for displaying staff list -->
            <div class="modal fade" id="kpi_staff_list_modal" tabindex="-1" role="dialog" aria-labelledby="kpi_modal_title">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <h4 class="modal-title" id="kpi_modal_title">Staff List</h4>
                        </div>
                        <div class="modal-body">
                            <div id="kpi_staff_list_loading" class="text-center" style="padding: 20px;">
                                <i class="fa fa-spinner fa-spin fa-2x"></i>
                                <p>Loading staff list...</p>
                            </div>
                            <div id="kpi_staff_list_content" style="display: none;">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th id="kpi_leave_dates_header" style="display: none;">Leave Period</th>
                                            </tr>
                                        </thead>
                                        <tbody id="kpi_staff_list_tbody">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div id="kpi_staff_list_empty" style="display: none; text-align: center; padding: 20px;">
                                <p class="text-muted">No staff found in this category.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<style>
.kpi-clickable-stat:hover {
    background-color: #f5f5f5;
    transition: background-color 0.2s ease;
}
.kpi-clickable-stat h3 {
    transition: transform 0.2s ease;
}
.kpi-clickable-stat:hover h3 {
    transform: scale(1.05);
}
</style>
