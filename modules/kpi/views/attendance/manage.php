<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">

        <!-- FILTER PANEL -->
        <div class="panel_s mbot15" id="kpi-filter-panel">
            <div class="panel-body">

                <div class="row mtop5 mbot10">
                    <div class="col-md-12">
                        <h4 class="no-margin">
                            <i class="fa fa-filter"></i> Filters
                        </h4>
                        <hr class="hr-panel-heading">
                    </div>
                </div>

                <div class="row">

                    <?php if ($show_full_filters) { ?>
                    <!-- STAFF -->
                    <div class="col-md-3 form-group">
                        <label><i class="fa fa-user"></i> Staff</label>
                        <select id="staff_filter" class="form-control selectpicker"
                                data-live-search="true" title="Select Staff">
                            <option value="">All Staff</option>
                            <?php foreach ($staff_members as $staff) { ?>
                                <option value="<?= $staff['staffid']; ?>">
                                    <?= $staff['firstname'] . ' ' . $staff['lastname']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <?php } ?>

                    <!-- VIEW TYPE -->
                    <div class="col-md-2 form-group">
                        <label><i class="fa fa-eye"></i> View Type</label>
                        <select id="view_filter" class="form-control">
                            <option value="daily">Daily View</option>
                            <option value="monthly">Monthly View</option>
                        </select>
                    </div>

                    <!-- MONTH -->
                    <div class="col-md-2 form-group">
                        <label><i class="fa fa-calendar"></i> Month</label>
                        <select id="month_filter" class="form-control">
                            <option value="">Select Month</option>
                            <?php foreach ($months as $month) { ?>
                                <option value="<?= $month['id']; ?>"
                                        <?= $month['id'] == date('Y-m') ? 'selected' : '' ?>>
                                    <?= $month['name']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <?php if ($show_full_filters) { ?>
                    <!-- DATE RANGE -->
                    <div class="col-md-2 form-group">
                        <label><i class="fa fa-calendar-o"></i> From Date</label>
                        <input type="date" id="from_date_filter" class="form-control">
                    </div>

                    <div class="col-md-2 form-group">
                        <label><i class="fa fa-calendar-check-o"></i> To Date</label>
                        <input type="date" id="to_date_filter" class="form-control">
                    </div>

                    <!-- STATUS -->
                    <div class="col-md-2 form-group">
                        <label><i class="fa fa-tag"></i> Status</label>
                        <select id="status_filter" class="form-control">
                            <option value="all">All Status</option>
                            <option value="early">Early</option>
                            <option value="on-time">On Time</option>
                            <option value="late">Late</option>
                            <option value="absent">Absent</option>
                        </select>
                    </div>
                    <?php } ?>

                </div>
            </div>
        </div>

        <!-- DATATABLE PANEL -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="panel-table-full">
                            <?php
                            $table_data = [];

                            if ($is_admin) {
                                $table_data[] = _l('Staff name');
                            }

                            $table_data[] = _l('Date');
                            $table_data[] = _l('Shift Time');
                            $table_data[] = _l('Check-in');
                            $table_data[] = _l('Points');
                            $table_data[] = _l('Status');

                            render_datatable($table_data, 'attendance-kpi', [], [
                                'id' => 'table-kpi',
                                'data-last-order-identifier' => 'attendance-kpi',
                                'data-default-order' => get_table_last_order('attendance-kpi'),
                            ]);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- JS Global Variables -->
<script>
    var KPI = {
        show_full_filters: <?php echo $show_full_filters ? 'true' : 'false'; ?>,
        current_staff_id: "<?php echo get_staff_user_id(); ?>",
        admin_url: "<?php echo admin_url(); ?>"
    };
</script>

<?php init_tail(); ?>

<!-- Load KPI JS Module -->
<script src="<?php echo module_dir_url('kpi', 'assets/js/kpi_module.js'); ?>"></script>

<!-- Load KPI CSS -->
<link href="<?php echo module_dir_url('kpi', 'assets/css/kpi_styles.css'); ?>" rel="stylesheet">

<style>
.dataTables_filter { display: none !important; }

/* Fix staff name column visibility - ensure it's fully visible */
#table-kpi thead th:first-child,
#table-kpi tbody td:first-child {
    padding-left: 20px !important;
    padding-right: 15px !important;
    min-width: 200px !important;
    width: 200px !important;
    text-align: left !important;
    position: relative !important;
    left: 0 !important;
    margin-left: 0 !important;
}

/* Ensure table container doesn't cut off first column */
.panel-table-full {
    padding-left: 0 !important;
    margin-left: 0 !important;
    overflow-x: visible !important;
}

/* Fix panel-body padding that might cut off first column */
.panel-body {
    padding-left: 15px !important;
    padding-right: 15px !important;
}

/* Fix DataTables wrapper - only one scrollbar */
.dataTables_wrapper {
    padding-left: 0 !important;
    margin-left: 0 !important;
    overflow-x: auto !important;
}

.dataTables_wrapper .table-responsive {
    padding-left: 0 !important;
    margin-left: 0 !important;
    overflow-x: visible !important;
}

.dataTables_wrapper .dataTables_scroll {
    margin-left: 0 !important;
    padding-left: 0 !important;
}

.dataTables_wrapper .dataTables_scrollHead table,
.dataTables_wrapper .dataTables_scrollBody table {
    margin-left: 0 !important;
    border-left: none !important;
}

/* Ensure the table itself doesn't have negative positioning */
#table-kpi {
    margin-left: 0 !important;
    padding-left: 0 !important;
    position: relative !important;
    left: 0 !important;
}

/* Fix first column specifically */
#table-kpi tbody tr td:first-child,
#table-kpi thead tr th:first-child {
    position: relative !important;
    left: 0 !important;
    transform: none !important;
}
</style>