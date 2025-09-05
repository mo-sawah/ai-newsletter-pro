<?php
/**
 * Database management class for AI Newsletter Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Newsletter_Pro_Database {
    
    /**
     * Create plugin tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Subscribers table
        $subscribers_table = "CREATE TABLE " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            name varchar(255) DEFAULT '',
            status enum('subscribed','unsubscribed','pending','bounced') DEFAULT 'subscribed',
            source varchar(100) DEFAULT 'unknown',
            tags text DEFAULT NULL,
            meta_data text DEFAULT NULL,
            subscribed_at datetime DEFAULT CURRENT_TIMESTAMP,
            unsubscribed_at datetime DEFAULT NULL,
            last_activity datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY status (status),
            KEY source (source),
            KEY subscribed_at (subscribed_at)
        ) $charset_collate;";
        
        // Campaigns table
        $campaigns_table = "CREATE TABLE " . AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            subject varchar(255) NOT NULL,
            content longtext NOT NULL,
            template varchar(100) DEFAULT 'default',
            status enum('draft','scheduled','sending','sent','paused') DEFAULT 'draft',
            type enum('manual','auto_ai','drip','broadcast') DEFAULT 'manual',
            recipients_count int(11) DEFAULT 0,
            sent_count int(11) DEFAULT 0,
            opened_count int(11) DEFAULT 0,
            clicked_count int(11) DEFAULT 0,
            settings text DEFAULT NULL,
            scheduled_at datetime DEFAULT NULL,
            sent_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY type (type),
            KEY scheduled_at (scheduled_at),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        // Widgets table
        $widgets_table = "CREATE TABLE " . AI_NEWSLETTER_PRO_WIDGETS_TABLE . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            type enum('popup','inline','floating','banner','sidebar') NOT NULL,
            settings text NOT NULL,
            position varchar(100) DEFAULT '',
            active tinyint(1) DEFAULT 1,
            impressions int(11) DEFAULT 0,
            conversions int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type),
            KEY active (active)
        ) $charset_collate;";
        
        // Analytics table
        $analytics_table = "CREATE TABLE " . AI_NEWSLETTER_PRO_ANALYTICS_TABLE . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type enum('subscription','unsubscription','email_open','email_click','widget_view','widget_click') NOT NULL,
            subscriber_id bigint(20) unsigned DEFAULT NULL,
            campaign_id bigint(20) unsigned DEFAULT NULL,
            widget_id bigint(20) unsigned DEFAULT NULL,
            user_agent text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            referrer varchar(255) DEFAULT NULL,
            metadata text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY subscriber_id (subscriber_id),
            KEY campaign_id (campaign_id),
            KEY widget_id (widget_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Campaign recipients table (for tracking individual sends)
        $campaign_recipients_table = "CREATE TABLE " . $wpdb->prefix . "ai_newsletter_campaign_recipients (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) unsigned NOT NULL,
            subscriber_id bigint(20) unsigned NOT NULL,
            status enum('pending','sent','failed','bounced') DEFAULT 'pending',
            sent_at datetime DEFAULT NULL,
            opened_at datetime DEFAULT NULL,
            clicked_at datetime DEFAULT NULL,
            open_count int(11) DEFAULT 0,
            click_count int(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY campaign_subscriber (campaign_id, subscriber_id),
            KEY campaign_id (campaign_id),
            KEY subscriber_id (subscriber_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($subscribers_table);
        dbDelta($campaigns_table);
        dbDelta($widgets_table);
        dbDelta($analytics_table);
        dbDelta($campaign_recipients_table);
        
        // Update database version
        update_option('ai_newsletter_pro_db_version', AI_NEWSLETTER_PRO_VERSION);
    }
    
    /**
     * Check if database needs updating
     */
    public function maybe_update_database() {
        $current_version = get_option('ai_newsletter_pro_db_version', '0.0.0');
        
        if (version_compare($current_version, AI_NEWSLETTER_PRO_VERSION, '<')) {
            $this->create_tables();
        }
    }
    
    /**
     * Get table name with prefix
     */
    public static function get_table_name($table) {
        switch ($table) {
            case 'subscribers':
                return AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE;
            case 'campaigns':
                return AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE;
            case 'widgets':
                return AI_NEWSLETTER_PRO_WIDGETS_TABLE;
            case 'analytics':
                return AI_NEWSLETTER_PRO_ANALYTICS_TABLE;
            default:
                return '';
        }
    }
    
    /**
     * Drop all plugin tables (used on uninstall)
     */
    public function drop_tables() {
        global $wpdb;
        
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
        
        // Delete options
        delete_option('ai_newsletter_pro_settings');
        delete_option('ai_newsletter_pro_db_version');
    }
    
    /**
     * Get database statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Subscriber stats
        $stats['total_subscribers'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE status = 'subscribed'"
        );
        
        $stats['pending_subscribers'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE status = 'pending'"
        );
        
        $stats['unsubscribed'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE status = 'unsubscribed'"
        );
        
        // Campaign stats
        $stats['total_campaigns'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE
        );
        
        $stats['sent_campaigns'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE . " WHERE status = 'sent'"
        );
        
        // Calculate average open rate
        $stats['avg_open_rate'] = $wpdb->get_var(
            "SELECT AVG(CASE WHEN recipients_count > 0 THEN (opened_count / recipients_count) * 100 ELSE 0 END) 
             FROM " . AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE . " 
             WHERE status = 'sent' AND recipients_count > 0"
        );
        
        // Calculate average click rate
        $stats['avg_click_rate'] = $wpdb->get_var(
            "SELECT AVG(CASE WHEN recipients_count > 0 THEN (clicked_count / recipients_count) * 100 ELSE 0 END) 
             FROM " . AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE . " 
             WHERE status = 'sent' AND recipients_count > 0"
        );
        
        // Widget stats
        $stats['total_widgets'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_WIDGETS_TABLE . " WHERE active = 1"
        );
        
        $stats['total_widget_impressions'] = $wpdb->get_var(
            "SELECT SUM(impressions) FROM " . AI_NEWSLETTER_PRO_WIDGETS_TABLE
        );
        
        $stats['total_widget_conversions'] = $wpdb->get_var(
            "SELECT SUM(conversions) FROM " . AI_NEWSLETTER_PRO_WIDGETS_TABLE
        );
        
        // Calculate conversion rate
        if ($stats['total_widget_impressions'] > 0) {
            $stats['conversion_rate'] = ($stats['total_widget_conversions'] / $stats['total_widget_impressions']) * 100;
        } else {
            $stats['conversion_rate'] = 0;
        }
        
        // Recent activity (last 30 days)
        $stats['subscribers_last_30_days'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " 
             WHERE subscribed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        
        return $stats;
    }
    
    /**
     * Clean up old analytics data
     */
    public function cleanup_old_data($days = 90) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM " . AI_NEWSLETTER_PRO_ANALYTICS_TABLE . " 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }
}