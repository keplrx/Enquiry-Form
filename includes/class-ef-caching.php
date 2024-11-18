<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base Cache Class
 * Handles core caching functionality with security features
 */
class EF_Cache {
    private static $instance = null;
    private $cache = [];
    private $encryption_key;
    private $access_counters = [];
    
    private $expiry_times = [
        'cart_contents' => 3600,     // 1 hour
        'email_content' => 86400,    // 24 hours
        'product_data' => 3600,      // 1 hour
        'enquiry_status' => 300      // 5 minutes
    ];

    private $rate_limits = [
        'cart_contents' => ['count' => 100, 'period' => 300],    // 100 requests per 5 minutes
        'email_content' => ['count' => 50, 'period' => 3600],    // 50 requests per hour
        'product_data' => ['count' => 200, 'period' => 300],     // 200 requests per 5 minutes
        'enquiry_status' => ['count' => 100, 'period' => 300]    // 100 requests per 5 minutes
    ];

    private function __construct() {
        $this->init_encryption_key();
        add_action('init', [$this, 'cleanup_expired_cache']);
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize encryption key
     */
    private function init_encryption_key() {
        $key_file = WP_CONTENT_DIR . '/../ef-encryption-key.php';
        if (!file_exists($key_file)) {
            $key = bin2hex(random_bytes(32));
            file_put_contents($key_file, "<?php return '" . $key . "';");
        }
        $this->encryption_key = include($key_file);
    }

    /**
     * Encrypt data
     */
    protected function encrypt($data) {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            serialize($data),
            'AES-256-CBC',
            $this->encryption_key,
            0,
            $iv
        );
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt data
     */
    protected function decrypt($encrypted_data) {
        $decoded = base64_decode($encrypted_data);
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            $this->encryption_key,
            0,
            $iv
        );
        return unserialize($decrypted);
    }

    /**
     * Check rate limit
     */
    protected function check_rate_limit($group) {
        if (!isset($this->rate_limits[$group])) {
            return true;
        }

        $current_time = time();
        $counter_key = $group . '_' . get_current_user_id();

        if (!isset($this->access_counters[$counter_key])) {
            $this->access_counters[$counter_key] = [
                'count' => 0,
                'timestamp' => $current_time
            ];
        }

        // Reset counter if period has passed
        if (($current_time - $this->access_counters[$counter_key]['timestamp']) > $this->rate_limits[$group]['period']) {
            $this->access_counters[$counter_key] = [
                'count' => 0,
                'timestamp' => $current_time
            ];
        }

        // Check rate limit
        if ($this->access_counters[$counter_key]['count'] >= $this->rate_limits[$group]['count']) {
            return false;
        }

        $this->access_counters[$counter_key]['count']++;
        return true;
    }

    /**
     * Get cached data with security checks
     */
    public function get($key, $group = '') {
        if (!wp_verify_nonce($_REQUEST['cache_nonce'] ?? '', 'ef_cache_' . $key)) {
            return false;
        }

        if (!$this->check_rate_limit($group)) {
            return false;
        }

        if (isset($this->cache[$key]) && $this->cache[$key]['expiry'] > time()) {
            return $this->decrypt($this->cache[$key]['data']);
        }
        return false;
    }

    /**
     * Set cached data with encryption
     */
    public function set($key, $data, $group = '') {
        if (!wp_verify_nonce($_REQUEST['cache_nonce'] ?? '', 'ef_cache_' . $key)) {
            return false;
        }

        $expiry = time() + ($this->expiry_times[$group] ?? 3600);
        $this->cache[$key] = [
            'data' => $this->encrypt($data),
            'expiry' => $expiry,
            'group' => $group
        ];
    }

    /**
     * Clean up expired cache entries
     */
    public function cleanup_expired_cache() {
        $current_time = time();
        foreach ($this->cache as $key => $value) {
            if ($value['expiry'] <= $current_time) {
                unset($this->cache[$key]);
            }
        }
    }

    public function delete($key) {
        unset($this->cache[$key]);
    }

    public function clear_group($group) {
        foreach ($this->cache as $key => $value) {
            if ($value['group'] === $group) {
                unset($this->cache[$key]);
            }
        }
    }
}

/**
 * Cart Cache Class
 */
class EF_Cart_Cache {
    private $cache;
    
    public function __construct() {
        $this->cache = EF_Cache::get_instance();
    }

    public function get_cart_contents() {
        $nonce = wp_create_nonce('ef_cache_cart_contents_' . get_current_user_id());
        $_REQUEST['cache_nonce'] = $nonce;
        
        $cache_key = 'cart_contents_' . get_current_user_id();
        $cached_content = $this->cache->get($cache_key, 'cart_contents');
        
        if ($cached_content === false) {
            $cart_contents = WC()->cart ? WC()->cart->get_cart() : [];
            $this->cache->set($cache_key, $cart_contents, 'cart_contents');
            return $cart_contents;
        }
        
        return $cached_content;
    }

    public function clear_cart_cache() {
        $this->cache->clear_group('cart_contents');
    }
}

/**
 * Product Cache Class
 */
class EF_Product_Cache {
    private $cache;
    
