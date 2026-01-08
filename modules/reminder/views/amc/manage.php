<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="no-margin">
                                    <?php echo _l('amc'); ?>
                                    <?php if (has_permission('reminder_amc', '', 'create')) { ?>
                                        <a href="#" class="btn btn-info pull-right" onclick="add_amc(); return false;">
                                            <i class="fa fa-plus"></i> <?php echo _l('add_amc'); ?>
                                        </a>
                                    <?php } ?>
                                    <!-- AMC Summary Toggle Button -->
                                    <a href="#" class="pull-right btn btn-default btn-with-tooltip mright10" data-toggle="tooltip" data-title="<?php echo _l('amc_summary'); ?>" data-placement="bottom" onclick="slideToggle('.amc-summary-overview'); return false;">
                                        <i class="fa fa-bar-chart"></i>
                                    </a>
                                </h4>
                                <hr class="hr-panel-heading">
                            </div>
                        </div>

                        <!-- AMC Summary Cards (Hidden by default) -->
                        <div class="row hide amc-summary-overview">
                            <hr />
                            <div class="col-md-12">
                                <h4 class="no-margin"><?php echo _l('amc_summary'); ?></h4>
                            </div>
                        </div>
                        
                        <!-- Row 1: Value Cards (Not clickable - informational only) -->
                        <div class="row hide amc-summary-overview mbot15">
                            <div class="col-md-4">
                                <div class="panel panel-primary" data-summary-type="total_value">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin summary-value"><?php echo app_format_money($summary_detailed['values']['total_value'], get_base_currency()); ?></h3>
                                        <p class="text-muted"><?php echo _l('total_amc_value'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-danger" data-summary-type="expired_value">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin summary-value"><?php echo app_format_money($summary_detailed['values']['expired_value'], get_base_currency()); ?></h3>
                                        <p class="text-muted"><?php echo _l('expired_amc_value'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-warning" data-summary-type="expiring_soon_value">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin summary-value"><?php echo app_format_money($summary_detailed['values']['expiring_soon_value'], get_base_currency()); ?></h3>
                                        <p class="text-muted"><?php echo _l('expiring_soon_value'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Row 2: Status Count Cards (Not clickable - informational only) -->
                        <div class="row hide amc-summary-overview mbot15">
                            <div class="col-md-2 col-md-offset-1">
                                <div class="panel panel-info" data-summary-type="total_count">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin summary-value"><?php echo $summary_detailed['counts']['total']; ?></h3>
                                        <p class="text-muted"><?php echo _l('total_amcs'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="panel panel-success" data-summary-type="ongoing_count">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin summary-value"><?php echo $summary_detailed['counts']['ongoing']; ?></h3>
                                        <p class="text-muted"><?php echo _l('ongoing_amcs'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="panel panel-danger" data-summary-type="expired_count">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin summary-value"><?php echo $summary_detailed['counts']['expired']; ?></h3>
                                        <p class="text-muted"><?php echo _l('expired_amcs'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="panel panel-warning" data-summary-type="on_hold_count">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin summary-value"><?php echo $summary_detailed['counts']['on_hold']; ?></h3>
                                        <p class="text-muted"><?php echo _l('on_hold_amcs'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="panel panel-info" data-summary-type="closed_count">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin summary-value"><?php echo $summary_detailed['counts']['closed']; ?></h3>
                                        <p class="text-muted"><?php echo _l('closed_amcs'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Row 3: Expiring Cards (Clickable - filter AMCs expiring in X days) -->
                        <div class="row hide amc-summary-overview mbot15">
                            <div class="col-md-4">
                                <div class="panel panel-warning summary-card-clickable" data-filter-type="expiry" data-filter-value="7" data-summary-type="expiring_7_count" style="cursor: pointer;">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin summary-value"><?php echo $summary_detailed['expiring']['in_7_days']; ?></h3>
                                        <p class="text-muted"><?php echo _l('expiring_in_7_days'); ?></p>
                                        <small class="text-muted"><i class="fa fa-info-circle"></i> Click to filter</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-warning summary-card-clickable" data-filter-type="expiry" data-filter-value="30" data-summary-type="expiring_30_count" style="cursor: pointer;">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin summary-value"><?php echo $summary_detailed['expiring']['in_30_days']; ?></h3>
                                        <p class="text-muted"><?php echo _l('expiring_in_30_days'); ?></p>
                                        <small class="text-muted"><i class="fa fa-info-circle"></i> Click to filter</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-warning summary-card-clickable" data-filter-type="expiry" data-filter-value="90" data-summary-type="expiring_90_count" style="cursor: pointer;">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin summary-value"><?php echo $summary_detailed['expiring']['in_90_days']; ?></h3>
                                        <p class="text-muted"><?php echo _l('expiring_in_90_days'); ?></p>
                                        <small class="text-muted"><i class="fa fa-info-circle"></i> Click to filter</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix"></div>

                        <!-- Comprehensive Filters Panel -->
                        <div class="row mbot15">
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="_filters _hidden_inputs">
                                            <div class="row mbot15">
                                                <div class="col-md-2 border-right">
                                                    <label><?php echo _l('customer'); ?></label>
                                                    <?php 
                                                    $client_options = [['userid' => '', 'company' => _l('all')]];
                                                    foreach ($clients as $client) {
                                                        $client_options[] = $client;
                                                    }
                                                    echo render_select('client_filter', $client_options, ['userid', 'company'], '', 
                                                        isset($active_filters['client_id']) ? $active_filters['client_id'] : '', 
                                                        ['data-live-search' => true, 'data-width' => '100%'], [], 'no-margin');
                                                    ?>
                                                </div>
                                                <div class="col-md-2 border-right">
                                                    <label><?php echo _l('status'); ?></label>
                                                    <select name="status_filter" id="status_filter" class="form-control selectpicker" data-width="100%">
                                                        <option value=""><?php echo _l('all'); ?></option>
                                                        <option value="ongoing" <?php echo (isset($active_filters['status']) && $active_filters['status'] == 'ongoing') ? 'selected' : ''; ?>><?php echo _l('ongoing'); ?></option>
                                                        <option value="expired" <?php echo (isset($active_filters['status']) && $active_filters['status'] == 'expired') ? 'selected' : ''; ?>><?php echo _l('expired'); ?></option>
                                                        <option value="on_hold" <?php echo (isset($active_filters['status']) && $active_filters['status'] == 'on_hold') ? 'selected' : ''; ?>><?php echo _l('on_hold'); ?></option>
                                                        <option value="closed" <?php echo (isset($active_filters['status']) && $active_filters['status'] == 'closed') ? 'selected' : ''; ?>><?php echo _l('closed'); ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2 border-right">
                                                    <label><?php echo _l('amc_type'); ?></label>
                                                    <select name="amc_type_filter" id="amc_type_filter" class="form-control selectpicker" data-width="100%" data-live-search="true">
                                                        <option value=""><?php echo _l('all'); ?></option>
                                                        <?php foreach ($amc_types as $type) { ?>
                                                            <option value="<?php echo $type->id; ?>" <?php echo (isset($active_filters['amc_type_id']) && $active_filters['amc_type_id'] == $type->id) ? 'selected' : ''; ?>><?php echo $type->name; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2 border-right">
                                                    <label><?php echo _l('expiry_type'); ?></label>
                                                    <select name="expiry_type_filter" id="expiry_type_filter" class="form-control selectpicker" data-width="100%">
                                                        <option value=""><?php echo _l('all'); ?></option>
                                                        <option value="expired"><?php echo _l('expired'); ?></option>
                                                        <option value="7"><?php echo _l('expiring_in_7_days'); ?></option>
                                                        <option value="30"><?php echo _l('expiring_in_30_days'); ?></option>
                                                        <option value="90"><?php echo _l('expiring_in_90_days'); ?></option>
                                                        <option value="active"><?php echo _l('active'); ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label><?php echo _l('period'); ?></label>
                                                    <select name="period_filter" id="period_filter" class="form-control selectpicker" data-width="100%">
                                                        <option value=""><?php echo _l('all'); ?></option>
                                                        <option value="this_month"><?php echo _l('this_month'); ?></option>
                                                        <option value="last_month"><?php echo _l('last_month'); ?></option>
                                                        <option value="next_month"><?php echo _l('next_month'); ?></option>
                                                        <option value="custom"><?php echo _l('custom'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mbot15">
                                                <div class="col-md-2 border-right">
                                                    <label><?php echo _l('end_date_from'); ?></label>
                                                    <input type="date" name="date_from_filter" id="date_from_filter" class="form-control" value="<?php echo isset($active_filters['date_from']) ? $active_filters['date_from'] : ''; ?>">
                                                </div>
                                                <div class="col-md-2 border-right">
                                                    <label><?php echo _l('end_date_to'); ?></label>
                                                    <input type="date" name="date_to_filter" id="date_to_filter" class="form-control" value="<?php echo isset($active_filters['date_to']) ? $active_filters['date_to'] : ''; ?>">
                                                </div>
                                                <div class="col-md-2 border-right">
                                                    <label><?php echo _l('amount_min'); ?></label>
                                                    <input type="number" name="amount_min_filter" id="amount_min_filter" class="form-control" step="0.01" value="<?php echo isset($active_filters['amount_min']) ? $active_filters['amount_min'] : ''; ?>" placeholder="<?php echo _l('min_amount'); ?>">
                                                </div>
                                                <div class="col-md-2 border-right">
                                                    <label><?php echo _l('amount_max'); ?></label>
                                                    <input type="number" name="amount_max_filter" id="amount_max_filter" class="form-control" step="0.01" value="<?php echo isset($active_filters['amount_max']) ? $active_filters['amount_max'] : ''; ?>" placeholder="<?php echo _l('max_amount'); ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <label><?php echo _l('tags'); ?></label>
                                                    <?php 
                                                    $tags_list = get_tags();
                                                    $tags_options = [['id' => '', 'name' => _l('all')]];
                                                    foreach ($tags_list as $tag) {
                                                        $tags_options[] = $tag;
                                                    }
                                                    echo render_select('tags_filter', $tags_options, ['id', 'name'], '', 
                                                        isset($active_filters['tags']) ? (is_array($active_filters['tags']) ? $active_filters['tags'] : [$active_filters['tags']]) : '', 
                                                        ['data-width' => '100%', 'data-none-selected-text' => _l('all'), 'multiple' => true, 
                                                         'data-actions-box' => true], [], 'no-margin');
                                                    ?>
                                                    <input type="hidden" name="tags_filter_input" id="tags_filter_input" value="">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12 text-right">
                                                    <button type="button" class="btn btn-info" onclick="applyFiltersAndUpdateSummary(); return false;">
                                                        <i class="fa fa-filter"></i> <?php echo _l('apply_filters'); ?>
                                                    </button>
                                                    <button type="button" class="btn btn-default" onclick="clearAllFilters(); return false;">
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
            </div>
        </div>

        <!-- AMC Table Panel -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="panel-table-full">
                            <?php
                            $table_data = [
                                _l('customer_name'),
                                _l('amc_location'),
                                _l('amc_type'),
                                _l('amount'),
                                _l('end_date'),
                                _l('status'),
                                _l('tags'),
                                [
                                    'name' => _l('options'),
                                    'th_attrs' => ['class' => 'not-export'],
                                ],
                            ];
                            render_datatable($table_data, 'amc', ['scroll-responsive']);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit AMC Modal -->
<div class="modal fade" id="amc_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="amc_modal_title"><?php echo _l('add_amc'); ?></h4>
            </div>
            <?php echo form_open(admin_url('reminder/add_amc'), ['id' => 'amc_form']); ?>
            <div class="modal-body">
                <input type="hidden" name="amc_id" id="amc_id">
                <div class="form-group">
                    <label for="client_id"><?php echo _l('customer'); ?> <span class="text-danger">*</span></label>
                    <select name="client_id" id="client_id" class="form-control selectpicker" data-live-search="true" required>
                        <option value=""><?php echo _l('select_customer'); ?></option>
                        <?php foreach ($clients as $client) { ?>
                            <option value="<?php echo $client['userid']; ?>"><?php echo $client['company']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="amc_location"><?php echo _l('amc_location'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="amc_location" id="amc_location" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="amc_type_id"><?php echo _l('amc_type'); ?></label>
                    <select name="amc_type_id" id="amc_type_id" class="form-control selectpicker" data-live-search="true">
                        <option value=""><?php echo _l('select_amc_type'); ?></option>
                        <?php if (isset($amc_types) && !empty($amc_types)) { ?>
                            <?php foreach ($amc_types as $type) { ?>
                                <option value="<?php echo $type->id; ?>"><?php echo $type->name; ?></option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="amount"><?php echo _l('amount'); ?> <span class="text-danger">*</span></label>
                    <input type="number" name="amount" id="amount" class="form-control" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="start_date"><?php echo _l('start_date'); ?></label>
                    <input type="date" name="start_date" id="start_date" class="form-control">
                </div>
                <div class="form-group">
                    <label for="end_date"><?php echo _l('end_date'); ?> <span class="text-danger">*</span></label>
                    <input type="date" name="end_date" id="end_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="notes"><?php echo _l('notes'); ?></label>
                    <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <div id="inputTagsWrapper">
                        <label for="tags">
                            <i class="fa fa-tag" aria-hidden="true"></i>
                            <?php echo _l('tags'); ?>
                        </label>
                        <input type="text" class="tagsinput" id="tags" name="tags" value="" data-role="tagsinput">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<!-- Renew AMC Modal -->
<div class="modal fade" id="renew_amc_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?php echo _l('renew_amc'); ?></h4>
            </div>
            <?php echo form_open('', ['id' => 'renew_amc_form']); ?>
            <div class="modal-body">
                <input type="hidden" name="renew_amc_id" id="renew_amc_id">
                <div class="form-group">
                    <label for="renew_amount"><?php echo _l('amount'); ?> <span class="text-danger">*</span></label>
                    <input type="number" name="amount" id="renew_amount" class="form-control" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="renew_start_date"><?php echo _l('start_date'); ?></label>
                    <input type="date" name="start_date" id="renew_start_date" class="form-control">
                </div>
                <div class="form-group">
                    <label for="renew_end_date"><?php echo _l('end_date'); ?> <span class="text-danger">*</span></label>
                    <input type="date" name="end_date" id="renew_end_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="renew_notes"><?php echo _l('notes'); ?></label>
                    <textarea name="notes" id="renew_notes" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-info"><?php echo _l('renew'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<style>
.amc-location-link {
    color: #337ab7 !important;
    text-decoration: none;
}
.amc-location-link:hover {
    color: #23527c !important;
    text-decoration: underline;
}
</style>

<script>
    var amc_table;
    $(function() {
        "use strict";
        
        var AmcServerParams = {};
        AmcServerParams['status_filter'] = '[name="status_filter"]';
        AmcServerParams['client_filter'] = '[name="client_filter"]';
        AmcServerParams['amc_type_filter'] = '[name="amc_type_filter"]';
        AmcServerParams['date_from_filter'] = '[name="date_from_filter"]';
        AmcServerParams['date_to_filter'] = '[name="date_to_filter"]';
        AmcServerParams['amount_min_filter'] = '[name="amount_min_filter"]';
        AmcServerParams['amount_max_filter'] = '[name="amount_max_filter"]';
        AmcServerParams['expiry_type_filter'] = '[name="expiry_type_filter"]';
        AmcServerParams['period_filter'] = '[name="period_filter"]';
        AmcServerParams['tags_filter'] = '[name="tags_filter_input"]';
        
        var tableElement = $('table.table-amc');
        if (tableElement.length > 0) {
            amc_table = initDataTable(
                '.table-amc',
                admin_url + 'reminder/amc_table',
                undefined,
                [7], // Column 7 (options) is not sortable - updated for AMC type column
                AmcServerParams
                // No default order - let DataTables use default (column 0)
            );
        }
        
        // Initialize tagsinput when modal is shown
        $('#amc_modal').on('shown.bs.modal', function() {
            if (!$('#tags').data('ui-tagit')) {
                appTagsInput($('#tags'));
            }
        });
        
        // Initialize tagsinput on page load if modal is already in DOM
        if ($('#tags').length && !$('#tags').data('ui-tagit')) {
            appTagsInput($('#tags'));
        }
        
        // Make summary cards clickable
        $('.summary-card-clickable').on('click', function() {
            var filterType = $(this).data('filter-type');
            var filterValue = $(this).data('filter-value');
            
            if (filterType === 'status') {
                // Clear other filters except status
                $('#expiry_type_filter').val('').trigger('change');
                $('#period_filter').val('').trigger('change');
                $('#amc_type_filter').val('').trigger('change');
                $('#date_from_filter').val('');
                $('#date_to_filter').val('');
                $('#client_filter').val('').trigger('change');
                $('#amount_min_filter').val('');
                $('#amount_max_filter').val('');
                $('#tags_filter').val(null).trigger('change');
                $('#tags_filter_input').val('');
                
                // Set status filter
                $('#status_filter').val(filterValue).trigger('change');
                
                // Apply filters and update summary
                applyFiltersAndUpdateSummary();
            } else if (filterType === 'expiry') {
                // Clear other filters except expiry
                $('#status_filter').val('').trigger('change');
                $('#period_filter').val('').trigger('change');
                $('#amc_type_filter').val('').trigger('change');
                $('#client_filter').val('').trigger('change');
                $('#amount_min_filter').val('');
                $('#amount_max_filter').val('');
                $('#tags_filter').val(null).trigger('change');
                $('#tags_filter_input').val('');
                
                // Set expiry filter and auto-populate dates
                var today = new Date();
                var dateFrom = formatDateForInput(today);
                var futureDate = new Date(today);
                futureDate.setDate(today.getDate() + parseInt(filterValue));
                var dateTo = formatDateForInput(futureDate);
                
                // Set expiry type filter and dates
                $('#expiry_type_filter').val(filterValue).trigger('change');
                $('#date_from_filter').val(dateFrom);
                $('#date_to_filter').val(dateTo);
                
                // Apply filters and update summary
                applyFiltersAndUpdateSummary();
            } else if (filterType === 'all') {
                // Show all - clear all filters
                clearAllFilters();
            }
        });
        
        // Handle expiry type filter change (auto-populate dates but don't apply)
        $('#expiry_type_filter').on('change', function() {
            var value = $(this).val();
            if (!value) {
                return;
            }
            
            var today = new Date();
            var dateFrom = '';
            var dateTo = '';
            
            if (value === 'expired') {
                // Expired - end_date < today
                dateTo = formatDateForInput(today);
            } else if (value === 'active') {
                // Active - status = ongoing
                $('#status_filter').val('ongoing').trigger('change');
                return;
            } else {
                // Expiring in X days
                dateFrom = formatDateForInput(today);
                var futureDate = new Date(today);
                futureDate.setDate(today.getDate() + parseInt(value));
                dateTo = formatDateForInput(futureDate);
            }
            
            if (dateFrom) {
                $('#date_from_filter').val(dateFrom);
            }
            if (dateTo) {
                $('#date_to_filter').val(dateTo);
            }
        });
        
        // Handle period filter change (auto-populate dates but don't apply)
        $('#period_filter').on('change', function() {
            var value = $(this).val();
            if (!value || value === 'custom') {
                return; // Custom period - user will set dates manually
            }
            
            var today = new Date();
            var dateFrom = '';
            var dateTo = '';
            
            if (value === 'this_month') {
                dateFrom = formatDateForInput(new Date(today.getFullYear(), today.getMonth(), 1));
                dateTo = formatDateForInput(new Date(today.getFullYear(), today.getMonth() + 1, 0));
            } else if (value === 'last_month') {
                var lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                dateFrom = formatDateForInput(new Date(lastMonth.getFullYear(), lastMonth.getMonth(), 1));
                dateTo = formatDateForInput(new Date(lastMonth.getFullYear(), lastMonth.getMonth() + 1, 0));
            } else if (value === 'next_month') {
                var nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);
                dateFrom = formatDateForInput(new Date(nextMonth.getFullYear(), nextMonth.getMonth(), 1));
                dateTo = formatDateForInput(new Date(nextMonth.getFullYear(), nextMonth.getMonth() + 1, 0));
            }
            
            if (dateFrom) {
                $('#date_from_filter').val(dateFrom);
            }
            if (dateTo) {
                $('#date_to_filter').val(dateTo);
            }
        });
        
        // Update tags filter hidden input
        window.updateTagsFilterInput = function() {
            var selected = $('#tags_filter').selectpicker('val');
            if (selected && selected.length > 0) {
                $('#tags_filter_input').val(selected.join(','));
            } else {
                $('#tags_filter_input').val('');
            }
        };
        
        // Update summary cards via AJAX
        window.updateSummaryCards = function() {
            // Collect all filter values
            var filterData = {
                client_filter: $('#client_filter').val() || '',
                status_filter: $('#status_filter').val() || '',
                amc_type_filter: $('#amc_type_filter').val() || '',
                date_from_filter: $('#date_from_filter').val() || '',
                date_to_filter: $('#date_to_filter').val() || '',
                amount_min_filter: $('#amount_min_filter').val() || '',
                amount_max_filter: $('#amount_max_filter').val() || '',
                expiry_type_filter: $('#expiry_type_filter').val() || '',
                period_filter: $('#period_filter').val() || '',
                tags_filter: $('#tags_filter_input').val() || ''
            };
            
            // Show loading indicator
            $('.summary-card-clickable .panel-body h3').each(function() {
                $(this).html('<i class="fa fa-spinner fa-spin"></i>');
            });
            
            // AJAX call to get updated summary
            $.ajax({
                url: admin_url + 'reminder/get_amc_summary',
                type: 'POST',
                data: filterData,
                dataType: 'json',
                success: function(response) {
                    if (response) {
                        // Update summary cards using data-summary-type attribute
                        $('[data-summary-type="total_value"] .summary-value').text(response.formatted.total_value);
                        $('[data-summary-type="expired_value"] .summary-value').text(response.formatted.expired_value);
                        $('[data-summary-type="expiring_soon_value"] .summary-value').text(response.formatted.expiring_soon_value);
                        $('[data-summary-type="total_count"] .summary-value').text(response.counts.total);
                        $('[data-summary-type="ongoing_count"] .summary-value').text(response.counts.ongoing);
                        $('[data-summary-type="expired_count"] .summary-value').text(response.counts.expired);
                        $('[data-summary-type="on_hold_count"] .summary-value').text(response.counts.on_hold);
                        $('[data-summary-type="closed_count"] .summary-value').text(response.counts.closed);
                        $('[data-summary-type="expiring_7_count"] .summary-value').text(response.expiring.in_7_days);
                        $('[data-summary-type="expiring_30_count"] .summary-value').text(response.expiring.in_30_days);
                        $('[data-summary-type="expiring_90_count"] .summary-value').text(response.expiring.in_90_days);
                    }
                },
                error: function() {
                    alert_float('danger', <?php echo json_encode(_l('error_loading_summary')); ?>);
                }
            });
        };
        
        // Apply filters and update summary
        window.applyFiltersAndUpdateSummary = function() {
            // Update tags filter input first
            updateTagsFilterInput();
            
            // Reload table
            if (typeof amc_table !== 'undefined' && amc_table) {
                amc_table.ajax.reload(null, false);
            }
            
            // Update summary cards
            updateSummaryCards();
        };
        
        // Clear all filters
        window.clearAllFilters = function() {
            $('#status_filter').val('').trigger('change');
            $('#client_filter').val('').trigger('change');
            $('#amc_type_filter').val('').trigger('change');
            $('#expiry_type_filter').val('').trigger('change');
            $('#period_filter').val('').trigger('change');
            $('#date_from_filter').val('');
            $('#date_to_filter').val('');
            $('#amount_min_filter').val('');
            $('#amount_max_filter').val('');
            $('#tags_filter').val(null).trigger('change');
            $('#tags_filter_input').val('');
            
            // Reload table
            if (typeof amc_table !== 'undefined' && amc_table) {
                amc_table.ajax.reload(null, false);
            }
            
            // Update summary cards
            updateSummaryCards();
        };
        
        // Helper function to format date for input
        function formatDateForInput(date) {
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            return year + '-' + month + '-' + day;
        }
        
        // Make functions globally accessible
        window.add_amc = function() {
            $('#amc_modal_title').text(<?php echo json_encode(_l('add_amc')); ?>);
            $('#amc_form')[0].reset();
            $('#amc_id').val('');
            // Clear tags safely
            if ($('#tags').data('ui-tagit')) {
                $('#tags').tagit('removeAll');
            } else {
                $('#tags').val('');
            }
            $('#amc_form').attr('action', <?php echo json_encode(admin_url('reminder/add_amc')); ?>);
            $('#amc_modal').modal('show');
        };
        
        window.edit_amc = function(id) {
            $.get(admin_url + 'reminder/get_amc/' + id, function(response) {
                var amc = JSON.parse(response);
                $('#amc_id').val(amc.id);
                $('#client_id').val(amc.client_id).trigger('change');
                $('#amc_location').val(amc.amc_location);
                $('#amc_type_id').val(amc.amc_type_id).trigger('change');
                $('#amount').val(amc.amount);
                $('#start_date').val(amc.start_date);
                $('#end_date').val(amc.end_date);
                $('#notes').val(amc.notes);
                // Set tags safely
                if ($('#tags').data('ui-tagit')) {
                    $('#tags').tagit('removeAll');
                    if (amc.tags) {
                        var tagsArray = amc.tags.split(',');
                        tagsArray.forEach(function(tag) {
                            tag = tag.trim();
                            if (tag) {
                                $('#tags').tagit('createTag', tag);
                            }
                        });
                    }
                } else {
                    $('#tags').val(amc.tags || '');
                }
                $('#amc_modal_title').text(<?php echo json_encode(_l('edit_amc')); ?>);
                $('#amc_form').attr('action', admin_url + 'reminder/update_amc/' + id);
                $('#amc_modal').modal('show');
            });
        };
        
        window.renew_amc = function(id) {
            $.get(admin_url + 'reminder/get_amc/' + id, function(response) {
                var amc = JSON.parse(response);
                $('#renew_amc_id').val(id);
                $('#renew_amount').val(amc.amount);
                $('#renew_start_date').val(amc.end_date);
                $('#renew_end_date').val('');
                $('#renew_notes').val('');
                $('#renew_amc_form').attr('action', admin_url + 'reminder/renew_amc/' + id);
                $('#renew_amc_modal').modal('show');
            });
        };
        
        window.delete_amc = function(id) {
            if (confirm(<?php echo json_encode(_l('confirm_action_prompt')); ?>)) {
                $.post(admin_url + 'reminder/delete_amc/' + id, function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        alert_float('success', result.message);
                        if (typeof amc_table !== 'undefined' && amc_table) {
                            amc_table.ajax.reload(null, false);
                        } else {
                            location.reload();
                        }
                    } else {
                        alert_float('danger', result.message);
                    }
                });
            }
        };
        
        // Form submission handlers
        $('#amc_form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');
            
            // Get tags value from tagit if initialized
            var tagsValue = '';
            if ($('#tags').data('ui-tagit')) {
                var tagsArray = $('#tags').tagit('assignedTags');
                tagsValue = tagsArray.join(',');
            } else {
                tagsValue = $('#tags').val();
            }
            
            // Add tags to form data
            var formData = form.serialize();
            if (tagsValue) {
                formData += '&tags=' + encodeURIComponent(tagsValue);
            }
            
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        alert_float('success', result.message);
                        $('#amc_modal').modal('hide');
                        if (typeof amc_table !== 'undefined' && amc_table) {
                            amc_table.ajax.reload(null, false);
                        } else {
                            location.reload();
                        }
                    } else {
                        alert_float('danger', result.message);
                    }
                }
            });
        });
        
        $('#renew_amc_form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');
            $.ajax({
                url: url,
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        alert_float('success', result.message);
                        $('#renew_amc_modal').modal('hide');
                        if (typeof amc_table !== 'undefined' && amc_table) {
                            amc_table.ajax.reload(null, false);
                        } else {
                            location.reload();
                        }
                    } else {
                        alert_float('danger', result.message);
                    }
                }
            });
        });
    });
</script>

