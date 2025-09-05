<?php
/**
 * Analytics class for AI Newsletter Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Newsletter_Pro_Analytics {
    
    /**
     * Track event
     */
    public function track_event($event_type, $subscriber_id = null, $campaign_id = null, $widget_id = null, $metadata = array()) {
        global $wpdb;
        
        return $wpdb->insert(
            AI_NEWSLETTER_PRO_ANALYTICS_TABLE,
            array(
                'event_type' => sanitize_text_field($event_type),
                'subscriber_id' => $subscriber_id ? intval($subscriber_id) : null,
                'campaign_id' => $campaign_id ? intval($campaign_id) : null,
                'widget_id' => $widget_id ? intval($widget_id) : null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip_address' => $this->get_client_ip(),
                'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
                'metadata' => json_encode($metadata),
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Get analytics data for dashboard
     */
    public function get_dashboard_analytics($days = 30) {
        global $wpdb;
        
        $date_filter = $wpdb->prepare("AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)", $days);
        
        // Get subscription events
        $subscription_data = $wpdb->get_results(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM " . AI_NEWSLETTER_PRO_ANALYTICS_TABLE . " 
             WHERE event_type = 'subscription' " . $date_filter . "
             GROUP BY DATE(created_at) 
             ORDER BY date ASC"
        );
        
        // Get widget performance
        $widget_performance = $wpdb->get_results(
            "SELECT widget_id, event_type, COUNT(*) as count 
             FROM " . AI_NEWSLETTER_PRO_ANALYTICS_TABLE . " 
             WHERE widget_id IS NOT NULL " . $date_filter . "
             GROUP BY widget_id, event_type"
        );
        
        // Get email campaign performance
        $campaign_performance = $wpdb->get_results(
            "SELECT campaign_id, event_type, COUNT(*) as count 
             FROM " . AI_NEWSLETTER_PRO_ANALYTICS_TABLE . " 
             WHERE campaign_id IS NOT NULL " . $date_filter . "
             GROUP BY campaign_id, event_type"
        );
        
        return array(
            'subscriptions' => $subscription_data,
            'widget_performance' => $widget_performance,
            'campaign_performance' => $campaign_performance
        );
    }
    
    /**
     * Get conversion funnel data
     */
    public function get_conversion_funnel($widget_id = null, $days = 30) {
        global $wpdb;
        
        $date_filter = $wpdb->prepare("AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)", $days);
        $widget_filter = $widget_id ? $wpdb->prepare("AND widget_id = %d", $widget_id) : "";
        
        $funnel_data = $wpdb->get_results(
            "SELECT event_type, COUNT(*) as count 
             FROM " . AI_NEWSLETTER_PRO_ANALYTICS_TABLE . " 
             WHERE event_type IN ('widget_view', 'widget_click', 'subscription') 
             {$widget_filter} {$date_filter}
             GROUP BY event_type"
        );
        
        $funnel = array(
            'views' => 0,
            'clicks' => 0,
            'subscriptions' => 0,
            'view_to_click_rate' => 0,
            'click_to_subscription_rate' => 0,
            'overall_conversion_rate' => 0
        );
        
        foreach ($funnel_data as $data) {
            switch ($data->event_type) {
                case 'widget_view':
                    $funnel['views'] = $data->count;
                    break;
                case 'widget_click':
                    $funnel['clicks'] = $data->count;
                    break;
                case 'subscription':
                    $funnel['subscriptions'] = $data->count;
                    break;
            }
        }
        
        // Calculate conversion rates
        if ($funnel['views'] > 0) {
            $funnel['view_to_click_rate'] = ($funnel['clicks'] / $funnel['views']) * 100;
            $funnel['overall_conversion_rate'] = ($funnel['subscriptions'] / $funnel['views']) * 100;
        }
        
        if ($funnel['clicks'] > 0) {
            $funnel['click_to_subscription_rate'] = ($funnel['subscriptions'] / $funnel['clicks']) * 100;
        }
        
        return $funnel;
    }
    
    /**
     * Get top performing content
     */
    public function get_top_performing_content($limit = 10, $days = 30) {
        global $wpdb;
        
        $date_filter = $wpdb->prepare("AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)", $days);
        
        // Get most engaging pages (where subscriptions happen)
        $top_pages = $wpdb->get_results($wpdb->prepare(
            "SELECT referrer, COUNT(*) as subscription_count 
             FROM " . AI_NEWSLETTER_PRO_ANALYTICS_TABLE . " 
             WHERE event_type = 'subscription' 
             AND referrer IS NOT NULL 
             AND referrer != '' " . $date_filter . "
             GROUP BY referrer 
             ORDER BY subscription_count DESC 
             LIMIT %d",
            $limit
        ));
        
        return $top_pages;
    }
    
    /**
     * Get subscriber growth data
     */
    public function get_subscriber_growth($days = 30) {
        global $wpdb;
        
        $growth_data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(subscribed_at) as date,
                COUNT(*) as new_subscribers,
                SUM(COUNT(*)) OVER (ORDER BY DATE(subscribed_at)) as total_subscribers
             FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " 
             WHERE subscribed_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             AND status = 'subscribed'
             GROUP BY DATE(subscribed_at) 
             ORDER BY date ASC",
            $days
        ));
        
        return $growth_data;
    }
    
    /**
     * Get email campaign analytics
     */
    public function get_campaign_analytics($campaign_id) {
        global $wpdb;
        
        // Get basic campaign stats
        $campaign_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                recipients_count,
                sent_count,
                opened_count,
                clicked_count
             FROM " . AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE . " 
             WHERE id = %d",
            $campaign_id
        ));
        
        // Get detailed recipient data
        $recipient_data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                status,
                opened_at,
                clicked_at,
                open_count,
                click_count
             FROM " . $wpdb->prefix . "ai_newsletter_campaign_recipients 
             WHERE campaign_id = %d",
            $campaign_id
        ));
        
        // Get engagement over time
        $engagement_timeline = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                HOUR(created_at) as hour,
                event_type,
                COUNT(*) as count
             FROM " . AI_NEWSLETTER_PRO_ANALYTICS_TABLE . " 
             WHERE campaign_id = %d 
             AND event_type IN ('email_open', 'email_click')
             GROUP BY DATE(created_at), HOUR(created_at), event_type
             ORDER BY date, hour",
            $campaign_id
        ));
        
        return array(
            'campaign_stats' => $campaign_stats,
            'recipient_data' => $recipient_data,
            'engagement_timeline' => $engagement_timeline
        );
    }
    
    /**
     * Get widget performance comparison
     */
    public function compare_widget_performance($days = 30) {
        global $wpdb;
        
        $date_filter = $wpdb->prepare("AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)", $days);
        
        // Get widget performance data
        $widget_stats = $wpdb->get_results(
            "SELECT 
                w.id,
                w.type,
                w.settings,
                COALESCE(SUM(CASE WHEN a.event_type = 'widget_view' THEN 1 ELSE 0 END), 0) as views,
                COALESCE(SUM(CASE WHEN a.event_type = 'widget_click' THEN 1 ELSE 0 END), 0) as clicks,
                COALESCE(SUM(CASE WHEN a.event_type = 'subscription' THEN 1 ELSE 0 END), 0) as conversions
             FROM " . AI_NEWSLETTER_PRO_WIDGETS_TABLE . " w
             LEFT JOIN " . AI_NEWSLETTER_PRO_ANALYTICS_TABLE . " a ON w.id = a.widget_id " . str_replace('AND', 'WHERE', $date_filter) . "
             WHERE w.active = 1
             GROUP BY w.id, w.type, w.settings"
        );
        
        // Calculate performance metrics
        foreach ($widget_stats as $stat) {
            $stat->click_rate = $stat->views > 0 ? ($stat->clicks / $stat->views) * 100 : 0;
            $stat->conversion_rate = $stat->views > 0 ? ($stat->conversions / $stat->views) * 100 : 0;
            $stat->settings = json_decode($stat->settings, true);
            $stat->title = $stat->settings['title'] ?? 'Untitled Widget';
        }
        
        return $widget_stats;
    }
    
    /**
     * Get geographic data (basic)
     */
    public function get_geographic_data($days = 30) {
        global $wpdb;
        
        $date_filter = $wpdb->prepare("AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)", $days);
        
        // Get IP-based location data (simplified)
        $ip_data = $wpdb->get_results(
            "SELECT 
                ip_address,
                COUNT(*) as events,
                COUNT(DISTINCT CASE WHEN event_type = 'subscription' THEN id END) as subscriptions
             FROM " . AI_NEWSLETTER_PRO_ANALYTICS_TABLE . " 
             WHERE ip_address IS NOT NULL " . $date_filter . "
             GROUP BY ip_address
             ORDER BY subscriptions DESC, events DESC
             LIMIT 100"
        );
        
        // In a full implementation, you would use a geolocation service
        // For now, return basic IP data
        return $ip_data;
    }
    
    /**
     * Get device and browser analytics
     */
    public function get_device_analytics($days = 30) {
        global $wpdb;
        
        $date_filter = $wpdb->prepare("AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)", $days);
        
        $user_agents = $wpdb->get_results(
            "SELECT 
                user_agent,
                COUNT(*) as events,
                COUNT(DISTINCT CASE WHEN event_type = 'subscription' THEN id END) as subscriptions
             FROM " . AI_NEWSLETTER_PRO_ANALYTICS_TABLE . " 
             WHERE user_agent IS NOT NULL " . $date_filter . "
             GROUP BY user_agent
             ORDER BY subscriptions DESC"
        );
        
        // Parse user agents to extract device/browser info
        $device_data = array(
            'mobile' => 0,
            'desktop' => 0,
            'tablet' => 0,
            'browsers' => array(),
            'os' => array()
        );
        
        foreach ($user_agents as $ua_data) {
            $ua = $ua_data->user_agent;
            $count = $ua_data->subscriptions;
            
            // Simple device detection
            if (preg_match('/Mobile|Android|iPhone|iPad/', $ua)) {
                if (preg_match('/iPad/', $ua)) {
                    $device_data['tablet'] += $count;
                } else {
                    $device_data['mobile'] += $count;
                }
            } else {
                $device_data['desktop'] += $count;
            }
            
            // Simple browser detection
            if (preg_match('/Chrome/', $ua)) {
                $device_data['browsers']['Chrome'] = ($device_data['browsers']['Chrome'] ?? 0) + $count;
            } elseif (preg_match('/Firefox/', $ua)) {
                $device_data['browsers']['Firefox'] = ($device_data['browsers']['Firefox'] ?? 0) + $count;
            } elseif (preg_match('/Safari/', $ua)) {
                $device_data['browsers']['Safari'] = ($device_data['browsers']['Safari'] ?? 0) + $count;
            } elseif (preg_match('/Edge/', $ua)) {
                $device_data['browsers']['Edge'] = ($device_data['browsers']['Edge'] ?? 0) + $count;
            }
        }
        
        return $device_data;
    }
    
    /**
     * Generate analytics report
     */
    public function generate_report($period = 'monthly') {
        $days = $period === 'weekly' ? 7 : ($period === 'monthly' ? 30 : 90);
        
        $report = array(
            'period' => $period,
            'days' => $days,
            'generated_at' => current_time('mysql'),
            'subscriber_growth' => $this->get_subscriber_growth($days),
            'widget_performance' => $this->compare_widget_performance($days),
            'conversion_funnel' => $this->get_conversion_funnel(null, $days),
            'top_content' => $this->get_top_performing_content(10, $days),
            'device_analytics' => $this->get_device_analytics($days)
        );
        
        // Calculate summary metrics
        $report['summary'] = $this->calculate_summary_metrics($report);
        
        return $report;
    }
    
    /**
     * Calculate summary metrics
     */
    private function calculate_summary_metrics($report) {
        $summary = array(
            'total_new_subscribers' => 0,
            'average_daily_growth' => 0,
            'best_performing_widget' => null,
            'overall_conversion_rate' => $report['conversion_funnel']['overall_conversion_rate'],
            'total_widget_views' => $report['conversion_funnel']['views'],
            'mobile_percentage' => 0
        );
        
        // Calculate subscriber growth
        if (!empty($report['subscriber_growth'])) {
            $summary['total_new_subscribers'] = array_sum(array_column($report['subscriber_growth'], 'new_subscribers'));
            $summary['average_daily_growth'] = $summary['total_new_subscribers'] / $report['days'];
        }
        
        // Find best performing widget
        if (!empty($report['widget_performance'])) {
            $best_widget = null;
            $best_rate = 0;
            
            foreach ($report['widget_performance'] as $widget) {
                if ($widget->conversion_rate > $best_rate) {
                    $best_rate = $widget->conversion_rate;
                    $best_widget = $widget;
                }
            }
            
            $summary['best_performing_widget'] = $best_widget;
        }
        
        // Calculate mobile percentage
        $device_data = $report['device_analytics'];
        $total_devices = $device_data['mobile'] + $device_data['desktop'] + $device_data['tablet'];
        if ($total_devices > 0) {
            $summary['mobile_percentage'] = (($device_data['mobile'] + $device_data['tablet']) / $total_devices) * 100;
        }
        
        return $summary;
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Track email open
     */
    public function track_email_open($campaign_id, $subscriber_id) {
        global $wpdb;
        
        // Update campaign recipient record
        $wpdb->update(
            $wpdb->prefix . 'ai_newsletter_campaign_recipients',
            array(
                'opened_at' => current_time('mysql'),
                'open_count' => new WP_Query_Clause('open_count + 1')
            ),
            array(
                'campaign_id' => $campaign_id,
                'subscriber_id' => $subscriber_id
            )
        );
        
        // Track analytics event
        $this->track_event('email_open', $subscriber_id, $campaign_id);
        
        // Update campaign totals
        $wpdb->query($wpdb->prepare(
            "UPDATE " . AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE . " 
             SET opened_count = (
                 SELECT COUNT(DISTINCT subscriber_id) 
                 FROM " . $wpdb->prefix . "ai_newsletter_campaign_recipients 
                 WHERE campaign_id = %d AND opened_at IS NOT NULL
             )
             WHERE id = %d",
            $campaign_id,
            $campaign_id
        ));
    }
    
    /**
     * Track email click
     */
    public function track_email_click($campaign_id, $subscriber_id, $url = '') {
        global $wpdb;
        
        // Update campaign recipient record
        $wpdb->update(
            $wpdb->prefix . 'ai_newsletter_campaign_recipients',
            array(
                'clicked_at' => current_time('mysql'),
                'click_count' => new WP_Query_Clause('click_count + 1')
            ),
            array(
                'campaign_id' => $campaign_id,
                'subscriber_id' => $subscriber_id
            )
        );
        
        // Track analytics event
        $this->track_event('email_click', $subscriber_id, $campaign_id, null, array('url' => $url));
        
        // Update campaign totals
        $wpdb->query($wpdb->prepare(
            "UPDATE " . AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE . " 
             SET clicked_count = (
                 SELECT COUNT(DISTINCT subscriber_id) 
                 FROM " . $wpdb->prefix . "ai_newsletter_campaign_recipients 
                 WHERE campaign_id = %d AND clicked_at IS NOT NULL
             )
             WHERE id = %d",
            $campaign_id,
            $campaign_id
        ));
    }
}