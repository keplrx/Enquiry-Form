<?php

// Don't allow direct access to the file
if (!defined('ABSPATH')) {
    exit;
}
// AJAX handler for form submission
function ef_handle_form_submission() {
    try {
        check_ajax_referer('ef_form_nonce', 'security');

        if (!isset($_POST['form_data'])) {
            throw new Exception('No form data received');
        }

        $form_data = $_POST['form_data'];
        
        // Validate required fields
        $required_fields = ['email', 'name', 'subject', 'content'];
        foreach ($required_fields as $field) {
            if (empty($form_data[$field])) {
                throw new Exception("Required field '$field' is missing");
            }
        }

        // Add cart items
        if (function_exists('WC') && isset(WC()->cart)) {
            $form_data['cart_items'] = WC()->cart->get_cart();
        } else {
            EF_Logger::log('WooCommerce cart not available', EF_Logger::WARNING);
        }

        EF_Logger::log('Processing form submission', EF_Logger::INFO, [
            'email' => $form_data['email'],
            'name' => $form_data['name']
        ]);

        $result = ef_process_form_data($form_data);

        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            throw new Exception($result['message']);
        }

    } catch (Exception $e) {
        EF_Logger::log('Form submission failed', EF_Logger::ERROR, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_ef_submit_form', 'ef_handle_form_submission');
add_action('wp_ajax_nopriv_ef_submit_form', 'ef_handle_form_submission');
