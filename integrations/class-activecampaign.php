<?php
/**
 * ActiveCampaign Integration for AI Newsletter Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Newsletter_Pro_ActiveCampaign {
    
    private $api_url;
    private $api_key;
    
    /**
     * Constructor
     */
    public function __construct($config = array()) {
        $this->api_url = rtrim($config['api_url'] ?? '', '/') . '/api/3/';
        $this->api_key = $config['api_key'] ?? '';
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        $response = $this->make_request('GET', 'users/me');
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Connection successful', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Connection failed', 'ai-newsletter-pro'));
    }
    
    /**
     * Add contact to ActiveCampaign
     */
    public function add_contact($email, $name = '', $custom_fields = array()) {
        $contact_data = array(
            'email' => $email
        );
        
        if (!empty($name)) {
            $name_parts = explode(' ', $name, 2);
            $contact_data['firstName'] = $name_parts[0];
            if (isset($name_parts[1])) {
                $contact_data['lastName'] = $name_parts[1];
            }
        }
        
        if (!empty($custom_fields)) {
            $contact_data['fieldValues'] = $custom_fields;
        }
        
        $data = array('contact' => $contact_data);
        
        $response = $this->make_request('POST', 'contacts', $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $response_data = json_decode($response['body'], true);
        
        if ($response['response']['code'] === 201) {
            return array('success' => true, 'contact_id' => $response_data['contact']['id'], 'message' => __('Contact added successfully', 'ai-newsletter-pro'));
        }
        
        // Check if contact already exists
        if ($response['response']['code'] === 422) {
            $existing = $this->get_contact($email);
            if ($existing['success']) {
                return array('success' => true, 'contact_id' => $existing['data']['id'], 'message' => __('Contact already exists', 'ai-newsletter-pro'));
            }
        }
        
        $error_message = $response_data['message'] ?? __('Unknown error occurred', 'ai-newsletter-pro');
        return array('success' => false, 'message' => $error_message);
    }
    
    /**
     * Get contact by email
     */
    public function get_contact($email) {
        $response = $this->make_request('GET', 'contacts?email=' . urlencode($email));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $data = json_decode($response['body'], true);
            
            if (!empty($data['contacts'])) {
                return array('success' => true, 'data' => $data['contacts'][0]);
            }
        }
        
        return array('success' => false, 'message' => __('Contact not found', 'ai-newsletter-pro'));
    }
    
    /**
     * Update contact
     */
    public function update_contact($contact_id, $data) {
        $update_data = array('contact' => $data);
        
        $response = $this->make_request('PUT', "contacts/{$contact_id}", $update_data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Contact updated successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to update contact', 'ai-newsletter-pro'));
    }
    
    /**
     * Delete contact
     */
    public function delete_contact($contact_id) {
        $response = $this->make_request('DELETE', "contacts/{$contact_id}");
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Contact deleted successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to delete contact', 'ai-newsletter-pro'));
    }
    
    /**
     * Add contact to list
     */
    public function add_to_list($contact_id, $list_id) {
        $data = array(
            'contactList' => array(
                'list' => $list_id,
                'contact' => $contact_id,
                'status' => 1 // subscribed
            )
        );
        
        $response = $this->make_request('POST', 'contactLists', $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 201) {
            return array('success' => true, 'message' => __('Contact added to list successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to add contact to list', 'ai-newsletter-pro'));
    }
    
    /**
     * Remove contact from list
     */
    public function remove_from_list($contact_id, $list_id) {
        // First get the contact list relation ID
        $response = $this->make_request('GET', "contactLists?filters[contact]={$contact_id}&filters[list]={$list_id}");
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $data = json_decode($response['body'], true);
        
        if (empty($data['contactLists'])) {
            return array('success' => false, 'message' => __('Contact not found in list', 'ai-newsletter-pro'));
        }
        
        $contact_list_id = $data['contactLists'][0]['id'];
        
        $response = $this->make_request('DELETE', "contactLists/{$contact_list_id}");
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Contact removed from list successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to remove contact from list', 'ai-newsletter-pro'));
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
     * Create campaign
     */
    public function create_campaign($subject, $content, $list_id, $options = array()) {
        $defaults = array(
            'type' => 'single',
            'name' => $subject,
            'subject' => $subject,
            'fromname' => get_bloginfo('name'),
            'fromemail' => get_option('admin_email')
        );
        
        $options = wp_parse_args($options, $defaults);
        
        $data = array(
            'campaign' => array(
                'type' => $options['type'],
                'name' => $options['name'],
                'subject' => $options['subject'],
                'fromname' => $options['fromname'],
                'fromemail' => $options['fromemail'],
                'list' => $list_id,
                'htmlcontent' => $content
            )
        );
        
        $response = $this->make_request('POST', 'campaigns', $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 201) {
            $campaign_data = json_decode($response['body'], true);
            return array('success' => true, 'campaign_id' => $campaign_data['campaign']['id'], 'message' => __('Campaign created successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to create campaign', 'ai-newsletter-pro'));
    }
    
    /**
     * Send campaign
     */
    public function send_campaign($campaign_id) {
        $data = array(
            'campaign' => array(
                'status' => 1 // send now
            )
        );
        
        $response = $this->make_request('PUT', "campaigns/{$campaign_id}", $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Campaign sent successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to send campaign', 'ai-newsletter-pro'));
    }
    
    /**
     * Add subscriber (alias for add_contact)
     */
    public function add_subscriber($email, $name = '') {
        return $this->add_contact($email, $name);
    }
    
    /**
     * Make API request to ActiveCampaign
     */
    private function make_request($method, $endpoint, $data = array()) {
        if (empty($this->api_key) || empty($this->api_url)) {
            return new WP_Error('no_api_credentials', __('API credentials not configured', 'ai-newsletter-pro'));
        }
        
        $url = $this->api_url . $endpoint;
        
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => array(
                'Api-Token' => $this->api_key,
                'Content-Type' => 'application/json'
            )
        );
        
        if (!empty($data) && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($data);
        } elseif (!empty($data) && $method === 'GET') {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($data);
        }
        
        return wp_remote_request($url, $args);
    }
    
    /**
     * Sync local subscribers to ActiveCampaign
     */
    public function sync_subscribers($subscribers, $list_id = null) {
        $results = array(
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        foreach ($subscribers as $subscriber) {
            $result = $this->add_contact($subscriber->email, $subscriber->name);
            
            if ($result['success']) {
                // Add to list if specified
                if ($list_id && isset($result['contact_id'])) {
                    $this->add_to_list($result['contact_id'], $list_id);
                }
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $subscriber->email . ': ' . $result['message'];
            }
            
            // Rate limiting
            usleep(500000); // 0.5 second delay
        }
        
        return $results;
    }
}