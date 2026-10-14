<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="leaveDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <?php echo _l('Paid Leave Details') . ' - ' . htmlspecialchars($staff_name); ?>
                </h4>
            </div>
            <div class="modal-body">
                <!-- ✅ Make table scrollable for large data -->
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-leave-details table-striped">
                        <thead style="position: sticky; top: 0; background: white; z-index: 10;">
                            <tr>
                                <th><?php echo _l('Subject'); ?></th>
                                <th><?php echo _l('Start Date'); ?></th>
                                <th><?php echo _l('End Date'); ?></th>
                                <th><?php echo _l('Type'); ?></th>
                                <th><?php echo _l('Status'); ?></th>
                                <th><?php echo _l('Approver'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($leaves)): ?>
                                <?php foreach ($leaves as $leave): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $subject = htmlspecialchars($leave['subject'] ?? '-');
                                            $leave_id = isset($leave['id']) ? (int)$leave['id'] : 0;
                                            if ($leave_id > 0 && $subject !== '-') {
                                                echo '<a href="' . admin_url('timesheets/requisition_detail/' . $leave_id) . '" target="_blank" style="color: #333;">' . $subject . '</a>';
                                            } else {
                                                echo $subject;
                                            }
                                            ?>
                                        </td>
                                        <td><?php 
                                            if (!empty($leave['start_time'])) {
                                                $start_date = is_numeric($leave['start_time']) ? date('Y-m-d', $leave['start_time']) : date('Y-m-d', strtotime($leave['start_time']));
                                                echo _d($start_date);
                                            } else {
                                                echo '-';
                                            }
                                        ?></td>
                                        <td><?php 
                                            if (!empty($leave['end_time'])) {
                                                $end_date = is_numeric($leave['end_time']) ? date('Y-m-d', $leave['end_time']) : date('Y-m-d', strtotime($leave['end_time']));
                                                echo _d($end_date);
                                            } else {
                                                echo '-';
                                            }
                                        ?></td>
                                        <td>
                                            <?php 
                                            $type_text = $leave['type_of_leave_text'] ?? '';
                                            if (empty($type_text) && isset($leave['type_of_leave'])) {
                                                $type_map = [
                                                    0 => 'Other',
                                                    1 => 'Sick Leave',
                                                    2 => 'Maternity Leave',
                                                    4 => 'Private Work Without Pay',
                                                    8 => 'Leave'
                                                ];
                                                $type_text = $type_map[$leave['type_of_leave']] ?? 'Other';
                                            }
                                            echo htmlspecialchars($type_text ?: '-');
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            // ✅ Use the new status labels from model if available
                                            if (isset($leave['status_label']) && isset($leave['status_class'])) {
                                                echo '<span class="label ' . $leave['status_class'] . '">' . _l($leave['status_label']) . '</span>';
                                            } else {
                                                // Fallback to old logic
                                                $status = isset($leave['status']) ? (int)$leave['status'] : 0;
                                                if ($status == 1) {
                                                    echo '<span class="label label-success">' . _l('Approved') . '</span>';
                                                } elseif ($status == 2) {
                                                    echo '<span class="label label-danger">' . _l('Rejected') . '</span>';
                                                } else {
                                                    echo '<span class="label label-warning">' . _l('Pending') . '</span>';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $approver_ids = isset($leave['approver_ids']) ? $leave['approver_ids'] : '';
                                            $approver_names = isset($leave['approver_names']) ? $leave['approver_names'] : '';
                                            
                                            if (!empty($approver_ids) && !empty($approver_names)) {
                                                $approver_id_array = explode(',', $approver_ids);
                                                $approver_name_array = explode(', ', $approver_names);
                                                
                                                echo '<div class="tw-flex tw--space-x-1">';
                                                foreach ($approver_id_array as $index => $approver_id) {
                                                    $approver_id = trim($approver_id);
                                                    if (!empty($approver_id) && is_numeric($approver_id)) {
                                                        $approver_name = isset($approver_name_array[$index]) ? trim($approver_name_array[$index]) : '';
                                                        $profile_image_url = staff_profile_image_url($approver_id, 'small');
                                                        
                                                        echo '<a href="' . admin_url('staff/profile/' . $approver_id) . '"
                                                               target= "blank" 
                                                               data-toggle="tooltip" 
                                                               data-title="' . htmlspecialchars($approver_name) . '" 
                                                               data-placement="top"
                                                               class="tw-inline-block">';
                                                        echo '<img src="' . $profile_image_url . '" 
                                                             alt="' . htmlspecialchars($approver_name) . '" 
                                                             class="staff-profile-image-small tw-rounded-full"
                                                             style="width: 32px; height: 32px; object-fit: cover; border: 2px solid #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">';
                                                        echo '</a>';
                                                    }
                                                }
                                                echo '</div>';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center"><?php echo _l('No records found'); ?></td>
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
