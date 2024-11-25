<?php
if (!defined('ABSPATH')) {
    exit;
}

function log_ef_error($message, $data = null) {
    if (WP_DEBUG) {
        error_log(sprintf(
            '[Enquiry Form] %s %s',
            $message,
            $data ? json_encode($data) : ''
        ));
    }
}

function get_enquiry_stats($period = 'week') {
    $cache_key = "ef_stats_{$period}";
    $cached_result = get_transient($cache_key);
    
    if ($cached_result !== false) {
        return $cached_result;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'ef_enquiries';
    $current_date = current_time('mysql');
    
    switch($period) {
        case 'year':
            $interval = '1 YEAR';
            break;
        case 'month':
            $interval = '1 MONTH';
            break;
        default:
            $interval = '7 DAY';
    }
    
    $query = $wpdb->prepare(
        "SELECT COUNT(*) as count FROM $table_name 
        WHERE created_at BETWEEN DATE_SUB(%s, INTERVAL $interval) AND %s",
        $current_date, $current_date
    );
    
    try {
        $result = $wpdb->get_var($query);
    } catch (Exception $e) {
        log_ef_error("Error getting enquiry stats for period: $period", $e->getMessage());
        return 0;
    }
    
    // Cache for 1 hour
    set_transient($cache_key, $result, HOUR_IN_SECONDS);
    
    return $result;
}

function get_new_enquiries_count() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ef_enquiries';
    $current_date = current_time('mysql');
    
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name 
        WHERE status = 'Unreplied' 
        AND created_at BETWEEN DATE_SUB(%s, INTERVAL 24 HOUR) AND %s",
        $current_date, $current_date
    ));
}

