<?php

if (!defined('ABSPATH')) {
    exit;
}

function display_enquiry_details_page() {
    global $wpdb;
    $enquiry_id = intval($_GET['enquiry_id']);
    $enquiries_table = $wpdb->prefix . 'ef_enquiries';
    $cart_items_table = $wpdb->prefix . 'ef_cart_items';

    $enquiry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $enquiries_table WHERE id = %d", $enquiry_id));
    $products = $wpdb->get_results($wpdb->prepare("SELECT * FROM $cart_items_table WHERE enquiry_id = %d", $enquiry_id));

    echo '<div class="wrap">';
    echo '<h1>Enquiry Details</h1>';
    
    echo '<table class="ef-enquiry-details">';
    echo '<tr><th>Name</th><td>' . esc_html($enquiry->name) . '</td></tr>';
    echo '<tr><th>Email</th><td>' . esc_html($enquiry->email) . '</td></tr>';
    echo '<tr><th>Phone</th><td>' . esc_html($enquiry->phone) . '</td></tr>';
    echo '<tr><th>Company</th><td>' . esc_html($enquiry->company) . '</td></tr>';
    echo '<tr><th>Date</th><td>' . esc_html($enquiry->created_at) . '</td></tr>';
    echo '<tr><th>Status</th><td>';
    echo '<select class="enquiry-status" data-enquiry-id="' . esc_attr($enquiry->id) . '">';
    $statuses = array('Unreplied', 'Replied', 'Done');
    foreach ($statuses as $status) {
        echo '<option value="' . esc_attr($status) . '"' . selected($enquiry->status, $status, false) . '>' . esc_html($status) . '</option>';
    }
    echo '</select></td></tr>';
    echo '</table>';

    echo '<h2>Message</h2>';
    echo '<table class="ef-enquiry-message">';
    echo '<tr><th>Subject</th><td>' . esc_html($enquiry->subject) . '</td></tr>';
    echo '<tr><th>Content</th><td>' . nl2br(esc_html($enquiry->content)) . '</td></tr>';
    echo '</table>';

    echo '<h2>Products</h2>';
    if (!empty($products)) {
        echo '<table class="ef-product-list">';
        echo '<thead><tr><th class="sku">SKU</th><th class="product-name">Product Name</th></tr></thead>';
        echo '<tbody>';
        foreach ($products as $product) {
            echo '<tr>';
            echo '<td class="sku">' . esc_html($product->sku) . '</td>';
            echo '<td class="product-name">' . esc_html($product->product_name) . '</td>';
            // echo '<td>' . esc_html($product->quantity) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No products associated with this enquiry.</p>';
    }

    // Add a back button
    echo '<a href="admin.php?page=ef-enquiries" class="button ef-back-button">Back to Enquiries</a>';
    echo '</div>';
}
