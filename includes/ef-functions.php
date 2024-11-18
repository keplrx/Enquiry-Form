<?php
// Don't allow direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Include class files
require_once ENQUIRY_FORM_PATH . 'includes/class-ef-ajax.php';
require_once ENQUIRY_FORM_PATH . 'includes/class-ef-post-type.php';
require_once ENQUIRY_FORM_PATH . 'includes/class-ef-db.php';
require_once ENQUIRY_FORM_PATH . 'includes/class-ef-email.php';
require_once ENQUIRY_FORM_PATH . 'includes/class-ef-logger.php';
require_once ENQUIRY_FORM_PATH . 'includes/class-ef-caching.php';

function ef_process_form_data($form_data) {
    try {
        EF_Logger::log('Processing form data', EF_Logger::INFO, ['data' => $form_data]);
        
        if (empty($form_data['email']) || empty($form_data['name'])) {
            throw new Exception('Required fields are missing');
        }
        
        $sanitized_data = ef_sanitize_form_data($form_data);
        
        // Validate the sanitized data
        $validation_errors = ef_validate_form_data($sanitized_data);
        if (!empty($validation_errors)) {
            throw new Exception('Validation failed: ' . implode(', ', $validation_errors));
        }
        
        $insert_result = ef_save_form_data($sanitized_data);
        
        if ($insert_result) {
            // Save cart items to the sanitized data for email use
            $sanitized_data['cart_items'] = WC()->cart->get_cart();
            
            try {
                $confirmation_result = ef_send_confirmation_email($sanitized_data);
                $notification_result = ef_send_notification_email($sanitized_data);
                
                EF_Logger::log(
                    'Email sending results',
                    EF_Logger::INFO,
                    [
                        'confirmation' => $confirmation_result,
                        'notification' => $notification_result
                    ]
                );
                
                if (!$confirmation_result || !$notification_result) {
                    EF_Logger::log(
                        'Email sending failed',
                        EF_Logger::WARNING,
                        ['error' => EF_Email::get_last_error()]
                    );
                }
            } catch (Exception $e) {
                EF_Logger::log(
                    'Email sending error',
                    EF_Logger::ERROR,
                    ['error' => $e->getMessage()]
                );
            }
            
            // Clear the cart after all operations are complete
            if (function_exists('WC') && isset(WC()->cart)) {
                WC()->cart->empty_cart();
            }
            
            return array(
                'success' => true,
                'message' => 'show_thank_you_popup'
            );
        }
        
        throw new Exception('Failed to save form data');
        
    } catch (Exception $e) {
        EF_Logger::log(
            'Form processing error',
            EF_Logger::ERROR,
            [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]
        );
        
        return array(
            'success' => false,
            'message' => 'An error occurred while processing your enquiry. Please try again later.'
        );
    }
}

function ef_save_form_data($form_data) {
    global $wpdb;
    $enquiries_table = $wpdb->prefix . 'ef_enquiries';
    $cart_items_table = $wpdb->prefix . 'ef_cart_items';

    $result = $wpdb->insert(
        $enquiries_table,
        array(
            'subject' => sanitize_text_field($form_data['subject']),
            'content' => sanitize_textarea_field($form_data['content']),
            'name' => sanitize_text_field($form_data['name']),
            'company' => sanitize_text_field($form_data['company']),
            'email' => sanitize_email($form_data['email']),
            'phone' => sanitize_text_field($form_data['phone']),
            'created_at' => current_time('mysql'),
            'processed' => 0,
            'status' => 'Unreplied'
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
    );

    if ($result !== false) {
        $enquiry_id = $wpdb->insert_id;
        $cart = WC()->cart;
        if ($cart) {
            foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                $wpdb->insert(
                    $cart_items_table,
                    array(
                        'enquiry_id' => $enquiry_id,
                        'product_name' => $product->get_name(),
                        'quantity' => $cart_item['quantity'],
                        'sku' => $product->get_sku()
                    ),
                    array('%d', '%s', '%d', '%s')
                );
            }
        }
    } else {
        error_log('Failed to insert form data into database: ' . $wpdb->last_error);
        error_log('Data: ' . print_r($form_data, true));
    }

    return $result !== false;
}

function ef_send_notification_email($form_data) {
    if (!class_exists('EF_Email')) {
        error_log('EF_Email class not found');
        return false;
    }
    $result = EF_Email::send_notification_email($form_data);
    if (!$result) {
        error_log('Failed to send notification email. Error: ' . EF_Email::get_last_error());
    }
    return $result;
}

function ef_get_email_content($form_data) {
    ob_start();
    include(ENQUIRY_FORM_PATH . 'emails/enquiry-notification-template.php');
    return ob_get_clean();
}

