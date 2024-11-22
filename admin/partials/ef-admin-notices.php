<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function ef_check_woocommerce_status() {
    // Only run this check on admin pages
    if (!is_admin()) {
        return;
    }

    // Get the current screen
    $screen = get_current_screen();

    // Check if we're on a WordPress core page or an EF plugin page
    $is_wp_core = in_array($screen->base, array('dashboard', 'update-core', 'plugins', 'themes', 'users', 'tools', 'options-general'));
    $is_ef_page = strpos($screen->base, 'enquiry-form') !== false;

    if ($is_wp_core || $is_ef_page) {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            // WooCommerce is not active, display the notice
            add_action('admin_notices', 'ef_display_woocommerce_missing_notice');
        }
    }
}
function ef_check_wp_mail_smtp_status() {
    // Only run this check on admin pages
    if (!is_admin()) {
        return;
    }

    // Get the current screen
    $screen = get_current_screen();

    // Check if we're on a WordPress core page or an EF plugin page
    $is_wp_core = in_array($screen->base, array('dashboard', 'update-core', 'plugins', 'themes', 'users', 'tools', 'options-general'));
    $is_ef_page = strpos($screen->base, 'enquiry-form') !== false;

    if ($is_wp_core || $is_ef_page) {
        // Check if WP Mail SMTP is active
        if (!class_exists('WP_Mail_Smtp')) {
            // WP Mail SMTP is not active, display the notice
            add_action('admin_notices', 'ef_display_wp_mail_smtp_missing_notice');
        }
    }
}

// Run the WP Mail SMTP check during the admin page load
add_action('admin_head', 'ef_check_wp_mail_smtp_status');
// Run this check during the admin page load
add_action('admin_head', 'ef_check_woocommerce_status');

function ef_display_woocommerce_missing_notice() {
    $class = 'notice notice-warning is-dismissible';
    $prefix = '<strong>' . __('Enquiry Form:', 'enquiry-form') . '</strong> ';
    $message = __('WooCommerce seems to be missing or deactivated. While you may still access and view enquiries, some functionalities may be limited. ', 'enquiry-form');
    
    // Get the URL of the plugins page
    $plugins_url = admin_url('plugins.php');
    
    // Add the link to the plugins page inline
    $message .= sprintf(__('Activate WooCommerce <a href="%s">here</a>.', 'enquiry-form'), esc_url($plugins_url));

    printf('<div class="%1$s"><p>%2$s%3$s</p></div>', esc_attr($class), $prefix, $message);
}

function ef_display_wp_mail_smtp_missing_notice() {
    $class = 'notice notice-warning is-dismissible';
    $prefix = '<strong>' . __('Enquiry Form:', 'enquiry-form') . '</strong> ';
    $message = __('WP Mail SMTP seems to be missing or deactivated. While you may still access and view enquiries, email functionalities may be disabled. ', 'enquiry-form');
    
    // Get the URL of the plugins page
    $plugins_url = admin_url('plugins.php');
    
    // Add the link to the plugins page inline
    $message .= sprintf(__('Activate WP Mail SMTP <a href="%s">here</a>.', 'enquiry-form'), esc_url($plugins_url));

    printf('<div class="%1$s"><p>%2$s%3$s</p></div>', esc_attr($class), $prefix, $message);
}