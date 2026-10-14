<?php

defined('BASEPATH') || exit('No direct script access allowed');

class Tenant_mods extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function upgrade()
    {
        $moduleName = $this->input->get("module_name");
        $modulePath = APP_MODULES_PATH . $moduleName;
        xcopy(FCPATH . 'modules_core/' . $moduleName, $modulePath);
        set_alert('success', 'Files Upgraded Successfully');
        redirect(admin_url('modules'));
    }


}

/* End of file Superadmin.php */
