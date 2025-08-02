<?php

// Exit if accessed directly
if ( !defined('ABSPATH') ) {
    exit;
}

function acmi_manager_create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_aircraft_types = $wpdb->prefix . 'acmi_aircraft_types';
    $table_acmi = $wpdb->prefix . 'acmi_availabilities';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Table 1: Aircraft Types
    $sql1 = "CREATE TABLE $table_aircraft_types (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        body_type VARCHAR(50) NOT NULL,
        aircraft_type VARCHAR(100) NOT NULL,
        configuration VARCHAR(100) DEFAULT NULL,
        image_url TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Table 2: ACMI Availabilities
    $sql2 = "CREATE TABLE $table_acmi (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        body_type VARCHAR(50) NOT NULL,
        aircraft_type_id BIGINT(20) UNSIGNED NOT NULL,
        region VARCHAR(100) NOT NULL,
        approvals TEXT DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (aircraft_type_id) REFERENCES $table_aircraft_types(id) ON DELETE CASCADE
    ) $charset_collate;";

    dbDelta($sql1);
    dbDelta($sql2);
}
