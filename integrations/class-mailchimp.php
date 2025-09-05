<?php
/**
 * Mailchimp Integration for AI Newsletter Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Newsletter_Pro_Mailchimp {
    
    private $api_key;
    private $list_id;
    private $api_url;
    
    /**
     * Constructor
     */
    public function __construct($config = array()) {
        $this->api_key = $config['api_key'] ?? '';
        $this->list_id = $config['list_id'] ?? '';
        
        // Extract datacenter from API key
        $datacenter = substr($this->api_key, strpos($this->api_key, '-') + 1);
        $this->api_url = "https://{$datacenter}.api.mailchimp.com/3.0/";
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        $response = $this->make_request('GET', 'ping');
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Connection successful', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Connection failed', 'ai-newsletter-pro'));
    }
    
    /**
     * Add subscriber to Mailchimp list
     */
    public function add_subscriber($email, $name = '', $merge_fields = array()) {
        if (empty($this->list_id)) {
            return array('success' => false, 'message' => __('List ID not configured', 'ai-newsletter-pro'));
        }
        
        $subscriber_hash = md5(strtolower($email));
        
        // Prepare merge fields
        $default_merge_fields = array();
        if (!empty($name)) {
            $name_parts = explode(' ', $name, 2);
            $default_merge_fields['FNAME'] = $name_parts[0];
            if (isset($name_parts[1])) {
                $default_merge_fields['LNAME'] = $name_parts[1];
            }
        }
        
        $merge_fields = array_merge($default_merge_fields, $merge_fields);
        
        $data = array(
            'email_address' => $email,
            'status' => 'subscribed',
            'merge_fields' => $merge_fields
        );
        
        $response = $this->make_request('PUT', "lists/{$this->list_id}/members/{$subscriber_hash}", $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $response_data = json_decode($response['body'], true);
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Subscriber added successfully', 'ai-newsletter-pro'));
        }
        
        $error_message = $response_data['detail'] ?? __('Unknown error occurred', 'ai-newsletter-pro');
        return array('success' => false, 'message' => $error_message);
    }
    
    /**
     * Unsubscribe member from list
     */
    public function unsubscribe($email) {
        if (empty($this->list_id)) {
            return array('success' => false, 'message' => __('List ID not configured', 'ai-newsletter-pro'));
        }
        
        $subscriber_hash = md5(strtolower($email));
        
        $data = array('status' => 'unsubscribed');
        
        $response = $this->make_request('PATCH', "lists/{$this->list_id}/members/{$subscriber_hash}", $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Member unsubscribed successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to unsubscribe member', 'ai-newsletter-pro'));
    }
    
    /**
     * Delete subscriber from list
     */
    public function delete_subscriber($email) {
        if (empty($this->list_id)) {
            return array('success' => false, 'message' => __('List ID not configured', 'ai-newsletter-pro'));
        }
        
        $subscriber_hash = md5(strtolower($email));
        
        $response = $this->make_request('DELETE', "lists/{$this->list_id}/members/{$subscriber_hash}");
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 204) {
            return array('success' => true, 'message' => __('Member deleted successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to delete member', 'ai-newsletter-pro'));
    }
    
    /**
     * Get subscriber info
     */
    public function get_subscriber($email) {
        if (empty($this->list_id)) {
            return array('success' => false, 'message' => __('List ID not configured', 'ai-newsletter-pro'));
        }
        
        $subscriber_hash = md5(strtolower($email));
        
        $response = $this->make_request('GET', "lists/{$this->list_id}/members/{$subscriber_hash}");
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $data = json_decode($response['body'], true);
            return array('success' => true, 'data' => $data);
        }
        
        return array('success' => false, 'message' => __('Member not found', 'ai-newsletter-pro'));
    }
    
    /**
     * Get list information
     */
    public function get_list_info() {
        if (empty($this->list_id)) {
            return array('success' => false, 'message' => __('List ID not configured', 'ai-newsletter-pro'));
        }
        
        $response = $this->make_request('GET', "lists/{$this->list_id}");
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $data = json_decode($response['body'], true);
            return array('success' => true, 'data' => $data);
        }
        
        return array('success' => false, 'message' => __('Failed to get list information', 'ai-newsletter-pro'));
    }
    
    /**
     * Get all lists
     */
    public function get_lists() {
        $response = $this->make_request('GET', 'lists');
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $data = json_decode($response['body'], true);
            return array('success' => true, 'data' => $data['lists']);
        }
        
        return array('success' => false, 'message' => __('Failed to get lists', 'ai-newsletter-pro'));
    }
    
    /**
     * Add tags to subscriber
     */
    public function add_tags($email, $tags) {
        if (empty($this->list_id) || empty($tags)) {
            return array('success' => false, 'message' => __('List ID or tags not provided', 'ai-newsletter-pro'));
        }
        
        $subscriber_hash = md5(strtolower($email));
        
        $tag_data = array();
        foreach ((array)$tags as $tag) {
            $tag_data[] = array(
                'name' => $tag,
                'status' => 'active'
            );
        }
        
        $data = array('tags' => $tag_data);
        
        $response = $this->make_request('POST', "lists/{$this->list_id}/members/{$subscriber_hash}/tags", $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 204) {
            return array('success' => true, 'message' => __('Tags added successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to add tags', 'ai-newsletter-pro'));
    }
    
    /**
     * Remove tags from subscriber
     */
    public function remove_tags($email, $tags) {
        if (empty($this->list_id) || empty($tags)) {
            return array('success' => false, 'message' => __('List ID or tags not provided', 'ai-newsletter-pro'));
        }
        
        $subscriber_hash = md5(strtolower($email));
        
        $tag_data = array();
        foreach ((array)$tags as $tag) {
            $tag_data[] = array(
                'name' => $tag,
                'status' => 'inactive'
            );
        }
        
        $data = array('tags' => $tag_data);
        
        $response = $this->make_request('POST', "lists/{$this->list_id}/members/{$subscriber_hash}/tags", $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 204) {
            return array('success' => true, 'message' => __('Tags removed successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to remove tags', 'ai-newsletter-pro'));
    }
    
    /**
     * Create campaign in Mailchimp
     */
    public function create_campaign($subject, $content, $options = array()) {
        $defaults = array(
            'type' => 'regular',
            'from_name' => get_bloginfo('name'),
            'reply_to' => get_option('admin_email'),
            'subject_line' => $subject
        );
        
        $options = wp_parse_args($options, $defaults);
        
        $campaign_data = array(
            'type' => $options['type'],
            'recipients' => array(
                'list_id' => $this->list_id
            ),
            'settings' => array(
                'subject_line' => $options['subject_line'],
                'from_name' => $options['from_name'],
                'reply_to' => $options['reply_to']
            )
        );
        
        $response = $this->make_request('POST', 'campaigns', $campaign_data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $campaign = json_decode($response['body'], true);
            
            // Set campaign content
            $content_result = $this->set_campaign_content($campaign['id'], $content);
            
            if ($content_result['success']) {
                return array('success' => true, 'campaign_id' => $campaign['id'], 'message' => __('Campaign created successfully', 'ai-newsletter-pro'));
            } else {
                return $content_result;
            }
        }
        
        return array('success' => false, 'message' => __('Failed to create campaign', 'ai-newsletter-pro'));
    }
    
    /**
     * Set campaign content
     */
    public function set_campaign_content($campaign_id, $content) {
        $content_data = array(
            'html' => $content
        );
        
        $response = $this->make_request('PUT', "campaigns/{$campaign_id}/content", $content_data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Campaign content set successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to set campaign content', 'ai-newsletter-pro'));
    }
    
    /**
     * Send campaign
     */
    public function send_campaign($campaign_id) {
        $response = $this->make_request('POST', "campaigns/{$campaign_id}/actions/send");
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 204) {
            return array('success' => true, 'message' => __('Campaign sent successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to send campaign', 'ai-newsletter-pro'));
    }
    
    /**
     * Make API request to Mailchimp
     */
    private function make_request($method, $endpoint, $data = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('API key not configured', 'ai-newsletter-pro'));
        }
        
        $url = $this->api_url . $endpoint;
        
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('user:' . $this->api_key),
                'Content-Type' => 'application/json'
            )
        );
        
        if (!empty($data) && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($data);
        }
        
        return wp_remote_request($url, $args);
    }
    
    /**
     * Sync local subscribers to Mailchimp
     */
    public function sync_subscribers($subscribers) {
        $results = array(
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        foreach ($subscribers as $subscriber) {
            $result = $this->add_subscriber($subscriber->email, $subscriber->name);
            
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $subscriber->email . ': ' . $result['message'];
            }
            
            // Add small delay to prevent rate limiting
            usleep(100000); // 0.1 second
        }
        
        return $results;
    }
}