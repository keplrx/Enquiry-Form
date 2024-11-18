<?php
if (!defined('ABSPATH')) {
    exit;
}

class EF_Logger {
    const ERROR = 'ERROR';
    const WARNING = 'WARNING';
    const INFO = 'INFO';
    
    private static $log_file;
    private static $instance = null;
    
    private function __construct() {
        self::$log_file = ENQUIRY_FORM_PATH . 'logs/enquiry-form-logs.txt';
        
        // Create logs directory if it doesn't exist
        $logs_dir = dirname(self::$log_file);
        if (!file_exists($logs_dir)) {
            wp_mkdir_p($logs_dir);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function log($message, $level = self::INFO, $context = array()) {
        $timestamp = current_time('mysql');
        $formatted_message = sprintf(
            "[%s] [%s] %s %s\n",
            $timestamp,
            $level,
            $message,
            !empty($context) ? json_encode($context) : ''
        );
        
        error_log($formatted_message, 3, self::$log_file);
        
        // Also log to WordPress error log for critical errors
        if ($level === self::ERROR) {
            error_log("Enquiry Form Plugin: " . $formatted_message);
        }
    }
    
    public static function clearLogs() {
        if (file_exists(self::$log_file)) {
            unlink(self::$log_file);
        }
    }
}
