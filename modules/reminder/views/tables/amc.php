<?php
defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'c.company as client_name',
    'ra.amc_location as amc_location',
    'rat.name as amc_type_name',
    'ra.amount as amount',
    'ra.end_date as end_date',
    'ra.status as status',
    '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ra.id and rel_type="amc" ORDER by tag_order ASC) as tags',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'reminder_amc ra';
$join         = [
    'LEFT JOIN ' . db_prefix() . 'clients c ON c.userid = ra.client_id',
    'LEFT JOIN ' . db_prefix() . 'contacts co ON co.userid = ra.client_id AND co.is_primary = 1',
    'LEFT JOIN ' . db_prefix() . 'reminder_amc_types rat ON rat.id = ra.amc_type_id',
];

$where = [];

// Get filters
$CI = &get_instance();
$status_filter = $CI->input->post('status_filter');
$client_filter = $CI->input->post('client_filter');
$amc_type_filter = $CI->input->post('amc_type_filter');
$date_from = $CI->input->post('date_from_filter');
$date_to = $CI->input->post('date_to_filter');
$amount_min = $CI->input->post('amount_min_filter');
$amount_max = $CI->input->post('amount_max_filter');
$expiry_type = $CI->input->post('expiry_type_filter');
$period = $CI->input->post('period_filter');
$tags_filter = $CI->input->post('tags_filter');

// Apply filters
if (!empty($status_filter)) {
    $where[] = 'AND ra.status = ' . $CI->db->escape($status_filter);
} else {
    // Default: Show only ongoing and expired
    $where[] = "AND ra.status IN ('ongoing', 'expired')";
}

if (!empty($client_filter)) {
    $where[] = 'AND ra.client_id = ' . (int)$client_filter;
}

if (!empty($amc_type_filter)) {
    $where[] = 'AND ra.amc_type_id = ' . (int)$amc_type_filter;
}

// Handle expiry type filter
if (!empty($expiry_type)) {
    $today = date('Y-m-d');
    switch ($expiry_type) {
        case 'expired':
            $where[] = 'AND ra.end_date < "' . $today . '"';
            $where[] = "AND ra.status = 'expired'";
            break;
        case '7':
            $date_7 = date('Y-m-d', strtotime('+7 days'));
            $where[] = 'AND ra.end_date >= "' . $today . '" AND ra.end_date <= "' . $date_7 . '"';
            $where[] = "AND ra.status != 'expired'";
            break;
        case '30':
            $date_30 = date('Y-m-d', strtotime('+30 days'));
            $where[] = 'AND ra.end_date >= "' . $today . '" AND ra.end_date <= "' . $date_30 . '"';
            $where[] = "AND ra.status != 'expired'";
            break;
        case '90':
            $date_90 = date('Y-m-d', strtotime('+90 days'));
            $where[] = 'AND ra.end_date >= "' . $today . '" AND ra.end_date <= "' . $date_90 . '"';
            $where[] = "AND ra.status != 'expired'";
            break;
        case 'active':
            $where[] = "AND ra.status = 'ongoing'";
            break;
    }
}

// Handle period filter
if (!empty($period)) {
    $today = date('Y-m-d');
    switch ($period) {
        case 'this_month':
            $where[] = 'AND MONTH(ra.end_date) = MONTH(CURDATE()) AND YEAR(ra.end_date) = YEAR(CURDATE())';
            break;
        case 'last_month':
            $where[] = 'AND MONTH(ra.end_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(ra.end_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))';
            break;
        case 'next_month':
            $where[] = 'AND MONTH(ra.end_date) = MONTH(DATE_ADD(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(ra.end_date) = YEAR(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))';
            break;
    }
}

if (!empty($date_from)) {
    $where[] = 'AND ra.end_date >= ' . $CI->db->escape($date_from);
}

if (!empty($date_to)) {
    $where[] = 'AND ra.end_date <= ' . $CI->db->escape($date_to);
}

if (!empty($amount_min)) {
    $where[] = 'AND ra.amount >= ' . (float)$amount_min;
}

if (!empty($amount_max)) {
    $where[] = 'AND ra.amount <= ' . (float)$amount_max;
}

// Handle tags filter (can be comma-separated string or array)
// Uses AND logic: AMC must have ALL selected tags
if (!empty($tags_filter)) {
    $tag_ids = [];
    if (is_array($tags_filter)) {
        $tag_ids = array_filter(array_map('intval', $tags_filter));
    } else {
        // Handle comma-separated string
        $tags_array = explode(',', $tags_filter);
        $tag_ids = array_filter(array_map('intval', $tags_array));
    }
    
    if (!empty($tag_ids)) {
        $tag_count = count($tag_ids);
        $tag_ids_escaped = array_map(function($id) use ($CI) {
            return $CI->db->escape($id);
        }, $tag_ids);
        
        // Filter for AMCs that have ALL selected tags (AND logic)
        $where[] = 'AND ra.id IN (
            SELECT rel_id 
            FROM ' . db_prefix() . 'taggables 
            WHERE rel_type = "amc" 
            AND tag_id IN (' . implode(',', $tag_ids_escaped) . ')
            GROUP BY rel_id
            HAVING COUNT(DISTINCT tag_id) = ' . (int)$tag_count . '
        )';
    }
}

