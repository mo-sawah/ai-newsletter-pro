<?php
/**
 * Email Services Base Class for AI Newsletter Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Newsletter_Pro_Email_Services {
    
    private $active_services = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_active_services();
        add_action('ai_newsletter_sync_subscriber', array($this, 'sync_subscriber_to_services'), 10, 2);
        add_action('ai_newsletter_unsubscribe_subscriber', array($this, 'unsubscribe_from_services'), 10, 1);
    }
    
    /**
     * Load active email services
     */
    private function load_active_services() {
        $settings = get_option('ai_newsletter_pro_settings', array());
        $integrations = $settings['integrations'] ?? array();
        
        foreach ($integrations as $service => $config) {
            if (!empty($config['enabled']) && !empty($config['api_key'])) {
                $this->active_services[$service] = $this->get_service_instance($service, $config);
            }
        }
    }
    
    /**
     * Get service instance
     */
    private function get_service_instance($service, $config) {
        switch ($service) {
            case 'mailchimp':
                if (class_exists('AI_Newsletter_Pro_Mailchimp')) {
                    return new AI_Newsletter_Pro_Mailchimp($config);
                }
                break;
                
            case 'convertkit':
                if (class_exists('AI_Newsletter_Pro_ConvertKit')) {
                    return new AI_Newsletter_Pro_ConvertKit($config);
                }
                break;
                
            case 'zoho':
                if (class_exists('AI_Newsletter_Pro_Zoho')) {
                    return new AI_Newsletter_Pro_Zoho($config);
                }
                break;
                
            case 'sendgrid':
                if (class_exists('AI_Newsletter_Pro_SendGrid')) {
                    return new AI_Newsletter_Pro_SendGrid($config);
                }
                break;
                
            case 'activecampaign':
                if (class_exists('AI_Newsletter_Pro_ActiveCampaign')) {
                    return new AI_Newsletter_Pro_ActiveCampaign($config);
                }
                break;
        }
        
        return null;
    }
    
    /**
     * Sync subscriber to all active services
     */
    public function sync_subscriber_to_services($subscriber_id, $action = 'add') {
        if (empty($this->active_services)) {
            return;
        }
        
        global $wpdb;
        $subscriber = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE id = %d",
            $subscriber_id
        ));
        
        if (!$subscriber) {
            return;
        }
        
        foreach ($this->active_services as $service_name => $service) {
            try {
                switch ($action) {
                    case 'add':
                        $result = $service->add_subscriber($subscriber->email, $subscriber->name);
                        break;
                        
                    case 'update':
                        // Try to update, fall back to add if not exists
                        $result = $service->add_subscriber($subscriber->email, $subscriber->name);
                        break;
                        
                    case 'unsubscribe':
                        if (method_exists($service, 'unsubscribe')) {
                            $result = $service->unsubscribe($subscriber->email);
                        }
                        break;
                        
                    case 'delete':
                        if (method_exists($service, 'delete_subscriber')) {
                            $result = $service->delete_subscriber($subscriber->email);
                        }
                        break;
                }
                
                // Log sync result
                $this->log_sync_result($service_name, $subscriber->email, $action, $result);
                
            } catch (Exception $e) {
                error_log("AI Newsletter Pro: Failed to sync to {$service_name}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Unsubscribe from all services
     */
    public function unsubscribe_from_services($email) {
        foreach ($this->active_services as $service_name => $service) {
            try {
                if (method_exists($service, 'unsubscribe')) {
                    $service->unsubscribe($email);
                }
            } catch (Exception $e) {
                error_log("AI Newsletter Pro: Failed to unsubscribe from {$service_name}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Test all active service connections
     */
    public function test_all_connections() {
        $results = array();
        
        foreach ($this->active_services as $service_name => $service) {
            if (method_exists($service, 'test_connection')) {
                $results[$service_name] = $service->test_connection();
            } else {
                $results[$service_name] = array(
                    'success' => false, 
                    'message' => __('Test connection method not available', 'ai-newsletter-pro')
                );
            }
        }
        
        return $results;
    }
    
    /**
     * Get subscriber counts from all services
     */
    public function get_service_subscriber_counts() {
        $counts = array();
        
        foreach ($this->active_services as $service_name => $service) {
            try {
                if (method_exists($service, 'get_subscriber_count')) {
                    $counts[$service_name] = $service->get_subscriber_count();
                } elseif (method_exists($service, 'get_list_info')) {
                    $list_info = $service->get_list_info();
                    $counts[$service_name] = $list_info['data']['stats']['member_count'] ?? 0;
                } else {
                    $counts[$service_name] = __('Not available', 'ai-newsletter-pro');
                }
            } catch (Exception $e) {
                $counts[$service_name] = __('Error retrieving count', 'ai-newsletter-pro');
                error_log("AI Newsletter Pro: Error getting subscriber count from {$service_name}: " . $e->getMessage());
            }
        }
        
        return $counts;
    }
    
    /**
     * Bulk sync subscribers to all services
     */
    public function bulk_sync_subscribers($limit = 50) {
        global $wpdb;
        
        $subscribers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " 
             WHERE status = 'subscribed' 
             ORDER BY subscribed_at DESC 
             LIMIT %d",
            $limit
        ));
        
        $results = array(
            'total' => count($subscribers),
            'success' => 0,
            'failed' => 0,
            'services' => array()
        );
        
        foreach ($this->active_services as $service_name => $service) {
            $service_results = array('success' => 0, 'failed' => 0, 'errors' => array());
            
            foreach ($subscribers as $subscriber) {
                try {
                    $sync_result = $service->add_subscriber($subscriber->email, $subscriber->name);
                    
                    if ($sync_result['success']) {
                        $service_results['success']++;
                    } else {
                        $service_results['failed']++;
                        $service_results['errors'][] = $subscriber->email . ': ' . $sync_result['message'];
                    }
                    
                    // Prevent rate limiting
                    usleep(100000); // 0.1 second delay
                    
                } catch (Exception $e) {
                    $service_results['failed']++;
                    $service_results['errors'][] = $subscriber->email . ': ' . $e->getMessage();
                }
            }
            
            $results['services'][$service_name] = $service_results;
            $results['success'] += $service_results['success'];
            $results['failed'] += $service_results['failed'];
        }
        
        return $results;
    }
    
    /**
     * Log sync results
     */
    private function log_sync_result($service, $email, $action, $result) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'service' => $service,
            'email' => $email,
            'action' => $action,
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'Unknown result'
        );
        
        // Store in transient for recent activity
        $recent_syncs = get_transient('ai_newsletter_recent_syncs') ?: array();
        array_unshift($recent_syncs, $log_entry);
        $recent_syncs = array_slice($recent_syncs, 0, 50); // Keep last 50 entries
        set_transient('ai_newsletter_recent_syncs', $recent_syncs, 24 * HOUR_IN_SECONDS);
    }
    
    /**
     * Get recent sync activity
     */
    public function get_recent_sync_activity() {
        return get_transient('ai_newsletter_recent_syncs') ?: array();
    }
    
    /**
     * Get available services
     */
    public function get_available_services() {
        return array(
            'mailchimp' => array(
                'name' => 'Mailchimp',
                'description' => 'World\'s largest marketing automation platform',
                'website' => 'https://mailchimp.com',
                'class' => 'AI_Newsletter_Pro_Mailchimp'
            ),
            'convertkit' => array(
                'name' => 'ConvertKit',
                'description' => 'Email marketing for online creators',
                'website' => 'https://convertkit.com',
                'class' => 'AI_Newsletter_Pro_ConvertKit'
            ),
            'zoho' => array(
                'name' => 'Zoho Campaigns',
                'description' => 'Email marketing by Zoho',
                'website' => 'https://zoho.com/campaigns',
                'class' => 'AI_Newsletter_Pro_Zoho'
            ),
            'sendgrid' => array(
                'name' => 'SendGrid',
                'description' => 'Email delivery service by Twilio',
                'website' => 'https://sendgrid.com',
                'class' => 'AI_Newsletter_Pro_SendGrid'
            ),
            'activecampaign' => array(
                'name' => 'ActiveCampaign',
                'description' => 'Customer experience automation',
                'website' => 'https://activecampaign.com',
                'class' => 'AI_Newsletter_Pro_ActiveCampaign'
            )
        );
    }
    
    /**
     * Check if service is active
     */
    public function is_service_active($service) {
        return isset($this->active_services[$service]);
    }
    
    /**
     * Get active services list
     */
    public function get_active_services() {
        return array_keys($this->active_services);
    }
    
    /**
     * Disable service
     */
    public function disable_service($service) {
        unset($this->active_services[$service]);
        
        $settings = get_option('ai_newsletter_pro_settings', array());
        if (isset($settings['integrations'][$service])) {
            $settings['integrations'][$service]['enabled'] = false;
            update_option('ai_newsletter_pro_settings', $settings);
        }
    }
    
    /**
     * Enable service
     */
    public function enable_service($service, $config) {
        $service_instance = $this->get_service_instance($service, $config);
        
        if ($service_instance) {
            $this->active_services[$service] = $service_instance;
            
            $settings = get_option('ai_newsletter_pro_settings', array());
            $settings['integrations'][$service] = array_merge($config, array('enabled' => true));
            update_option('ai_newsletter_pro_settings', $settings);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get service statistics
     */
    public function get_service_statistics() {
        $stats = array();
        
        foreach ($this->active_services as $service_name => $service) {
            $stats[$service_name] = array(
                'name' => $service_name,
                'status' => 'active',
                'subscriber_count' => 'N/A',
                'last_sync' => 'N/A',
                'sync_errors' => 0
            );
            
            // Get recent sync data for this service
            $recent_syncs = $this->get_recent_sync_activity();
            $service_syncs = array_filter($recent_syncs, function($sync) use ($service_name) {
                return $sync['service'] === $service_name;
            });
            
            if (!empty($service_syncs)) {
                $latest_sync = reset($service_syncs);
                $stats[$service_name]['last_sync'] = $latest_sync['timestamp'];
                
                $errors = array_filter($service_syncs, function($sync) {
                    return !$sync['success'];
                });
                $stats[$service_name]['sync_errors'] = count($errors);
            }
        }
        
        return $stats;
    }
    
    /**
     * Export subscribers from a specific service
     */
    public function export_from_service($service_name, $limit = 1000) {
        if (!isset($this->active_services[$service_name])) {
            return array('success' => false, 'message' => __('Service not active', 'ai-newsletter-pro'));
        }
        
        $service = $this->active_services[$service_name];
        
        try {
            if (method_exists($service, 'get_subscribers')) {
                return $service->get_subscribers($limit);
            } elseif (method_exists($service, 'export_subscribers')) {
                return $service->export_subscribers($limit);
            } else {
                return array('success' => false, 'message' => __('Export not supported by this service', 'ai-newsletter-pro'));
            }
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
    
    /**
     * Import subscribers to a specific service
     */
    public function import_to_service($service_name, $subscribers) {
        if (!isset($this->active_services[$service_name])) {
            return array('success' => false, 'message' => __('Service not active', 'ai-newsletter-pro'));
        }
        
        $service = $this->active_services[$service_name];
        $results = array('success' => 0, 'failed' => 0, 'errors' => array());
        
        foreach ($subscribers as $subscriber) {
            try {
                $result = $service->add_subscriber($subscriber['email'], $subscriber['name'] ?? '');
                
                if ($result['success']) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = $subscriber['email'] . ': ' . $result['message'];
                }
                
                // Rate limiting
                usleep(100000); // 0.1 second delay
                
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = $subscriber['email'] . ': ' . $e->getMessage();
            }
        }
        
        return $results;
    }
    
    /**
     * Validate service configuration
     */
    public function validate_service_config($service, $config) {
        $required_fields = $this->get_service_required_fields($service);
        $missing_fields = array();
        
        foreach ($required_fields as $field) {
            if (empty($config[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            return array(
                'success' => false, 
                'message' => sprintf(__('Missing required fields: %s', 'ai-newsletter-pro'), implode(', ', $missing_fields))
            );
        }
        
        // Test connection if possible
        $service_instance = $this->get_service_instance($service, $config);
        if ($service_instance && method_exists($service_instance, 'test_connection')) {
            return $service_instance->test_connection();
        }
        
        return array('success' => true, 'message' => __('Configuration appears valid', 'ai-newsletter-pro'));
    }
    
    /**
     * Get required fields for a service
     */
    private function get_service_required_fields($service) {
        $required_fields = array(
            'mailchimp' => array('api_key', 'list_id'),
            'convertkit' => array('api_key', 'form_id'),
            'zoho' => array('client_id', 'client_secret'),
            'sendgrid' => array('api_key'),
            'activecampaign' => array('api_url', 'api_key')
        );
        
        return $required_fields[$service] ?? array('api_key');
    }
    
    /**
     * Handle webhook from email service
     */
    public function handle_webhook($service, $data) {
        switch ($service) {
            case 'mailchimp':
                return $this->handle_mailchimp_webhook($data);
            case 'convertkit':
                return $this->handle_convertkit_webhook($data);
            default:
                return array('success' => false, 'message' => __('Webhook not supported for this service', 'ai-newsletter-pro'));
        }
    }
    
    /**
     * Handle Mailchimp webhook
     */
    private function handle_mailchimp_webhook($data) {
        $type = $data['type'] ?? '';
        $email = $data['data']['email'] ?? '';
        
        if (!$email) {
            return array('success' => false, 'message' => __('No email provided', 'ai-newsletter-pro'));
        }
        
        global $wpdb;
        
        switch ($type) {
            case 'unsubscribe':
                $wpdb->update(
                    AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE,
                    array(
                        'status' => 'unsubscribed',
                        'unsubscribed_at' => current_time('mysql')
                    ),
                    array('email' => $email)
                );
                break;
                
            case 'subscribe':
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE email = %s",
                    $email
                ));
                
                if (!$existing) {
                    $wpdb->insert(
                        AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE,
                        array(
                            'email' => $email,
                            'name' => $data['data']['merges']['FNAME'] ?? '',
                            'status' => 'subscribed',
                            'source' => 'mailchimp_webhook',
                            'subscribed_at' => current_time('mysql')
                        )
                    );
                }
                break;
                
            case 'cleaned':
                $wpdb->update(
                    AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE,
                    array('status' => 'bounced'),
                    array('email' => $email)
                );
                break;
        }
        
        return array('success' => true, 'message' => __('Webhook processed', 'ai-newsletter-pro'));
    }
    
    /**
     * Handle ConvertKit webhook
     */
    private function handle_convertkit_webhook($data) {
        $email = $data['subscriber']['email_address'] ?? '';
        
        if (!$email) {
            return array('success' => false, 'message' => __('No email provided', 'ai-newsletter-pro'));
        }
        
        global $wpdb;
        
        // ConvertKit sends subscriber data on form subscription
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE email = %s",
            $email
        ));
        
        if (!$existing) {
            $wpdb->insert(
                AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE,
                array(
                    'email' => $email,
                    'name' => $data['subscriber']['first_name'] ?? '',
                    'status' => 'subscribed',
                    'source' => 'convertkit_webhook',
                    'subscribed_at' => current_time('mysql')
                )
            );
        }
        
        return array('success' => true, 'message' => __('Webhook processed', 'ai-newsletter-pro'));
    }
    
    /**
     * Schedule regular sync with all services
     */
    public function schedule_regular_sync() {
        if (!wp_next_scheduled('ai_newsletter_regular_sync')) {
            wp_schedule_event(time(), 'daily', 'ai_newsletter_regular_sync');
        }
    }
    
    /**
     * Perform regular sync (called by cron)
     */
    public static function perform_regular_sync() {
        $email_services = new self();
        
        // Get recently added subscribers (last 24 hours)
        global $wpdb;
        $recent_subscribers = $wpdb->get_results(
            "SELECT * FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " 
             WHERE status = 'subscribed' 
             AND subscribed_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             ORDER BY subscribed_at DESC"
        );
        
        if (!empty($recent_subscribers)) {
            foreach ($email_services->active_services as $service_name => $service) {
                try {
                    foreach ($recent_subscribers as $subscriber) {
                        $service->add_subscriber($subscriber->email, $subscriber->name);
                        usleep(200000); // 0.2 second delay between calls
                    }
                } catch (Exception $e) {
                    error_log("AI Newsletter Pro: Regular sync failed for {$service_name}: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Get service health status
     */
    public function get_service_health() {
        $health_status = array();
        
        foreach ($this->active_services as $service_name => $service) {
            $status = array(
                'name' => $service_name,
                'active' => true,
                'last_check' => current_time('mysql'),
                'status' => 'unknown',
                'message' => ''
            );
            
            try {
                if (method_exists($service, 'test_connection')) {
                    $test_result = $service->test_connection();
                    $status['status'] = $test_result['success'] ? 'healthy' : 'error';
                    $status['message'] = $test_result['message'];
                } else {
                    $status['status'] = 'no_test';
                    $status['message'] = __('Health check not available', 'ai-newsletter-pro');
                }
            } catch (Exception $e) {
                $status['status'] = 'error';
                $status['message'] = $e->getMessage();
            }
            
            $health_status[$service_name] = $status;
        }
        
        return $health_status;
    }
    
    /**
     * Clean up old sync logs
     */
    public function cleanup_old_sync_logs() {
        $recent_syncs = get_transient('ai_newsletter_recent_syncs') ?: array();
        
        // Remove entries older than 7 days
        $week_ago = strtotime('-7 days');
        $recent_syncs = array_filter($recent_syncs, function($sync) use ($week_ago) {
            return strtotime($sync['timestamp']) > $week_ago;
        });
        
        set_transient('ai_newsletter_recent_syncs', $recent_syncs, 24 * HOUR_IN_SECONDS);
    }
}

// Hook for regular sync
add_action('ai_newsletter_regular_sync', array('AI_Newsletter_Pro_Email_Services', 'perform_regular_sync'));

// Initialize email services
if (!function_exists('ai_newsletter_pro_get_email_services')) {
    function ai_newsletter_pro_get_email_services() {
        static $instance = null;
        if ($instance === null) {
            $instance = new AI_Newsletter_Pro_Email_Services();
        }
        return $instance;
    }
}