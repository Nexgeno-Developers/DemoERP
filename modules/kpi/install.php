<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Create table for daily attendance points
if (!$CI->db->table_exists(db_prefix() . 'kpi_daily_points')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'kpi_daily_points` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `staff_id` int(11) NOT NULL,
        `date` date NOT NULL,
        `points` int(11) NOT NULL DEFAULT 0,
        `shift_time` varchar(50) NULL,
        `check_in_time` varchar(50) NULL,
        `created_at` datetime NOT NULL,
        `updated_at` datetime NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_staff_date` (`staff_id`, `date`),
        KEY `idx_staff_id` (`staff_id`),
        KEY `idx_date` (`date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
}

// Create table for monthly points totals
if (!$CI->db->table_exists(db_prefix() . 'kpi_monthly_points')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'kpi_monthly_points` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `staff_id` int(11) NOT NULL,
        `year` int(4) NOT NULL,
        `month` int(2) NOT NULL,
        `total_points` int(11) NOT NULL DEFAULT 0,
        `created_at` datetime NOT NULL,
        `updated_at` datetime NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_staff_year_month` (`staff_id`, `year`, `month`),
        KEY `idx_staff_id` (`staff_id`),
        KEY `idx_year_month` (`year`, `month`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
}
