<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Drop tblreminder_amc_history table first (due to foreign key constraints if any)
if ($CI->db->table_exists(db_prefix() . 'reminder_amc_history')) {
    $CI->db->query('DROP TABLE ' . db_prefix() . 'reminder_amc_history');
}

// Drop tblreminder_amc table
if ($CI->db->table_exists(db_prefix() . 'reminder_amc')) {
    $CI->db->query('DROP TABLE ' . db_prefix() . 'reminder_amc');
}

// Drop tblreminder_amc_types table
if ($CI->db->table_exists(db_prefix() . 'reminder_amc_types')) {
    $CI->db->query('DROP TABLE ' . db_prefix() . 'reminder_amc_types');
}

// Clean up any tags associated with AMC records
$CI->db->where('rel_type', 'amc');
$CI->db->delete(db_prefix() . 'taggables');

