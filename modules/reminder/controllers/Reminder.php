<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Reminder extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('reminder_model');
    }

    /**
     * AMC main page
     */
    public function amc()
    {
        if (!has_permission('reminder_amc', '', 'view')) {
            access_denied('reminder_amc');
        }

        $data['title'] = _l('amc');
        $data['clients'] = $this->clients_model->get();
        $data['amc_types'] = $this->reminder_model->get_amc_types(true); // Get only active types
        
        // Get filters from POST/GET
        $filters = [
            'client_id' => $this->input->post('client_filter') ?: $this->input->get('client_filter'),
            'status' => $this->input->post('status_filter') ?: $this->input->get('status_filter'),
            'amc_type_id' => $this->input->post('amc_type_filter') ?: $this->input->get('amc_type_filter'),
            'date_from' => $this->input->post('date_from_filter') ?: $this->input->get('date_from_filter'),
            'date_to' => $this->input->post('date_to_filter') ?: $this->input->get('date_to_filter'),
            'amount_min' => $this->input->post('amount_min_filter') ?: $this->input->get('amount_min_filter'),
            'amount_max' => $this->input->post('amount_max_filter') ?: $this->input->get('amount_max_filter'),
            'expiry_type' => $this->input->post('expiry_type_filter') ?: $this->input->get('expiry_type_filter'),
            'period' => $this->input->post('period_filter') ?: $this->input->get('period_filter'),
            'tags' => $this->input->post('tags_filter') ?: $this->input->get('tags_filter'),
        ];
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });
        
        // Get detailed summary (respects filters)
        $data['summary_detailed'] = $this->reminder_model->get_amc_summary_detailed($filters);
        
        // Get simple summary for backward compatibility
        $data['summary'] = $this->reminder_model->get_amc_summary();
        
        // Pass current filters to view
        $data['active_filters'] = $filters;
        
        $this->load->view('reminder/amc/manage', $data);
    }

    /**
     * AMC datatable
     */
    public function amc_table()
    {
        if (!has_permission('reminder_amc', '', 'view')) {
            ajax_access_denied();
        }

        $this->app->get_table_data(module_views_path('reminder', 'tables/amc'));
    }

    /**
     * Get AMC summary via AJAX (respects current filters)
     */
    public function get_amc_summary()
    {
        if (!has_permission('reminder_amc', '', 'view')) {
            ajax_access_denied();
        }

        // Get filters from POST
        $filters = [
            'client_id' => $this->input->post('client_filter'),
            'status' => $this->input->post('status_filter'),
            'amc_type_id' => $this->input->post('amc_type_filter'),
            'date_from' => $this->input->post('date_from_filter'),
            'date_to' => $this->input->post('date_to_filter'),
            'amount_min' => $this->input->post('amount_min_filter'),
            'amount_max' => $this->input->post('amount_max_filter'),
            'expiry_type' => $this->input->post('expiry_type_filter'),
            'period' => $this->input->post('period_filter'),
            'tags' => $this->input->post('tags_filter'),
        ];
        
        // Handle expiry_type and period filters - convert to date ranges
        if (!empty($filters['expiry_type'])) {
            $today = date('Y-m-d');
            switch ($filters['expiry_type']) {
                case 'expired':
                    $filters['date_to'] = $today;
                    $filters['status'] = 'expired';
                    break;
                case '7':
                    $filters['date_from'] = $today;
                    $filters['date_to'] = date('Y-m-d', strtotime('+7 days'));
                    break;
                case '30':
                    $filters['date_from'] = $today;
                    $filters['date_to'] = date('Y-m-d', strtotime('+30 days'));
                    break;
                case '90':
                    $filters['date_from'] = $today;
                    $filters['date_to'] = date('Y-m-d', strtotime('+90 days'));
                    break;
                case 'active':
                    $filters['status'] = 'ongoing';
                    break;
            }
        }
        
        // Handle period filter
        if (!empty($filters['period'])) {
            $today = date('Y-m-d');
            switch ($filters['period']) {
                case 'this_month':
                    $filters['date_from'] = date('Y-m-01');
                    $filters['date_to'] = date('Y-m-t');
                    break;
                case 'last_month':
                    $filters['date_from'] = date('Y-m-01', strtotime('first day of last month'));
                    $filters['date_to'] = date('Y-m-t', strtotime('last day of last month'));
                    break;
                case 'next_month':
                    $filters['date_from'] = date('Y-m-01', strtotime('first day of next month'));
                    $filters['date_to'] = date('Y-m-t', strtotime('last day of next month'));
                    break;
            }
        }
        
        // Handle tags filter (comma-separated string)
        if (!empty($filters['tags']) && is_string($filters['tags'])) {
            $tags_array = explode(',', $filters['tags']);
            $filters['tags'] = array_filter(array_map('trim', $tags_array));
        }
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '' && (!is_array($value) || !empty($value));
        });
        
        // Get detailed summary with filters
        $summary = $this->reminder_model->get_amc_summary_detailed($filters);
        
        // Format values for display
        $base_currency = get_base_currency();
        $summary['formatted'] = [
            'total_value' => app_format_money($summary['values']['total_value'], $base_currency),
            'expired_value' => app_format_money($summary['values']['expired_value'], $base_currency),
            'expiring_soon_value' => app_format_money($summary['values']['expiring_soon_value'], $base_currency),
        ];
        
        echo json_encode($summary);
    }

    /**
     * Add AMC
     */
    public function add_amc()
    {
        if (!has_permission('reminder_amc', '', 'create')) {
            ajax_access_denied();
        }

        if ($this->input->post()) {
            $data = [
                'client_id'  => $this->input->post('client_id'),
                'amc_location'   => $this->input->post('amc_location'),
                'amc_type_id' => $this->input->post('amc_type_id') ? (int)$this->input->post('amc_type_id') : null,
                'amount'     => $this->input->post('amount'),
                'start_date' => $this->input->post('start_date') ? $this->input->post('start_date') : null,
                'end_date'   => $this->input->post('end_date'),
                'notes'      => $this->input->post('notes'),
                'created_at' => date('Y-m-d H:i:s'),
            ];

            // Auto-calculate status based on end_date
            if (!empty($data['end_date'])) {
                $end_date = strtotime($data['end_date']);
                $today = strtotime(date('Y-m-d'));
                if ($end_date < $today) {
                    $data['status'] = 'expired';
                } else {
                    $data['status'] = 'ongoing';
                }
            } else {
                $data['status'] = 'ongoing';
            }

            $id = $this->reminder_model->add_amc($data);
            
            if ($id) {
                // Handle tags
                $tags = $this->input->post('tags');
                if ($tags) {
                    handle_tags_save($tags, $id, 'amc');
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => _l('added_successfully', _l('amc')),
                    'id'      => $id
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => _l('problem_adding', _l('amc'))
                ]);
            }
        }
    }

    /**
     * Get AMC by ID
     */
    public function get_amc($id)
    {
        if (!has_permission('reminder_amc', '', 'view')) {
            ajax_access_denied();
        }

        $amc = $this->reminder_model->get_amc($id);
        
        // Add tags to the response
        if ($amc) {
            $amc->tags = prep_tags_input(get_tags_in($id, 'amc'));
        }
        
        echo json_encode($amc);
    }

    /**
     * Update AMC
     */
    public function update_amc($id)
    {
        if (!has_permission('reminder_amc', '', 'edit')) {
            ajax_access_denied();
        }

        if ($this->input->post()) {
            $data = [
                'client_id'  => $this->input->post('client_id'),
                'amc_location'   => $this->input->post('amc_location'),
                'amc_type_id' => $this->input->post('amc_type_id') ? (int)$this->input->post('amc_type_id') : null,
                'amount'     => $this->input->post('amount'),
                'start_date' => $this->input->post('start_date') ? $this->input->post('start_date') : null,
                'end_date'   => $this->input->post('end_date'),
                'notes'      => $this->input->post('notes'),
            ];

            // Auto-calculate status if end_date changed
            if (!empty($data['end_date'])) {
                $end_date = strtotime($data['end_date']);
                $today = strtotime(date('Y-m-d'));
                if ($end_date < $today) {
                    $data['status'] = 'expired';
                } else {
                    // Only set to ongoing if not manually set to on_hold or closed
                    $current_amc = $this->reminder_model->get_amc($id);
                    if ($current_amc && !in_array($current_amc->status, ['on_hold', 'closed'])) {
                        $data['status'] = 'ongoing';
                    }
                }
            }

            $result = $this->reminder_model->update_amc($id, $data);
            
            // Handle tags - always save tags even if update didn't affect rows
            $tags = $this->input->post('tags');
            if ($tags !== null) {
                handle_tags_save($tags, $id, 'amc');
            }
            
            // Return success if update succeeded (even if no rows affected, data was already correct)
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => _l('updated_successfully', _l('amc'))
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => _l('problem_updating', _l('amc'))
                ]);
            }
        }
    }

    /**
     * Renew AMC
     */
    public function renew_amc($id)
    {
        if (!has_permission('reminder_amc', '', 'create')) {
            ajax_access_denied();
        }

        if ($this->input->post()) {
            // Get current AMC data
            $current_amc = $this->reminder_model->get_amc($id);
            
            if (!$current_amc) {
                echo json_encode([
                    'success' => false,
                    'message' => _l('amc_not_found')
                ]);
                return;
            }

            // Store previous data in history
            $history_data = [
                'amc_id'    => $id,
                'amount'    => $current_amc->amount,
                'start_date' => $current_amc->start_date,
                'end_date'  => $current_amc->end_date,
                'notes'     => $current_amc->notes,
                'renewed_by_staff_id' => get_staff_user_id(),
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $this->reminder_model->add_amc_history($history_data);

            // Update AMC with new data
            $data = [
                'amount'     => $this->input->post('amount'),
                'end_date'  => $this->input->post('end_date'),
                'notes'     => $this->input->post('notes'),
            ];

            // Update start_date to current end_date if provided
            if (!empty($this->input->post('start_date'))) {
                $data['start_date'] = $this->input->post('start_date');
            } else {
                $data['start_date'] = $current_amc->end_date;
            }

            // Auto-calculate status
            if (!empty($data['end_date'])) {
                $end_date = strtotime($data['end_date']);
                $today = strtotime(date('Y-m-d'));
                if ($end_date < $today) {
                    $data['status'] = 'expired';
                } else {
                    $data['status'] = 'ongoing';
                }
            }

            $result = $this->reminder_model->update_amc($id, $data);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => _l('renewed_successfully', _l('amc'))
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => _l('problem_renewing', _l('amc'))
                ]);
            }
        }
    }

    /**
     * Update AMC status manually
     */
    public function update_amc_status($id)
    {
        if (!has_permission('reminder_amc', '', 'edit')) {
            ajax_access_denied();
        }

        $status = $this->input->post('status');
        $allowed_statuses = ['ongoing', 'expired', 'on_hold', 'closed'];
        
        if (!in_array($status, $allowed_statuses)) {
            echo json_encode([
                'success' => false,
                'message' => _l('invalid_status')
            ]);
            return;
        }

        $result = $this->reminder_model->update_amc($id, ['status' => $status]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => _l('status_updated_successfully')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('problem_updating_status')
            ]);
        }
    }

    /**
     * Delete AMC
     */
    public function delete_amc($id)
    {
        if (!has_permission('reminder_amc', '', 'delete')) {
            ajax_access_denied();
        }

        $result = $this->reminder_model->delete_amc($id);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => _l('deleted', _l('amc'))
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('problem_deleting', _l('amc'))
            ]);
        }
    }

    /**
     * AMC detail page
     */
    public function amc_detail($id)
    {
        if (!has_permission('reminder_amc', '', 'view')) {
            access_denied('reminder_amc');
        }

        $data['amc'] = $this->reminder_model->get_amc($id);
        
        if (!$data['amc']) {
            show_404();
        }

        $data['history'] = $this->reminder_model->get_amc_history($id);
        $data['title'] = $data['amc']->amc_location;
        
        $this->load->view('reminder/amc/detail', $data);
    }

    /**
     * Anniversary page
     */
    public function anniversary()
    {
        if (!has_permission('reminder_anniversary', '', 'view')) {
            access_denied('reminder_anniversary');
        }

        $data['title'] = _l('anniversary');
        $this->load->view('reminder/anniversary/manage', $data);
    }

    /**
     * Anniversary datatable
     */
    public function anniversary_table()
    {
        if (!has_permission('reminder_anniversary', '', 'view')) {
            ajax_access_denied();
        }

        $this->app->get_table_data(module_views_path('reminder', 'tables/anniversary'));
    }

    /**
     * Birthday page
     */
    public function birthday()
    {
        if (!has_permission('reminder_birthday', '', 'view')) {
            access_denied('reminder_birthday');
        }

        $data['title'] = _l('birthday');
        $this->load->view('reminder/birthday/manage', $data);
    }

    /**
     * Birthday datatable
     */
    public function birthday_table()
    {
        if (!has_permission('reminder_birthday', '', 'view')) {
            ajax_access_denied();
        }

        $this->app->get_table_data(module_views_path('reminder', 'tables/birthday'));
    }

    /**
     * Settings page
     */
    public function settings()
    {
        if (!has_permission('reminder_settings', '', 'view')) {
            access_denied('reminder_settings');
        }

        $data['title'] = _l('settings');
        $data['group'] = $this->input->get('group') ?: 'amc_types';
        $data['tab'][] = 'amc_types';
        
        if ($data['group'] == 'amc_types') {
            $data['amc_types'] = $this->reminder_model->get_amc_types();
        }
        
        $this->load->view('reminder/settings/manage', $data);
    }

    /**
     * Add AMC type
     */
    public function add_amc_type()
    {
        if (!has_permission('reminder_settings', '', 'create')) {
            ajax_access_denied();
        }

        if ($this->input->post()) {
            $data = [
                'name'        => $this->input->post('name'),
                'description' => $this->input->post('description'),
                'order'       => $this->input->post('order') ? (int)$this->input->post('order') : 0,
                'active'      => $this->input->post('active') ? 1 : 0,
            ];

            $id = $this->reminder_model->add_amc_type($data);
            
            if ($id) {
                echo json_encode([
                    'success' => true,
                    'message' => _l('added_successfully', _l('amc_type')),
                    'id'      => $id
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => _l('problem_adding', _l('amc_type'))
                ]);
            }
        }
    }

    /**
     * Get AMC type by ID
     */
    public function get_amc_type($id)
    {
        if (!has_permission('reminder_settings', '', 'view')) {
            ajax_access_denied();
        }

        $amc_type = $this->reminder_model->get_amc_type($id);
        echo json_encode($amc_type);
    }

    /**
     * Update AMC type
     */
    public function update_amc_type($id)
    {
        if (!has_permission('reminder_settings', '', 'edit')) {
            ajax_access_denied();
        }

        if ($this->input->post()) {
            $data = [
                'name'        => $this->input->post('name'),
                'description' => $this->input->post('description'),
                'order'       => $this->input->post('order') ? (int)$this->input->post('order') : 0,
                'active'      => $this->input->post('active') ? 1 : 0,
            ];

            $result = $this->reminder_model->update_amc_type($id, $data);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => _l('updated_successfully', _l('amc_type'))
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => _l('problem_updating', _l('amc_type'))
                ]);
            }
        }
    }

    /**
     * Delete AMC type
     */
    public function delete_amc_type($id)
    {
        if (!has_permission('reminder_settings', '', 'delete')) {
            ajax_access_denied();
        }

        $result = $this->reminder_model->delete_amc_type($id);
        
        if (is_array($result) && isset($result['error'])) {
            echo json_encode([
                'success' => false,
                'message' => $result['error']
            ]);
        } elseif ($result) {
            echo json_encode([
                'success' => true,
                'message' => _l('deleted', _l('amc_type'))
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('problem_deleting', _l('amc_type'))
            ]);
        }
    }
}

