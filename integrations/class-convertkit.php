<?php
/**
 * ConvertKit Integration for AI Newsletter Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Newsletter_Pro_ConvertKit {
    
    private $api_key;
    private $api_secret;
    private $form_id;
    private $api_url = 'https://api.convertkit.com/v3/';
    
    /**
     * Constructor
     */
    public function __construct($config = array()) {
        $this->api_key = $config['api_key'] ?? '';
        $this->api_secret = $config['api_secret'] ?? '';
        $this->form_id = $config['form_id'] ?? '';
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        $response = $this->make_request('GET', 'account');
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Connection successful', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Connection failed', 'ai-newsletter-pro'));
    }
    
    /**
     * Add subscriber to ConvertKit
     */
    public function add_subscriber($email, $name = '', $custom_fields = array()) {
        $data = array(
            'email' => $email,
            'api_key' => $this->api_key
        );
        
        if (!empty($name)) {
            $data['first_name'] = $name;
        }
        
        if (!empty($custom_fields)) {
            $data['fields'] = $custom_fields;
        }
        
        if (!empty($this->form_id)) {
            $endpoint = "forms/{$this->form_id}/subscribe";
        } else {
            $endpoint = 'subscribers';
        }
        
        $response = $this->make_request('POST', $endpoint, $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $response_data = json_decode($response['body'], true);
        
        if ($response['response']['code'] === 200 && isset($response_data['subscription'])) {
            return array('success' => true, 'message' => __('Subscriber added successfully', 'ai-newsletter-pro'));
        }
        
        $error_message = $response_data['message'] ?? __('Unknown error occurred', 'ai-newsletter-pro');
        return array('success' => false, 'message' => $error_message);
    }
    
    /**
     * Unsubscribe from ConvertKit
     */
    public function unsubscribe($email) {
        $data = array(
            'email' => $email,
            'api_secret' => $this->api_secret
        );
        
        $response = $this->make_request('PUT', 'unsubscribe', $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Subscriber unsubscribed successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to unsubscribe', 'ai-newsletter-pro'));
    }
    
    /**
     * Get subscriber info
     */
    public function get_subscriber($email) {
        $response = $this->make_request('GET', "subscribers?email_address={$email}");
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $data = json_decode($response['body'], true);
            
            if (!empty($data['subscribers'])) {
                return array('success' => true, 'data' => $data['subscribers'][0]);
            }
        }
        
        return array('success' => false, 'message' => __('Subscriber not found', 'ai-newsletter-pro'));
    }
    
    /**
     * Get all forms
     */
    public function get_forms() {
        $response = $this->make_request('GET', 'forms');
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $data = json_decode($response['body'], true);
            return array('success' => true, 'data' => $data['forms']);
        }
        
        return array('success' => false, 'message' => __('Failed to get forms', 'ai-newsletter-pro'));
    }
    
    /**
     * Get all sequences
     */
    public function get_sequences() {
        $response = $this->make_request('GET', 'sequences');
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $data = json_decode($response['body'], true);
            return array('success' => true, 'data' => $data['courses']);
        }
        
        return array('success' => false, 'message' => __('Failed to get sequences', 'ai-newsletter-pro'));
    }
    
    /**
     * Add subscriber to sequence
     */
    public function add_to_sequence($email, $sequence_id) {
        $data = array(
            'email' => $email,
            'api_key' => $this->api_key
        );
        
        $response = $this->make_request('POST', "sequences/{$sequence_id}/subscribe", $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Subscriber added to sequence successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to add subscriber to sequence', 'ai-newsletter-pro'));
    }
    
    /**
     * Tag subscriber
     */
    public function tag_subscriber($email, $tag_id) {
        $data = array(
            'email' => $email,
            'api_key' => $this->api_key
        );
        
        $response = $this->make_request('POST', "tags/{$tag_id}/subscribe", $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Subscriber tagged successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to tag subscriber', 'ai-newsletter-pro'));
    }
    
    /**
     * Get all tags
     */
    public function get_tags() {
        $response = $this->make_request('GET', 'tags');
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $data = json_decode($response['body'], true);
            return array('success' => true, 'data' => $data['tags']);
        }
        
        return array('success' => false, 'message' => __('Failed to get tags', 'ai-newsletter-pro'));
    }
    
    /**
     * Create a broadcast
     */
    public function create_broadcast($subject, $content, $options = array()) {
        $data = array(
            'subject' => $subject,
            'content' => $content,
            'api_key' => $this->api_key
        );
        
        if (isset($options['description'])) {
            $data['description'] = $options['description'];
        }
        
        if (isset($options['public'])) {
            $data['public'] = $options['public'];
        }
        
        $response = $this->make_request('POST', 'broadcasts', $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 201) {
            $broadcast = json_decode($response['body'], true);
            return array('success' => true, 'broadcast_id' => $broadcast['broadcast']['id'], 'message' => __('Broadcast created successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to create broadcast', 'ai-newsletter-pro'));
    }
    
    /**
     * Send broadcast
     */
    public function send_broadcast($broadcast_id) {
        $data = array('api_secret' => $this->api_secret);
        
        $response = $this->make_request('POST', "broadcasts/{$broadcast_id}/send", $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 204) {
            return array('success' => true, 'message' => __('Broadcast sent successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to send broadcast', 'ai-newsletter-pro'));
    }
    
    /**
     * Get account info
     */
    public function get_account_info() {
        $response = $this->make_request('GET', 'account');
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $data = json_decode($response['body'], true);
            return array('success' => true, 'data' => $data);
        }
        
        return array('success' => false, 'message' => __('Failed to get account info', 'ai-newsletter-pro'));
    }
    
    /**
     * Make API request to ConvertKit
     */
    private function make_request($method, $endpoint, $data = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('API key not configured', 'ai-newsletter-pro'));
        }
        
        $url = $this->api_url . $endpoint;
        
        // Add API key to GET requests
        if ($method === 'GET' && !empty($this->api_key)) {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . 'api_key=' . $this->api_key;
        }
        
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        );
        
        if (!empty($data) && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            if ($method === 'POST' && !isset($data['api_key'])) {
                $data['api_key'] = $this->api_key;
            }
            $args['body'] = json_encode($data);
        }
        
        return wp_remote_request($url, $args);
    }
    
    /**
     * Sync local subscribers to ConvertKit
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
            
            // Add delay to prevent rate limiting
            sleep(1);
        }
        
        return $results;
    }
}