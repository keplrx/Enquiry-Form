<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enquiry Confirmation</title>
    <style>
        <?php include(ENQUIRY_FORM_PATH . 'emails/css/email.css'); ?>
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="logo">
                    <img src="<?php echo esc_url(ENQUIRY_FORM_URL . 'assets/images/kbss-logo.jpg'); ?>" alt="KBSS Logo">
                </div>
                <h1 style="padding-top: 15px;">Enquiry Confirmation</h1>
            </div>
        </div>
        <p><strong>Hello <?php echo esc_html($form_data['name']); ?>!</strong></p>
        <p>Thank you for reaching out to us. We have received your enquiry, here are the details:</p>
        
        <table class="table">
            <tr>
                <td>Subject:</td>
                <td><?php echo esc_html($form_data['subject']); ?></td>
            </tr>
            <tr>
                <td>Company:</td>
                <td><?php echo esc_html($form_data['company']); ?></td>
            </tr>
            <tr>
                <td>Email:</td>
                <td><?php echo esc_html($form_data['email']); ?></td>
            </tr>
            <tr>
                <td>Phone:</td>
                <td><?php echo esc_html($form_data['phone']); ?></td>
            </tr>
        </table>

        <div class="message">
            <p><strong>Message:</strong></p>
            <p><?php echo nl2br(esc_html($form_data['content'])); ?></p>
        </div>

        <?php if (!empty($cart_items)) : ?>
            <div class="cart-contents">
                <h2>Cart Contents</h2>
                <table class="table">
                    <tr>
                        <th>Product</th>
                        <!-- <th>Category</th> -->
                        <th>SKU</th>
                    </tr>
                    <?php foreach ($cart_items as $cart_item_key => $cart_item) : 
                        $product = $cart_item['data'];
                        $product_name = $product->get_name();
                        $sku = $product->get_sku();
                        $category = '';
                        $terms = get_the_terms($product->get_id(), 'product_cat');
                        if ($terms && !is_wp_error($terms)) {
                            $category = $terms[0]->name;
                        }
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($product_name); ?></strong></td>
                            <!-- <td><?php echo esc_html($category); ?></td> -->
                            <td><?php echo esc_html($sku); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>

        
        <div class="footer">
            <p><strong>Kong Beng Stationery & Sports Pte Ltd</strong></p>
            <p>41 Jalan Pemimpin, Kong Beng Industrial Building</p>
            <p>#04-04 Singapore 577186</p>
            <p>Main: +65 6258-1611 | Fax: +65 6259 9991 | Email: sales@kongbeng.com</p>
        </div>
    </div>
</body>
</html>
