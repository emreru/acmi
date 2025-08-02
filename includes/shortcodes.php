<?php

// Aktif uçakları listeleyen kısa kod
function acmi_display_shortcode() {
    ob_start();
    include plugin_dir_path(__FILE__) . '../frontend/acmi-display.php';
    return ob_get_clean();
}
add_shortcode('acmi_display', 'acmi_display_shortcode');

// Detay sayfası için kısa kod (acmi-details.php)
function acmi_details_shortcode() {
    ob_start();
    include plugin_dir_path(__FILE__) . '../frontend/acmi-details.php';
    return ob_get_clean();
}
add_shortcode('acmi_details', 'acmi_details_shortcode');
