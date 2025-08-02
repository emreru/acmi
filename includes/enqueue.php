<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

function acmi_enqueue_styles() {
    wp_enqueue_style('acmi-bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');
    wp_enqueue_style('acmi-style', plugin_dir_url(__FILE__) . '../assets/css/acmi-style.css', array(), '1.0');
}
add_action('wp_enqueue_scripts', 'acmi_enqueue_styles');
