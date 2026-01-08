<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <!-- Attendance Leaderboard Panel -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="no-margin"><?php echo _l('attendance_leaderboard'); ?></h4>
                                <hr class="hr-panel-heading">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <p class="text-muted"><?php echo _l('attendance_leaderboard_description'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Panel -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="no-margin"><?php echo _l('filters'); ?></h4>
                                <hr class="hr-panel-heading">
                            </div>
                        </div>
                        <div class="row mbot15">
                            <div class="col-md-12">
                                <div class="_filters _hidden_inputs">
                                    <div class="row">
                                        <?php
                                        // Check if user has view permission (not just view_own)
                                        $has_view_permission = has_permission('kpi_attendance', '', 'view');
                                        ?>
                                        <div class="col-md-3">
                                            <label><?php echo _l('date'); ?></label>
                                            <input type="date" name="date_filter" id="date_filter" class="form-control">
                                        </div>
                                        <?php if ($has_view_permission && isset($staff) && !empty($staff)) { ?>
                                        <div class="col-md-3">
                                            <label><?php echo _l('staff'); ?></label>
                                            <select name="staff_filter" id="staff_filter" class="form-control selectpicker" data-width="100%" data-live-search="true" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                                <option value=""><?php echo _l('all'); ?></option>
                                                <?php foreach ($staff as $s) { ?>
                                                    <option value="<?php echo $s['staffid']; ?>"><?php echo $s['firstname'] . ' ' . $s['lastname']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <?php } ?>
                                        <div class="col-md-3">
                                            <label><?php echo _l('status'); ?></label>
                                            <select name="status_filter" id="status_filter" class="form-control selectpicker" data-width="100%">
                                                <option value=""><?php echo _l('all'); ?></option>
                                                <option value="present"><?php echo _l('present'); ?></option>
                                                <option value="absent"><?php echo _l('absent'); ?></option>
                                                <option value="late"><?php echo _l('late'); ?></option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 text-right">
                                            <br>
                                            <button type="button" class="btn btn-info" onclick="applyFilters(); return false;">
                                                <i class="fa fa-filter"></i> <?php echo _l('apply_filters'); ?>
                                            </button>
                                            <button type="button" class="btn btn-default" onclick="clearFilters(); return false;">
                                                <i class="fa fa-times"></i> <?php echo _l('clear_filters'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Table Panel -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="panel-table-full">
                            <?php
                            $table_data = [];
                            
                            // Name column - always show since we're displaying all staff
                            $table_data[] = _l('name');
                            
                            $table_data[] = _l('date');
                            $table_data[] = _l('shift_time');
                            $table_data[] = _l('check_in_time');
                            $table_data[] = _l('points');
                            $table_data[] = _l('status');
                            
                            render_datatable($table_data, 'attendance', ['scroll-responsive']);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
