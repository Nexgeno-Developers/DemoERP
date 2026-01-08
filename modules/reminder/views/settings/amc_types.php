<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12">
        <h4 class="no-margin">
            <?php echo _l('amc_types'); ?>
            <?php if (has_permission('reminder_settings', '', 'create')) { ?>
                <a href="#" class="btn btn-info pull-right" onclick="add_amc_type(); return false;">
                    <i class="fa fa-plus"></i> <?php echo _l('add_amc_type'); ?>
                </a>
            <?php } ?>
        </h4>
        <hr class="hr-panel-heading">
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="table-responsive">
            <table class="table dt-table table-amc-types" data-order-col="3" data-order-type="asc">
                <thead>
                    <tr>
                        <th><?php echo _l('name'); ?></th>
                        <th><?php echo _l('description'); ?></th>
                        <th><?php echo _l('order'); ?></th>
                        <th><?php echo _l('active'); ?></th>
                        <th><?php echo _l('options'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($amc_types as $type) { ?>
                        <tr>
                            <td><?php echo $type->name; ?></td>
                            <td><?php echo $type->description ?: '-'; ?></td>
                            <td><?php echo $type->order; ?></td>
                            <td>
                                <?php if ($type->active) { ?>
                                    <span class="label label-success"><?php echo _l('yes'); ?></span>
                                <?php } else { ?>
                                    <span class="label label-default"><?php echo _l('no'); ?></span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if (has_permission('reminder_settings', '', 'edit')) { ?>
                                    <a href="#" class="btn btn-default btn-icon" onclick="edit_amc_type(<?php echo $type->id; ?>); return false;" data-toggle="tooltip" title="<?php echo _l('edit'); ?>">
                                        <i class="fa fa-pencil-square-o"></i>
                                    </a>
                                <?php } ?>
                                <?php if (has_permission('reminder_settings', '', 'delete')) { ?>
                                    <a href="#" class="btn btn-danger btn-icon" onclick="delete_amc_type(<?php echo $type->id; ?>); return false;" data-toggle="tooltip" title="<?php echo _l('delete'); ?>">
                                        <i class="fa fa-remove"></i>
                                    </a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit AMC Type Modal -->
<div class="modal fade" id="amc_type_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="amc_type_modal_title"><?php echo _l('add_amc_type'); ?></h4>
            </div>
            <?php echo form_open('', ['id' => 'amc_type_form']); ?>
            <div class="modal-body">
                <input type="hidden" name="amc_type_id" id="amc_type_id">
                <div class="form-group">
                    <label for="name"><?php echo _l('name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="description"><?php echo _l('description'); ?></label>
                    <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="order"><?php echo _l('order'); ?></label>
                    <input type="number" name="order" id="order" class="form-control" value="0" min="0">
                </div>
                <div class="form-group">
                    <div class="checkbox checkbox-primary">
                        <input type="checkbox" name="active" id="active" value="1" checked>
                        <label for="active"><?php echo _l('active'); ?></label>
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

