<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<link rel="stylesheet" href="<?php echo base_url('modules/nexreports/assets/css/nexreports.css'); ?>">

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                
                <!-- FILTERS PANEL -->
                <div class="panel_s">
                    <div class="panel-body">
                        <!-- HEADER with Icon Button -->
                        <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
                            <h4 class="no-margin tw-font-semibold tw-text-lg">
                                Projects Overview
                            </h4>
                            
                            <button type="button" 
                                    class="btn btn-default btn-with-tooltip" 
                                    id="filter-templates-btn"
                                    data-toggle="tooltip" 
                                    data-placement="bottom" 
                                    title="List of Filter Templates">
                                <i class="fa fa-list"></i>
                            </button>
                        </div>
                        <hr class="hr-panel-separator">

                        <!-- FILTERS FORM -->
                        <?php echo form_open('', ['id' => 'nexreports-filters-form']); ?>

                        <div class="row">
                            <!-- Project Name Filter -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="project_name"><?php echo _l('project_name'); ?></label>
                                    <select name="project_name" id="project_name" class="selectpicker" data-width="100%" data-live-search="true" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <option value=""><?php echo _l('dropdown_non_selected_tex'); ?></option>
                                        <?php foreach ($all_projects as $project) { ?>
                                            <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['name']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Customer Filter -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="customer"><?php echo _l('client'); ?></label>
                                    <select name="customer" id="customer" class="selectpicker" data-width="100%" data-live-search="true" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <option value=""><?php echo _l('dropdown_non_selected_tex'); ?></option>
                                        <?php foreach ($customers as $customer) { ?>
                                            <option value="<?php echo $customer['userid']; ?>"><?php echo htmlspecialchars($customer['company']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Members Filter -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="members"><?php echo _l('project_members'); ?></label>
                                    <select name="members[]" id="members" class="selectpicker" data-width="100%" data-live-search="true" multiple data-actions-box="true" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <?php foreach ($staff_members as $member) { ?>
                                            <option value="<?php echo $member['staffid']; ?>">
                                                <?php echo htmlspecialchars($member['firstname'] . ' ' . $member['lastname']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status"><?php echo _l('project_status'); ?></label>
                                    <select name="status[]" id="status" class="selectpicker" data-width="100%" data-live-search="true" multiple data-actions-box="true" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <?php foreach ($project_statuses as $status_id => $status_name) { ?>
                                            <option value="<?php echo $status_id; ?>"><?php echo $status_name; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Tags Filter -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tags"><?php echo _l('tags'); ?></label>
                                    <select name="tags[]" id="tags" class="selectpicker" data-width="100%" data-live-search="true" multiple data-actions-box="true" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <?php if (is_array($tags) && count($tags) > 0) { ?>
                                            <?php foreach ($tags as $tag) { ?>
                                                <option value="<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></option>
                                            <?php } ?>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Date Type Filter -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_type">Date type</label>
                                    <select name="date_type" id="date_type" class="selectpicker" data-width="100%">
                                        <option value=""><?php echo _l('dropdown_non_selected_tex'); ?></option>
                                        <?php foreach ($date_types as $key => $label) { ?>
                                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Period Filter -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="period">Period</label>
                                    <select name="period" id="period" class="selectpicker" data-width="100%">
                                        <option value="this_month" selected>This Month</option>
                                        <option value="last_month">Last Month</option>
                                        <option value="">Period</option>
                                    </select>
                                </div>
                            </div>

                            <!-- DYNAMIC FILTERS WILL BE LOADED HERE -->
                            <div id="filter-container" class="row mb-3"></div>
                        </div>

                        <!-- Filter Buttons with Checkboxes in Same Row -->
                        <div class="row">
                            <div class="col-md-12">
                                <div style="display: flex; align-items: center; gap: 25px; margin-top: 20px;">
                                    <!-- Apply Button -->
                                    <button type="button" id="apply-filters-btn" class="btn btn-primary">
                                        <?php echo _l('apply'); ?>
                                    </button>
                                    
                                    <!-- Reset Button -->
                                    <button type="button" id="reset-filters-btn" class="btn btn-default">
                                        <i class="fa fa-refresh"></i> <?php echo _l('reset'); ?>
                                    </button>
                                    
                                    <!-- Set as Default -->
                                    <div class="checkbox-wrapper">
                                        <input type="checkbox" 
                                               id="set-as-default" 
                                               class="custom-checkbox-square" 
                                               data-toggle="tooltip" 
                                               data-placement="top" 
                                               title="Set as Default Template when load project Filter">
                                        <label for="set-as-default" class="checkbox-label">
                                            Set as Default
                                        </label>
                                    </div>
                                    
                                    <!-- Save as Filter Template -->
                                    <div class="checkbox-wrapper">
                                        <input type="checkbox" 
                                               id="save-filter-template" 
                                               name="filter_action" 
                                               class="custom-checkbox-round" 
                                               data-toggle="tooltip" 
                                               data-placement="top" 
                                               title="Select Checked to Save Filter Template">
                                        <input type="text" 
                                               id="filter-template-name" 
                                               class="form-control" 
                                               placeholder="Save as Filter Template (Add Filter Name here)" 
                                               style="width: 350px; height: 36px; margin-left: 8px;"
                                               data-toggle="tooltip" 
                                               data-placement="top" 
                                               title="Save as Filter Template (Add Filter Name here)">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php echo form_close(); ?>

                    </div>
                </div>
                <!-- END FILTERS PANEL -->

                <!-- TABLE PANEL (SEPARATE PANEL) -->
                <div class="panel_s" style="margin-top: 25px;">
                    <div class="panel-body">
                        <div class="panel-table-full">
                            <?php 
                            // Ensure custom_fields is available for table
                            if (!isset($custom_fields)) {
                                $custom_fields = [];
                            }
                            
                            // Find "Project Lead By" custom field and separate it
                            $project_lead_by_field = null;
                            $other_custom_fields = [];
                            
                            if (is_array($custom_fields) && count($custom_fields) > 0) {
                                foreach ($custom_fields as $field) {
                                    if (strtolower($field['name']) === 'project lead by') {
                                        $project_lead_by_field = $field;
                                    } else {
                                        $other_custom_fields[] = $field;
                                    }
                                }
                            }
                            
                            // Build table columns directly here instead of separate file
                            $table_data = [
                                '#',
                            ];
                            
                            // Add Project Lead By before Project Name if it exists
                            if ($project_lead_by_field) {
                                $table_data[] = htmlspecialchars($project_lead_by_field['name']);
                            }
                            
                            // Continue with other columns
                            $table_data = array_merge($table_data, [
                                'Project Name',
                                [
                                    'name'     => 'Customer',
                                    'th_attrs' => ['class' => isset($client) ? 'not_visible' : ''],
                                ],
                                'Tags',
                                'Start Date',
                                'Deadline',
                                'Members',
                                'Estimated Hours',
                                'Logged Hours',
                            ]);

                            // Add remaining custom field columns dynamically (excluding Project Lead By)
                            if (count($other_custom_fields) > 0) {
                                foreach ($other_custom_fields as $field) {
                                    $table_data[] = htmlspecialchars($field['name']);
                                }
                            }

                            // Add remaining static columns
                            $table_data[] = 'Progress';
                            $table_data[] = 'Status';

                            // Render the DataTable directly
                            render_datatable(
                                $table_data,
                                'nexreports',
                                ['number-index-1'],
                                [
                                    'data-last-order-identifier' => 'nexreports_projects',
                                    'data-default-order'         => get_table_last_order('nexreports_projects'),
                                    'id' => 'nexreports-table',
                                ]
                            );
                            ?>
                        </div>
                    </div>
                </div>
                <!-- END TABLE PANEL -->

            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script src="<?php echo base_url('modules/nexreports/assets/js/projects.js'); ?>"></script>