function get_status_distribution() {
    $cache_key = 'ef_status_distribution';
    $cached_result = get_transient($cache_key);
    
    if ($cached_result !== false) {
        return $cached_result;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'ef_enquiries';
    
    // Check if table exists and has any entries
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    $has_entries = $table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table_name") > 0 : false;
    
    if (!$table_exists || !$has_entries) {
        return array(
            array('status' => 'Unreplied', 'count' => 0),
            array('status' => 'Replied', 'count' => 0),
            array('status' => 'Done', 'count' => 0)
        );
    }
    
    // First, get the IDs of the last 20 enquiries
    $last_20_ids = $wpdb->get_col(
        "SELECT id 
        FROM {$table_name} 
        ORDER BY created_at DESC 
        LIMIT 20"
    );

    if (empty($last_20_ids)) {
        return array(
            array('status' => 'Unreplied', 'count' => 0),
            array('status' => 'Replied', 'count' => 0),
            array('status' => 'Done', 'count' => 0)
        );
    }

    // Convert IDs array to comma-separated string
    $id_list = implode(',', array_map('intval', $last_20_ids));

    // Get status distribution for these IDs
    $results = $wpdb->get_results(
        "SELECT status, COUNT(*) as count 
        FROM {$table_name} 
        WHERE id IN ({$id_list})
        GROUP BY status"
    );

    if (empty($results)) {
        return array(
            array('status' => 'Unreplied', 'count' => 0),
            array('status' => 'Replied', 'count' => 0),
            array('status' => 'Done', 'count' => 0)
        );
    }

    // Format results to ensure all statuses are represented
    $formatted_results = array();
    $found_statuses = array();

    foreach ($results as $row) {
        $formatted_results[] = array(
            'status' => $row->status,
            'count' => (int)$row->count
        );
        $found_statuses[] = $row->status;
    }

    // Add missing statuses with count 0
    $all_statuses = array('Unreplied', 'Replied', 'Done');
    foreach ($all_statuses as $status) {
        if (!in_array($status, $found_statuses)) {
            $formatted_results[] = array(
                'status' => $status,
                'count' => 0
            );
        }
    }

    // Cache for 5 minutes
    set_transient($cache_key, $formatted_results, 5 * MINUTE_IN_SECONDS);
    
    return $formatted_results;
}

function get_enquiries_trend() {
    $cache_key = 'ef_enquiries_trend';
    $cached_result = get_transient($cache_key);
    
    if ($cached_result !== false) {
        return $cached_result;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'ef_enquiries';
    
    // Get daily counts for the last 30 days
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM {$table_name}
        WHERE created_at >= DATE_SUB(%s, INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ", current_time('mysql')));
    
    // Cache for 1 hour
    set_transient($cache_key, $results, HOUR_IN_SECONDS);
    
    return $results;
}

function display_home_page() {
    // Add refresh handler
    if (isset($_POST['refresh_stats']) && check_admin_referer('ef_refresh_stats')) {
        delete_transient('ef_stats_year');
        delete_transient('ef_stats_month');
        delete_transient('ef_stats_week');
        delete_transient('ef_status_distribution');
        delete_transient('ef_enquiries_trend');
    }
    
    $year_count = get_enquiry_stats('year');
    $month_count = get_enquiry_stats('month');
    $week_count = get_enquiry_stats('week');
    $new_enquiries = get_new_enquiries_count();
    $status_distribution = get_status_distribution();
    $trend_data = get_enquiries_trend();
    
    $has_data = $year_count > 0 || $month_count > 0 || $week_count > 0;
    
    ?>
    <div class="wrap">
        <h1>Enquiry Form Dashboard</h1>
        
        <?php if (!$has_data): ?>
            <div class="notice notice-info">
                <p>No enquiries have been submitted yet. The dashboard will populate once you receive enquiries.</p>
                <p>To test the system, you can:</p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li>Submit a test enquiry from your website's front end</li>
                    <li>Import sample data from the Settings page</li>
                </ul>
            </div>
        <?php else: ?>
            <!-- Existing dashboard content -->
            <form method="post" class="ef-refresh-form">
                <?php wp_nonce_field('ef_refresh_stats'); ?>
                <button type="submit" name="refresh_stats" class="button">
                    <span class="dashicons dashicons-update"></span> 
                    Refresh Statistics
                </button>
            </form>
            
            <div class="ef-dashboard-grid">
                <!-- Left Column -->
                <div class="ef-left-column">
                    <!-- Stats Section -->
                    <div class="ef-stats-section">
                        <h3>Enquiries Received</h3>
                        <div class="ef-stat-boxes">
                            <div class="ef-stat-box new-enquiries">
                                <span class="ef-stat-number"><?php echo esc_html($new_enquiries); ?></span>
                                <span class="ef-stat-label">New Enquiries (24h)</span>
                            </div>
                            <div class="ef-stat-box">
                                <span class="ef-stat-number"><?php echo esc_html($week_count); ?></span>
                                <span class="ef-stat-label">Last 7 Days</span>
                            </div>
                            <div class="ef-stat-box">
                                <span class="ef-stat-number"><?php echo esc_html($month_count); ?></span>
                                <span class="ef-stat-label">Last Month</span>
                            </div>
                            <div class="ef-stat-box">
                                <span class="ef-stat-number"><?php echo esc_html($year_count); ?></span>
                                <span class="ef-stat-label">Last Year</span>
                            </div>
                        </div>
                    </div>

                    <!-- Trend Chart -->
                    <div class="ef-chart-container trend-chart">
                        <h3>Enquiries Trend</h3>
                        <div class="ef-chart-wrapper">
                            <canvas id="trendChart" data-trend='<?php echo esc_attr($trend_data); ?>'></canvas>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Status Distribution -->
                <div class="ef-right-column">
                    <!-- Status Distribution -->
                    <div class="ef-chart-container status-chart">
                        <h3>Status Distribution (Last 20)</h3>
                        <div class="ef-chart-wrapper">
                            <canvas id="statusChart" data-status-distribution='<?php echo esc_attr($status_data); ?>'></canvas>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

