<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Reminder_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get AMC summary counts (simple version for backward compatibility)
     */
    public function get_amc_summary()
    {
        $this->db->select('status, COUNT(*) as count');
        $this->db->from(db_prefix() . 'reminder_amc');
        $this->db->group_by('status');
        $results = $this->db->get()->result_array();

        $summary = [
            'total'    => 0,
            'ongoing'  => 0,
            'expired'  => 0,
            'on_hold'  => 0,
            'closed'   => 0,
        ];

        foreach ($results as $row) {
            $summary['total'] += $row['count'];
            if (isset($summary[$row['status']])) {
                $summary[$row['status']] = $row['count'];
            }
        }

        // Get total count
        $this->db->select('COUNT(*) as total');
        $this->db->from(db_prefix() . 'reminder_amc');
        $total_result = $this->db->get()->row();
        $summary['total'] = $total_result->total;

        return $summary;
    }

    /**
     * Get detailed AMC summary with values and expiry counts
     * Respects active filters
     */
    public function get_amc_summary_detailed($filters = [])
    {
        $today = date('Y-m-d');
        $date_7_days = date('Y-m-d', strtotime('+7 days'));
        $date_30_days = date('Y-m-d', strtotime('+30 days'));
        $date_90_days = date('Y-m-d', strtotime('+90 days'));
        
        // Build base query with filters
        $this->db->select('ra.*');
        $this->db->from(db_prefix() . 'reminder_amc ra');
        
        // Apply filters
        if (!empty($filters['client_id'])) {
            $this->db->where('ra.client_id', (int)$filters['client_id']);
        }
        
        if (!empty($filters['amc_type_id'])) {
            $this->db->where('ra.amc_type_id', (int)$filters['amc_type_id']);
        }
        
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $this->db->where_in('ra.status', $filters['status']);
            } else {
                $this->db->where('ra.status', $filters['status']);
            }
        }
        
        // Handle date filters (from expiry_type or period filters)
        if (!empty($filters['date_from'])) {
            $this->db->where('ra.end_date >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $this->db->where('ra.end_date <=', $filters['date_to']);
        }
        
        if (!empty($filters['amount_min'])) {
            $this->db->where('ra.amount >=', (float)$filters['amount_min']);
        }
        
        if (!empty($filters['amount_max'])) {
            $this->db->where('ra.amount <=', (float)$filters['amount_max']);
        }
        
        if (!empty($filters['tags'])) {
            // Convert to array if needed
            $tag_ids = [];
            if (is_array($filters['tags'])) {
                $tag_ids = array_filter(array_map('intval', $filters['tags']));
            } else {
                $tags_array = explode(',', $filters['tags']);
                $tag_ids = array_filter(array_map('intval', $tags_array));
            }
            
            if (!empty($tag_ids)) {
                $tag_count = count($tag_ids);
                $tag_ids_escaped = array_map('intval', $tag_ids);
                
                // Filter for AMCs that have ALL selected tags (AND logic)
                $this->db->where('ra.id IN (
                    SELECT rel_id 
                    FROM ' . db_prefix() . 'taggables 
                    WHERE rel_type = "amc" 
                    AND tag_id IN (' . implode(',', $tag_ids_escaped) . ')
                    GROUP BY rel_id
                    HAVING COUNT(DISTINCT tag_id) = ' . (int)$tag_count . '
                )', null, false);
            }
        }
        
        // Get all AMCs matching filters
        $all_amcs = $this->db->get()->result_array();
        
        // Calculate metrics
        $total_value = 0;
        $expired_value = 0;
        $expiring_soon_value = 0;
        $status_counts = [
            'total' => 0,
            'ongoing' => 0,
            'expired' => 0,
            'on_hold' => 0,
            'closed' => 0,
        ];
        $expiring_counts = [
            'in_7_days' => 0,
            'in_30_days' => 0,
            'in_90_days' => 0,
        ];
        
        foreach ($all_amcs as $amc) {
            $amount = (float)$amc['amount'];
            $status = $amc['status'];
            $end_date = $amc['end_date'];
            
            // Total value (all AMCs)
            $total_value += $amount;
            
            // Status counts
            $status_counts['total']++;
            if (isset($status_counts[$status])) {
                $status_counts[$status]++;
            }
            
            // Expired value
            if ($status == 'expired') {
                $expired_value += $amount;
            }
            
            // Expiring soon value (within 30 days, not expired)
            if ($status != 'expired' && $end_date && $end_date >= $today && $end_date <= $date_30_days) {
                $expiring_soon_value += $amount;
            }
            
            // Expiring counts
            if ($status != 'expired' && $end_date && $end_date >= $today) {
                if ($end_date <= $date_7_days) {
                    $expiring_counts['in_7_days']++;
                }
                if ($end_date <= $date_30_days) {
                    $expiring_counts['in_30_days']++;
                }
                if ($end_date <= $date_90_days) {
                    $expiring_counts['in_90_days']++;
                }
            }
        }
        
        return [
            'values' => [
                'total_value' => $total_value,
                'expired_value' => $expired_value,
                'expiring_soon_value' => $expiring_soon_value,
            ],
            'counts' => $status_counts,
            'expiring' => $expiring_counts,
        ];
    }

    /**
     * Get AMC by ID
     */
    public function get_amc($id)
    {
        $this->db->select('ra.*, c.company as client_name, rat.name as amc_type_name');
        $this->db->from(db_prefix() . 'reminder_amc ra');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = ra.client_id', 'left');
        $this->db->join(db_prefix() . 'reminder_amc_types rat', 'rat.id = ra.amc_type_id', 'left');
        $this->db->where('ra.id', $id);
        return $this->db->get()->row();
    }

    /**
     * Get all AMCs with filters
     */
    public function get_amcs($filters = [])
    {
        $this->db->select('ra.*, c.company as client_name');
        $this->db->from(db_prefix() . 'reminder_amc ra');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = ra.client_id', 'left');

        // Apply filters
        if (isset($filters['status']) && !empty($filters['status'])) {
            $this->db->where('ra.status', $filters['status']);
        }

        if (isset($filters['client_id']) && !empty($filters['client_id'])) {
            $this->db->where('ra.client_id', $filters['client_id']);
        }

        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $this->db->where('ra.end_date >=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $this->db->where('ra.end_date <=', $filters['date_to']);
        }

        if (isset($filters['amount_min']) && !empty($filters['amount_min'])) {
            $this->db->where('ra.amount >=', $filters['amount_min']);
        }

        if (isset($filters['amount_max']) && !empty($filters['amount_max'])) {
            $this->db->where('ra.amount <=', $filters['amount_max']);
        }

        // Default: Show only ongoing and expired, sorted by nearest expiry
        if (!isset($filters['status']) || empty($filters['status'])) {
            $this->db->where_in('ra.status', ['ongoing', 'expired']);
            // Sort: expired first, then by nearest expiry date
            $this->db->order_by("CASE WHEN ra.status = 'expired' THEN 0 ELSE 1 END", 'ASC', false);
            $this->db->order_by('ra.end_date', 'ASC');
        } else {
            $this->db->order_by('ra.end_date', 'ASC');
        }

        return $this->db->get()->result();
    }

    /**
     * Add AMC
     */
    public function add_amc($data)
    {
        $this->db->insert(db_prefix() . 'reminder_amc', $data);
        return $this->db->insert_id();
    }

    /**
     * Update AMC
     */
    public function update_amc($id, $data)
    {
        if (empty($data)) {
            return true; // No data to update, but not an error
        }
        
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'reminder_amc', $data);
        
        // Return true if update executed successfully (even if no rows affected)
        // This handles cases where data hasn't changed
        return $this->db->error()['code'] == 0;
    }

    /**
     * Delete AMC
     */
    public function delete_amc($id)
    {
        // Delete history first
        $this->db->where('amc_id', $id);
        $this->db->delete(db_prefix() . 'reminder_amc_history');
        
        // Delete AMC
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'reminder_amc');
        
        return $this->db->affected_rows() > 0;
    }

    /**
     * Add AMC history
     */
    public function add_amc_history($data)
    {
        $this->db->insert(db_prefix() . 'reminder_amc_history', $data);
        return $this->db->insert_id();
    }

    /**
     * Get AMC history
     */
    public function get_amc_history($amc_id)
    {
        $this->db->select('rah.*, s.firstname, s.lastname');
        $this->db->from(db_prefix() . 'reminder_amc_history rah');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = rah.renewed_by_staff_id', 'left');
        $this->db->where('rah.amc_id', $amc_id);
        $this->db->order_by('rah.created_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Get anniversary clients
     */
    public function get_anniversary_clients($filters = [])
    {
        $this->db->select('c.userid, c.company, c.datecreated, 
                          YEAR(CURDATE()) - YEAR(c.datecreated) as years,
                          DATE_FORMAT(c.datecreated, "%m-%d") as anniversary_date');
        $this->db->from(db_prefix() . 'clients c');
        $this->db->where('c.active', 1);
        $this->db->where('c.datecreated IS NOT NULL');

        // Filter by upcoming days
        if (isset($filters['upcoming_days']) && !empty($filters['upcoming_days'])) {
            $days = (int)$filters['upcoming_days'];
            $this->db->having('DATEDIFF(DATE(CONCAT(YEAR(CURDATE()), "-", anniversary_date)), CURDATE()) BETWEEN 0 AND ' . $days);
        }

        $this->db->order_by('anniversary_date', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get birthday clients
     */
    public function get_birthday_clients($filters = [])
    {
        $this->db->select('c.userid, c.company, 
                          CONCAT(co.firstname, " ", co.lastname) as contact_name,
                          co.birthday,
                          DATE_FORMAT(co.birthday, "%m-%d") as birthday_date,
                          YEAR(CURDATE()) - YEAR(co.birthday) as age');
        $this->db->from(db_prefix() . 'clients c');
        $this->db->join(db_prefix() . 'contacts co', 'co.userid = c.userid AND co.is_primary = 1', 'left');
        $this->db->where('c.active', 1);
        $this->db->where('co.birthday IS NOT NULL');

        // Filter by upcoming days
        if (isset($filters['upcoming_days']) && !empty($filters['upcoming_days'])) {
            $days = (int)$filters['upcoming_days'];
            $this->db->having('DATEDIFF(DATE(CONCAT(YEAR(CURDATE()), "-", birthday_date)), CURDATE()) BETWEEN 0 AND ' . $days);
        }

        $this->db->order_by('birthday_date', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get all AMC types
     */
    public function get_amc_types($active_only = false)
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . 'reminder_amc_types');
        if ($active_only) {
            $this->db->where('active', 1);
        }
        $this->db->order_by('order', 'ASC');
        $this->db->order_by('name', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get AMC type by ID
     */
    public function get_amc_type($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'reminder_amc_types')->row();
    }

    /**
     * Add AMC type
     */
    public function add_amc_type($data)
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $this->db->insert(db_prefix() . 'reminder_amc_types', $data);
        return $this->db->insert_id();
    }

    /**
     * Update AMC type
     */
    public function update_amc_type($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'reminder_amc_types', $data);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Delete AMC type
     */
    public function delete_amc_type($id)
    {
        // Check if any AMC is using this type
        $this->db->where('amc_type_id', $id);
        $count = $this->db->count_all_results(db_prefix() . 'reminder_amc');
        
        if ($count > 0) {
            return ['error' => 'Cannot delete AMC type. It is being used by ' . $count . ' AMC record(s).'];
        }
        
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'reminder_amc_types');
        return $this->db->affected_rows() > 0;
    }
}

