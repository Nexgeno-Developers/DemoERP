<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <i class="fa fa-calendar-check-o"></i> Leaves Overview
                        </h4>
                        <hr class="hr-panel-heading">

                        <!-- Filters -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="year_filter">Year Selection - Financial Year</label>
                                    <select name="year_filter" id="year_filter" class="form-control selectpicker">
                                        <?php foreach ($years as $y): ?>
                                            <option value="<?php echo $y; ?>" <?php echo ($y == $selected_year) ? 'selected' : ''; ?>>
                                                <?php echo $y; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status_filter">Staff Status</label>
                                    <select name="status_filter" id="status_filter" class="form-control selectpicker">
                                        <option value="all" <?php echo ($selected_status == 'all') ? 'selected' : ''; ?>>All Staff</option>
                                        <option value="active" <?php echo ($selected_status == 'active') ? 'selected' : ''; ?>>Active Staff</option>
                                        <option value="inactive" <?php echo ($selected_status == 'inactive') ? 'selected' : ''; ?>>Inactive Staff</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="staff_filter">Staff</label>
                                    <select name="staff_filter" id="staff_filter" class="form-control selectpicker" data-live-search="true">
                                        <option value="">All Staff</option>
                                        <?php foreach ($staff_members as $staff): ?>
                                            <option value="<?php echo $staff['staffid']; ?>" <?php echo ($staff['staffid'] == $selected_staff) ? 'selected' : ''; ?>>
                                                <?php echo $staff['firstname'] . ' ' . $staff['lastname']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label><br>
                                    <button type="button" id="apply_filters" class="btn btn-info">
                                        <i class="fa fa-filter"></i> Apply Filters
                                    </button>
                                    <button type="button" id="reset_filters" class="btn btn-default">
                                        <i class="fa fa-refresh"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Leaves Table -->
                        <div class="row mtop15">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered leaves-overview-table" id="leaves_table">
                                        <thead>
                                            <tr style="background-color: #f8f9fa;">
                                                <th>Staff Name</th>
                                                <th>Leave Type</th>
                                                <th class="text-center">Assigned</th>
                                                <th class="text-center">Applied</th>
                                                <th class="text-center">Approved</th>
                                                <th class="text-center">Rejected</th>
                                                <th class="text-center">Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($leaves_data)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">
                                                        <i class="fa fa-info-circle"></i> No leave data found for the selected filters
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php 
                                                // Group data by staff
                                                $grouped_data = [];
                                                foreach ($leaves_data as $leave) {
                                                    $grouped_data[$leave['staff_name']][] = $leave;
                                                }
                                                
                                                foreach ($grouped_data as $staff_name => $staff_leaves): 
                                                    $first_row = true;
                                                    foreach ($staff_leaves as $leave): 
                                                        // Determine row color
                                                        $row_class = '';
                                                        
                                                        if ($leave['approved'] > $leave['assigned']) {
                                                            $row_class = 'bg-danger-light'; // Red - approved more than assigned
                                                        } elseif ($leave['applied'] > $leave['assigned']) {
                                                            $row_class = 'bg-warning-light'; // Light orange - applied more than assigned
                                                        }
                                                ?>
                                                    <tr class="<?php echo $row_class; ?>">
                                                        <?php if ($first_row): ?>
                                                            <td rowspan="<?php echo count($staff_leaves); ?>" style="vertical-align: middle; font-weight: 600;">
                                                                <?php echo $staff_name; ?>
                                                            </td>
                                                        <?php endif; ?>
                                                        <td><?php echo $leave['leave_type']; ?></td>
                                                        <td class="text-center"><?php echo number_format($leave['assigned'], 0); ?></td>
                                                        <td class="text-center"><?php echo $leave['applied']; ?></td>
                                                        <td class="text-center"><?php echo $leave['approved']; ?></td>
                                                        <td class="text-center"><?php echo $leave['rejected']; ?></td>
                                                        <td class="text-center <?php echo ($leave['balance'] < 0) ? 'text-danger' : ''; ?>">
                                                            <?php echo number_format($leave['balance'], 1); ?>
                                                            <?php if ($leave['balance'] < 0): ?>
                                                                <i class="fa fa-exclamation-triangle text-danger" title="Negative balance"></i>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php 
                                                        $first_row = false;
                                                    endforeach; 
                                                endforeach; 
                                                ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.leaves-overview-table {
    font-size: 13px;
    border: 2px solid #333 !important;
}

.leaves-overview-table thead th {
    font-weight: 600;
    border: 2px solid #333 !important;
    border-bottom: 3px solid #333 !important;
}

.leaves-overview-table tbody tr {
    border: 1px solid #666 !important;
}

.leaves-overview-table tbody td {
    vertical-align: middle;
    border: 1px solid #666 !important;
}

/* Staff name column - darker border to show grouping */
.leaves-overview-table tbody td[rowspan] {
    border: 2px solid #333 !important;
    border-right: 3px solid #333 !important;
    font-weight: 600;
    background-color: #f5f5f5;
}

.leaves-overview-table tbody tr:hover {
    opacity: 0.9;
}

.text-danger {
    color: #dc3545 !important;
    font-weight: 600;
}

/* Row background colors */
.bg-warning-light {
    background-color: #ffe0b2 !important; /* Light orange */
}

.bg-danger-light {
    background-color: #ffcdd2 !important; /* Light red */
}

.bg-warning-light:hover,
.bg-danger-light:hover {
    opacity: 0.85;
}
</style>

<?php init_tail(); ?>

<script>
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Apply filters
        $('#apply_filters').on('click', function() {
            var year = $('#year_filter').val();
            var staff = $('#staff_filter').val();
            var status = $('#status_filter').val();
            
            var url = '<?php echo admin_url('nexreports/leaves'); ?>?year=' + year + '&status=' + status;
            if (staff) {
                url += '&staff=' + staff;
            }
            
            window.location.href = url;
        });
        
        // Reset filters
        $('#reset_filters').on('click', function() {
            window.location.href = '<?php echo admin_url('nexreports/leaves'); ?>';
        });
    });
})(jQuery);
</script>

</body>
</html>
