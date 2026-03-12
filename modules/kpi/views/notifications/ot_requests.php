<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (!empty($ot_requests)) { ?>
    <?php foreach ($ot_requests as $request) { ?>
    <div class="kpi-request-item tw-flex tw-items-center tw-cursor-pointer" 
         data-request-id="<?= e($request->id); ?>" 
         data-request-type="ot"
         onclick="openOTDetails(<?= e($request->id); ?>)">
        
        <!-- Staff Avatar -->
        <div class="tw-flex-shrink-0">
            <?php 
            // Use Perfex CRM's built-in staff profile image function
            echo staff_profile_image($request->creator, ['kpi-request-avatar'], 'small');
            ?>
        </div>
        
        <!-- Request Content -->
        <div class="kpi-request-content tw-ml-3">
            <div class="kpi-request-title">
                <?= e($request->firstname . ' ' . $request->lastname); ?> has requested overtime
            </div>
            <div class="kpi-request-meta">
                <span class="kpi-request-badge ot">
                    <?= e($request->timekeeping_value); ?> hours
                </span>
                <span class="tw-text-neutral-500">•</span>
                <span><?= _d($request->additional_day); ?></span>
                <?php if (!empty($request->time_in) && !empty($request->time_out)) { ?>
                <span class="tw-text-neutral-500">•</span>
                <span><?= e($request->time_in . ' - ' . $request->time_out); ?></span>
                <?php } ?>
            </div>
        </div>
        
        <!-- Arrow Icon -->
        <div class="tw-flex-shrink-0 tw-ml-auto">
            <i class="fa fa-chevron-right tw-text-neutral-400"></i>
        </div>
    </div>
    <?php } ?>
<?php } else { ?>
    <div class="tw-p-4 tw-text-center tw-text-neutral-500">
        <i class="fa fa-clock-o tw-text-2xl tw-mb-2"></i>
        <p class="tw-mb-0"><?= _l('No Pending OT Requests'); ?></p>
    </div>
<?php } ?>