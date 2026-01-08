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
                                    <a href="<?php echo admin_url('reminder/amc'); ?>">
                                        <i class="fa fa-arrow-left"></i> <?php echo _l('back_to_amc_list'); ?>
                                    </a>
                                </h4>
                                <hr class="hr-panel-heading">
                            </div>
                        </div>

                        <!-- Current AMC Details -->
                        <div class="row">
                            <div class="col-md-12">
                                <h4><?php echo _l('amc_details'); ?></h4>
                                <table class="table table-bordered">
                                    <tr>
                                        <td width="20%"><strong><?php echo _l('customer'); ?>:</strong></td>
                                        <td><?php echo $amc->client_name; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('amc_location'); ?>:</strong></td>
                                        <td><?php echo $amc->amc_location; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('amc_type'); ?>:</strong></td>
                                        <td><?php echo !empty($amc->amc_type_name) ? $amc->amc_type_name : '-'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('amount'); ?>:</strong></td>
                                        <td><?php echo app_format_money($amc->amount, get_base_currency()); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('start_date'); ?>:</strong></td>
                                        <td><?php echo $amc->start_date ? _d($amc->start_date) : '-'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('end_date'); ?>:</strong></td>
                                        <td><?php echo $amc->end_date ? _d($amc->end_date) : '-'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('status'); ?>:</strong></td>
                                        <td>
                                            <?php
                                            $status_class = 'default';
                                            switch ($amc->status) {
                                                case 'ongoing':
                                                    $status_class = 'success';
                                                    break;
                                                case 'expired':
                                                    $status_class = 'danger';
                                                    break;
                                                case 'on_hold':
                                                    $status_class = 'warning';
                                                    break;
                                                case 'closed':
                                                    $status_class = 'info';
                                                    break;
                                            }
                                            ?>
                                            <span class="label label-<?php echo $status_class; ?>"><?php echo ucfirst($amc->status); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('notes'); ?>:</strong></td>
                                        <td><?php echo $amc->notes ? nl2br($amc->notes) : '-'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('created_at'); ?>:</strong></td>
                                        <td><?php echo _dt($amc->created_at); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Renewal History -->
                        <div class="row mtop20">
                            <div class="col-md-12">
                                <h4><?php echo _l('renewal_history'); ?></h4>
                                <?php if (!empty($history)) { ?>
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th><?php echo _l('amount'); ?></th>
                                                <th><?php echo _l('start_date'); ?></th>
                                                <th><?php echo _l('end_date'); ?></th>
                                                <th><?php echo _l('notes'); ?></th>
                                                <th><?php echo _l('renewed_by'); ?></th>
                                                <th><?php echo _l('created_at'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($history as $h) { ?>
                                                <tr>
                                                    <td><?php echo app_format_money($h->amount, get_base_currency()); ?></td>
                                                    <td><?php echo $h->start_date ? _d($h->start_date) : '-'; ?></td>
                                                    <td><?php echo $h->end_date ? _d($h->end_date) : '-'; ?></td>
                                                    <td><?php echo $h->notes ? nl2br($h->notes) : '-'; ?></td>
                                                    <td><?php echo (!empty($h->firstname) && !empty($h->lastname)) ? trim($h->firstname . ' ' . $h->lastname) : '-'; ?></td>
                                                    <td><?php echo _dt($h->created_at); ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                <?php } else { ?>
                                    <p class="text-muted"><?php echo _l('no_renewal_history'); ?></p>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

