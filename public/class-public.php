<?php
/**
 * Public-facing functionality for AI Newsletter Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Newsletter_Pro_Public {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_footer', array($this, 'add_tracking_script'));
        add_action('template_redirect', array($this, 'handle_tracking_requests'));
        add_action('template_redirect', array($this, 'handle_unsubscribe_requests'));
        add_action('wp_ajax_ai_newsletter_widget_impression', array($this, 'track_widget_impression'));
        add_action('wp_ajax_nopriv_ai_newsletter_widget_impression', array($this, 'track_widget_impression'));
        add_action('wp_ajax_ai_newsletter_widget_conversion', array($this, 'track_widget_conversion'));
        add_action('wp_ajax_nopriv_ai_newsletter_widget_conversion', array($this, 'track_widget_conversion'));
    }
    
    /**
     * Initialize public functionality
     */
    public function init() {
        // Handle AJAX requests
        add_action('wp_ajax_ai_newsletter_subscribe', array($this, 'handle_subscription'));
        add_action('wp_ajax_nopriv_ai_newsletter_subscribe', array($this, 'handle_subscription'));
        
        // Handle form submissions via GET (for non-AJAX fallback)
        if (isset($_GET['ai_newsletter_action'])) {
            $this->handle_frontend_actions();
        }
    }
    
    /**
     * Handle frontend actions
     */
    private function handle_frontend_actions() {
        $action = sanitize_text_field($_GET['ai_newsletter_action']);
        
        switch ($action) {
            case 'subscribe':
                $this->handle_get_subscription();
                break;
            case 'unsubscribe':
                $this->handle_get_unsubscribe();
                break;
            case 'confirm':
                $this->handle_get_confirmation();
                break;
        }
    }
    
    /**
     * Handle subscription via GET (fallback)
     */
    private function handle_get_subscription() {
        if (!isset($_GET['email']) || !wp_verify_nonce($_GET['_wpnonce'], 'ai_newsletter_subscribe')) {
            wp_die(__('Invalid request', 'ai-newsletter-pro'));
        }
        
        $email = sanitize_email($_GET['email']);
        $source = sanitize_text_field($_GET['source'] ?? 'form');
        
        if (class_exists('AI_Newsletter_Pro_Subscriber_Manager')) {
            $subscriber_manager = new AI_Newsletter_Pro_Subscriber_Manager();
            $result = $subscriber_manager->add_subscriber($email, '', $source);
            
            if ($result['success']) {
                wp_redirect(add_query_arg('newsletter_status', 'subscribed', home_url()));
            } else {
                wp_redirect(add_query_arg('newsletter_status', 'error', home_url()));
            }
        }
        
        exit;
    }
    
    /**
     * Handle unsubscribe via GET
     */
    private function handle_get_unsubscribe() {
        if (!isset($_GET['subscriber']) || !isset($_GET['token'])) {
            wp_die(__('Invalid unsubscribe link', 'ai-newsletter-pro'));
        }
        
        $subscriber_id = intval($_GET['subscriber']);
        $token = sanitize_text_field($_GET['token']);
        
        // Verify token
        if (!wp_verify_nonce($token, 'ai_newsletter_unsubscribe_' . $subscriber_id)) {
            wp_die(__('Invalid unsubscribe token', 'ai-newsletter-pro'));
        }
        
        if (class_exists('AI_Newsletter_Pro_Subscriber_Manager')) {
            global $wpdb;
            $subscriber = $wpdb->get_row($wpdb->prepare(
                "SELECT email FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE id = %d",
                $subscriber_id
            ));
            
            if ($subscriber) {
                $subscriber_manager = new AI_Newsletter_Pro_Subscriber_Manager();
                $result = $subscriber_manager->unsubscribe($subscriber->email);
                
                if ($result['success']) {
                    wp_redirect(add_query_arg('newsletter_status', 'unsubscribed', home_url()));
                } else {
                    wp_redirect(add_query_arg('newsletter_status', 'error', home_url()));
                }
            }
        }
        
        wp_die(__('Subscriber not found', 'ai-newsletter-pro'));
    }
    
    /**
     * Handle email confirmation
     */
    private function handle_get_confirmation() {
        if (!isset($_GET['token'])) {
            wp_die(__('Invalid confirmation link', 'ai-newsletter-pro'));
        }
        
        $token = sanitize_text_field($_GET['token']);
        
        if (class_exists('AI_Newsletter_Pro_Subscriber_Manager')) {
            $subscriber_manager = new AI_Newsletter_Pro_Subscriber_Manager();
            $result = $subscriber_manager->confirm_subscription($token);
            
            if ($result['success']) {
                wp_redirect(add_query_arg('newsletter_status', 'confirmed', home_url()));
            } else {
                wp_redirect(add_query_arg('newsletter_status', 'error', home_url()));
            }
        }
        
        exit;
    }
    
    /**
     * Handle AJAX subscription
     */
    public function handle_subscription() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ai_newsletter_pro_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'ai-newsletter-pro')));
        }
        
        $email = sanitize_email($_POST['email']);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $source = sanitize_text_field($_POST['source'] ?? 'unknown');
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Invalid email address', 'ai-newsletter-pro')));
        }
        
        // Use subscriber manager if available
        if (class_exists('AI_Newsletter_Pro_Subscriber_Manager')) {
            $subscriber_manager = new AI_Newsletter_Pro_Subscriber_Manager();
            $result = $subscriber_manager->add_subscriber($email, $name, $source);
            
            if ($result['success']) {
                wp_send_json_success(array('message' => $result['message']));
            } else {
                wp_send_json_error(array('message' => $result['message']));
            }
        } else {
            // Basic handling if class not available
            $this->basic_subscription_handling($email, $name, $source);
            wp_send_json_success(array('message' => __('Successfully subscribed!', 'ai-newsletter-pro')));
        }
    }
    
    /**
     * Basic subscription handling fallback
     */
    private function basic_subscription_handling($email, $name, $source) {
        global $wpdb;
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '" . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . "'") != AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE) {
            return false;
        }
        
        // Check if already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE email = %s",
            $email
        ));
        
        if ($existing) {
            return false;
        }
        
        // Insert new subscriber
        return $wpdb->insert(
            AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE,
            array(
                'email' => $email,
                'name' => $name,
                'source' => $source,
                'status' => 'subscribed',
                'subscribed_at' => current_time('mysql'),
                'last_activity' => current_time('mysql')
            )
        );
    }
    
    /**
     * Track widget impression
     */
    public function track_widget_impression() {
        if (!wp_verify_nonce($_POST['nonce'], 'ai_newsletter_pro_nonce')) {
            wp_die('Security check failed');
        }
        
        $widget_id = intval($_POST['widget_id']);
        
        if ($widget_id && class_exists('AI_Newsletter_Pro_Analytics')) {
            $analytics = new AI_Newsletter_Pro_Analytics();
            $analytics->track_event('widget_view', null, null, $widget_id);
            
            // Update widget impression count
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "UPDATE " . AI_NEWSLETTER_PRO_WIDGETS_TABLE . " 
                 SET impressions = impressions + 1 
                 WHERE id = %d",
                $widget_id
            ));
        }
        
        wp_send_json_success();
    }
    
    /**
     * Track widget conversion
     */
    public function track_widget_conversion() {
        if (!wp_verify_nonce($_POST['nonce'], 'ai_newsletter_pro_nonce')) {
            wp_die('Security check failed');
        }
        
        $widget_id = intval($_POST['widget_id']);
        
        if ($widget_id && class_exists('AI_Newsletter_Pro_Analytics')) {
            $analytics = new AI_Newsletter_Pro_Analytics();
            $analytics->track_event('widget_click', null, null, $widget_id);
            
            // Update widget conversion count
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "UPDATE " . AI_NEWSLETTER_PRO_WIDGETS_TABLE . " 
                 SET conversions = conversions + 1 
                 WHERE id = %d",
                $widget_id
            ));
        }
        
        wp_send_json_success();
    }
    
    /**
     * Handle tracking requests (email opens, clicks)
     */
    public function handle_tracking_requests() {
        if (!isset($_GET['ai_newsletter_track'])) {
            return;
        }
        
        $track_type = sanitize_text_field($_GET['ai_newsletter_track']);
        $campaign_id = intval($_GET['c'] ?? 0);
        $subscriber_id = intval($_GET['s'] ?? 0);
        $token = sanitize_text_field($_GET['t'] ?? '');
        
        // Verify token
        if (!wp_verify_nonce($token, 'ai_newsletter_track_' . $campaign_id . '_' . $subscriber_id)) {
            return;
        }
        
        if (class_exists('AI_Newsletter_Pro_Analytics')) {
            $analytics = new AI_Newsletter_Pro_Analytics();
            
            switch ($track_type) {
                case 'open':
                    $analytics->track_email_open($campaign_id, $subscriber_id);
                    break;
                case 'click':
                    $url = sanitize_url($_GET['url'] ?? '');
                    $analytics->track_email_click($campaign_id, $subscriber_id, $url);
                    
                    // Redirect to original URL if provided
                    if ($url) {
                        wp_redirect($url);
                        exit;
                    }
                    break;
            }
        }
        
        // Return 1x1 pixel image for tracking
        if ($track_type === 'open') {
            $this->output_tracking_pixel();
        }
    }
    
    /**
     * Handle unsubscribe requests
     */
    public function handle_unsubscribe_requests() {
        if (!isset($_GET['ai_newsletter_action']) || $_GET['ai_newsletter_action'] !== 'unsubscribe') {
            return;
        }
        
        $subscriber_id = intval($_GET['subscriber'] ?? 0);
        $token = sanitize_text_field($_GET['token'] ?? '');
        
        if (!$subscriber_id || !$token) {
            wp_die(__('Invalid unsubscribe link', 'ai-newsletter-pro'));
        }
        
        // Verify token
        if (!wp_verify_nonce($token, 'ai_newsletter_unsubscribe_' . $subscriber_id)) {
            wp_die(__('Invalid unsubscribe token', 'ai-newsletter-pro'));
        }
        
        // Get subscriber email
        global $wpdb;
        $subscriber = $wpdb->get_row($wpdb->prepare(
            "SELECT email FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE id = %d",
            $subscriber_id
        ));
        
        if (!$subscriber) {
            wp_die(__('Subscriber not found', 'ai-newsletter-pro'));
        }
        
        // Process unsubscribe
        if (class_exists('AI_Newsletter_Pro_Subscriber_Manager')) {
            $subscriber_manager = new AI_Newsletter_Pro_Subscriber_Manager();
            $result = $subscriber_manager->unsubscribe($subscriber->email, 'link');
            
            if ($result['success']) {
                $this->show_unsubscribe_confirmation();
            } else {
                wp_die($result['message']);
            }
        } else {
            // Basic unsubscribe handling
            $wpdb->update(
                AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE,
                array(
                    'status' => 'unsubscribed',
                    'unsubscribed_at' => current_time('mysql')
                ),
                array('id' => $subscriber_id)
            );
            
            $this->show_unsubscribe_confirmation();
        }
        
        exit;
    }
    
    /**
     * Output tracking pixel
     */
    private function output_tracking_pixel() {
        header('Content-Type: image/gif');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // 1x1 transparent GIF
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
    }
    
    /**
     * Show unsubscribe confirmation page
     */
    private function show_unsubscribe_confirmation() {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php _e('Unsubscribed', 'ai-newsletter-pro'); ?> - <?php bloginfo('name'); ?></title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: #f8fafc;
                    margin: 0;
                    padding: 2rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                }
                .unsubscribe-confirmation {
                    background: white;
                    padding: 3rem;
                    border-radius: 1rem;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                    text-align: center;
                    max-width: 500px;
                    width: 100%;
                }
                .unsubscribe-confirmation h1 {
                    color: #111827;
                    margin-bottom: 1rem;
                    font-size: 2rem;
                }
                .unsubscribe-confirmation p {
                    color: #6b7280;
                    line-height: 1.6;
                    margin-bottom: 2rem;
                }
                .back-link {
                    display: inline-block;
                    background: #3b82f6;
                    color: white;
                    padding: 0.75rem 2rem;
                    border-radius: 0.5rem;
                    text-decoration: none;
                    font-weight: 600;
                    transition: background 0.2s;
                }
                .back-link:hover {
                    background: #1d4ed8;
                    color: white;
                }
            </style>
        </head>
        <body>
            <div class="unsubscribe-confirmation">
                <h1><?php _e('Successfully Unsubscribed', 'ai-newsletter-pro'); ?></h1>
                <p><?php _e('You have been successfully unsubscribed from our newsletter. We\'re sorry to see you go!', 'ai-newsletter-pro'); ?></p>
                <p><?php _e('If you change your mind, you can always subscribe again using any of our signup forms.', 'ai-newsletter-pro'); ?></p>
                <a href="<?php echo esc_url(home_url()); ?>" class="back-link">
                    <?php _e('Return to Website', 'ai-newsletter-pro'); ?>
                </a>
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * Add tracking script to footer
     */
    public function add_tracking_script() {
        if (is_admin()) {
            return;
        }
        
        ?>
        <script>
        // AI Newsletter Pro tracking
        (function() {
            // Track page view for analytics
            if (typeof ai_newsletter_pro_ajax !== 'undefined') {
                // Track that user visited a page (for conversion funnel)
                var viewed = sessionStorage.getItem('ai_newsletter_page_viewed');
                if (!viewed) {
                    sessionStorage.setItem('ai_newsletter_page_viewed', '1');
                    
                    // You can add additional page view tracking here
                }
            }
            
            // Enhanced link tracking for newsletter emails
            document.addEventListener('click', function(e) {
                var link = e.target.closest('a');
                if (link && link.href.indexOf('ai_newsletter_track=click') !== -1) {
                    // Add small delay to ensure tracking fires
                    e.preventDefault();
                    setTimeout(function() {
                        window.location.href = link.href;
                    }, 100);
                }
            });
            
            // Track scroll depth for analytics
            var maxScroll = 0;
            var scrollTracked = false;
            
            window.addEventListener('scroll', function() {
                var scrollPercent = Math.round((window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100);
                
                if (scrollPercent > maxScroll) {
                    maxScroll = scrollPercent;
                }
                
                // Track 50% scroll depth once per session
                if (scrollPercent >= 50 && !scrollTracked) {
                    scrollTracked = true;
                    sessionStorage.setItem('ai_newsletter_scroll_50', '1');
                }
            });
        })();
        </script>
        <?php
    }
    
    /**
     * Handle newsletter status messages
     */
    public function show_status_messages() {
        if (!isset($_GET['newsletter_status'])) {
            return;
        }
        
        $status = sanitize_text_field($_GET['newsletter_status']);
        $messages = array(
            'subscribed' => __('Thank you for subscribing to our newsletter!', 'ai-newsletter-pro'),
            'confirmed' => __('Your subscription has been confirmed!', 'ai-newsletter-pro'),
            'unsubscribed' => __('You have been unsubscribed from our newsletter.', 'ai-newsletter-pro'),
            'error' => __('An error occurred. Please try again.', 'ai-newsletter-pro')
        );
        
        if (isset($messages[$status])) {
            $message_class = $status === 'error' ? 'error' : 'success';
            echo '<div class="ai-newsletter-status-message ' . esc_attr($message_class) . '">';
            echo '<p>' . esc_html($messages[$status]) . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Add custom body classes for newsletter pages
     */
    public function add_body_classes($classes) {
        if (isset($_GET['newsletter_status'])) {
            $classes[] = 'ai-newsletter-status-page';
            $classes[] = 'ai-newsletter-status-' . sanitize_html_class($_GET['newsletter_status']);
        }
        
        return $classes;
    }
    
    /**
     * Enqueue frontend styles for status messages
     */
    public function enqueue_status_styles() {
        if (isset($_GET['newsletter_status'])) {
            ?>
            <style>
            .ai-newsletter-status-message {
                background: #ecfdf5;
                border: 1px solid #10b981;
                color: #059669;
                padding: 1rem;
                margin: 1rem 0;
                border-radius: 0.5rem;
                text-align: center;
            }
            .ai-newsletter-status-message.error {
                background: #fef2f2;
                border-color: #ef4444;
                color: #dc2626;
            }
            </style>
            <?php
        }
    }
}

// Initialize public functionality
new AI_Newsletter_Pro_Public();