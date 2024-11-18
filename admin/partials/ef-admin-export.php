<?php

if (!defined('ABSPATH')) {
    exit;
}

function display_enquiry_export_page() {
    // Display error message if exists
    $error_message = get_transient('ef_export_error');
    if ($error_message) {
        delete_transient('ef_export_error');
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
        <?php
    }

    // // Check if the current user is "Nash_Intern"
    // $current_user = wp_get_current_user();
    // if ($current_user->user_login !== 'Nash_Intern') {
    //     echo '<div class="wrap"><h1>Work in Progress</h1><p>This export page is currently under development. Please check back later.</p></div>';
    //     echo '<br>';
    //     echo '<p>If you need to access this page, please contact the developer at <a href="mailto:nashc.mad@gmail.com">nashc.mad@gmail.com</a>.</p>';
    //     echo '<br>';
    //     echo '<a href="?page=ef-settings">Back to Settings</a>';
    //     return;
    // }

    // Display the export form
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="get" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="export_enquiries">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="status">Status</label></th>
                    <td>
                        <select name="status" id="status">
                            <option value="all">All</option>
                            <option value="Unreplied">Unreplied</option>
                            <option value="Replied">Replied</option>
                            <option value="Done">Done</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="start_date">Start Date (optional)</label></th>
                    <td><input type="date" name="start_date" id="start_date"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="end_date">End Date (optional)</label></th>
                    <td><input type="date" name="end_date" id="end_date"></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" class="button button-primary" value="Export CSV">
            </p>
        </form>
    </div>
    <?php
}

function export_enquiries_to_csv() {
    try {
        // Verify user permissions
        if (!current_user_can('manage_options')) {
            throw new Exception('Insufficient permissions to export data.');
        }

        global $wpdb;
        
        // Get and sanitize parameters
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

        // Define table names
        $enquiries_table = $wpdb->prefix . 'ef_enquiries';
        $cart_items_table = $wpdb->prefix . 'ef_cart_items';

        // Build the query
        $query = "SELECT e.id AS enquiry_id, e.subject, e.content, e.name, e.company, 
                        e.email, e.phone, e.created_at, e.processed, e.status, 
                        ci.product_name, ci.quantity, ci.sku
                 FROM $enquiries_table AS e
                 LEFT JOIN $cart_items_table AS ci ON e.id = ci.enquiry_id
                 WHERE 1=1";
        
        $query_params = array();

        // Add status filter
        if ($status !== 'all') {
            $query .= " AND e.status = %s";
            $query_params[] = $status;
        }

        // Add date range filters
        if (!empty($start_date)) {
            $query .= " AND e.created_at >= %s";
            $query_params[] = $start_date . ' 00:00:00';
        }
        if (!empty($end_date)) {
            $query .= " AND e.created_at <= %s";
            $query_params[] = $end_date . ' 23:59:59';
        }

        $query .= " ORDER BY e.id, ci.id";

        // Prepare and execute the query
        $final_query = $query_params ? $wpdb->prepare($query, $query_params) : $query;
        $results = $wpdb->get_results($final_query, ARRAY_A);

        if (!$results) {
            throw new Exception('No data available for the selected criteria.');
        }

        // Set headers for CSV export
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename=enquiries_export_' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        if ($output === false) {
            throw new Exception('Failed to open output stream');
        }
        
        // Write CSV headers
        fputcsv($output, array('Enquiry ID', 'Subject', 'Content', 'Name', 'Company', 
                              'Email', 'Phone', 'Created At', 'Processed', 'Status', 
                              'Product Name', 'Quantity', 'SKU'));

        // Write data rows
        foreach ($results as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        
        // Log successful export
        EF_Logger::log('CSV export completed successfully', EF_Logger::INFO, [
            'count' => count($results),
            'status_filter' => $status,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'user' => wp_get_current_user()->user_login
        ]);

        exit;

    } catch (Exception $e) {
        EF_Logger::log('CSV export failed', EF_Logger::ERROR, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'status_filter' => $status ?? 'all',
            'start_date' => $start_date ?? '',
            'end_date' => $end_date ?? '',
            'user' => wp_get_current_user()->user_login
        ]);

        // Store the error message in transient
        set_transient('ef_export_error', $e->getMessage(), 45);
        
        // Redirect back to the export page
        wp_redirect(add_query_arg(
            array('page' => 'ef-export', 'error' => '1'),
            admin_url('admin.php')
        ));
        exit;
    }
}

// Hook the export function
add_action('admin_post_export_enquiries', 'export_enquiries_to_csv');