function ef_sanitize_form_data($form_data) {
    return array(
        'subject' => sanitize_text_field($form_data['subject']),
        'content' => sanitize_textarea_field($form_data['content']),
        'name' => sanitize_text_field($form_data['name']),
        'company' => sanitize_text_field($form_data['company']),
        'email' => sanitize_email($form_data['email']),
        'phone' => sanitize_text_field($form_data['phone']),
    );
}

function ef_test_form_submission($form_data) {
    // Sanitize the form data
    $sanitized_data = ef_sanitize_form_data($form_data);
    
    // Validate the sanitized data
    $errors = ef_validate_form_data($sanitized_data);

    if (!empty($errors)) {
        return "Submission failed: " . implode(', ', $errors);
    }

    // Actually save the data
    $saved = ef_save_form_data($sanitized_data);

    if ($saved) {
        // Attempt to send notification email
        $email_sent = ef_send_notification_email($sanitized_data);
        if ($email_sent) {
            return "Submission successful: Data saved and notification sent.";
        } else {
            return "Submission partially successful: Data saved but notification failed.";
        }
    } else {
        return "Submission failed: Unable to save data";
    }
}

function ef_send_confirmation_email($form_data) {
    if (!class_exists('EF_Email')) {
        error_log('EF_Email class not found');
        return false;
    }

    $result = EF_Email::send_confirmation_email($form_data);
    if (!$result) {
        error_log('Failed to send confirmation email to ' . $form_data['email'] . '. Error: ' . EF_Email::get_last_error());
    }
    return $result;
}

function ef_validate_form_data($form_data) {
    $errors = array();

    // Example validation: Check if email is valid
    if (!is_email($form_data['email'])) {
        $errors[] = 'Invalid email address.';
    }

    // Add more validation rules as needed
    // Example: Check if phone number is numeric
    if (!is_numeric($form_data['phone'])) {
        $errors[] = 'Phone number must be numeric.';
    }

    return $errors;
}

function ef_check_new_entries() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ef_enquiries';

    // Fetch entries that haven't been processed yet
    $new_entries = $wpdb->get_results("SELECT * FROM $table_name WHERE processed = 0");

    foreach ($new_entries as $entry) {
        // Send emails
        ef_send_notification_email((array)$entry);
        ef_send_confirmation_email((array)$entry);

        // Mark as processed
        $wpdb->update($table_name, array('processed' => 1), array('id' => $entry->id));
    }
}

// Schedule the event
if (!wp_next_scheduled('ef_check_new_entries_event')) {
    wp_schedule_event(time(), 'hourly', 'ef_check_new_entries_event');
}

add_action('ef_check_new_entries_event', 'ef_check_new_entries');

add_action('woocommerce_checkout_order_processed', 'ef_save_cart_items', 10, 1);

function ef_save_cart_items($order_id) {
    if (!class_exists('WC_Order')) {
        return;
    }

    $order = wc_get_order($order_id);
    $cart_items = $order->get_items();

    global $wpdb;
    $cart_items_table = $wpdb->prefix . 'ef_cart_items';

    foreach ($cart_items as $item_id => $item) {
        $product_name = $item->get_name();
        $quantity = $item->get_quantity();
        $sku = $item->get_product()->get_sku();

        $wpdb->insert(
            $cart_items_table,
            array(
                'enquiry_id' => $order_id,
                'product_name' => sanitize_text_field($product_name),
                'quantity' => intval($quantity),
                'sku' => sanitize_text_field($sku)
            ),
            array('%d', '%s', '%d', '%s')
        );
    }
}

add_action('wp_ajax_update_enquiry_status', 'update_enquiry_status');

function update_enquiry_status() {
    global $wpdb;
    $enquiry_id = intval($_POST['enquiry_id']);
    $status = sanitize_text_field($_POST['status']);
    $table_name = $wpdb->prefix . 'ef_enquiries';

    $result = $wpdb->update($table_name, array('status' => $status), array('id' => $enquiry_id));

    if ($result !== false) {
        wp_send_json_success('Status updated successfully.');
    } else {
        wp_send_json_error('Failed to update status in the database.');
    }
}

// Enqueue styles and scripts
if (!function_exists('enquiry_form_enqueue_assets')) {
    function enquiry_form_enqueue_assets() {
        wp_enqueue_style('enquiry-form-style', ENQUIRY_FORM_URL . 'assets/css/style.css', array(), ENQUIRY_FORM_VERSION);
        wp_enqueue_script('enquiry-form-script', ENQUIRY_FORM_URL . 'assets/js/script.js', array('jquery'), ENQUIRY_FORM_VERSION, true);
        wp_enqueue_style('twentytwentyfour-style', get_template_directory_uri() . '/style.css', array(), wp_get_theme()->get('Version'));
    }
}



