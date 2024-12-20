<?php
if (!defined('ABSPATH')) {
    exit;
}

function display_email_template_editor() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle which template we're editing
    $template_type = isset($_GET['template_type']) ? sanitize_key($_GET['template_type']) : 'confirmation';
    
    // Save changes if form is submitted
    if (isset($_POST['save_email_template']) && check_admin_referer('ef_email_template_nonce')) {
        $template_content = wp_kses_post($_POST['email_template_content']);
        $template_styles = sanitize_textarea_field($_POST['email_template_styles']);
        
        update_option("ef_email_{$template_type}_template", $template_content);
        update_option('ef_email_template_styles', $template_styles); // Styles are shared
        
        add_settings_error(
            'ef_email_template_messages',
            'ef_email_template_updated',
            __('Email template has been saved.', 'enquiry-form'),
            'updated'
        );
    }

    // Get saved template content or load default
    $template_content = get_option(
        "ef_email_{$template_type}_template", 
        get_default_template_content($template_type)
    );
    $template_styles = get_option('ef_email_template_styles', get_default_template_styles());

    // Template variables with descriptions
    $template_vars = array(
        // Customer Details
        '{form_data.name}' => 'Customer\'s name',
        '{form_data.subject}' => 'Enquiry subject',
        '{form_data.company}' => 'Customer\'s company',
        '{form_data.email}' => 'Customer\'s email',
        '{form_data.phone}' => 'Customer\'s phone',
        '{form_data.content}' => 'Customer\'s message',
        
        // Cart Items (Available in loop)
        '{product_name}' => 'Product name (use within cart items loop)',
        '{sku}' => 'Product SKU (use within cart items loop)',
        
        // Company Details (Footer)
        '{company_address}' => 'Company full address',
        '{company_phone}' => 'Company phone number',
        '{company_fax}' => 'Company fax number',
        '{company_email}' => 'Company email address'
    );

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php settings_errors('ef_email_template_messages'); ?>

        <!-- Template Type Selector -->
        <div class="ef-template-selector">
            <a href="?page=ef-settings&tab=template&template_type=confirmation" 
               class="button <?php echo $template_type === 'confirmation' ? 'button-primary' : ''; ?>">
                Customer Confirmation Email
            </a>
            <a href="?page=ef-settings&tab=template&template_type=notification" 
               class="button <?php echo $template_type === 'notification' ? 'button-primary' : ''; ?>">
                Admin Notification Email
            </a>
        </div>

        <div class="ef-email-editor-container">
            <!-- Template Variables Reference -->
            <div class="ef-template-vars">
                <h3><?php _e('Available Variables', 'enquiry-form'); ?></h3>
                <ul>
                    <?php foreach ($template_vars as $var => $description) : ?>
                        <li>
                            <code><?php echo esc_html($var); ?></code>
                            <span class="description"><?php echo esc_html($description); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Email Template Editor Form -->
            <form method="post" action="">
                <?php wp_nonce_field('ef_email_template_nonce'); ?>
                
                <div class="ef-editor-tabs">
                    <button type="button" class="ef-tab-button active" data-tab="content">
                        <?php _e('Content', 'enquiry-form'); ?>
                    </button>
                    <button type="button" class="ef-tab-button" data-tab="styles">
                        <?php _e('Styles', 'enquiry-form'); ?>
                    </button>
                    <button type="button" class="ef-tab-button" data-tab="preview">
                        <?php _e('Preview', 'enquiry-form'); ?>
                    </button>
                </div>

                <div class="ef-tab-content active" id="content-tab">
                    <?php 
                    wp_editor(
                        $template_content,
                        'email_template_content',
                        array(
                            'media_buttons' => true,
                            'textarea_rows' => 20,
                            'editor_height' => 400,
                            'editor_class' => 'ef-template-editor'
                        )
                    );
                    ?>
                </div>

                <div class="ef-tab-content" id="styles-tab">
                    <textarea 
                        name="email_template_styles" 
                        id="email_template_styles" 
                        class="large-text code" 
                        rows="20"
                    ><?php echo esc_textarea($template_styles); ?></textarea>
                </div>

                <div class="ef-tab-content" id="preview-tab">
                    <div class="ef-template-preview">
                        <div id="template-preview-content"></div>
                    </div>
                </div>

                <p class="submit">
                    <input type="submit" name="save_email_template" class="button button-primary" value="<?php esc_attr_e('Save Template', 'enquiry-form'); ?>">
                    <button type="button" class="button" id="reset-template">
                        <?php esc_attr_e('Reset to Default', 'enquiry-form'); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>

    <?php
    // Add necessary styles and scripts
    add_action('admin_footer', 'ef_email_editor_scripts');
}

