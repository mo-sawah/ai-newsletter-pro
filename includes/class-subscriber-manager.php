<?php
/**
 * Subscriber Manager class for AI Newsletter Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Newsletter_Pro_Subscriber_Manager {
    
    /**
     * Add new subscriber
     */
    public function add_subscriber($email, $name = '', $source = 'unknown', $status = 'subscribed') {
        global $wpdb;
        
        // Validate email
        if (!is_email($email)) {
            return array('success' => false, 'message' => __('Invalid email address', 'ai-newsletter-pro'));
        }
        
        // Check if subscriber already exists
        $existing = $this->get_subscriber_by_email($email);
        if ($existing) {
            if ($existing->status === 'subscribed') {
                return array('success' => false, 'message' => __('Already subscribed', 'ai-newsletter-pro'));
            } else {
                // Reactivate subscriber
                return $this->reactivate_subscriber($email);
            }
        }
        
        // Get settings
        $settings = get_option('ai_newsletter_pro_settings', array());
        $double_optin = $settings['general']['double_optin'] ?? true;
        
        // Set status based on double opt-in setting
        $subscriber_status = $double_optin ? 'pending' : 'subscribed';
        
        // Insert subscriber
        $result = $wpdb->insert(
            AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE,
            array(
                'email' => sanitize_email($email),
                'name' => sanitize_text_field($name),
                'status' => $subscriber_status,
                'source' => sanitize_text_field($source),
                'subscribed_at' => current_time('mysql'),
                'last_activity' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return array('success' => false, 'message' => __('Database error occurred', 'ai-newsletter-pro'));
        }
        
        $subscriber_id = $wpdb->insert_id;
        
        // Log analytics event
        $this->log_event('subscription', $subscriber_id, null, null, array(
            'source' => $source,
            'double_optin' => $double_optin
        ));
        
        // Send welcome email or confirmation email
        if ($double_optin) {
            $this->send_confirmation_email($email, $subscriber_id);
            $message = __('Please check your email to confirm subscription', 'ai-newsletter-pro');
        } else {
            $this->send_welcome_email($email, $name);
            $message = __('Successfully subscribed!', 'ai-newsletter-pro');
            
            // Sync with email services
            $this->sync_to_email_services($subscriber_id);
        }
        
        return array('success' => true, 'message' => $message, 'subscriber_id' => $subscriber_id);
    }
    
    /**
     * Confirm subscription (for double opt-in)
     */
    public function confirm_subscription($token) {
        global $wpdb;
        
        // Decode token to get subscriber ID
        $subscriber_id = $this->decode_confirmation_token($token);
        if (!$subscriber_id) {
            return array('success' => false, 'message' => __('Invalid confirmation link', 'ai-newsletter-pro'));
        }
        
        // Update subscriber status
        $result = $wpdb->update(
            AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE,
            array(
                'status' => 'subscribed',
                'last_activity' => current_time('mysql')
            ),
            array('id' => $subscriber_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return array('success' => false, 'message' => __('Confirmation failed', 'ai-newsletter-pro'));
        }
        
        $subscriber = $this->get_subscriber($subscriber_id);
        
        // Send welcome email
        $this->send_welcome_email($subscriber->email, $subscriber->name);
        
        // Sync with email services
        $this->sync_to_email_services($subscriber_id);
        
        // Log analytics event
        $this->log_event('subscription_confirmed', $subscriber_id);
        
        return array('success' => true, 'message' => __('Subscription confirmed!', 'ai-newsletter-pro'));
    }
    
    /**
     * Unsubscribe subscriber
     */
    public function unsubscribe($email, $reason = '') {
        global $wpdb;
        
        $subscriber = $this->get_subscriber_by_email($email);
        if (!$subscriber) {
            return array('success' => false, 'message' => __('Subscriber not found', 'ai-newsletter-pro'));
        }
        
        // Update subscriber status
        $result = $wpdb->update(
            AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE,
            array(
                'status' => 'unsubscribed',
                'unsubscribed_at' => current_time('mysql'),
                'last_activity' => current_time('mysql')
            ),
            array('id' => $subscriber->id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return array('success' => false, 'message' => __('Unsubscribe failed', 'ai-newsletter-pro'));
        }
        
        // Log analytics event
        $this->log_event('unsubscription', $subscriber->id, null, null, array(
            'reason' => $reason
        ));
        
        // Sync with email services
        $this->sync_unsubscribe_to_email_services($subscriber->id);
        
        // Send unsubscribe confirmation email
        $this->send_unsubscribe_confirmation($email);
        
        return array('success' => true, 'message' => __('Successfully unsubscribed', 'ai-newsletter-pro'));
    }
    
    /**
     * Reactivate subscriber
     */
    public function reactivate_subscriber($email) {
        global $wpdb;
        
        $result = $wpdb->update(
            AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE,
            array(
                'status' => 'subscribed',
                'unsubscribed_at' => null,
                'last_activity' => current_time('mysql')
            ),
            array('email' => $email),
            array('%s', '%s', '%s'),
            array('%s')
        );
        
        if ($result === false) {
            return array('success' => false, 'message' => __('Reactivation failed', 'ai-newsletter-pro'));
        }
        
        $subscriber = $this->get_subscriber_by_email($email);
        
        // Log analytics event
        $this->log_event('resubscription', $subscriber->id);
        
        // Sync with email services
        $this->sync_to_email_services($subscriber->id);
        
        return array('success' => true, 'message' => __('Welcome back! You\'re subscribed again.', 'ai-newsletter-pro'));
    }
    
    /**
     * Get subscriber by ID
     */
    public function get_subscriber($subscriber_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE id = %d",
            $subscriber_id
        ));
    }
    
    /**
     * Get subscriber by email
     */
    public function get_subscriber_by_email($email) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE email = %s",
            $email
        ));
    }
    
    /**
     * Get subscribers with pagination
     */
    public function get_subscribers($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'subscribed',
            'limit' => 50,
            'offset' => 0,
            'search' => '',
            'order_by' => 'subscribed_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array('1=1');
        $where_values = array();
        
        // Status filter
        if (!empty($args['status']) && $args['status'] !== 'all') {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        // Search filter
        if (!empty($args['search'])) {
            $where_clauses[] = '(email LIKE %s OR name LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        $order_sql = sprintf('%s %s', sanitize_sql_orderby($args['order_by']), $args['order']);
        
        $sql = "SELECT * FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " 
                WHERE {$where_sql} 
                ORDER BY {$order_sql} 
                LIMIT %d OFFSET %d";
        
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get subscriber count
     */
    public function get_subscriber_count($status = 'subscribed') {
        global $wpdb;
        
        if ($status === 'all') {
            return $wpdb->get_var("SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE);
        }
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE status = %s",
            $status
        ));
    }
    
    /**
     * Update subscriber
     */
    public function update_subscriber($subscriber_id, $data) {
        global $wpdb;
        
        $allowed_fields = array('name', 'status', 'tags', 'meta_data');
        $update_data = array();
        $update_format = array();
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                $update_data[$field] = $value;
                $update_format[] = '%s';
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $update_data['last_activity'] = current_time('mysql');
        $update_format[] = '%s';
        
        return $wpdb->update(
            AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE,
            $update_data,
            array('id' => $subscriber_id),
            $update_format,
            array('%d')
        );
    }
    
    /**
     * Delete subscriber
     */
    public function delete_subscriber($subscriber_id) {
        global $wpdb;
        
        // Log deletion event first
        $this->log_event('subscriber_deleted', $subscriber_id);
        
        // Delete from email services
        $this->sync_deletion_to_email_services($subscriber_id);
        
        // Delete from database
        return $wpdb->delete(
            AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE,
            array('id' => $subscriber_id),
            array('%d')
        );
    }
    
    /**
     * Import subscribers from CSV
     */
    public function import_from_csv($file_path, $options = array()) {
        if (!file_exists($file_path)) {
            return array('success' => false, 'message' => __('File not found', 'ai-newsletter-pro'));
        }
        
        $defaults = array(
            'email_column' => 0,
            'name_column' => 1,
            'skip_header' => true,
            'update_existing' => false
        );
        
        $options = wp_parse_args($options, $defaults);
        
        $imported = 0;
        $skipped = 0;
        $errors = array();
        
        if (($handle = fopen($file_path, 'r')) !== false) {
            $row_number = 0;
            
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $row_number++;
                
                // Skip header row
                if ($row_number === 1 && $options['skip_header']) {
                    continue;
                }
                
                // Validate row data
                if (!isset($data[$options['email_column']])) {
                    $errors[] = sprintf(__('Row %d: Missing email column', 'ai-newsletter-pro'), $row_number);
                    continue;
                }
                
                $email = sanitize_email($data[$options['email_column']]);
                $name = isset($data[$options['name_column']]) ? sanitize_text_field($data[$options['name_column']]) : '';
                
                if (!is_email($email)) {
                    $errors[] = sprintf(__('Row %d: Invalid email address', 'ai-newsletter-pro'), $row_number);
                    continue;
                }
                
                // Check if subscriber exists
                $existing = $this->get_subscriber_by_email($email);
                
                if ($existing) {
                    if ($options['update_existing']) {
                        $this->update_subscriber($existing->id, array('name' => $name));
                        $imported++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $result = $this->add_subscriber($email, $name, 'csv_import', 'subscribed');
                    if ($result['success']) {
                        $imported++;
                    } else {
                        $errors[] = sprintf(__('Row %d: %s', 'ai-newsletter-pro'), $row_number, $result['message']);
                    }
                }
            }
            
            fclose($handle);
        }
        
        return array(
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        );
    }
    
    /**
     * Export subscribers to CSV
     */
    public function export_to_csv($status = 'subscribed', $filename = null) {
        if (!$filename) {
            $filename = 'subscribers_' . date('Y-m-d_H-i-s') . '.csv';
        }
        
        $subscribers = $this->get_subscribers(array(
            'status' => $status,
            'limit' => 999999
        ));
        
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        $file = fopen($file_path, 'w');
        
        // Write header
        fputcsv($file, array('Email', 'Name', 'Status', 'Source', 'Subscribed At', 'Tags'));
        
        // Write data
        foreach ($subscribers as $subscriber) {
            fputcsv($file, array(
                $subscriber->email,
                $subscriber->name,
                $subscriber->status,
                $subscriber->source,
                $subscriber->subscribed_at,
                $subscriber->tags
            ));
        }
        
        fclose($file);
        
        return array(
            'success' => true,
            'file_path' => $file_path,
            'file_url' => $upload_dir['url'] . '/' . $filename,
            'count' => count($subscribers)
        );
    }
    
    /**
     * Send confirmation email
     */
    private function send_confirmation_email($email, $subscriber_id) {
        $token = $this->generate_confirmation_token($subscriber_id);
        $confirmation_url = add_query_arg(array(
            'ai_newsletter_action' => 'confirm',
            'token' => $token
        ), home_url());
        
        $subject = __('Please confirm your subscription', 'ai-newsletter-pro');
        $message = sprintf(
            __('Please click the following link to confirm your subscription: %s', 'ai-newsletter-pro'),
            $confirmation_url
        );
        
        wp_mail($email, $subject, $message);
    }
    
    /**
     * Send welcome email
     */
    private function send_welcome_email($email, $name) {
        $settings = get_option('ai_newsletter_pro_settings', array());
        $from_name = $settings['general']['from_name'] ?? get_bloginfo('name');
        
        $subject = sprintf(__('Welcome to %s!', 'ai-newsletter-pro'), $from_name);
        $message = sprintf(
            __('Hi %s,\n\nWelcome to our newsletter! You\'ll receive our latest updates and exclusive content.\n\nThanks for subscribing!', 'ai-newsletter-pro'),
            $name ?: __('there', 'ai-newsletter-pro')
        );
        
        wp_mail($email, $subject, $message);
    }
    
    /**
     * Send unsubscribe confirmation
     */
    private function send_unsubscribe_confirmation($email) {
        $subject = __('Unsubscribe Confirmation', 'ai-newsletter-pro');
        $message = __('You have been successfully unsubscribed from our newsletter. We\'re sorry to see you go!', 'ai-newsletter-pro');
        
        wp_mail($email, $subject, $message);
    }
    
    /**
     * Generate confirmation token
     */
    private function generate_confirmation_token($subscriber_id) {
        return wp_hash($subscriber_id . time(), 'nonce');
    }
    
    /**
     * Decode confirmation token
     */
    private function decode_confirmation_token($token) {
        // Simple token validation - in production, use more secure method
        global $wpdb;
        
        $subscribers = $wpdb->get_results(
            "SELECT id FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE status = 'pending'"
        );
        
        foreach ($subscribers as $subscriber) {
            if (wp_hash($subscriber->id . time(), 'nonce') === $token) {
                return $subscriber->id;
            }
            // Check tokens from last 24 hours
            for ($i = 0; $i < 24 * 60; $i++) {
                $test_time = time() - ($i * 60);
                if (wp_hash($subscriber->id . $test_time, 'nonce') === $token) {
                    return $subscriber->id;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Sync subscriber to email services
     */
    private function sync_to_email_services($subscriber_id) {
        $subscriber = $this->get_subscriber($subscriber_id);
        if (!$subscriber) return;
        
        $settings = get_option('ai_newsletter_pro_settings', array());
        $integrations = $settings['integrations'] ?? array();
        
        foreach ($integrations as $service => $config) {
            if (!empty($config['enabled'])) {
                $this->sync_subscriber_to_service($subscriber, $service, $config);
            }
        }
    }
    
    /**
     * Sync subscriber to specific service
     */
    private function sync_subscriber_to_service($subscriber, $service, $config) {
        try {
            switch ($service) {
                case 'mailchimp':
                    if (class_exists('AI_Newsletter_Pro_Mailchimp')) {
                        $mailchimp = new AI_Newsletter_Pro_Mailchimp($config);
                        $mailchimp->add_subscriber($subscriber->email, $subscriber->name);
                    }
                    break;
                
                case 'convertkit':
                    if (class_exists('AI_Newsletter_Pro_ConvertKit')) {
                        $convertkit = new AI_Newsletter_Pro_ConvertKit($config);
                        $convertkit->add_subscriber($subscriber->email, $subscriber->name);
                    }
                    break;
                
                case 'zoho':
                    if (class_exists('AI_Newsletter_Pro_Zoho')) {
                        $zoho = new AI_Newsletter_Pro_Zoho($config);
                        $zoho->add_subscriber($subscriber->email, $subscriber->name);
                    }
                    break;
                
                case 'sendgrid':
                    if (class_exists('AI_Newsletter_Pro_SendGrid')) {
                        $sendgrid = new AI_Newsletter_Pro_SendGrid($config);
                        $sendgrid->add_contact($subscriber->email, $subscriber->name);
                    }
                    break;
                
                case 'activecampaign':
                    if (class_exists('AI_Newsletter_Pro_ActiveCampaign')) {
                        $activecampaign = new AI_Newsletter_Pro_ActiveCampaign($config);
                        $activecampaign->add_contact($subscriber->email, $subscriber->name);
                    }
                    break;
            }
        } catch (Exception $e) {
            error_log('AI Newsletter Pro: Failed to sync subscriber to ' . $service . ': ' . $e->getMessage());
        }
    }
    
    /**
     * Sync unsubscribe to email services
     */
    private function sync_unsubscribe_to_email_services($subscriber_id) {
        $subscriber = $this->get_subscriber($subscriber_id);
        if (!$subscriber) return;
        
        $settings = get_option('ai_newsletter_pro_settings', array());
        $integrations = $settings['integrations'] ?? array();
        
        foreach ($integrations as $service => $config) {
            if (!empty($config['enabled'])) {
                $this->sync_unsubscribe_to_service($subscriber, $service, $config);
            }
        }
    }
    
    /**
     * Sync unsubscribe to specific service
     */
    private function sync_unsubscribe_to_service($subscriber, $service, $config) {
        try {
            switch ($service) {
                case 'mailchimp':
                    if (class_exists('AI_Newsletter_Pro_Mailchimp')) {
                        $mailchimp = new AI_Newsletter_Pro_Mailchimp($config);
                        $mailchimp->unsubscribe($subscriber->email);
                    }
                    break;
                
                case 'convertkit':
                    if (class_exists('AI_Newsletter_Pro_ConvertKit')) {
                        $convertkit = new AI_Newsletter_Pro_ConvertKit($config);
                        $convertkit->unsubscribe($subscriber->email);
                    }
                    break;
                
                // Add other services as needed
            }
        } catch (Exception $e) {
            error_log('AI Newsletter Pro: Failed to sync unsubscribe to ' . $service . ': ' . $e->getMessage());
        }
    }
    
    /**
     * Sync deletion to email services
     */
    private function sync_deletion_to_email_services($subscriber_id) {
        $subscriber = $this->get_subscriber($subscriber_id);
        if (!$subscriber) return;
        
        $settings = get_option('ai_newsletter_pro_settings', array());
        $integrations = $settings['integrations'] ?? array();
        
        foreach ($integrations as $service => $config) {
            if (!empty($config['enabled'])) {
                $this->sync_deletion_to_service($subscriber, $service, $config);
            }
        }
    }
    
    /**
     * Sync deletion to specific service
     */
    private function sync_deletion_to_service($subscriber, $service, $config) {
        try {
            switch ($service) {
                case 'mailchimp':
                    if (class_exists('AI_Newsletter_Pro_Mailchimp')) {
                        $mailchimp = new AI_Newsletter_Pro_Mailchimp($config);
                        $mailchimp->delete_subscriber($subscriber->email);
                    }
                    break;
                
                // Add other services as needed
            }
        } catch (Exception $e) {
            error_log('AI Newsletter Pro: Failed to sync deletion to ' . $service . ': ' . $e->getMessage());
        }
    }
    
    /**
     * Log analytics event
     */
    private function log_event($event_type, $subscriber_id = null, $campaign_id = null, $widget_id = null, $metadata = array()) {
        global $wpdb;
        
        $wpdb->insert(
            AI_NEWSLETTER_PRO_ANALYTICS_TABLE,
            array(
                'event_type' => $event_type,
                'subscriber_id' => $subscriber_id,
                'campaign_id' => $campaign_id,
                'widget_id' => $widget_id,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip_address' => $this->get_client_ip(),
                'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
                'metadata' => json_encode($metadata),
                'created_at' => current_time('mysql')
            )
        );
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
     * Add tags to subscriber
     */
    public function add_tags($subscriber_id, $tags) {
        $subscriber = $this->get_subscriber($subscriber_id);
        if (!$subscriber) return false;
        
        $existing_tags = !empty($subscriber->tags) ? json_decode($subscriber->tags, true) : array();
        $new_tags = array_merge($existing_tags, (array)$tags);
        $new_tags = array_unique($new_tags);
        
        return $this->update_subscriber($subscriber_id, array('tags' => json_encode($new_tags)));
    }
    
    /**
     * Remove tags from subscriber
     */
    public function remove_tags($subscriber_id, $tags) {
        $subscriber = $this->get_subscriber($subscriber_id);
        if (!$subscriber) return false;
        
        $existing_tags = !empty($subscriber->tags) ? json_decode($subscriber->tags, true) : array();
        $remaining_tags = array_diff($existing_tags, (array)$tags);
        
        return $this->update_subscriber($subscriber_id, array('tags' => json_encode($remaining_tags)));
    }
    
    /**
     * Get subscribers by tags
     */
    public function get_subscribers_by_tags($tags, $operator = 'OR') {
        global $wpdb;
        
        $tags = (array)$tags;
        $conditions = array();
        
        foreach ($tags as $tag) {
            $conditions[] = $wpdb->prepare('tags LIKE %s', '%"' . $wpdb->esc_like($tag) . '"%');
        }
        
        $operator = strtoupper($operator) === 'AND' ? 'AND' : 'OR';
        $where_clause = implode(" {$operator} ", $conditions);
        
        $sql = "SELECT * FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " 
                WHERE status = 'subscribed' AND ({$where_clause}) 
                ORDER BY subscribed_at DESC";
        
        return $wpdb->get_results($sql);
    }
}