<?php
class EF_Admin {
    public function __construct() {
        // ... other constructor code ...

        add_action('admin_init', array($this, 'handle_csv_export'));
    }

    public function handle_csv_export() {
        if (isset($_POST['export_csv']) && current_user_can('manage_options')) {
            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'all';
            $start_date = !empty($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
            $end_date = !empty($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

            $this->generate_csv_export($status, $start_date, $end_date);
            exit;
        }
    }

    private function generate_csv_export($status, $start_date, $end_date) {
        if (headers_sent()) {
            die("Headers already sent. Cannot generate CSV.");
        }

        global $wpdb;
        $enquiries_table = $wpdb->prefix . 'ef_enquiries';
        $cart_items_table = $wpdb->prefix . 'ef_cart_items';

        // ... rest of your CSV generation code ...

        exit;
    }
}
