<?php
// Don't allow direct access to the file
if (!defined('ABSPATH')) {
    exit;
}
// Include necessary files
require_once plugin_dir_path(__FILE__) . 'partials/ef-public-ajax-handler.php';
require_once plugin_dir_path(__FILE__) . 'partials/ef-public-display-cart.php';
require_once plugin_dir_path(__FILE__) . 'partials/ef-public-display-form.php';
require_once plugin_dir_path(__FILE__) . 'partials/ef-public-thankyou.php';
require_once plugin_dir_path(__FILE__) . 'partials/ef-shortcode.php';
// Enqueue public styles
function ef_enqueue_public_styles(): void {
    wp_enqueue_style('ef-public-style', plugin_dir_url(__FILE__) . 'css/public-style.css', array(), '1.0.0', 'all');
}
add_action('wp_enqueue_scripts', 'ef_enqueue_public_styles');

// Enqueue public scripts
function ef_enqueue_public_scripts(): void {
    wp_enqueue_script('ef-public-script', plugin_dir_url(__FILE__) . 'js/public-script.js', array('jquery'), '1.0.0', true);

    // Localize the script with new data
    $script_data = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'update_cart_nonce' => wp_create_nonce('update_cart_nonce'),
        'form_nonce' => wp_create_nonce('ef_form_nonce')
    );
    wp_localize_script('ef-public-script', 'efPublicParams', $script_data);
}
add_action('wp_enqueue_scripts', 'ef_enqueue_public_scripts');

function ef_get_thank_you_content() {
    check_ajax_referer('ef_form_nonce', 'security');
    $thank_you_content = ef_display_thank_you();
    wp_send_json_success($thank_you_content);
}
add_action('wp_ajax_ef_get_thank_you_content', 'ef_get_thank_you_content');
add_action('wp_ajax_nopriv_ef_get_thank_you_content', 'ef_get_thank_you_content');
