<?php
/**
 * Zoho Campaigns Integration for AI Newsletter Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Newsletter_Pro_Zoho {
    
    private $client_id;
    private $client_secret;
    private $refresh_token;
    private $access_token;
    private $api_url = 'https://campaigns.zoho.com/api/v1.1/';
    
    /**
     * Constructor
     */
    public function __construct($config = array()) {
        $this->client_id = $config['client_id'] ?? '';
        $this->client_secret = $config['client_secret'] ?? '';
        $this->refresh_token = $config['refresh_token'] ?? '';
        $this->access_token = $this->get_access_token();
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        $response = $this->make_request('GET', 'getlists');
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Connection successful', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Connection failed', 'ai-newsletter-pro'));
    }
    
    /**
     * Add subscriber to Zoho Campaigns
     */
    public function add_subscriber($email, $name = '', $list_key = '') {
        if (empty($list_key)) {
            // Get first available list
            $lists = $this->get_lists();
            if ($lists['success'] && !empty($lists['data'])) {
                $list_key = $lists['data'][0]['listkey'];
            } else {
                return array('success' => false, 'message' => __('No mailing list found', 'ai-newsletter-pro'));
            }
        }
        
        $data = array(
            'listkey' => $list_key,
            'contactinfo' => json_encode(array(
                'Contact Email' => $email,
                'First Name' => $name
            ))
        );
        
        $response = $this->make_request('POST', 'listsubscribe', $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $response_data = json_decode($response['body'], true);
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Subscriber added successfully', 'ai-newsletter-pro'));
        }
        
        $error_message = $response_data['message'] ?? __('Unknown error occurred', 'ai-newsletter-pro');
        return array('success' => false, 'message' => $error_message);
    }
    
    /**
     * Unsubscribe from Zoho Campaigns
     */
    public function unsubscribe($email, $list_key = '') {
        if (empty($list_key)) {
            $lists = $this->get_lists();
            if ($lists['success'] && !empty($lists['data'])) {
                $list_key = $lists['data'][0]['listkey'];
            } else {
                return array('success' => false, 'message' => __('No mailing list found', 'ai-newsletter-pro'));
            }
        }
        
        $data = array(
            'listkey' => $list_key,
            'contactinfo' => json_encode(array(
                'Contact Email' => $email
            ))
        );
        
        $response = $this->make_request('POST', 'listunsubscribe', $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Subscriber unsubscribed successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to unsubscribe', 'ai-newsletter-pro'));
    }
    
    /**
     * Get all lists
     */
    public function get_lists() {
        $response = $this->make_request('GET', 'getlists');
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $data = json_decode($response['body'], true);
            return array('success' => true, 'data' => $data['list_of_details']);
        }
        
        return array('success' => false, 'message' => __('Failed to get lists', 'ai-newsletter-pro'));
    }
    
    /**
     * Create campaign
     */
    public function create_campaign($subject, $content, $list_key, $options = array()) {
        $defaults = array(
            'campaignname' => $subject,
            'subject' => $subject,
            'fromname' => get_bloginfo('name'),
            'fromemail' => get_option('admin_email')
        );
        
        $options = wp_parse_args($options, $defaults);
        
        $data = array(
            'campaignname' => $options['campaignname'],
            'subject' => $options['subject'],
            'fromname' => $options['fromname'],
            'fromemail' => $options['fromemail'],
            'listkey' => $list_key,
            'content' => $content
        );
        
        $response = $this->make_request('POST', 'createcampaign', $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $campaign_data = json_decode($response['body'], true);
            return array('success' => true, 'campaign_id' => $campaign_data['campaign_key'], 'message' => __('Campaign created successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to create campaign', 'ai-newsletter-pro'));
    }
    
    /**
     * Send campaign
     */
    public function send_campaign($campaign_key) {
        $data = array('campaignkey' => $campaign_key);
        
        $response = $this->make_request('POST', 'sendcampaign', $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Campaign sent successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to send campaign', 'ai-newsletter-pro'));
    }
    
    /**
     * Get subscriber info
     */
    public function get_subscriber($email, $list_key = '') {
        if (empty($list_key)) {
            $lists = $this->get_lists();
            if ($lists['success'] && !empty($lists['data'])) {
                $list_key = $lists['data'][0]['listkey'];
            } else {
                return array('success' => false, 'message' => __('No mailing list found', 'ai-newsletter-pro'));
            }
        }
        
        $data = array(
            'listkey' => $list_key,
            'emailid' => $email
        );
        
        $response = $this->make_request('GET', 'getcontactdetails', $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $data = json_decode($response['body'], true);
            return array('success' => true, 'data' => $data);
        }
        
        return array('success' => false, 'message' => __('Subscriber not found', 'ai-newsletter-pro'));
    }
    
    /**
     * Get access token using refresh token
     */
    private function get_access_token() {
        if (empty($this->refresh_token)) {
            return '';
        }
        
        // Check if we have a cached token
        $cached_token = get_transient('ai_newsletter_zoho_access_token');
        if ($cached_token) {
            return $cached_token;
        }
        
        $token_url = 'https://accounts.zoho.com/oauth/v2/token';
        
        $data = array(
            'refresh_token' => $this->refresh_token,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'refresh_token'
        );
        
        $response = wp_remote_post($token_url, array(
            'body' => $data,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return '';
        }
        
        $token_data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($token_data['access_token'])) {
            $access_token = $token_data['access_token'];
            $expires_in = $token_data['expires_in'] ?? 3600;
            
            // Cache token for slightly less than expiry time
            set_transient('ai_newsletter_zoho_access_token', $access_token, $expires_in - 300);
            
            return $access_token;
        }
        
        return '';
    }
    
    /**
     * Make API request to Zoho Campaigns
     */
    private function make_request($method, $endpoint, $data = array()) {
        if (empty($this->access_token)) {
            return new WP_Error('no_access_token', __('Access token not available', 'ai-newsletter-pro'));
        }
        
        $url = $this->api_url . $endpoint;
        
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Zoho-oauthtoken ' . $this->access_token,
                'Content-Type' => 'application/x-www-form-urlencoded'
            )
        );
        
        if (!empty($data)) {
            if ($method === 'GET') {
                $url .= '?' . http_build_query($data);
            } else {
                $args['body'] = $data;
            }
        }
        
        return wp_remote_request($url, $args);
    }
    
    /**
     * Get campaign statistics
     */
    public function get_campaign_stats($campaign_key) {
        $data = array('campaignkey' => $campaign_key);
        
        $response = $this->make_request('GET', 'getcampaignstats', $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $stats = json_decode($response['body'], true);
            return array('success' => true, 'data' => $stats);
        }
        
        return array('success' => false, 'message' => __('Failed to get campaign stats', 'ai-newsletter-pro'));
    }
    
    /**
     * Sync local subscribers to Zoho
     */
    public function sync_subscribers($subscribers, $list_key = '') {
        if (empty($list_key)) {
            $lists = $this->get_lists();
            if ($lists['success'] && !empty($lists['data'])) {
                $list_key = $lists['data'][0]['listkey'];
            } else {
                return array('success' => false, 'message' => __('No mailing list found', 'ai-newsletter-pro'));
            }
        }
        
        $results = array(
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        foreach ($subscribers as $subscriber) {
            $result = $this->add_subscriber($subscriber->email, $subscriber->name, $list_key);
            
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