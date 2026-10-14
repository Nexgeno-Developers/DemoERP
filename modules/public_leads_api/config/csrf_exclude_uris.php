<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Exclude public leads API endpoints from global CSRF checks.
return [
    'api/public/leads/store',
    'public_leads_api/public_api/store',
];