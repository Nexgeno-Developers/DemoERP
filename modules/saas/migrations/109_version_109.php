<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Version_109 extends App_module_migration
{
    public function up()
    {
        $my_files_list = [
            VIEWPATH . 'admin/modules/my_list.php'      => module_dir_path(SUPERADMIN_MODULE, '/resources/application/views/admin/modules/my_list.php')
        ];

        // Copy each file in $my_files_list to its actual path if it doesn't already exist
        foreach ($my_files_list as $actual_path => $resource_path) {
            if (file_exists($actual_path)) {
                copy($resource_path, $actual_path);
            }
        }

        // An array of files to backup
        $backup_files_list = [
            APPPATH.'config/config.php'               => module_dir_path(SUPERADMIN_MODULE, '/resources/application/config/config.php')
        ];

        // Backup each file in $backup_files_list by renaming it with a '.backup' suffix if it exists, then copy the new version from the resources directory
        foreach ($backup_files_list as $actual_path => $resource_path) {
            if (file_exists($actual_path) && !file_exists($actual_path . '.backup')) {
                rename($actual_path, $actual_path.'.backup');
            }
            if (!file_exists($actual_path)) {
                copy($resource_path, $actual_path);
            }
        }

        _maybe_create_upload_path(FCPATH . 'modules_core');
        // Copy module files to the corresponding tenant's module path
        xcopy(FCPATH . 'modules/', FCPATH . 'modules_core');
    }
}