    public function __construct() {
        $this->cache = EF_Cache::get_instance();
    }

    public function get_product_data($product_id) {
        $nonce = wp_create_nonce('ef_cache_product_' . $product_id);
        $_REQUEST['cache_nonce'] = $nonce;
        
        $cache_key = 'product_data_' . $product_id;
        $cached_data = $this->cache->get($cache_key, 'product_data');
        
        if ($cached_data === false) {
            $product = wc_get_product($product_id);
            if (!$product) {
                return false;
            }
            
            $product_data = [
                'name' => $product->get_name(),
                'sku' => $product->get_sku(),
                'price' => $product->get_price(),
                'categories' => wp_get_post_terms($product_id, 'product_cat', ['fields' => 'names'])
            ];
            
            $this->cache->set($cache_key, $product_data, 'product_data');
            return $product_data;
        }
        
        return $cached_data;
    }

    public function clear_product_cache($product_id = null) {
        if ($product_id) {
            $this->cache->delete('product_data_' . $product_id);
        } else {
            $this->cache->clear_group('product_data');
        }
    }
}

/**
 * Email Cache Class
 */
class EF_Email_Cache {
    private $cache;
    
    public function __construct() {
        $this->cache = EF_Cache::get_instance();
    }

    public function get_email_content($form_data) {
        $nonce = wp_create_nonce('ef_cache_email_' . md5(serialize($form_data)));
        $_REQUEST['cache_nonce'] = $nonce;
        
        $cache_key = 'email_content_' . md5(serialize($form_data));
        $cached_content = $this->cache->get($cache_key, 'email_content');
        
        if ($cached_content === false) {
            ob_start();
            include(ENQUIRY_FORM_PATH . 'emails/enquiry-confirmation-template.php');
            $content = ob_get_clean();
            $this->cache->set($cache_key, $content, 'email_content');
            return $content;
        }
        
        return $cached_content;
    }

    public function get_notification_content($form_data) {
        $nonce = wp_create_nonce('ef_cache_notification_' . md5(serialize($form_data)));
        $_REQUEST['cache_nonce'] = $nonce;
        
        $cache_key = 'notification_content_' . md5(serialize($form_data));
        $cached_content = $this->cache->get($cache_key, 'email_content');
        
        if ($cached_content === false) {
            ob_start();
            include(ENQUIRY_FORM_PATH . 'emails/enquiry-notification-template.php');
            $content = ob_get_clean();
            $this->cache->set($cache_key, $content, 'email_content');
            return $content;
        }
        
        return $cached_content;
    }

    public function clear_email_cache() {
        $this->cache->clear_group('email_content');
    }
}

/**
 * Enquiry Cache Class
 */
class EF_Enquiry_Cache {
    private $cache;
    
    public function __construct() {
        $this->cache = EF_Cache::get_instance();
    }

    public function get_enquiry_status($enquiry_id) {
        $nonce = wp_create_nonce('ef_cache_enquiry_' . $enquiry_id);
        $_REQUEST['cache_nonce'] = $nonce;
        
        $cache_key = 'enquiry_status_' . $enquiry_id;
        $cached_status = $this->cache->get($cache_key, 'enquiry_status');
        
        if ($cached_status === false) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'ef_enquiries';
            $status = $wpdb->get_var($wpdb->prepare(
                "SELECT status FROM $table_name WHERE id = %d",
                $enquiry_id
            ));
            $this->cache->set($cache_key, $status, 'enquiry_status');
            return $status;
        }
        
        return $cached_status;
    }

    public function get_enquiry_details($enquiry_id) {
        $nonce = wp_create_nonce('ef_cache_enquiry_details_' . $enquiry_id);
        $_REQUEST['cache_nonce'] = $nonce;
        
        $cache_key = 'enquiry_details_' . $enquiry_id;
        $cached_details = $this->cache->get($cache_key, 'enquiry_status');
        
        if ($cached_details === false) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'ef_enquiries';
            $details = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $enquiry_id
            ));
            $this->cache->set($cache_key, $details, 'enquiry_status');
            return $details;
        }
        
        return $cached_details;
    }

    public function update_enquiry_status($enquiry_id, $status) {
        $nonce = wp_create_nonce('ef_cache_enquiry_' . $enquiry_id);
        $_REQUEST['cache_nonce'] = $nonce;
        
        $cache_key = 'enquiry_status_' . $enquiry_id;
        $this->cache->set($cache_key, $status, 'enquiry_status');
        
        // Also update details cache if it exists
        $details_key = 'enquiry_details_' . $enquiry_id;
        $cached_details = $this->cache->get($details_key, 'enquiry_status');
        if ($cached_details !== false) {
            $cached_details->status = $status;
            $this->cache->set($details_key, $cached_details, 'enquiry_status');
        }
    }

    public function clear_enquiry_cache($enquiry_id = null) {
        if ($enquiry_id) {
            $this->cache->delete('enquiry_status_' . $enquiry_id);
            $this->cache->delete('enquiry_details_' . $enquiry_id);
        } else {
            $this->cache->clear_group('enquiry_status');
        }
    }
}
