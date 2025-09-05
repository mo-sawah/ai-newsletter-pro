<?php
/**
 * Campaign Manager class for AI Newsletter Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Newsletter_Pro_Campaign_Manager {
    
    /**
     * Create new campaign
     */
    public function create_campaign($title, $subject, $content, $options = array()) {
        global $wpdb;
        
        $defaults = array(
            'type' => 'manual',
            'status' => 'draft',
            'template' => 'default',
            'scheduled_at' => null
        );
        
        $options = wp_parse_args($options, $defaults);
        
        $result = $wpdb->insert(
            AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE,
            array(
                'title' => sanitize_text_field($title),
                'subject' => sanitize_text_field($subject),
                'content' => wp_kses_post($content),
                'type' => sanitize_text_field($options['type']),
                'status' => sanitize_text_field($options['status']),
                'template' => sanitize_text_field($options['template']),
                'scheduled_at' => $options['scheduled_at'],
                'settings' => json_encode($options),
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            )
        );
        
        if ($result === false) {
            return array('success' => false, 'message' => __('Failed to create campaign', 'ai-newsletter-pro'));
        }
        
        $campaign_id = $wpdb->insert_id;
        
        return array(
            'success' => true, 
            'message' => __('Campaign created successfully', 'ai-newsletter-pro'),
            'campaign_id' => $campaign_id
        );
    }
    
    /**
     * Get campaign by ID
     */
    public function get_campaign($campaign_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE . " WHERE id = %d",
            $campaign_id
        ));
    }
    
    /**
     * Update campaign
     */
    public function update_campaign($campaign_id, $data) {
        global $wpdb;
        
        $allowed_fields = array('title', 'subject', 'content', 'status', 'template', 'scheduled_at', 'settings');
        $update_data = array();
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                if ($field === 'settings' && is_array($value)) {
                    $update_data[$field] = json_encode($value);
                } else {
                    $update_data[$field] = $value;
                }
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $update_data['updated_at'] = current_time('mysql');
        
        return $wpdb->update(
            AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE,
            $update_data,
            array('id' => $campaign_id)
        );
    }
    
    /**
     * Send campaign to subscribers
     */
    public function send_campaign($campaign_id, $recipient_filter = array()) {
        global $wpdb;
        
        $campaign = $this->get_campaign($campaign_id);
        if (!$campaign) {
            return array('success' => false, 'message' => __('Campaign not found', 'ai-newsletter-pro'));
        }
        
        if ($campaign->status !== 'draft' && $campaign->status !== 'scheduled') {
            return array('success' => false, 'message' => __('Campaign cannot be sent in current status', 'ai-newsletter-pro'));
        }
        
        // Get subscribers based on filter
        $subscribers = $this->get_campaign_recipients($recipient_filter);
        
        if (empty($subscribers)) {
            return array('success' => false, 'message' => __('No recipients found', 'ai-newsletter-pro'));
        }
        
        // Update campaign status
        $this->update_campaign($campaign_id, array(
            'status' => 'sending',
            'recipients_count' => count($subscribers)
        ));
        
        // Create recipient records
        $this->create_recipient_records($campaign_id, $subscribers);
        
        // Send emails
        $sent_count = $this->process_campaign_sending($campaign, $subscribers);
        
        // Update final status
        $final_status = $sent_count > 0 ? 'sent' : 'failed';
        $this->update_campaign($campaign_id, array(
            'status' => $final_status,
            'sent_count' => $sent_count,
            'sent_at' => current_time('mysql')
        ));
        
        return array(
            'success' => $sent_count > 0,
            'message' => sprintf(__('Campaign sent to %d recipients', 'ai-newsletter-pro'), $sent_count),
            'sent_count' => $sent_count,
            'total_recipients' => count($subscribers)
        );
    }
    
    /**
     * Get campaign recipients based on filter
     */
    private function get_campaign_recipients($filter = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'subscribed',
            'tags' => array(),
            'exclude_unsubscribed' => true
        );
        
        $filter = wp_parse_args($filter, $defaults);
        
        $where_clauses = array("status = 'subscribed'");
        $query_params = array();
        
        // Filter by tags if specified
        if (!empty($filter['tags'])) {
            $tag_conditions = array();
            foreach ($filter['tags'] as $tag) {
                $tag_conditions[] = 'tags LIKE %s';
                $query_params[] = '%"' . $wpdb->esc_like($tag) . '"%';
            }
            if (!empty($tag_conditions)) {
                $where_clauses[] = '(' . implode(' OR ', $tag_conditions) . ')';
            }
        }
        
        $where_clause = implode(' AND ', $where_clauses);
        
        $sql = "SELECT * FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE " . $where_clause;
        
        if (!empty($query_params)) {
            $sql = $wpdb->prepare($sql, $query_params);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Create recipient records for tracking
     */
    private function create_recipient_records($campaign_id, $subscribers) {
        global $wpdb;
        
        $values = array();
        $placeholders = array();
        
        foreach ($subscribers as $subscriber) {
            $values[] = $campaign_id;
            $values[] = $subscriber->id;
            $values[] = 'pending';
            $placeholders[] = '(%d, %d, %s)';
        }
        
        if (!empty($values)) {
            $sql = "INSERT INTO " . $wpdb->prefix . "ai_newsletter_campaign_recipients 
                    (campaign_id, subscriber_id, status) VALUES " . implode(', ', $placeholders);
            
            $wpdb->query($wpdb->prepare($sql, $values));
        }
    }
    
    /**
     * Process campaign sending
     */
    private function process_campaign_sending($campaign, $subscribers) {
        $sent_count = 0;
        
        foreach ($subscribers as $subscriber) {
            $success = $this->send_email_to_subscriber($campaign, $subscriber);
            
            if ($success) {
                $sent_count++;
                $this->update_recipient_status($campaign->id, $subscriber->id, 'sent');
            } else {
                $this->update_recipient_status($campaign->id, $subscriber->id, 'failed');
            }
            
            // Add small delay to prevent overwhelming mail server
            usleep(100000); // 0.1 second delay
        }
        
        return $sent_count;
    }
    
    /**
     * Send email to individual subscriber
     */
    private function send_email_to_subscriber($campaign, $subscriber) {
        $settings = get_option('ai_newsletter_pro_settings', array());
        $general = $settings['general'] ?? array();
        
        $from_name = $general['from_name'] ?? get_bloginfo('name');
        $from_email = $general['from_email'] ?? get_option('admin_email');
        
        // Personalize content
        $content = $this->personalize_content($campaign->content, $subscriber);
        
        // Add tracking pixels and links
        $content = $this->add_tracking_elements($content, $campaign->id, $subscriber->id);
        
        // Set headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: ' . ($general['reply_to'] ?? $from_email)
        );
        
        // Send email
        $result = wp_mail(
            $subscriber->email,
            $campaign->subject,
            $content,
            $headers
        );
        
        return $result;
    }
    
    /**
     * Personalize email content for subscriber
     */
    private function personalize_content($content, $subscriber) {
        $replacements = array(
            '{subscriber_email}' => $subscriber->email,
            '{subscriber_name}' => $subscriber->name ?: 'there',
            '{subscriber_first_name}' => explode(' ', $subscriber->name ?: 'Friend')[0],
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => home_url(),
            '{current_date}' => date_i18n(get_option('date_format'))
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
    
    /**
     * Add tracking elements to email content
     */
    private function add_tracking_elements($content, $campaign_id, $subscriber_id) {
        // Add tracking pixel for open tracking
        $tracking_pixel = $this->generate_tracking_pixel($campaign_id, $subscriber_id);
        
        // Add unsubscribe link
        $unsubscribe_link = $this->generate_unsubscribe_link($subscriber_id);
        
        // Insert tracking pixel before closing body tag
        $content = str_replace('</body>', $tracking_pixel . '</body>', $content);
        
        // Add unsubscribe footer if not present
        if (strpos($content, 'unsubscribe') === false) {
            $unsubscribe_footer = '<div style="text-align: center; font-size: 12px; color: #666; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">';
            $unsubscribe_footer .= sprintf(__('You received this email because you subscribed to our newsletter. <a href="%s">Unsubscribe</a>', 'ai-newsletter-pro'), $unsubscribe_link);
            $unsubscribe_footer .= '</div>';
            
            $content = str_replace('</body>', $unsubscribe_footer . '</body>', $content);
        }
        
        return $content;
    }
    
    /**
     * Generate tracking pixel for email opens
     */
    private function generate_tracking_pixel($campaign_id, $subscriber_id) {
        $tracking_url = add_query_arg(array(
            'ai_newsletter_track' => 'open',
            'c' => $campaign_id,
            's' => $subscriber_id,
            't' => wp_hash($campaign_id . $subscriber_id, 'nonce')
        ), home_url());
        
        return '<img src="' . esc_url($tracking_url) . '" width="1" height="1" style="display:none;" alt="">';
    }
    
    /**
     * Generate unsubscribe link
     */
    private function generate_unsubscribe_link($subscriber_id) {
        return add_query_arg(array(
            'ai_newsletter_action' => 'unsubscribe',
            'subscriber' => $subscriber_id,
            'token' => wp_hash($subscriber_id, 'nonce')
        ), home_url());
    }
    
    /**
     * Update recipient status
     */
    private function update_recipient_status($campaign_id, $subscriber_id, $status) {
        global $wpdb;
        
        $update_data = array('status' => $status);
        
        if ($status === 'sent') {
            $update_data['sent_at'] = current_time('mysql');
        }
        
        $wpdb->update(
            $wpdb->prefix . 'ai_newsletter_campaign_recipients',
            $update_data,
            array(
                'campaign_id' => $campaign_id,
                'subscriber_id' => $subscriber_id
            )
        );
    }
    
    /**
     * Schedule campaign for later sending
     */
    public function schedule_campaign($campaign_id, $send_time) {
        $result = $this->update_campaign($campaign_id, array(
            'status' => 'scheduled',
            'scheduled_at' => $send_time
        ));
        
        if ($result) {
            // Schedule WordPress cron job
            wp_schedule_single_event(strtotime($send_time), 'ai_newsletter_send_scheduled_campaign', array($campaign_id));
            
            return array('success' => true, 'message' => __('Campaign scheduled successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to schedule campaign', 'ai-newsletter-pro'));
    }
    
    /**
     * Get campaigns with pagination
     */
    public function get_campaigns($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'all',
            'type' => 'all',
            'limit' => 50,
            'offset' => 0,
            'order_by' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array('1=1');
        $where_values = array();
        
        if ($args['status'] !== 'all') {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        if ($args['type'] !== 'all') {
            $where_clauses[] = 'type = %s';
            $where_values[] = $args['type'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        $order_sql = sprintf('%s %s', sanitize_sql_orderby($args['order_by']), $args['order']);
        
        $sql = "SELECT * FROM " . AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE . " 
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
     * Delete campaign
     */
    public function delete_campaign($campaign_id) {
        global $wpdb;
        
        // Delete recipient records
        $wpdb->delete(
            $wpdb->prefix . 'ai_newsletter_campaign_recipients',
            array('campaign_id' => $campaign_id)
        );
        
        // Delete campaign
        $result = $wpdb->delete(
            AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE,
            array('id' => $campaign_id)
        );
        
        return $result !== false;
    }
    
    /**
     * Duplicate campaign
     */
    public function duplicate_campaign($campaign_id) {
        $campaign = $this->get_campaign($campaign_id);
        
        if (!$campaign) {
            return array('success' => false, 'message' => __('Campaign not found', 'ai-newsletter-pro'));
        }
        
        $new_title = $campaign->title . ' (Copy)';
        
        return $this->create_campaign(
            $new_title,
            $campaign->subject,
            $campaign->content,
            array(
                'type' => $campaign->type,
                'template' => $campaign->template
            )
        );
    }
    
    /**
     * Get campaign statistics
     */
    public function get_campaign_stats($campaign_id) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_recipients,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
                SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened_count,
                SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked_count,
                AVG(open_count) as avg_opens,
                AVG(click_count) as avg_clicks
             FROM " . $wpdb->prefix . "ai_newsletter_campaign_recipients 
             WHERE campaign_id = %d",
            $campaign_id
        ));
        
        if ($stats) {
            $stats->open_rate = $stats->sent_count > 0 ? ($stats->opened_count / $stats->sent_count) * 100 : 0;
            $stats->click_rate = $stats->sent_count > 0 ? ($stats->clicked_count / $stats->sent_count) * 100 : 0;
            $stats->click_to_open_rate = $stats->opened_count > 0 ? ($stats->clicked_count / $stats->opened_count) * 100 : 0;
        }
        
        return $stats;
    }
    
    /**
     * Process scheduled campaigns
     */
    public static function process_scheduled_campaigns() {
        global $wpdb;
        
        $scheduled_campaigns = $wpdb->get_results(
            "SELECT id FROM " . AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE . " 
             WHERE status = 'scheduled' 
             AND scheduled_at <= NOW()"
        );
        
        $campaign_manager = new self();
        
        foreach ($scheduled_campaigns as $campaign) {
            $campaign_manager->send_campaign($campaign->id);
        }
    }
}

// Hook for processing scheduled campaigns
add_action('ai_newsletter_send_scheduled_campaign', array('AI_Newsletter_Pro_Campaign_Manager', 'process_scheduled_campaigns'));

// Register cron event for checking scheduled campaigns
if (!wp_next_scheduled('ai_newsletter_check_scheduled')) {
    wp_schedule_event(time(), 'hourly', 'ai_newsletter_check_scheduled');
}

add_action('ai_newsletter_check_scheduled', array('AI_Newsletter_Pro_Campaign_Manager', 'process_scheduled_campaigns'));