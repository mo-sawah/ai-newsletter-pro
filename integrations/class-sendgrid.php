<?php
/**
 * SendGrid Integration for AI Newsletter Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Newsletter_Pro_SendGrid {
    
    private $api_key;
    private $list_id;
    private $api_url = 'https://api.sendgrid.com/v3/';
    
    /**
     * Constructor
     */
    public function __construct($config = array()) {
        $this->api_key = $config['api_key'] ?? '';
        $this->list_id = $config['list_id'] ?? '';
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        $response = $this->make_request('GET', 'user/account');
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            return array('success' => true, 'message' => __('Connection successful', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Connection failed', 'ai-newsletter-pro'));
    }
    
    /**
     * Add contact to SendGrid
     */
    public function add_contact($email, $name = '', $custom_fields = array()) {
        $contact_data = array(
            'email' => $email
        );
        
        if (!empty($name)) {
            $name_parts = explode(' ', $name, 2);
            $contact_data['first_name'] = $name_parts[0];
            if (isset($name_parts[1])) {
                $contact_data['last_name'] = $name_parts[1];
            }
        }
        
        if (!empty($custom_fields)) {
            $contact_data['custom_fields'] = $custom_fields;
        }
        
        $data = array(
            'contacts' => array($contact_data)
        );
        
        if (!empty($this->list_id)) {
            $data['list_ids'] = array($this->list_id);
        }
        
        $response = $this->make_request('PUT', 'marketing/contacts', $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 202) {
            return array('success' => true, 'message' => __('Contact added successfully', 'ai-newsletter-pro'));
        }
        
        $response_data = json_decode($response['body'], true);
        $error_message = $response_data['errors'][0]['message'] ?? __('Unknown error occurred', 'ai-newsletter-pro');
        
        return array('success' => false, 'message' => $error_message);
    }
    
    /**
     * Remove contact from list
     */
    public function remove_contact($email) {
        // First get contact ID
        $contact = $this->get_contact($email);
        
        if (!$contact['success']) {
            return $contact;
        }
        
        $contact_id = $contact['data']['id'];
        
        if (!empty($this->list_id)) {
            // Remove from specific list
            $data = array(
                'contact_ids' => array($contact_id)
            );
            
            $response = $this->make_request('DELETE', "marketing/lists/{$this->list_id}/contacts", $data);
        } else {
            // Delete contact entirely
            $response = $this->make_request('DELETE', "marketing/contacts?ids={$contact_id}");
        }
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 202 || $response['response']['code'] === 204) {
            return array('success' => true, 'message' => __('Contact removed successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to remove contact', 'ai-newsletter-pro'));
    }
    
    /**
     * Get contact information
     */
    public function get_contact($email) {
        $response = $this->make_request('POST', 'marketing/contacts/search', array(
            'query' => "email LIKE '{$email}'"
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $data = json_decode($response['body'], true);
            
            if (!empty($data['result'])) {
                return array('success' => true, 'data' => $data['result'][0]);
            }
        }
        
        return array('success' => false, 'message' => __('Contact not found', 'ai-newsletter-pro'));
    }
    
    /**
     * Get all lists
     */
    public function get_lists() {
        $response = $this->make_request('GET', 'marketing/lists');
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $data = json_decode($response['body'], true);
            return array('success' => true, 'data' => $data['result']);
        }
        
        return array('success' => false, 'message' => __('Failed to get lists', 'ai-newsletter-pro'));
    }
    
    /**
     * Create a new list
     */
    public function create_list($name) {
        $data = array('name' => $name);
        
        $response = $this->make_request('POST', 'marketing/lists', $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 201) {
            $list_data = json_decode($response['body'], true);
            return array('success' => true, 'list_id' => $list_data['id'], 'message' => __('List created successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to create list', 'ai-newsletter-pro'));
    }
    
    /**
     * Send single email
     */
    public function send_email($to_email, $subject, $content, $from_email = '', $from_name = '') {
        if (empty($from_email)) {
            $from_email = get_option('admin_email');
        }
        
        if (empty($from_name)) {
            $from_name = get_bloginfo('name');
        }
        
        $data = array(
            'personalizations' => array(
                array(
                    'to' => array(
                        array('email' => $to_email)
                    )
                )
            ),
            'from' => array(
                'email' => $from_email,
                'name' => $from_name
            ),
            'subject' => $subject,
            'content' => array(
                array(
                    'type' => 'text/html',
                    'value' => $content
                )
            )
        );
        
        $response = $this->make_request('POST', 'mail/send', $data);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 202) {
            return array('success' => true, 'message' => __('Email sent successfully', 'ai-newsletter-pro'));
        }
        
        return array('success' => false, 'message' => __('Failed to send email', 'ai-newsletter-pro'));
    }
    
    /**
     * Get sender identities
     */
    public function get_senders() {
        $response = $this->make_request('GET', 'verified_senders');
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        if ($response['response']['code'] === 200) {
            $data = json_decode($response['body'], true);
            return array('success' => true, 'data' => $data['results']);
        }
        
        return array('success' => false, 'message' => __('Failed to get senders', 'ai-newsletter-pro'));
    }
    
    /**
     * Add subscriber (alias for add_contact)
     */
    public function add_subscriber($email, $name = '') {
        return $this->add_contact($email, $name);
    }
    
    /**
     * Make API request to SendGrid
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
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            )
        );
        
        if (!empty($data) && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($data);
        } elseif (!empty($data) && $method === 'GET') {
            $url .= '?' . http_build_query($data);
        }
        
        return wp_remote_request($url, $args);
    }
    
    /**
     * Sync local subscribers to SendGrid
     */
    public function sync_subscribers($subscribers) {
        $results = array(
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        // SendGrid allows batch operations, so we'll process in chunks
        $chunks = array_chunk($subscribers, 50);
        
        foreach ($chunks as $chunk) {
            $contacts = array();
            
            foreach ($chunk as $subscriber) {
                $contact = array('email' => $subscriber->email);
                
                if (!empty($subscriber->name)) {
                    $name_parts = explode(' ', $subscriber->name, 2);
                    $contact['first_name'] = $name_parts[0];
                    if (isset($name_parts[1])) {
                        $contact['last_name'] = $name_parts[1];
                    }
                }
                
                $contacts[] = $contact;
            }
            
            $data = array('contacts' => $contacts);
            
            if (!empty($this->list_id)) {
                $data['list_ids'] = array($this->list_id);
            }
            
            $response = $this->make_request('PUT', 'marketing/contacts', $data);
            
            if (is_wp_error($response) || $response['response']['code'] !== 202) {
                $results['failed'] += count($contacts);
                $results['errors'][] = 'Batch failed: ' . (is_wp_error($response) ? $response->get_error_message() : 'HTTP ' . $response['response']['code']);
            } else {
                $results['success'] += count($contacts);
            }
            
            // Rate limiting
            sleep(1);
        }
        
        return $results;
    }
}