<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="otDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <?php echo _l('OT Hours Details') . ' - ' . htmlspecialchars($staff_name); ?>
                </h4>
            </div>
            <div class="modal-body">
                <!-- ✅ Make table scrollable for large data -->
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-ot-details table-striped">
                        <thead style="position: sticky; top: 0; background: white; z-index: 10;">
                            <tr>
                                <th><?php echo _l('Additional day'); ?></th>
                                <th><?php echo _l('Time in'); ?></th>
                                <th><?php echo _l('Time out'); ?></th>
                                <th><?php echo _l('Timesheets value'); ?></th>
                                <th><?php echo _l('Status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ot_records)): ?>
                                <?php foreach ($ot_records as $ot): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            if (!empty($ot['additional_day'])) {
                                                $ot_date = is_numeric($ot['additional_day']) ? date('Y-m-d', $ot['additional_day']) : date('Y-m-d', strtotime($ot['additional_day']));
                                                echo _d($ot_date);
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo !empty($ot['time_in']) ? htmlspecialchars($ot['time_in']) : '-'; ?></td>
                                        <td><?php echo !empty($ot['time_out']) ? htmlspecialchars($ot['time_out']) : '-'; ?></td>
                                        <td><?php echo !empty($ot['timekeeping_value']) ? number_format((float)$ot['timekeeping_value'], 1) : '-'; ?></td>
                                        <td>
                                            <?php
                                            // ✅ Use the new status labels from model
                                            if (isset($ot['status_label']) && isset($ot['status_class'])) {
                                                echo '<span class="label ' . $ot['status_class'] . '">' . _l($ot['status_label']) . '</span>';
                                            } else {
                                                // Fallback to old logic
                                                $status = isset($ot['status']) ? $ot['status'] : '';
                                                if ($status == '1' || strtolower($status) == 'approved') {
                                                    echo '<span class="label label-success">' . _l('Approved') . '</span>';
                                                } elseif ($status == '2' || strtolower($status) == 'rejected') {
                                                    echo '<span class="label label-danger">' . _l('Rejected') . '</span>';
                                                } else {
                                                    echo '<span class="label label-warning">' . _l('Pending') . '</span>';
                                                }
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center"><?php echo _l('No records found'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            </div>
        </div>
    </div>
</div>
