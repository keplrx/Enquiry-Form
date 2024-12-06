<?php
// Don't allow direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

class EF_Email {
    private static $last_error = '';

    public static function get_notification_template($form_data) {
        $cart_items = WC()->cart->get_cart();
        ob_start();
        include(ENQUIRY_FORM_PATH . 'emails/enquiry-notification-template.php');
        return ob_get_clean();
    }

    public static function get_confirmation_template($form_data) {
        $cart_items = WC()->cart->get_cart();
        ob_start();
        include(ENQUIRY_FORM_PATH . 'emails/enquiry-confirmation-template.php');
        return ob_get_clean();
    }

    public static function send_notification_email($form_data) {
        try {
            if (!class_exists('WPMailSMTP\Core')) {
                throw new Exception('WP Mail SMTP plugin is not active');
            }

            if (empty($form_data['email']) || empty($form_data['name'])) {
                throw new Exception('Required form data missing');
            }

            $to = get_option('enquiry_form_email', get_option('admin_email'));
            $subject = 'New Enquiry: ' . sanitize_text_field($form_data['subject']);
            $message = self::get_notification_template($form_data);
            $headers = array('Content-Type: text/html; charset=UTF-8');

            $result = wp_mail($to, $subject, $message, $headers);
            
            if (!$result) {
                throw new Exception('Failed to send notification email');
            }

            EF_Logger::log(
                'Notification email sent successfully',
                EF_Logger::INFO,
                ['recipient' => $to]
            );

            return true;

        } catch (Exception $e) {
            self::$last_error = $e->getMessage();
            EF_Logger::log(
                'Notification email failed',
                EF_Logger::ERROR,
                [
                    'error' => $e->getMessage(),
                    'recipient' => $to ?? 'unknown',
                    'wp_mail_smtp_active' => class_exists('WPMailSMTP\Core')
                ]
            );
            return false;
        }
    }

    public static function send_confirmation_email($form_data) {
        try {
            if (!class_exists('WPMailSMTP\Core')) {
                throw new Exception('WP Mail SMTP plugin is not active');
            }

            $to = $form_data['email'];
            if (empty($to)) {
                throw new Exception('Recipient email is missing');
            }

            $from_email = get_option('enquiry_form_email', 'noreply@example.com');
            $subject = 'Enquiry Confirmation: ' . sanitize_text_field($form_data['subject']);
            $message = self::get_confirmation_template($form_data);
            $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $from_email);

            $result = wp_mail($to, $subject, $message, $headers);
            if (!$result) {
                throw new Exception('Failed to send confirmation email');
            }

            EF_Logger::log(
                'Confirmation email sent successfully',
                EF_Logger::INFO,
                ['recipient' => $to]
            );

            return true;

        } catch (Exception $e) {
            self::$last_error = $e->getMessage();
            EF_Logger::log(
                'Confirmation email failed',
                EF_Logger::ERROR,
                [
                    'error' => $e->getMessage(),
                    'recipient' => $to ?? 'unknown',
                    'wp_mail_smtp_active' => class_exists('WPMailSMTP\Core')
                ]
            );
            return false;
        }
    }

    public static function get_last_error() {
        return self::$last_error;
    }

    public static function set_last_error($error) {
        self::$last_error = $error;
    }
}
