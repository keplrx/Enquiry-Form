<?php
// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// include_once ENQUIRY_FORM_PATH . 'admin/partials/ef-admin-notices.php';
include_once ENQUIRY_FORM_PATH . 'admin/partials/ef-admin-home.php';
include_once ENQUIRY_FORM_PATH . 'admin/partials/ef-admin-settings.php';
include_once ENQUIRY_FORM_PATH . 'admin/partials/ef-admin-table.php';
include_once ENQUIRY_FORM_PATH . 'admin/partials/ef-admin-details.php';
include_once ENQUIRY_FORM_PATH . 'admin/partials/ef-admin-ajax-handler.php';
include_once ENQUIRY_FORM_PATH . 'admin/partials/ef-admin-notices.php';
include_once ENQUIRY_FORM_PATH . 'admin/partials/ef-admin-export.php';

function enquiry_form_admin_menu() {
    // Main Menu (Parent)
    add_menu_page(
        'Enquiry Form', // Page title
        'Enquiry Form', // Menu title
        'manage_options', // Capability
        'enquiry-form', // Menu slug
        'display_home_page', // Function to display the page
        'dashicons-list-view', // Icon
        6 // Position
    );

    // Submenu - Home
    add_submenu_page(
        'enquiry-form', // Parent slug (must match the menu slug of the parent)
        'Home', // Page title
        'Home', // Menu title
        'manage_options', // Capability
        'enquiry-form', // Menu slug (same as the parent for the main page)
        'display_home_page' // Function to display the page
    );

       // Submenu - Home
    add_submenu_page(
        'enquiry-form', // Parent slug (must match the menu slug of the parent)
        'Enquiries', // Page title
        'Enquiries', // Menu title
        'manage_options', // Capability
        'ef-enquiries', // Menu slug 
        'display_enquiry_orders_page' // Function to display the page
    );

    // Submenu - Settings
    add_submenu_page(
        'enquiry-form', // Parent slug
        'Settings', // Page title
        'Settings', // Menu title
        'manage_options', // Capability
        'ef-settings', // Menu slug
        'display_enquiry_settings_page' // Function to display the page
    );

    // Submenu - Export
    add_submenu_page(
        'enquiry-form', // Parent slug
        'Export', // Page title
        'Export', // Menu title
        'manage_options', // Capability
        'ef-export', // Menu slug
        'display_enquiry_export_page' // Function to display the page
    );    
    
    // Hidden Submenu - Enquiry Details (Not visible in the menu)
    add_submenu_page(
        null, // No parent menu (null to hide it from menu)
        'Enquiry Details', // Page title
        'Enquiry Details', // Menu title (Not visible)
        'manage_options', // Capability
        'enquiry-details', // Menu slug
        'display_enquiry_details_page' // Function to display the page
    );
}

add_action('admin_menu', 'enquiry_form_admin_menu');

// function enquiry_form_enqueue_admin_assets() {
//     // Enqueue existing admin script
//     wp_enqueue_script('enquiry-form-admin-script', ENQUIRY_FORM_URL . 'admin/js/admin-script.js', array('jquery'), ENQUIRY_FORM_VERSION, true);
    
//     // Enqueue admin stylesheet
//     wp_enqueue_style('enquiry-form-admin-style', ENQUIRY_FORM_URL . 'admin/css/admin-style.css', array(), ENQUIRY_FORM_VERSION);
// }
// add_action('admin_enqueue_scripts', 'enquiry_form_enqueue_admin_assets');

function enquiry_form_enqueue_admin_assets($hook) {
    // Remove the condition to allow styles to load on all admin pages
    // if (strpos($hook, 'enquiry-form') === false) {
    //     return;
    // }

    // Enqueue Chart.js
    wp_enqueue_script(
        'chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js',
        array(),
        '3.7.0',
        true
    );

    // Enqueue ChartDataLabels plugin
    wp_enqueue_script(
        'chartjs-plugin-datalabels',
        'https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels',
        array('chartjs'),
        '2.0.0',
        true
    );

    // Your admin script - using the working path structure
    wp_enqueue_script(
        'ef-admin-script',
        ENQUIRY_FORM_URL . 'admin/js/admin-script.js',
        array('jquery', 'chartjs', 'chartjs-plugin-datalabels'),
        ENQUIRY_FORM_VERSION,
        true
    );

    // Enqueue admin styles - using the working path structure
    wp_enqueue_style(
        'ef-admin-style',
        ENQUIRY_FORM_URL . 'admin/css/admin-style.css',
        array(),
        ENQUIRY_FORM_VERSION
    );
}
add_action('admin_enqueue_scripts', 'enquiry_form_enqueue_admin_assets');

