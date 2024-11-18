<?php
if (!defined('ABSPATH')) {
    exit;
}

function ef_display_thank_you() {
    ob_start();
    ?>
    <div class="ef-thank-you-container">
        <div class="ef-thank-you-content">
            <h1>Thank you for your enquiry!</h1>
            <p>We have received your enquiry and will get back to you shortly.</p>
            <p>Look out for an email from us for your confirmation!</p>
            <div class="ef-thank-you-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="5" width="18" height="14" rx="2" ry="2"></rect>
                    <polyline points="3 7 12 13 21 7"></polyline>
                </svg>
            </div>
            <!-- <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed at diam consequat nibh eleifend.</p> -->
            <div class="ef-button-container">
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="ef-button ef-button-primary">Enquire about more products</a>
                <a href="<?php echo esc_url(home_url()); ?>" class="ef-button ef-button-secondary">Return to Home</a>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
