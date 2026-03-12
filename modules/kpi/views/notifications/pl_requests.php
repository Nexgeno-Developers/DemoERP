<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (!empty($pl_requests)) { ?>
    <?php foreach ($pl_requests as $request) { ?>
    <div class="kpi-request-item tw-flex tw-items-center tw-cursor-pointer" 
         data-request-id="<?= e($request->id); ?>" 
         data-request-type="pl"
         onclick="openPLDetails(<?= e($request->id); ?>)">
        
        <!-- Staff Avatar -->
        <div class="tw-flex-shrink-0">
            <?php 
            // Use Perfex CRM's built-in staff profile image function
            echo staff_profile_image($request->staff_id, ['kpi-request-avatar'], 'small');
            ?>
        </div>
        
        <!-- Request Content -->
        <div class="kpi-request-content tw-ml-3">
            <div class="kpi-request-title">
                <?= e($request->firstname . ' ' . $request->lastname); ?> has requested leave
            </div>
            <div class="kpi-request-meta">
                <span class="kpi-request-badge pl">
                    <?php 
                    $days = $request->number_of_leaving_day ?? 1;
                    echo e($days . ' ' . ($days == 1 ? 'day' : 'days'));
                    ?>
                </span>
                <span class="tw-text-neutral-500">•</span>
                <span><?= _d($request->start_time); ?></span>
                <?php if ($request->start_time !== $request->end_time) { ?>
                <span class="tw-text-neutral-500">to</span>
                <span><?= _d($request->end_time); ?></span>
                <?php } ?>
            </div>
            <?php if (!empty($request->subject)) { ?>
            <div class="tw-text-xs tw-text-neutral-600 tw-mt-1 tw-truncate">
                <?= e($request->subject); ?>
            </div>
            <?php } ?>
        </div>
        
        <!-- Arrow Icon -->
        <div class="tw-flex-shrink-0 tw-ml-auto">
            <i class="fa fa-chevron-right tw-text-neutral-400"></i>
        </div>
    </div>
    <?php } ?>
<?php } else { ?>
    <div class="tw-p-4 tw-text-center tw-text-neutral-500">
        <i class="fa fa-calendar-o tw-text-2xl tw-mb-2"></i>
        <p class="tw-mb-0"><?= _l('No Pending PL Requests'); ?></p>
    </div>
<?php } ?>