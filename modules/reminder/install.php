<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Create tblreminder_amc_types table
if (!$CI->db->table_exists(db_prefix() . 'reminder_amc_types')) {
    $CI->db->query('CREATE TABLE ' . db_prefix() . "reminder_amc_types (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `description` TEXT NULL,
        `order` INT(11) NOT NULL DEFAULT 0,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `active` (`active`),
        KEY `order` (`order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

// Create tblreminder_amc table
if (!$CI->db->table_exists(db_prefix() . 'reminder_amc')) {
    $CI->db->query('CREATE TABLE ' . db_prefix() . "reminder_amc (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `client_id` INT(11) NOT NULL,
        `amc_location` VARCHAR(255) NOT NULL,
        `amc_type_id` INT(11) NULL,
        `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `start_date` DATE NULL,
        `end_date` DATE NULL,
        `status` VARCHAR(50) NOT NULL DEFAULT 'ongoing',
        `notes` TEXT NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `client_id` (`client_id`),
        KEY `amc_type_id` (`amc_type_id`),
        KEY `end_date` (`end_date`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

// Create tblreminder_amc_history table
if (!$CI->db->table_exists(db_prefix() . 'reminder_amc_history')) {
    $CI->db->query('CREATE TABLE ' . db_prefix() . "reminder_amc_history (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `amc_id` INT(11) NOT NULL,
        `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `start_date` DATE NULL,
        `end_date` DATE NULL,
        `notes` TEXT NULL,
        `renewed_by_staff_id` INT(11) NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `amc_id` (`amc_id`),
        KEY `renewed_by_staff_id` (`renewed_by_staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

// Add amc_type_id column to existing reminder_amc table if it doesn't exist
if ($CI->db->table_exists(db_prefix() . 'reminder_amc')) {
    if (!$CI->db->field_exists('amc_type_id', db_prefix() . 'reminder_amc')) {
        $CI->db->query('ALTER TABLE ' . db_prefix() . 'reminder_amc ADD `amc_type_id` INT(11) NULL AFTER `amc_location`');
        $CI->db->query('ALTER TABLE ' . db_prefix() . 'reminder_amc ADD KEY `amc_type_id` (`amc_type_id`)');
    }
}

// Add renewed_by_staff_id column to reminder_amc_history table if it doesn't exist
if ($CI->db->table_exists(db_prefix() . 'reminder_amc_history')) {
    if (!$CI->db->field_exists('renewed_by_staff_id', db_prefix() . 'reminder_amc_history')) {
        $CI->db->query('ALTER TABLE ' . db_prefix() . 'reminder_amc_history ADD `renewed_by_staff_id` INT(11) NULL AFTER `notes`');
        $CI->db->query('ALTER TABLE ' . db_prefix() . 'reminder_amc_history ADD KEY `renewed_by_staff_id` (`renewed_by_staff_id`)');
    }
}

