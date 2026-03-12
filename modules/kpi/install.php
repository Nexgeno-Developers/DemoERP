<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!$CI->db->table_exists(db_prefix() . 'attendance_kpi')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "attendance_kpi` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `staff_id` INT(11) NOT NULL,
        `date` DATE NOT NULL,
        `check_in` TIME NULL,
        `daily_points` INT NOT NULL DEFAULT 0,
        `streak_points` INT NOT NULL DEFAULT 0,
        `bonus_points` INT NOT NULL DEFAULT 0,
        `penalty_points` INT NOT NULL DEFAULT 0,
        `total_points` INT NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_kpi` (`staff_id`, `date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

if (!$CI->db->table_exists(db_prefix() . 'attendance_kpi_monthly')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "attendance_kpi_monthly` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `staff_id` INT(11) NOT NULL,
        `month` VARCHAR(7) NOT NULL, /* YYYY-MM */
        `daily_total` INT NOT NULL DEFAULT 0,
        `bonus_total` INT NOT NULL DEFAULT 0,
        `penalty_total` INT NOT NULL DEFAULT 0,
        `streak_total` INT NOT NULL DEFAULT 0,
        `final_score` INT NOT NULL DEFAULT 0,
        `grade` VARCHAR(20) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_month` (`staff_id`, `month`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

if (!$CI->db->table_exists(db_prefix() . 'attendance_kpi_streaks')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "attendance_kpi_streaks` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `staff_id` INT NOT NULL,
        `current_streak` INT NOT NULL DEFAULT 0,
        `last_date` DATE NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_streak` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}