<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Auth Routes
$route['api/v1/auth/staff/login']['post']    = 'crm_mobile_api/auth/staff_login';
$route['api/v1/auth/customer/login']['post'] = 'crm_mobile_api/auth/customer_login';
$route['api/v1/auth/me']['get']              = 'crm_mobile_api/auth/me';

// Metadata Routes
$route['api/v1/tickets/meta']['get']         = 'crm_mobile_api/tickets_meta/index';

// Customer Ticket Routes
$route['api/v1/customer/tickets']['get']                  = 'crm_mobile_api/customer_tickets/index';
$route['api/v1/customer/tickets']['post']                 = 'crm_mobile_api/customer_tickets/store';
$route['api/v1/customer/tickets/(:num)']['get']           = 'crm_mobile_api/customer_tickets/show/$1';
$route['api/v1/customer/tickets/(:num)/reply']['post']    = 'crm_mobile_api/customer_tickets/reply/$1';

// Staff Ticket Routes
$route['api/v1/staff/tickets']['get']                      = 'crm_mobile_api/staff_tickets/index';
$route['api/v1/staff/tickets/(:num)']['get']               = 'crm_mobile_api/staff_tickets/show/$1';
$route['api/v1/staff/tickets/(:num)/status']['put']         = 'crm_mobile_api/staff_tickets/update_status/$1';
$route['api/v1/staff/tickets/(:num)/status']['post']        = 'crm_mobile_api/staff_tickets/update_status/$1';
$route['api/v1/staff/tickets/(:num)/reply']['post']         = 'crm_mobile_api/staff_tickets/reply/$1';
$route['api/v1/staff/tickets/(:num)/attachments']['post']   = 'crm_mobile_api/staff_tickets/upload_attachment/$1';