try {
    $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['ra.id as id', 'ra.client_id as client_id', 'CONCAT(co.firstname, " ", co.lastname) as contact_name']);

    $output  = $result['output'];
    $rResult = $result['rResult'];

    // Initialize aaData if not set
    if (!isset($output['aaData'])) {
        $output['aaData'] = [];
    }
} catch (Exception $e) {
    // Return empty result on error to prevent JSON breakage
    $CI = &get_instance();
    $output = [
        'draw' => intval($CI->input->post('draw')),
        'iTotalRecords' => 0,
        'iTotalDisplayRecords' => 0,
        'aaData' => []
    ];
    $rResult = [];
}

// Sort results: expired first, then by nearest expiry date (only if no status filter)
if (!empty($rResult) && empty($status_filter)) {
    usort($rResult, function($a, $b) {
        // Expired items first
        $a_expired = ($a['status'] == 'expired') ? 0 : 1;
        $b_expired = ($b['status'] == 'expired') ? 0 : 1;
        if ($a_expired != $b_expired) {
            return $a_expired - $b_expired;
        }
        // Then sort by end_date ascending
        if (empty($a['end_date']) && empty($b['end_date'])) {
            return 0;
        }
        if (empty($a['end_date'])) {
            return 1;
        }
        if (empty($b['end_date'])) {
            return -1;
        }
        return strcmp($a['end_date'], $b['end_date']);
    });
}

foreach ($rResult as $aRow) {
    $row = [];

    // Get ID
    $amc_id = isset($aRow['id']) ? intval($aRow['id']) : 0;
    $client_id = isset($aRow['client_id']) ? intval($aRow['client_id']) : 0;

    // Customer Name (clickable) - show contact name if client name is empty
    $client_name = !empty($aRow['client_name']) ? $aRow['client_name'] : '';
    $contact_name = !empty($aRow['contact_name']) ? trim($aRow['contact_name']) : '';
    
    if ($client_id > 0) {
        // Use client name if available, otherwise use contact name
        $display_name = !empty($client_name) ? $client_name : (!empty($contact_name) ? $contact_name : '-');
        if ($display_name != '-') {
            $client_name_link = '<a href="' . admin_url('clients/client/' . $client_id) . '" class="tw-font-medium">' . $display_name . '</a>';
            $row[] = $client_name_link;
        } else {
            $row[] = $display_name;
        }
    } else {
        $row[] = '-';
    }

    // AMC Location (clickable with subtle color)
    $amc_location = !empty($aRow['amc_location']) ? $aRow['amc_location'] : '-';
    if ($amc_location != '-') {
        $amc_location_link = '<a href="' . admin_url('reminder/amc_detail/' . $amc_id) . '" class="amc-location-link">' . $amc_location . '</a>';
        $row[] = $amc_location_link;
    } else {
        $row[] = $amc_location;
    }

    // AMC Type
    $amc_type = !empty($aRow['amc_type_name']) ? $aRow['amc_type_name'] : '-';
    $row[] = $amc_type;

    // Amount
    $amount = !empty($aRow['amount']) ? $aRow['amount'] : 0;
    $row[] = app_format_money($amount, get_base_currency());

    // End Date
    $end_date = !empty($aRow['end_date']) ? _d($aRow['end_date']) : '-';
    $row[] = $end_date;

    // Status (badge)
    $status = !empty($aRow['status']) ? $aRow['status'] : 'ongoing';
    $status_class = 'default';
    switch ($status) {
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
    $status_badge = '<span class="label label-' . $status_class . '">' . ucfirst($status) . '</span>';
    $row[] = $status_badge;

    // Tags
    $tags = !empty($aRow['tags']) ? $aRow['tags'] : '';
    $row[] = render_tags($tags);

    // Actions
    $options = '';
    if (has_permission('reminder_amc', '', 'edit')) {
        $options .= '<a href="#" class="btn btn-default btn-icon" onclick="edit_amc(' . $amc_id . '); return false;" data-toggle="tooltip" title="' . _l('edit') . '"><i class="fa fa-pencil-square-o"></i></a>';
    }
    if (has_permission('reminder_amc', '', 'create')) {
        $options .= ' <a href="#" class="btn btn-info btn-icon" onclick="renew_amc(' . $amc_id . '); return false;" data-toggle="tooltip" title="' . _l('renew') . '"><i class="fa fa-refresh"></i></a>';
    }
    if (has_permission('reminder_amc', '', 'delete')) {
        $options .= ' <a href="#" class="btn btn-danger btn-icon" onclick="delete_amc(' . $amc_id . '); return false;" data-toggle="tooltip" title="' . _l('delete') . '"><i class="fa fa-remove"></i></a>';
    }
    $row[] = $options;

    $output['aaData'][] = $row;
}

