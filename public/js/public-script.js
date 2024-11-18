jQuery(document).ready(function($) {
    // Cache for thank you content
    let cachedThankYouContent = null;

    // Pre-load thank you content when page loads
    function preloadThankYouContent() {
        $.ajax({
            url: efPublicParams.ajax_url,
            type: 'POST',
            data: {
                action: 'ef_get_thank_you_content',
                security: efPublicParams.form_nonce
            },
            success: function(response) {
                if (response.success) {
                    cachedThankYouContent = response.data;
                }
            }
        });
    }

    // Call preload function when page loads
    preloadThankYouContent();

    function isValidEmail(email) {
        // Basic email validation regex
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        // Check basic format
        if (!emailRegex.test(email)) {
            return false;
        }
        
        // Split email into parts
        const [localPart, domain] = email.split('@');
        
        // Check domain has at least one period and valid TLD length
        const domainParts = domain.split('.');
        const tld = domainParts[domainParts.length - 1];
        
        return domainParts.length >= 2 && tld.length >= 2;
    }

    $('#enquiry-form').on('submit', function(e) {
        e.preventDefault();
        
        const emailInput = $('#email').val();
        if (!isValidEmail(emailInput)) {
            alert('The email entered is not valid.');
            return false;
        }

        // Disable the submit button and change cursor
        var $submitButton = $(this).find('button[type="submit"]');
        $submitButton.prop('disabled', true).text('Submitting...');
        $('body').addClass('loading');

        var formData = {
            subject: $('#subject').val(),
            content: $('#content').val(),
            name: $('#name').val(),
            company: $('#company').val(),
            email: $('#email').val(),
            phone: $('#phone').val(),
            cart_items: []
        };

        $('.cart-item').each(function() {
            var item = {
                name: $(this).find('.product-name').text(),
                quantity: $(this).find('.quantity-input').val(),
                sku: $(this).find('.product-sku').text()
            };
            formData.cart_items.push(item);
        });

        $.ajax({
            url: efPublicParams.ajax_url,
            type: 'POST',
            data: {
                action: 'ef_submit_form',
                security: efPublicParams.form_nonce,
                form_data: formData
            },
            success: function(response) {
                if (response.success && response.data === 'show_thank_you_popup') {
                    // Re-enable button and show popup in quick succession
                    $submitButton.prop('disabled', false).text('Submit Enquiry');
                    $('body').removeClass('loading');
                    showThankYouPopup(); // This will now be instant if cached
                } else {
                    alert(response.data || 'Failed to submit enquiry. Please try again.');
                    $submitButton.prop('disabled', false).text('Submit Enquiry');
                    $('body').removeClass('loading');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                alert('An error occurred. Please try again.');
                $submitButton.prop('disabled', false).text('Submit Enquiry');
                $('body').removeClass('loading');
            }
        });
    });

    function showThankYouPopup() {
        // Use cached content if available, otherwise fetch it
        if (cachedThankYouContent) {
            $('body').append('<div id="ef-thank-you-overlay"></div>');
            $('body').append('<div id="ef-thank-you-popup">' + cachedThankYouContent + '</div>');
            $('#ef-thank-you-overlay, #ef-thank-you-popup').fadeIn();
        } else {
            // Fallback to original ajax call if cache somehow failed
            $.ajax({
                url: efPublicParams.ajax_url,
                type: 'POST',
                data: {
                    action: 'ef_get_thank_you_content',
                    security: efPublicParams.form_nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('body').append('<div id="ef-thank-you-overlay"></div>');
                        $('body').append('<div id="ef-thank-you-popup">' + response.data + '</div>');
                        $('#ef-thank-you-overlay, #ef-thank-you-popup').fadeIn();
                    }
                }
            });
        }
    }

    $(document).on('click', '#ef-thank-you-overlay, .ef-button-secondary', function() {
        $('#ef-thank-you-overlay, #ef-thank-you-popup').fadeOut(function() {
            $(this).remove();
        });
    });
});
