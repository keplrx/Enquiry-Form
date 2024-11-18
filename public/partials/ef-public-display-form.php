<?php
function form_display() {
    ob_start();
    ?>
    <form id="enquiry-form" class="ef-enquiry-form" method="post">
        <div class="form-group">
            <label for="subject">SUBJECT:</label>
            <input type="text" id="subject" name="subject" placeholder="Subject" required>
        </div>

        <div class="form-group">
            <label for="content">CONTENT:</label>
            <textarea id="content" name="content" placeholder="Enter your message..." required></textarea>
        </div>

        <div class="form-row">
            <div class="form-group half-width">
                <label for="name">NAME:</label>
                <input type="text" id="name" name="name" placeholder="Your Name" required>
            </div>
            <div class="form-group half-width">
                <label for="company">COMPANY NAME:</label>
                <input type="text" id="company" name="company" placeholder="Company Name" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group half-width">
                <label for="email">EMAIL:</label>
                <input type="email" id="email" name="email" placeholder="Your Email" required>
            </div>
            <div class="form-group half-width">
                <label for="phone">PHONE NUMBER:</label>
                <input type="tel" id="phone" name="phone" placeholder="Your Phone Number" required>
            </div>
        </div>

        <div class="form-group submit-group">
            <?php
            if (function_exists('WC') && isset(WC()->cart) && WC()->cart->is_empty()) {
                $button_text = "Enquire without products";
            } else {
                $button_text = "Submit Enquiry";
            }
            ?>
            <button type="submit" class="submit-button"><?php echo esc_html($button_text); ?></button>
            <div id="error-message" style="color: red; margin-top: 10px;"></div>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

// Register the shortcode
add_shortcode('form_display', 'form_display');
