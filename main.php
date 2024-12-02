<?php
/*
Plugin Name: Enquiry Form
Description: A plugin made by an intern at KBSS :)
Version: 0.9.0 
Author: Nash Madrid
Author URI: https://sites.google.com/view/portfoliobynashmadrid/home
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

// Copyright (C) 2024  Nash Madrid

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with this program; if not, see
// <https://www.gnu.org/licenses/>.



// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}   

// Define plugin constants
define('ENQUIRY_FORM_VERSION', '1.0');
define('ENQUIRY_FORM_PATH', plugin_dir_path(__FILE__));
define('ENQUIRY_FORM_URL', plugin_dir_url(__FILE__));



// Include logger class
require_once ENQUIRY_FORM_PATH . 'includes/class-ef-logger.php';

// Initialize logger
EF_Logger::getInstance();

// Include necessary files

include_once ENQUIRY_FORM_PATH . 'includes/ef-functions.php';  // Core functions
include_once ENQUIRY_FORM_PATH . 'admin/ef-admin.php';      // Admin dashboard settings
include_once ENQUIRY_FORM_PATH . 'public/ef-public.php';      // Public display settings


// Hook to initialize plugin
function enquiry_form_init() {
    
    // Register styles and scripts
    enquiry_form_enqueue_assets();

}
add_action('init', 'enquiry_form_init');

// Enqueue styles and scripts
if (!function_exists('enquiry_form_enqueue_assets')) {
    function enquiry_form_enqueue_assets() {
        wp_enqueue_style('enquiry-form-style', ENQUIRY_FORM_URL . 'assets/css/style.css', array(), ENQUIRY_FORM_VERSION);
        wp_enqueue_script('enquiry-form-script', ENQUIRY_FORM_URL . 'assets/js/script.js', array('jquery'), ENQUIRY_FORM_VERSION, true);
        wp_enqueue_style('twentytwentyfour-style', get_template_directory_uri() . '/style.css', array(), wp_get_theme()->get('Version'));
    }
}

// Register activation hook
register_activation_hook(__FILE__, ['EF_Activation', 'activate']);

// // Activation/Deactivation hooks
// function enquiry_form_activate() {
//     // Code to run when the plugin is activated
//     // Example: create custom database tables, set default options, etc.
//     include_once ENQUIRY_FORM_PATH . 'includes/enquiry_form_install.php';
//     enquiry_form_create_database();
// }
// register_activation_hook(__FILE__, 'enquiry_form_activate');

// function enquiry_form_deactivate() {
//     // Code to run when the plugin is deactivated
// }
// register_deactivation_hook(__FILE__, 'enquiry_form_deactivate');

// // Uninstall hook
// function enquiry_form_uninstall() {
//     // Code to run when the plugin is uninstalled
//     // Example: remove database tables, clear options, etc.
//     include_once ENQUIRY_FORM_PATH . 'includes/enquiry_form_uninstall.php';
//     enquiry_form_remove_database();
// }
// register_uninstall_hook(__FILE__, 'enquiry_form_uninstall');


?>
