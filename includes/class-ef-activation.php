<?php
if (!defined('ABSPATH')) {
    exit;
}

class EF_Activation {
    
    public static function activate() {
        self::create_tables();
        self::create_demo_entries();
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $enquiries_table = $wpdb->prefix . 'ef_enquiries';
        $cart_items_table = $wpdb->prefix . 'ef_cart_items';

        $enquiries_sql = "CREATE TABLE $enquiries_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            subject varchar(255) NOT NULL,
            content text NOT NULL,
            name varchar(100) NOT NULL,
            company varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed tinyint(1) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'Unreplied',
            PRIMARY KEY  (id)
        ) $charset_collate;";
        $cart_items_sql = "CREATE TABLE $cart_items_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            enquiry_id mediumint(9) NOT NULL,
            product_name varchar(255) NOT NULL,
            quantity int NOT NULL,
            sku varchar(100) NOT NULL,
            PRIMARY KEY  (id),
            CONSTRAINT fk_enquiry
                FOREIGN KEY (enquiry_id) 
                REFERENCES $enquiries_table(id)
                ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($enquiries_sql);
        dbDelta($cart_items_sql);
    }

    private static function create_demo_entries() {
        global $wpdb;
        $enquiries_table = $wpdb->prefix . 'ef_enquiries';
        $cart_items_table = $wpdb->prefix . 'ef_cart_items';

        // Check if demo entries already exist
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $enquiries_table WHERE email LIKE '%demo@example.com'");
        
        if ($count > 0) {
            return; // Demo entries already exist
        }

        $demo_entries = [
            [
                'subject' => 'Product Inquiry - Office Supplies',
                'content' => 'Looking for bulk order of office supplies.',
                'name' => 'John Demo',
                'company' => 'Demo Corp',
                'email' => 'john.demo@example.com',
                'phone' => '91230101',
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
            ],
            [
                'subject' => 'Price Quote Request',
                'content' => 'Requesting quote for IT equipment.',
                'name' => 'Jane Demo',
                'company' => 'Tech Demo Ltd',
                'email' => 'jane.demo@example.com',
                'phone' => '91230102',
                'created_at' => date('Y-m-d H:i:s', strtotime('-4 days'))
            ],
            [
                'subject' => 'Bulk Order Inquiry',
                'content' => 'Interested in wholesale pricing.',
                'name' => 'Bob Demo',
                'company' => 'Demo Wholesale',
                'email' => 'bob.demo@example.com',
                'phone' => '91230103',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ],
            [
                'subject' => 'Product Availability Check',
                'content' => 'Checking stock for multiple items.',
                'name' => 'Alice Demo',
                'company' => 'Demo Retail',
                'email' => 'alice.demo@example.com',
                'phone' => '91230104',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'subject' => 'Custom Order Request',
                'content' => 'Need customized product specifications.',
                'name' => 'Charlie Demo',
                'company' => 'Demo Industries',
                'email' => 'charlie.demo@example.com',
                'phone' => '91230105',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ]
        ];

        foreach ($demo_entries as $entry) {
            $wpdb->insert($enquiries_table, $entry);
            $enquiry_id = $wpdb->insert_id;

            // Add sample cart items for each enquiry
            $wpdb->insert($cart_items_table, [
                'enquiry_id' => $enquiry_id,
                'product_name' => 'Sample Product',
                'quantity' => rand(1, 10),
                'sku' => 'DEMO-' . rand(1000, 9999)
            ]);
        }
    }
}

