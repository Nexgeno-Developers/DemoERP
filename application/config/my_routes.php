<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Load module routes (SAFE MODE)
|--------------------------------------------------------------------------
| This file is loaded BEFORE CI_Controller exists.
| Do NOT use get_instance(), DB, or Perfex services here.
|
| Only include static route files.
*/

$modules_path = APPPATH . '../modules/';

if (is_dir($modules_path)) {
    foreach (glob($modules_path . '*/config/routes.php') as $route_file) {
        include $route_file;
    }
}
