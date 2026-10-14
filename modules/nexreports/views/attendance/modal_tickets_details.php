<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="ticketsDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?php echo _l('tickets') . ' (' . _l('open') . ')' . ' - ' . htmlspecialchars($staff_name); ?></h4>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-tickets-details">
                        <thead>
                            <tr>
                                <th><?php echo _l('subject'); ?></th>
                                <th><?php echo _l('ticket_dt_department'); ?></th>
                                <th><?php echo _l('ticket_dt_status'); ?></th>
                                <th><?php echo _l('ticket_dt_priority'); ?></th>
                                <th><?php echo _l('ticket_dt_last_reply'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($tickets)): ?>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $subject = htmlspecialchars($ticket['subject'] ?? '-');
                                            $ticket_id = isset($ticket['ticketid']) ? (int)$ticket['ticketid'] : 0;
                                            if ($ticket_id > 0 && $subject !== '-') {
                                                echo '<a href="' . admin_url('tickets/ticket/' . $ticket_id) . '" target="_blank" style="color: #333;">' . $subject . '</a>';
                                            } else {
                                                echo $subject;
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo !empty($ticket['department_name']) ? htmlspecialchars($ticket['department_name']) : '-'; ?></td>
                                        <td>
                                            <?php
                                            $status_name = isset($ticket['status_name']) ? $ticket['status_name'] : '';
                                            $status_color = isset($ticket['statuscolor']) ? $ticket['statuscolor'] : '#777';
                                            if ($status_name) {
                                                echo '<span class="label" style="background-color: ' . htmlspecialchars($status_color) . '; color: #fff;">' . htmlspecialchars($status_name) . '</span>';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo !empty($ticket['priority_name']) ? htmlspecialchars($ticket['priority_name']) : '-'; ?></td>
                                        <td>
                                            <?php 
                                            if (!empty($ticket['lastreply']) && $ticket['lastreply'] !== '0000-00-00 00:00:00') {
                                                // lastreply is already a datetime string from database
                                                echo _dt($ticket['lastreply']);
                                            } else {
                                                echo '-';
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
