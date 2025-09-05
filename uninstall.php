<?php
/**
 * AI Newsletter Pro Uninstall
 * 
 * This file handles the cleanup when the plugin is deleted
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Plugin constants
define('AI_NEWSLETTER_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Database table names
global $wpdb;
define('AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE', $wpdb->prefix . 'ai_newsletter_subscribers');
define('AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE', $wpdb->prefix . 'ai_newsletter_campaigns');
define('AI_NEWSLETTER_PRO_WIDGETS_TABLE', $wpdb->prefix . 'ai_newsletter_widgets');
define('AI_NEWSLETTER_PRO_ANALYTICS_TABLE', $wpdb->prefix . 'ai_newsletter_analytics');

/**
 * Delete all plugin data
 */
function ai_newsletter_pro_uninstall() {
    global $wpdb;
    
    // Get uninstall option - whether to keep data or not
    $settings = get_option('ai_newsletter_pro_settings', array());
    $keep_data = $settings['advanced']['keep_data_on_uninstall'] ?? false;
    
    if ($keep_data) {
        // Just deactivate but keep data
        return;
    }
    
    // Drop all plugin tables
    $tables = array(
        AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE,
        AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE,
        AI_NEWSLETTER_PRO_WIDGETS_TABLE,
        AI_NEWSLETTER_PRO_ANALYTICS_TABLE,
        $wpdb->prefix . 'ai_newsletter_campaign_recipients'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    // Delete all plugin options
    $options = array(
        'ai_newsletter_pro_settings',
        'ai_newsletter_pro_db_version',
        'ai_newsletter_pro_activation_time',
        'ai_newsletter_pro_widget_cache',
        'ai_newsletter_pro_stats_cache'
    );
    
    foreach ($options as $option) {
        delete_option($option);
        delete_site_option($option);
    }
    
    // Clear any scheduled cron jobs
    wp_clear_scheduled_hook('ai_newsletter_check_scheduled');
    wp_clear_scheduled_hook('ai_newsletter_send_scheduled_campaign');
    wp_clear_scheduled_hook('ai_newsletter_cleanup_analytics');
    wp_clear_scheduled_hook('ai_newsletter_daily_stats');
    
    // Delete any uploaded files (export files, etc.)
    $upload_dir = wp_upload_dir();
    $plugin_upload_dir = $upload_dir['basedir'] . '/ai-newsletter-pro/';
    
    if (is_dir($plugin_upload_dir)) {
        ai_newsletter_pro_delete_directory($plugin_upload_dir);
    }
    
    // Clear any transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ai_newsletter_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ai_newsletter_%'");
    
    // Clear user meta data related to plugin
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ai_newsletter_%'");
    
    // Log uninstall
    error_log('AI Newsletter Pro: Plugin uninstalled and all data removed');
}

/**
 * Recursively delete directory
 */
function ai_newsletter_pro_delete_directory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            ai_newsletter_pro_delete_directory($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}

/**
 * Create data export for user before deletion
 */
function ai_newsletter_pro_export_data_before_uninstall() {
    global $wpdb;
    
    $export_data = array(
        'plugin' => 'AI Newsletter Pro',
        'version' => '1.0.3',
        'exported_at' => current_time('mysql'),
        'subscribers' => array(),
        'campaigns' => array(),
        'widgets' => array(),
        'settings' => get_option('ai_newsletter_pro_settings', array())
    );
    
    // Export subscribers
    if ($wpdb->get_var("SHOW TABLES LIKE '" . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . "'") == AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE) {
        $subscribers = $wpdb->get_results("SELECT * FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE);
        $export_data['subscribers'] = $subscribers;
    }
    
    // Export campaigns
    if ($wpdb->get_var("SHOW TABLES LIKE '" . AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE . "'") == AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE) {
        $campaigns = $wpdb->get_results("SELECT * FROM " . AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE);
        $export_data['campaigns'] = $campaigns;
    }
    
    // Export widgets
    if ($wpdb->get_var("SHOW TABLES LIKE '" . AI_NEWSLETTER_PRO_WIDGETS_TABLE . "'") == AI_NEWSLETTER_PRO_WIDGETS_TABLE) {
        $widgets = $wpdb->get_results("SELECT * FROM " . AI_NEWSLETTER_PRO_WIDGETS_TABLE);
        $export_data['widgets'] = $widgets;
    }
    
    // Save export file
    $upload_dir = wp_upload_dir();
    $export_file = $upload_dir['path'] . '/ai-newsletter-pro-export-' . date('Y-m-d-H-i-s') . '.json';
    
    if (!is_dir($upload_dir['path'])) {
        wp_mkdir_p($upload_dir['path']);
    }
    
    file_put_contents($export_file, json_encode($export_data, JSON_PRETTY_PRINT));
    
    // Store export file location in option (will be deleted but available until then)
    update_option('ai_newsletter_pro_export_file', $export_file);
    
    return $export_file;
}

/**
 * Send notification email to admin about uninstall
 */
function ai_newsletter_pro_send_uninstall_notification() {
    $admin_email = get_option('admin_email');
    $site_name = get_bloginfo('name');
    $site_url = home_url();
    
    $subject = sprintf(__('[%s] AI Newsletter Pro has been uninstalled', 'ai-newsletter-pro'), $site_name);
    
    $message = sprintf(
        __("Hello,\n\nAI Newsletter Pro has been uninstalled from your website %s (%s).\n\nAll plugin data has been removed from your database.\n\nIf you need to restore your data, please check for any export files in your uploads directory.\n\nThank you for using AI Newsletter Pro!\n\nBest regards,\nAI Newsletter Pro Team", 'ai-newsletter-pro'),
        $site_name,
        $site_url
    );
    
    wp_mail($admin_email, $subject, $message);
}

// Execute uninstall process
try {
    // Create data export first (optional safety measure)
    if (current_user_can('export')) {
        ai_newsletter_pro_export_data_before_uninstall();
    }
    
    // Send notification
    ai_newsletter_pro_send_uninstall_notification();
    
    // Perform uninstall
    ai_newsletter_pro_uninstall();
    
} catch (Exception $e) {
    error_log('AI Newsletter Pro Uninstall Error: ' . $e->getMessage());
}