function get_default_template_content($template_type) {
    $template_path = ENQUIRY_FORM_PATH . "emails/enquiry-{$template_type}-template.php";
    if (file_exists($template_path)) {
        return file_get_contents($template_path);
    }
    return '';
}

function get_default_template_styles() {
    // Load default template styles from file
    $styles_path = ENQUIRY_FORM_PATH . 'emails/css/email.css';
    if (file_exists($styles_path)) {
        return file_get_contents($styles_path);
    }
    return '';
}

function ef_email_editor_scripts() {
    // Add sample data for preview
    $sample_data = array(
        'form_data' => array(
            'name' => 'John Doe',
            'subject' => 'Product Enquiry',
            'company' => 'Sample Company Ltd',
            'email' => 'john@example.com',
            'phone' => '+65 1234 5678',
            'content' => "Hello,\n\nI'm interested in your products.\nPlease send me more information.\n\nThanks,\nJohn"
        ),
        'cart_items' => array(
            array(
                'data' => (object)array(
                    'name' => 'Sample Product 1',
                    'sku' => 'SKU001'
                )
            ),
            array(
                'data' => (object)array(
                    'name' => 'Sample Product 2',
                    'sku' => 'SKU002'
                )
            )
        )
    );
    ?>
    <style>
        .ef-email-editor-container {
            margin-top: 20px;
        }
        .ef-template-vars {
            background: #fff;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
        }
        .ef-template-vars code {
            display: inline-block;
            margin-right: 10px;
        }
        .ef-editor-tabs {
            margin-bottom: 15px;
        }
        .ef-tab-button {
            padding: 8px 15px;
            margin-right: 5px;
            border: 1px solid #ccc;
            background: #f5f5f5;
            cursor: pointer;
        }
        .ef-tab-button.active {
            background: #fff;
            border-bottom-color: #fff;
        }
        .ef-tab-content {
            display: none;
            padding: 20px;
            border: 1px solid #ccc;
            background: #fff;
        }
        .ef-tab-content.active {
            display: block;
        }
        .ef-template-preview {
            background: #f5f5f5;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .ef-template-selector {
            margin: 20px 0;
        }
        .ef-template-selector .button {
            margin-right: 10px;
        }
    </style>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Tab switching
        $('.ef-tab-button').click(function() {
            $('.ef-tab-button').removeClass('active');
            $('.ef-tab-content').removeClass('active');
            
            $(this).addClass('active');
            $('#' + $(this).data('tab') + '-tab').addClass('active');

            if ($(this).data('tab') === 'preview') {
                updatePreview();
            }
        });

        // Enhanced preview update
        function updatePreview() {
            var content = wp.editor.getContent('email_template_content');
            var styles = $('#email_template_styles').val();
            
            // Replace template variables with sample data
            var sampleData = <?php echo json_encode($sample_data); ?>;
            
            content = content.replace(/{form_data\.(\w+)}/g, function(match, field) {
                return sampleData.form_data[field] || match;
            });

            // Handle cart items loop
            var cartItemsHtml = '';
            if (content.includes('<?php echo esc_js('<?php foreach ($cart_items as'); ?>')) {
                sampleData.cart_items.forEach(function(item) {
                    var itemHtml = content
                        .match(/\<\?php foreach[\s\S]*?endforeach; \?>/)[0]
                        .replace(/{product_name}/g, item.data.name)
                        .replace(/{sku}/g, item.data.sku);
                    cartItemsHtml += itemHtml;
                });
                content = content.replace(/\<\?php foreach[\s\S]*?endforeach; \?>/, cartItemsHtml);
            }

            // Add styles and display
            var preview = $('<div>')
                .html(content)
                .prepend('<style>' + styles + '</style>');
            
            $('#template-preview-content').html(preview);
        }

        // Reset template
        $('#reset-template').click(function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to reset to the default template? All changes will be lost.')) {
                // Ajax call to reset template
                $.post(ajaxurl, {
                    action: 'ef_reset_email_template',
                    security: '<?php echo wp_create_nonce("ef_reset_template"); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            }
        });
    });
    </script>
    <?php
}