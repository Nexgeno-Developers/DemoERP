<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<!-- Link CSS file -->
<link rel="stylesheet" href="<?php echo base_url('modules/nexreports/assets/css/attendance.css'); ?>">

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">            
                <!-- FILTERS PANEL -->
                <div class="panel_s">
                    <div class="panel-body">
                        <!-- HEADER -->
                        <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
                            <h4 class="no-margin tw-font-semibold tw-text-lg">
                                <?php echo _l('HRMS Overview'); ?>
                            </h4>
                        </div>
                        <hr class="hr-panel-separator">

                        <!-- FILTERS FORM -->
                        <?php echo form_open('', ['id' => 'attendance-filters-form']); ?>

<div class="row">

  <!-- Period Filter -->
  <div class="col-md-3">
    <div class="form-group">
      <label for="period">Period</label>
      <select name="period" id="period" class="form-control selectpicker" data-width="100%">
        <option value="this_month" selected>This Month</option>
        <option value="last_month">Last Month</option>
        <option value="custom_period">Period</option>
      </select>

      <!-- REQUIRED hidden inputs -->
      <input type="hidden" name="from_date" id="from_date">
      <input type="hidden" name="to_date" id="to_date">
    </div>
  </div>

  <!-- Staff Status -->
  <div class="col-md-3">
    <div class="form-group">
      <label for="active_status"><?php echo _l('Staff Status'); ?></label>
      <select name="active_status" id="active_status" class="selectpicker" data-width="100%"
        <?php echo !$can_manage_filters ? 'disabled' : ''; ?>>
        <option value="all"><?php echo _l('All Staff'); ?></option>
        <option value="active" selected><?php echo _l('Active Staff'); ?></option>
        <option value="inactive"><?php echo _l('Inactive Staff'); ?></option>
      </select>
    </div>
  </div>

  <!-- Staff -->
  <div class="col-md-3">
    <div class="form-group">
      <label for="staff_member">Staff</label>
      <select name="staff_member[]" id="staff_member" class="selectpicker"
        data-width="100%" data-live-search="true" multiple data-actions-box="true"
        <?php echo !$can_manage_filters ? 'disabled' : ''; ?>>
        <?php foreach ($staff_members as $member) { ?>
          <option value="<?php echo $member['staffid']; ?>" data-active="<?php echo $member['active']; ?>">
            <?php echo htmlspecialchars($member['firstname'] . ' ' . $member['lastname']); ?>
            <?php if ($member['active'] == 0): ?>
              <span class="text-muted">(Inactive)</span>
            <?php endif; ?>
          </option>
        <?php } ?>
      </select>
    </div>
  </div>

  <!-- Apply / Reset -->
  <div class="col-md-3">
    <div class="form-group">
      <label>&nbsp;</label>
      <div class="tw-flex tw-gap-2">
        <button type="button" id="apply-filters-btn" class="btn btn-primary tw-flex-1">
          <?php echo _l('apply'); ?>
        </button>
        <button type="button" id="reset-filters-btn" class="btn btn-default tw-flex-1">
          <i class="fa fa-refresh"></i> <?php echo _l('reset'); ?>
        </button>
      </div>
    </div>
  </div>

</div>

<!-- Custom Month / Year (SECOND ROW, HIDDEN) -->
<div class="row" id="custom-period-container" style="display:none; margin-top:10px;">
  <div class="col-md-3">
    <div class="form-group">
      <label>Month</label>
      <select id="custom_month" class="selectpicker" data-width="100%">
        <option value="1">January</option>
        <option value="2">February</option>
        <option value="3">March</option>
        <option value="4">April</option>
        <option value="5">May</option>
        <option value="6">June</option>
        <option value="7">July</option>
        <option value="8">August</option>
        <option value="9">September</option>
        <option value="10">October</option>
        <option value="11">November</option>
        <option value="12">December</option>
      </select>
    </div>
  </div>

  <div class="col-md-3">
    <div class="form-group">
      <label>Year</label>
      <select id="custom_year" class="selectpicker" data-width="100%">
        <?php for ($y = date('Y'); $y >= date('Y') - 6; $y--) { ?>
          <option value="<?= $y ?>"><?= $y ?></option>
        <?php } ?>
      </select>
    </div>
  </div>
</div>

<?php echo form_close(); ?>


                    </div>
                </div>
                <!-- END FILTERS PANEL -->

                <!-- TABLE PANEL -->
                <div class="panel_s" style="margin-top: 25px;">
                    <div class="panel-body">
                        <div class="panel-table-full">
                            <?php 
                            // Build table columns for attendance directly here
                            $table_data = [
                                'Staff Name',
                                'Timesheet Hrs',
                                'Checkin-out Hrs', 
                                'OT Hrs',
                                'Paid Leave Hrs',
                                'Tickets',
                                'Payable Hrs',
                                'Actual Wrk Hrs',
                                'Salary',
                                'Reflectable Salary'
                            ];

                            // Render the DataTable directly
                            render_datatable(
                                $table_data,
                                'attendance-reports',
                                ['number-index-1'],
                                [
                                    'data-last-order-identifier' => 'nexreports_attendance',
                                    'data-default-order'         => get_table_last_order('nexreports_attendance'),
                                    'id' => 'attendance-reports-table',
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

<!-- Modals for Leave and OT Details -->
<div id="leave-details-modal-container"></div>
<div id="ot-details-modal-container"></div>
<div id="tickets-details-modal-container"></div>

<!-- Link JS file -->
<script>
  // Pass comprehensive role and staff data to JavaScript
  console.log("🔍 Role Information:", {
    role_type: <?php echo json_encode($role_type ?? 'unknown'); ?>,
    can_manage_filters: <?php echo json_encode($can_manage_filters ?? false); ?>,
    can_view_all: <?php echo json_encode($can_view_all ?? false); ?>,
    current_staff_id: <?php echo json_encode($current_staff_id ?? 0); ?>
  });
  
  // Staff data for different status types
  var staffData = {
    active: <?php echo json_encode($staff_active ?? []); ?>,
    inactive: <?php echo json_encode($staff_inactive ?? []); ?>,
    all: <?php echo json_encode($staff_all ?? []); ?>
  };
  
  // Legacy variables for backward compatibility
  var allStaffMembers = <?php echo json_encode($all_staff_members ?? []); ?>;
  var canManageFilters = <?php echo json_encode($can_manage_filters ?? false); ?>;
  var roleType = <?php echo json_encode($role_type ?? 'unknown'); ?>;
  
  console.log("🔍 Staff Data:", staffData);
  console.log("🔍 Legacy allStaffMembers:", allStaffMembers);
</script>
<script src="<?php echo base_url('modules/nexreports/assets/js/attendance.js'); ?>"></script>