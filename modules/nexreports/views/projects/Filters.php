<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <?php echo _l('advanced_filters'); ?>
                        </h4>
                        <hr class="hr-panel-separator">
                        
                        <?php echo form_open('', ['id' => 'nexreports-filters-form']); ?>
                        
                        <div class="row">
                            <!-- Project Name Filter -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="project_name"><?php echo _l('project_name'); ?></label>
                                    <input type="text" 
                                           id="project_name" 
                                           name="project_name" 
                                           class="form-control" 
                                           value="<?php echo isset($current_filters['project_name']) ? htmlspecialchars($current_filters['project_name']) : ''; ?>"
                                           placeholder="<?php echo _l('search'); ?>...">
                                </div>
                            </div>

                            <!-- Customer Filter -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="customer"><?php echo _l('client'); ?></label>
                                    <select name="customer" id="customer" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <option value=""></option>
                                        <?php foreach ($customers as $customer) { ?>
                                            <option value="<?php echo $customer['userid']; ?>" 
                                                <?php echo (isset($current_filters['customer']) && $current_filters['customer'] == $customer['userid']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($customer['company']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Date Type Filter -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="date_type"><?php echo _l('date_type'); ?></label>
                                    <select name="date_type" id="date_type" class="selectpicker" data-width="100%">
                                        <option value=""><?php echo _l('select'); ?></option>
                                        <?php foreach ($date_types as $key => $label) { ?>
                                            <option value="<?php echo $key; ?>" 
                                                <?php echo (isset($current_filters['date_type']) && $current_filters['date_type'] == $key) ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- From Date -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="from_date"><?php echo _l('from_date'); ?></label>
                                    <input type="text" 
                                           id="from_date" 
                                           name="from_date" 
                                           class="form-control datepicker" 
                                           value="<?php echo isset($current_filters['from_date']) ? htmlspecialchars($current_filters['from_date']) : ''; ?>"
                                           autocomplete="off">
                                </div>
                            </div>

                            <!-- To Date -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="to_date"><?php echo _l('to_date'); ?></label>
                                    <input type="text" 
                                           id="to_date" 
                                           name="to_date" 
                                           class="form-control datepicker" 
                                           value="<?php echo isset($current_filters['to_date']) ? htmlspecialchars($current_filters['to_date']) : ''; ?>"
                                           autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Members Filter -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="members[]"><?php echo _l('project_members'); ?></label>
                                    <select name="members[]" 
                                            id="members" 
                                            class="selectpicker" 
                                            data-width="100%" 
                                            multiple 
                                            data-actions-box="true"
                                            data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <?php foreach ($staff_members as $member) { 
                                            $selected = '';
                                            if (isset($current_filters['members']) && is_array($current_filters['members'])) {
                                                $selected = in_array($member['staffid'], $current_filters['members']) ? 'selected' : '';
                                            }
                                        ?>
                                            <option value="<?php echo $member['staffid']; ?>" <?php echo $selected; ?>>
                                                <?php echo htmlspecialchars($member['firstname'] . ' ' . $member['lastname']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status[]"><?php echo _l('project_status'); ?></label>
                                    <select name="status[]" 
                                            id="status" 
                                            class="selectpicker" 
                                            data-width="100%" 
                                            multiple 
                                            data-actions-box="true"
                                            data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <?php foreach ($project_statuses as $status_id => $status_name) { 
                                            $selected = '';
                                            if (isset($current_filters['status']) && is_array($current_filters['status'])) {
                                                $selected = in_array($status_id, $current_filters['status']) ? 'selected' : '';
                                            }
                                        ?>
                                            <option value="<?php echo $status_id; ?>" <?php echo $selected; ?>>
                                                <?php echo $status_name; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Tags Filter -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="tags[]"><?php echo _l('tags'); ?></label>
                                    <select name="tags[]" 
                                            id="tags" 
                                            class="selectpicker" 
                                            data-width="100%" 
                                            multiple 
                                            data-actions-box="true"
                                            data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <?php foreach ($tags as $tag) { 
                                            $selected = '';
                                            if (isset($current_filters['tags']) && is_array($current_filters['tags'])) {
                                                $selected = in_array($tag['id'], $current_filters['tags']) ? 'selected' : '';
                                            }
                                        ?>
                                            <option value="<?php echo $tag['id']; ?>" <?php echo $selected; ?>>
                                                <?php echo htmlspecialchars($tag['name']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr class="hr-panel-separator">

                        <!-- Action Buttons -->
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-filter"></i> <?php echo _l('apply_filters'); ?>
                                </button>
                                <button type="button" class="btn btn-default" onclick="resetFiltersForm()">
                                    <i class="fa fa-refresh"></i> <?php echo _l('reset'); ?>
                                </button>
                                <a href="<?php echo admin_url('nexreports'); ?>" class="btn btn-default">
                                    <i class="fa fa-arrow-left"></i> <?php echo _l('back'); ?>
                                </a>
                            </div>
                        </div>

                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    // Initialize datepicker
    $('.datepicker').datepicker({
        format: app.options.date_format || 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });

    // Initialize selectpicker
    $('.selectpicker').selectpicker('refresh');

    // Form submission
    $('#nexreports-filters-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: admin_url + 'nexreports/apply_filters',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert_float('success', response.message);
                    setTimeout(function() {
                        window.location.href = admin_url + 'nexreports';
                    }, 500);
                }
            },
            error: function() {
                alert_float('danger', '<?php echo _l('something_went_wrong'); ?>');
            }
        });
    });
});

function resetFiltersForm() {
    $.ajax({
        url: admin_url + 'nexreports/reset_filters',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert_float('success', response.message);
                setTimeout(function() {
                    window.location.href = admin_url + 'nexreports';
                }, 500);
            }
        },
        error: function() {
            alert_float('danger', '<?php echo _l('something_went_wrong'); ?>');
        }
    });
}
</script>

