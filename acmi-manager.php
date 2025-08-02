<?php
/*
Plugin Name: ACMI Manager
Plugin URI: https://yellowbrotherstechnology.com/
Description: Manage rental aircraft (ACMI) availability and display active types.
Version: 1.6
Author: Yellow Brothers Technology
Author URI: https://yellowbrotherstechnology.com/
*/

// Güvenlik için doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// EKLENTİ YAŞAM DÖNGÜSÜ (Aktivasyon / Deaktivasyon)
// =============================================================================

/**
 * Eklenti etkinleştirildiğinde çalışacak fonksiyon. Gerekli veritabanı tablolarını oluşturur.
 */
function acmi_manager_activate() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $charset_collate = $wpdb->get_charset_collate();

    // 1. AOG Kontakları Tablosu
    $table_aog_contacts = $wpdb->prefix . 'acmi_aog_contacts';
    $sql_aog_contacts = "CREATE TABLE $table_aog_contacts ( id mediumint(9) NOT NULL AUTO_INCREMENT, name varchar(255) DEFAULT '' NOT NULL, email varchar(255) NOT NULL, created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL, PRIMARY KEY  (id), UNIQUE KEY email (email) ) $charset_collate;";
    dbDelta($sql_aog_contacts);

    // 2. E-posta Kuyruk Tablosu
    $table_email_queue = $wpdb->prefix . 'acmi_email_queue';
    $sql_email_queue = "CREATE TABLE $table_email_queue ( id bigint(20) NOT NULL AUTO_INCREMENT, recipient_email varchar(255) NOT NULL, subject text NOT NULL, message longtext NOT NULL, status varchar(20) DEFAULT 'pending' NOT NULL, created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL, sent_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL, PRIMARY KEY  (id), KEY status (status) ) $charset_collate;";
    dbDelta($sql_email_queue);
}
register_activation_hook(__FILE__, 'acmi_manager_activate');

/**
 * Eklenti devre dışı bırakıldığında zamanlanmış görevi temizler.
 */
function acmi_manager_deactivate() {
    $timestamp = wp_next_scheduled('acmi_process_email_queue_hook');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'acmi_process_email_queue_hook');
    }
}
register_deactivation_hook(__FILE__, 'acmi_manager_deactivate');


// =============================================================================
// GEREKLİ DOSYALARI INCLUDE ETME
// =============================================================================
include plugin_dir_path(__FILE__) . 'includes/database.php';
include plugin_dir_path(__FILE__) . 'includes/enqueue.php';
include plugin_dir_path(__FILE__) . 'includes/shortcodes.php';

// =============================================================================
// YÖNETİM PANELİ MENÜLERİ VE SAYFA YÜKLEYİCİLERİ
// =============================================================================

// Callback fonksiyonları (Sayfa içeriklerini yükler)
function acmi_load_dashboard_page() { include plugin_dir_path(__FILE__) . 'admin/admin-dashboard.php'; }
function acmi_load_aircraft_types_page() { include plugin_dir_path(__FILE__) . 'admin/admin-aircraft-types.php'; }
function acmi_load_add_new_page() { include plugin_dir_path(__FILE__) . 'admin/admin-add-acmi.php'; }
function acmi_load_list_page() { include plugin_dir_path(__FILE__) . 'admin/admin-acmi-list.php'; }
function acmi_load_edit_page() { include plugin_dir_path(__FILE__) . 'admin/admin-edit-acmi.php'; }
function acmi_load_contacts_page() { include plugin_dir_path(__FILE__) . 'admin/admin-contacts.php'; }
function acmi_load_aog_contacts_page() { include plugin_dir_path(__FILE__) . 'admin/admin-aog-contacts.php'; }
function acmi_load_aog_alert_form_page() { include plugin_dir_path(__FILE__) . 'admin/admin-aog-alert-form.php'; }

// Menüleri WordPress'e kaydeder
function acmi_manager_setup_admin_pages() {
    add_menu_page('ACMI Dashboard', 'ACMI Manager', 'manage_options', 'acmi-dashboard', 'acmi_load_dashboard_page', 'dashicons-airplane', 25);
    add_submenu_page('acmi-dashboard', 'ACMI Dashboard', 'Dashboard', 'manage_options', 'acmi-dashboard', 'acmi_load_dashboard_page');
    add_submenu_page('acmi-dashboard', 'Aircraft Types', 'Aircraft Types', 'manage_options', 'acmi-aircraft-types', 'acmi_load_aircraft_types_page');
    add_submenu_page('acmi-dashboard', 'Add New ACMI', 'Add ACMI', 'manage_options', 'acmi-add-acmi', 'acmi_load_add_new_page');
    add_submenu_page('acmi-dashboard', 'ACMI List', 'ACMI List', 'manage_options', 'acmi-acmi-list', 'acmi_load_list_page');
    add_submenu_page('acmi-dashboard', 'Contact Form Leads', 'Contacts', 'manage_options', 'acmi-contacts', 'acmi_load_contacts_page');
    add_submenu_page('acmi-dashboard', 'AOG Contacts', 'AOG Contacts', 'manage_options', 'acmi-aog-contacts', 'acmi_load_aog_contacts_page');
    add_submenu_page('acmi-dashboard', 'Send AOG Alert', '<span style="color:#ff6f61;font-weight:bold;">Send AOG Alert</span>', 'manage_options', 'acmi-aog-alert-form', 'acmi_load_aog_alert_form_page');
    add_submenu_page(null, 'Edit ACMI Record', 'Edit ACMI', 'manage_options', 'acmi-edit-acmi', 'acmi_load_edit_page');
}
add_action('admin_menu', 'acmi_manager_setup_admin_pages');


// =============================================================================
// WP-CRON (OTOMATİK E-POSTA GÖNDERİMİ) SİSTEMİ
// =============================================================================

// Cron için özel zaman aralığı ekler
function acmi_add_cron_intervals($schedules) {
    $schedules['five_minutes'] = ['interval' => 300, 'display'  => esc_html__('Every Five Minutes')];
    return $schedules;
}
add_filter('cron_schedules', 'acmi_add_cron_intervals');

// Cron görevini zamanlar
function acmi_schedule_email_cron_job() {
    if (!wp_next_scheduled('acmi_process_email_queue_hook')) {
        wp_schedule_event(time(), 'five_minutes', 'acmi_process_email_queue_hook');
    }
}
add_action('init', 'acmi_schedule_email_cron_job');

// Kuyruktaki e-postaları gönderir
function acmi_process_email_queue() {
    global $wpdb;
    $table_queue = $wpdb->prefix . 'acmi_email_queue';
    $emails_to_send = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_queue WHERE status = %s ORDER BY created_at ASC LIMIT %d", 'pending', 20));

    if (empty($emails_to_send)) { return; }
    
    add_filter('wp_mail_content_type', function() { return 'text/html'; });
    foreach ($emails_to_send as $email) {
        $sent = wp_mail($email->recipient_email, $email->subject, $email->message);
        $new_status = $sent ? 'sent' : 'failed';
        $wpdb->update($table_queue, ['status'  => $new_status, 'sent_at' => current_time('mysql')], ['id' => $email->id]);
    }
    remove_filter('wp_mail_content_type', function() { return 'text/html'; });
}
add_action('acmi_process_email_queue_hook', 'acmi_process_email_queue');