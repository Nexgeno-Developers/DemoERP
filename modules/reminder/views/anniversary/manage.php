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
                                <h4 class="no-margin"><?php echo _l('anniversary'); ?></h4>
                                <hr class="hr-panel-heading">
                            </div>
                        </div>

                        <!-- Period Filter Panel -->
                        <div class="row mbot15">
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="_filters _hidden_inputs">
                                            <div class="row">
                                                <div class="col-md-3 border-right">
                                                    <label><?php echo _l('period'); ?></label>
                                                    <select name="period_filter" id="period_filter" class="form-control selectpicker" data-width="100%">
                                                        <option value=""><?php echo _l('all'); ?></option>
                                                        <option value="this_week"><?php echo _l('this_week'); ?></option>
                                                        <option value="next_week"><?php echo _l('next_week'); ?></option>
                                                        <option value="this_month"><?php echo _l('this_month'); ?></option>
                                                        <option value="last_month"><?php echo _l('last_month'); ?></option>
                                                        <option value="next_month"><?php echo _l('next_month'); ?></option>
                                                        <option value="custom"><?php echo _l('custom'); ?></option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3 border-right">
                                                    <label><?php echo _l('date_from'); ?></label>
                                                    <input type="date" name="date_from_filter" id="date_from_filter" class="form-control" value="<?php echo isset($active_filters['date_from']) ? $active_filters['date_from'] : ''; ?>">
                                                </div>
                                                <div class="col-md-3 border-right">
                                                    <label><?php echo _l('date_to'); ?></label>
                                                    <input type="date" name="date_to_filter" id="date_to_filter" class="form-control" value="<?php echo isset($active_filters['date_to']) ? $active_filters['date_to'] : ''; ?>">
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
            </div>
        </div>

        <!-- Anniversary Table Panel -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="panel-table-full">
                            <?php
                            $table_data = [
                                _l('contact_name'),
                                _l('position'),
                                _l('email'),
                                _l('phonenumber'),
                                _l('anniversary_date'),
                            ];
                            render_datatable($table_data, 'anniversary', ['scroll-responsive']);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
(function($) {
    "use strict";
    
    var anniversary_table;
    var AnniversaryServerParams = {};
    AnniversaryServerParams['period_filter'] = '[name="period_filter"]';
    AnniversaryServerParams['date_from_filter'] = '[name="date_from_filter"]';
    AnniversaryServerParams['date_to_filter'] = '[name="date_to_filter"]';
    
    $(document).ready(function() {
        anniversary_table = initDataTable(
            '.table-anniversary',
            admin_url + 'reminder/anniversary_table',
            undefined,
            [],
            AnniversaryServerParams
        );
        
        // Handle period filter change
        $('#period_filter').on('change', function() {
            var value = $(this).val();
            if (!value || value === 'custom') {
                return;
            }
            
            var today = new Date();
            var dateFrom = '';
            var dateTo = '';
            
            if (value === 'this_week') {
                // Get Monday of current week
                var day = today.getDay();
                var diff = today.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
                var monday = new Date(today.getFullYear(), today.getMonth(), diff);
                dateFrom = formatDateForInput(monday);
                // Get Sunday of current week
                var sunday = new Date(monday);
                sunday.setDate(monday.getDate() + 6);
                dateTo = formatDateForInput(sunday);
            } else if (value === 'next_week') {
                // Get Monday of next week
                var day = today.getDay();
                var diff = today.getDate() - day + (day === 0 ? -6 : 1) + 7; // Next week
                var nextMonday = new Date(today.getFullYear(), today.getMonth(), diff);
                dateFrom = formatDateForInput(nextMonday);
                // Get Sunday of next week
                var nextSunday = new Date(nextMonday);
                nextSunday.setDate(nextMonday.getDate() + 6);
                dateTo = formatDateForInput(nextSunday);
            } else if (value === 'this_month') {
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
            
            if (dateFrom) $('#date_from_filter').val(dateFrom);
            if (dateTo) $('#date_to_filter').val(dateTo);
        });
    });
    
    window.applyFilters = function() {
        if (typeof anniversary_table !== 'undefined' && anniversary_table) {
            anniversary_table.ajax.reload(null, false);
        }
    };
    
    window.clearFilters = function() {
        $('#period_filter').val('').trigger('change');
        $('#date_from_filter').val('');
        $('#date_to_filter').val('');
        if (typeof anniversary_table !== 'undefined' && anniversary_table) {
            anniversary_table.ajax.reload(null, false);
        }
    };
    
    function formatDateForInput(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }
})(jQuery);
</script>
