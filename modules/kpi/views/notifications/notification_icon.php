<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<li class="icon dropdown tw-relative tw-block kpi-notifications-wrapper header-kpi-notifications rtl:tw-ml-3"
    data-toggle="tooltip"
    title="Pending OT, PL and Tickets Requests"
    data-placement="bottom">
    
    <a href="#" class="dropdown-toggle kpi-notifications-icon !tw-px-0 tw-group" 
       data-toggle="dropdown" aria-expanded="false">
        <span class="sm:tw-inline-flex sm:tw-items-center sm:tw-justify-center sm:tw-h-8 sm:tw-w-9 sm:-tw-mt-1.5">
            <i class="fa-solid fa-clipboard fa-lg tw-shrink-0 tw-text-neutral-400 group-hover:tw-text-neutral-800"></i>
            <?php if ($total_pending_requests > 0) { ?>
            <span class="tw-leading-none tw-px-1 tw-py-0.5 tw-text-xs bg-danger tw-z-10 tw-absolute tw-rounded-full -tw-right-0.5 -tw-top-2 sm:tw-top-2 tw-min-w-[18px] tw-min-h-[18px] tw-inline-flex tw-items-center tw-justify-center kpi-notifications-badge tw-mt-px">
                <?= e($total_pending_requests); ?>
            </span>
            <?php } ?>
        </span>
    </a>
    
    <ul class="dropdown-menu kpi-notifications animated fadeIn width400">
        <!-- Tab Navigation -->
        <div class="tw-px-3 tw-py-2 tw-border-b">
            <ul class="nav nav-tabs nav-tabs-segmented tw-mb-0" role="tablist">
                <li role="presentation" class="active">
                    <a href="#kpi-ot-tab" aria-controls="kpi-ot-tab" role="tab" data-toggle="tab" class="tw-text-sm">
                        OT 
                        <?php if ($pending_ot_count > 0) { ?>
                        <span class="badge badge-danger"><?= $pending_ot_count; ?></span>
                        <?php } ?>
                    </a>
                </li>
                <li role="presentation">
                    <a href="#kpi-pl-tab" aria-controls="kpi-pl-tab" role="tab" data-toggle="tab" class="tw-text-sm">
                        PL 
                        <?php if ($pending_pl_count > 0) { ?>
                        <span class="badge badge-danger"><?= $pending_pl_count; ?></span>
                        <?php } ?>
                    </a>
                </li>
                <li role="presentation">
                    <a href="#kpi-tickets-tab" aria-controls="kpi-tickets-tab" role="tab" data-toggle="tab" class="tw-text-sm">
                        Tickets
                        <?php if ($open_tickets_count > 0) { ?>
                        <span class="badge badge-danger"><?= $open_tickets_count; ?></span>
                        <?php } ?>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Tab Content -->
        <div class="tab-content">
            <!-- OT Tab -->
            <div role="tabpanel" class="tab-pane active" id="kpi-ot-tab">
                <?php if (!empty($pending_ot_requests)) { ?>
                    <?php foreach ($pending_ot_requests as $request) { ?>
                    <div class="kpi-request-item tw-flex tw-items-center tw-cursor-pointer"
                         data-request-id="<?= e($request->id); ?>"
                         data-request-type="ot"
                         onclick="openOTDetails(<?= e($request->id); ?>)">
                        <div class="tw-flex-shrink-0">
                            <?php echo staff_profile_image($request->creator, ['kpi-request-avatar'], 'small'); ?>
                        </div>
                        <div class="kpi-request-content tw-ml-3">
                            <div class="kpi-request-title">
                                <?= e($request->firstname . ' ' . $request->lastname); ?> has requested overtime
                            </div>
                            <div class="kpi-request-meta">
                                <span class="kpi-request-badge ot"><?= e($request->timekeeping_value); ?> hours</span>
                                <span class="tw-text-neutral-500">•</span>
                                <span><?= _d($request->additional_day); ?></span>
                                <?php if (!empty($request->time_in) && !empty($request->time_out)) { ?>
                                <span class="tw-text-neutral-500">•</span>
                                <span><?= e($request->time_in . ' - ' . $request->time_out); ?></span>
                                <?php } ?>
                            </div>
                        </div>
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
            </div>

            <!-- PL Tab -->
            <div role="tabpanel" class="tab-pane" id="kpi-pl-tab">
                <?php if (!empty($pending_pl_requests)) { ?>
                    <?php foreach ($pending_pl_requests as $request) { ?>
                    <div class="kpi-request-item tw-flex tw-items-center tw-cursor-pointer"
                         data-request-id="<?= e($request->id); ?>"
                         data-request-type="pl"
                         onclick="openPLDetails(<?= e($request->id); ?>)">
                        <div class="tw-flex-shrink-0">
                            <?php echo staff_profile_image($request->staff_id, ['kpi-request-avatar'], 'small'); ?>
                        </div>
                        <div class="kpi-request-content tw-ml-3">
                            <div class="kpi-request-title">
                                <?= e($request->firstname . ' ' . $request->lastname); ?> has requested leave
                            </div>
                            <div class="kpi-request-meta">
                                <?php $days = $request->number_of_leaving_day ?? 1; ?>
                                <span class="kpi-request-badge pl"><?= e($days . ' ' . ($days == 1 ? 'day' : 'days')); ?></span>
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
            </div>

            <!-- Tickets Tab -->
            <div role="tabpanel" class="tab-pane" id="kpi-tickets-tab">
                <?php if (!empty($open_tickets)) { ?>
                    <?php foreach ($open_tickets as $ticket) { ?>
                    <div class="kpi-request-item tw-flex tw-items-center tw-cursor-pointer"
                         onclick="window.open('<?= admin_url('tickets/ticket/' . $ticket['ticketid']); ?>', '_blank')"">
                        <div class="tw-flex-shrink-0 tw-w-9 tw-h-9 tw-rounded-full tw-bg-neutral-200 tw-flex tw-items-center tw-justify-center">
                            <i class="fa fa-ticket tw-text-neutral-500"></i>
                        </div>
                        <div class="kpi-request-content tw-ml-3 tw-overflow-hidden">
                            <div class="kpi-request-title tw-truncate">
                                <?php if (!empty($ticket['firstname'])) { ?>
                                <?= e($ticket['firstname'] . ' ' . $ticket['lastname']); ?> —
                                <?php } ?>
                                #<?= e($ticket['ticketid']); ?> <?= e($ticket['subject']); ?>
                            </div>
                            <div class="kpi-request-meta">
                                <?php if (!empty($ticket['statuscolor'])) { ?>
                                <span class="kpi-request-badge" style="background:<?= e($ticket['statuscolor']); ?>;color:#fff;">
                                    <?= e($ticket['status_name']); ?>
                                </span>
                                <?php } elseif (!empty($ticket['status_name'])) { ?>
                                <span class="kpi-request-badge ot"><?= e($ticket['status_name']); ?></span>
                                <?php } ?>
                                <?php if (!empty($ticket['priority_name'])) { ?>
                                <span class="tw-text-neutral-500">•</span>
                                <span><?= e($ticket['priority_name']); ?></span>
                                <?php } ?>
                                <?php if (!empty($ticket['department_name'])) { ?>
                                <span class="tw-text-neutral-500">•</span>
                                <span><?= e($ticket['department_name']); ?></span>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="tw-flex-shrink-0 tw-ml-auto">
                            <i class="fa fa-chevron-right tw-text-neutral-400"></i>
                        </div>
                    </div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="tw-p-4 tw-text-center tw-text-neutral-500">
                        <i class="fa fa-ticket tw-text-2xl tw-mb-2"></i>
                        <p class="tw-mb-0"><?= _l('No Open Tickets'); ?></p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </ul>
</li>
