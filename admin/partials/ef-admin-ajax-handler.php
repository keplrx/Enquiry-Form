<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_bulk_update_enquiry_status', 'bulk_update_enquiry_status');

function bulk_update_enquiry_status() {
    try {
        if (!current_user_can('manage_options')) {
            throw new Exception('Insufficient permissions');
        }

        global $wpdb;
        $enquiries_table = $wpdb->prefix . 'ef_enquiries';

        $enquiries = isset($_POST['enquiries']) ? $_POST['enquiries'] : [];
        $new_status = sanitize_text_field($_POST['status']);

        if (empty($enquiries) || !in_array($new_status, ['Unreplied', 'Replied', 'Done'])) {
            throw new Exception('Invalid parameters provided');
        }

        $enquiries = array_map('intval', $enquiries);
        $placeholders = implode(',', array_fill(0, count($enquiries), '%d'));

        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $enquiries_table SET status = %s WHERE id IN ($placeholders)",
            array_merge([$new_status], $enquiries)
        ));

        if ($result === false) {
            throw new Exception('Database update failed: ' . $wpdb->last_error);
        }

        EF_Logger::log(
            'Bulk status update successful',
            EF_Logger::INFO,
            [
                'enquiries' => $enquiries,
                'new_status' => $new_status
            ]
        );

        wp_send_json_success();

    } catch (Exception $e) {
        EF_Logger::log(
            'Bulk status update failed',
            EF_Logger::ERROR,
            [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]
        );
        wp_send_json_error($e->getMessage());
    }
}

add_action('wp_ajax_bulk_delete_enquiries', 'bulk_delete_enquiries');

function bulk_delete_enquiries() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }

    global $wpdb;
    $enquiries_table = $wpdb->prefix . 'ef_enquiries';
    $enquiries = isset($_POST['enquiries']) ? array_map('intval', $_POST['enquiries']) : array();

    if (empty($enquiries)) {
        wp_send_json_error('No enquiries selected');
    }

    $placeholders = implode(',', array_fill(0, count($enquiries), '%d'));
    $query = $wpdb->prepare("DELETE FROM $enquiries_table WHERE id IN ($placeholders)", $enquiries);
    $result = $wpdb->query($query);

    if ($result === false) {
        wp_send_json_error('Error deleting enquiries');
    } else {
        wp_send_json_success(array('deleted' => $result));
    }
}

function ef_update_enquiry_status() {
    try {
        // Verify user capabilities and nonce
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        check_ajax_referer('ef_admin_nonce', 'security');

        // Validate and sanitize inputs
        $enquiry_id = isset($_POST['enquiry_id']) ? absint($_POST['enquiry_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        if (!$enquiry_id || !in_array($status, ['Unreplied', 'Replied', 'Done'], true)) {
            wp_send_json_error('Invalid parameters');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ef_enquiries';

        // Use prepared statement for update
        $result = $wpdb->update(
            $table_name,
            ['status' => $status],
            ['id' => $enquiry_id],
            ['%s'],
            ['%d']
        );

        if ($result === false) {
            throw new Exception($wpdb->last_error);
        }

        // Clear cache for this enquiry
        EF_Cache::get_instance()->clear_enquiry_cache($enquiry_id);

        wp_send_json_success();

    } catch (Exception $e) {
        EF_Logger::log('Status update failed', EF_Logger::ERROR, [
            'enquiry_id' => $enquiry_id ?? 'unknown',
            'error' => $e->getMessage()
        ]);
        wp_send_json_error('Update failed: ' . $e->getMessage());
    }
}
add_action('wp_ajax_update_enquiry_status', 'ef_update_enquiry_status');
