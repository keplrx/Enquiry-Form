<?php

// Don't allow direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

function cart_display() {
    ob_start();

    // Assume WooCommerce is active and cart is not empty, as checked in ef-public.php
    $cart = WC()->cart;

    ?>
    <form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
        <table class="cart-table">
            <thead>
                <tr>
                    <th class="product-column">Product</th>
                    <th class="category-column">Category</th>
                    <th class="sku-column">SKU</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                    $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                    $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                    if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
                        $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                        ?>
                        <tr>
                            <td class="product-column">
                                <div class="product-info">
                                    <?php
                                    $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);
                                    if (!$product_permalink) {
                                        echo $thumbnail;
                                    } else {
                                        printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail);
                                    }
                                    ?>
                                    <div class="product-details">
                                        <span class="product-name"><?php echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key) . '&nbsp;'); ?></span>
                                        <a href="<?php echo esc_url(wc_get_cart_remove_url($cart_item_key)); ?>" class="remove-item" aria-label="<?php echo esc_html__('Remove this item', 'woocommerce'); ?>"><?php echo esc_html__('Remove', 'woocommerce'); ?></a>
                                    </div>
                                </div>
                            </td>
                            <td class="category-column">
                                <?php
                                $categories = get_the_terms($product_id, 'product_cat');
                                if ($categories && !is_wp_error($categories)) {
                                    $category_names = array();
                                    foreach ($categories as $category) {
                                        $category_names[] = $category->name;
                                    }
                                    echo esc_html(implode(', ', $category_names));
                                }
                                ?>
                            </td>
                            <td class="sku-column">
                                <?php echo '<div class="sku-text">' . $_product->get_sku() . '</div>'; ?>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>
        <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
    </form>
    <?php

    return ob_get_clean();
}

add_shortcode('cart_display', 'cart_display');
