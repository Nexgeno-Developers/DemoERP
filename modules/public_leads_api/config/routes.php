<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Public API endpoint (POST /api/public/leads/store)
$route['api/public/leads/store']['post'] = 'public_leads_api/public_api/store';
$route['api/public/leads/store']         = 'public_leads_api/public_api/store';

// Admin controller entry (fallback)
$route['admin/public_leads_api'] = 'public_leads_api/public_leads_api/index';
$route['admin/public_leads_api/logs'] = 'public_leads_api/public_leads_api/logs';
