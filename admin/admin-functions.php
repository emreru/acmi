<?php
// Admin Menüleri Oluştur
function acmi_manager_admin_menu() {
    add_menu_page(
        'ACMI Manager',
        'ACMI Manager',
        'manage_options',
        'acmi-dashboard',
        'acmi_manager_render_dashboard',
        'dashicons-airplane',
        26
    );

    add_submenu_page(
        'acmi-dashboard',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'acmi-dashboard',
        'acmi_manager_render_dashboard'
    );

    add_submenu_page(
        'acmi-dashboard',
        'Aircraft Types',
        'Aircraft Types',
        'manage_options',
        'acmi-aircraft-types',
        'acmi_manager_render_aircraft_types'
    );

    add_submenu_page(
        'acmi-dashboard',
        'Add ACMI',
        'Add ACMI',
        'manage_options',
        'acmi-add-acmi',
        'acmi_manager_render_add_acmi'
    );

    add_submenu_page(
        'acmi-dashboard',
        'ACMI List',
        'ACMI List',
        'manage_options',
        'acmi-acmi-list',
        'acmi_manager_render_acmi_list'
    );
}
add_action('admin_menu', 'acmi_manager_admin_menu');

// Sayfa Gösterimleri
function acmi_manager_render_dashboard() {
    include plugin_dir_path(__FILE__) . 'admin-dashboard.php';
}

function acmi_manager_render_aircraft_types() {
    include plugin_dir_path(__FILE__) . 'admin-aircraft-types.php';
}

function acmi_manager_render_add_acmi() {
    include plugin_dir_path(__FILE__) . 'admin-add-acmi.php';
}

function acmi_manager_render_acmi_list() {
    include plugin_dir_path(__FILE__) . 'admin-acmi-list.php';
}
