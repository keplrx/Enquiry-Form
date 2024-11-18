<?php

// Don't allow direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Wrapper function that combines cart and form
function cart_and_form_display() {
    ob_start(); // Start output buffering

    // Check if WooCommerce is active and the cart is available
    if (function_exists('WC') && isset(WC()->cart)) {
        if (!WC()->cart->is_empty()) {
            echo cart_display(); // Display the cart
            echo form_display(); // Display the form
        } else {
            echo '<div class="empty-cart-message">';
            echo '<h2 class="empty-cart-text">Your enquiry cart is empty! :(</h2>';
            echo '<a href="' . esc_url(wc_get_page_permalink('shop')) . '" class="add-products-link">Add Products</a>';
            echo '</div>';
            echo form_display();
        }
    } else {
        echo '<p>WooCommerce is not active or not fully loaded.</p>';
    }

    return ob_get_clean(); // Return the buffered content and clear the buffer
}

// Register the shortcodes
function ef_register_shortcodes() {
    add_shortcode('enquiry_form', 'cart_and_form_display');
    // add_shortcode('enquiry_thank_you', 'ef_display_thank_you');
}
add_action('init', 'ef_register_shortcodes');
