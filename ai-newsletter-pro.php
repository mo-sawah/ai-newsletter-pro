<?php
/**
 * Plugin Name: AI Newsletter Pro
 * Plugin URI: https://sawahsolutions.com/ai-newsletter-pro
 * Description: A comprehensive newsletter plugin with AI-powered content curation, multiple widget layouts, and seamless email service integrations.
 * Version: 1.0.0
 * Author: Mohamed Sawah
 * Author URI: https://sawahsolutions.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-newsletter-pro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('AI_NEWSLETTER_PRO_VERSION', '1.0.0');
define('AI_NEWSLETTER_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_NEWSLETTER_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_NEWSLETTER_PRO_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Database table names
global $wpdb;
define('AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE', $wpdb->prefix . 'ai_newsletter_subscribers');
define('AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE', $wpdb->prefix . 'ai_newsletter_campaigns');
define('AI_NEWSLETTER_PRO_WIDGETS_TABLE', $wpdb->prefix . 'ai_newsletter_widgets');
define('AI_NEWSLETTER_PRO_ANALYTICS_TABLE', $wpdb->prefix . 'ai_newsletter_analytics');

/**
 * Main plugin class
 */
class AI_Newsletter_Pro {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX hooks
        add_action('wp_ajax_ai_newsletter_subscribe', array($this, 'handle_subscription'));
        add_action('wp_ajax_nopriv_ai_newsletter_subscribe', array($this, 'handle_subscription'));
        
        // REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once AI_NEWSLETTER_PRO_PLUGIN_DIR . 'includes/class-database.php';
        require_once AI_NEWSLETTER_PRO_PLUGIN_DIR . 'includes/class-widget-manager.php';
        require_once AI_NEWSLETTER_PRO_PLUGIN_DIR . 'includes/class-email-services.php';
        require_once AI_NEWSLETTER_PRO_PLUGIN_DIR . 'includes/class-ai-curator.php';
        require_once AI_NEWSLETTER_PRO_PLUGIN_DIR . 'includes/class-campaign-manager.php';
        require_once AI_NEWSLETTER_PRO_PLUGIN_DIR . 'includes/class-subscriber-manager.php';
        require_once AI_NEWSLETTER_PRO_PLUGIN_DIR . 'includes/class-analytics.php';
        require_once AI_NEWSLETTER_PRO_PLUGIN_DIR . 'includes/class-shortcodes.php';
        
        // Admin classes
        if (is_admin()) {
            require_once AI_NEWSLETTER_PRO_PLUGIN_DIR . 'admin/class-admin.php';
        }
        
        // Public classes
        require_once AI_NEWSLETTER_PRO_PLUGIN_DIR . 'public/class-public.php';
        
        // Email service integrations
        require_once AI_NEWSLETTER_PRO_PLUGIN_DIR . 'integrations/class-mailchimp.php';
        require_once AI_NEWSLETTER_PRO_PLUGIN_DIR . 'integrations/class-convertkit.php';
        require_once AI_NEWSLETTER_PRO_PLUGIN_DIR . 'integrations/class-zoho.php';
        require_once AI_NEWSLETTER_PRO_PLUGIN_DIR . 'integrations/class-sendgrid.php';
        require_once AI_NEWSLETTER_PRO_PLUGIN_DIR . 'integrations/class-activecampaign.php';
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $database = new AI_Newsletter_Pro_Database();
        $database->create_tables();
        
        // Create default settings
        $this->create_default_settings();
        
        // Create default widgets
        $this->create_default_widgets();
        
        // Schedule AI curation if enabled
        if (!wp_next_scheduled('ai_newsletter_pro_auto_curate')) {
            wp_schedule_event(time(), 'weekly', 'ai_newsletter_pro_auto_curate');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('ai_newsletter_pro_auto_curate');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'ai-newsletter-pro',
            false,
            dirname(AI_NEWSLETTER_PRO_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize shortcodes
        new AI_Newsletter_Pro_Shortcodes();
        
        // Initialize widget manager
        new AI_Newsletter_Pro_Widget_Manager();
        
        // Initialize AI curator
        new AI_Newsletter_Pro_AI_Curator();
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style(
            'ai-newsletter-pro-frontend',
            AI_NEWSLETTER_PRO_PLUGIN_URL . 'public/css/newsletter-frontend.css',
            array(),
            AI_NEWSLETTER_PRO_VERSION
        );
        
        wp_enqueue_style(
            'ai-newsletter-pro-widgets',
            AI_NEWSLETTER_PRO_PLUGIN_URL . 'public/css/newsletter-widgets.css',
            array(),
            AI_NEWSLETTER_PRO_VERSION
        );
        
        wp_enqueue_script(
            'ai-newsletter-pro-widgets',
            AI_NEWSLETTER_PRO_PLUGIN_URL . 'public/js/newsletter-widgets.js',
            array('jquery'),
            AI_NEWSLETTER_PRO_VERSION,
            true
        );
        
        wp_enqueue_script(
            'ai-newsletter-pro-frontend',
            AI_NEWSLETTER_PRO_PLUGIN_URL . 'public/js/newsletter-frontend.js',
            array('jquery'),
            AI_NEWSLETTER_PRO_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('ai-newsletter-pro-frontend', 'ai_newsletter_pro_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_newsletter_pro_nonce'),
            'messages' => array(
                'success' => __('Thank you for subscribing!', 'ai-newsletter-pro'),
                'error' => __('Something went wrong. Please try again.', 'ai-newsletter-pro'),
                'invalid_email' => __('Please enter a valid email address.', 'ai-newsletter-pro'),
                'already_subscribed' => __('You are already subscribed!', 'ai-newsletter-pro')
            )
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'ai-newsletter-pro') === false) {
            return;
        }
        
        wp_enqueue_style(
            'ai-newsletter-pro-admin',
            AI_NEWSLETTER_PRO_PLUGIN_URL . 'public/css/newsletter-admin.css',
            array(),
            AI_NEWSLETTER_PRO_VERSION
        );
        
        wp_enqueue_script(
            'ai-newsletter-pro-admin',
            AI_NEWSLETTER_PRO_PLUGIN_URL . 'public/js/newsletter-admin.js',
            array('jquery'),
            AI_NEWSLETTER_PRO_VERSION,
            true
        );
        
        // Localize admin script
        wp_localize_script('ai-newsletter-pro-admin', 'ai_newsletter_pro_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_newsletter_pro_admin_nonce')
        ));
    }
    
    /**
     * Handle subscription AJAX request
     */
    public function handle_subscription() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ai_newsletter_pro_nonce')) {
            wp_die('Security check failed');
        }
        
        $email = sanitize_email($_POST['email']);
        $source = sanitize_text_field($_POST['source'] ?? 'unknown');
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Invalid email address', 'ai-newsletter-pro')));
        }
        
        $subscriber_manager = new AI_Newsletter_Pro_Subscriber_Manager();
        $result = $subscriber_manager->add_subscriber($email, '', $source);
        
        if ($result['success']) {
            wp_send_json_success(array('message' => __('Successfully subscribed!', 'ai-newsletter-pro')));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('ai-newsletter-pro/v1', '/subscribe', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_subscribe'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('ai-newsletter-pro/v1', '/unsubscribe', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_unsubscribe'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * REST API subscribe endpoint
     */
    public function rest_subscribe($request) {
        $email = sanitize_email($request->get_param('email'));
        $source = sanitize_text_field($request->get_param('source') ?? 'api');
        
        if (!is_email($email)) {
            return new WP_Error('invalid_email', 'Invalid email address', array('status' => 400));
        }
        
        $subscriber_manager = new AI_Newsletter_Pro_Subscriber_Manager();
        $result = $subscriber_manager->add_subscriber($email, '', $source);
        
        if ($result['success']) {
            return rest_ensure_response(array('message' => 'Successfully subscribed'));
        } else {
            return new WP_Error('subscription_failed', $result['message'], array('status' => 400));
        }
    }
    
    /**
     * REST API unsubscribe endpoint
     */
    public function rest_unsubscribe($request) {
        $email = sanitize_email($request->get_param('email'));
        
        if (!is_email($email)) {
            return new WP_Error('invalid_email', 'Invalid email address', array('status' => 400));
        }
        
        $subscriber_manager = new AI_Newsletter_Pro_Subscriber_Manager();
        $result = $subscriber_manager->unsubscribe($email);
        
        if ($result['success']) {
            return rest_ensure_response(array('message' => 'Successfully unsubscribed'));
        } else {
            return new WP_Error('unsubscribe_failed', $result['message'], array('status' => 400));
        }
    }
    
    /**
     * Create default settings
     */
    private function create_default_settings() {
        $default_settings = array(
            'general' => array(
                'double_optin' => true,
                'gdpr_compliance' => true,
                'auto_ai_curation' => false,
                'from_name' => get_bloginfo('name'),
                'from_email' => get_option('admin_email'),
                'reply_to' => get_option('admin_email')
            ),
            'widgets' => array(
                'popup_enabled' => true,
                'popup_delay' => 5000,
                'popup_scroll_trigger' => 50,
                'floating_enabled' => false,
                'banner_enabled' => false
            ),
            'integrations' => array(
                'mailchimp' => array('enabled' => false, 'api_key' => '', 'list_id' => ''),
                'convertkit' => array('enabled' => false, 'api_key' => '', 'form_id' => ''),
                'zoho' => array('enabled' => false, 'client_id' => '', 'client_secret' => ''),
                'sendgrid' => array('enabled' => false, 'api_key' => ''),
                'activecampaign' => array('enabled' => false, 'api_url' => '', 'api_key' => '')
            ),
            'ai' => array(
                'openai_api_key' => '',
                'content_selection_criteria' => 'engagement',
                'newsletter_frequency' => 'weekly',
                'max_articles' => 5
            )
        );
        
        update_option('ai_newsletter_pro_settings', $default_settings);
    }
    
    /**
     * Create default widgets
     */
    private function create_default_widgets() {
        global $wpdb;
        
        $default_widgets = array(
            array(
                'type' => 'popup',
                'settings' => json_encode(array(
                    'title' => 'Join 10,000+ Readers',
                    'subtitle' => 'Get weekly insights, exclusive content, and industry updates delivered to your inbox.',
                    'button_text' => 'Subscribe Now',
                    'style' => 'modern',
                    'trigger' => 'time',
                    'trigger_value' => 5000
                )),
                'position' => 'center',
                'active' => 1,
                'created_at' => current_time('mysql')
            ),
            array(
                'type' => 'inline',
                'settings' => json_encode(array(
                    'title' => 'Love This Content?',
                    'subtitle' => 'Subscribe for weekly insights, behind-the-scenes content, and exclusive tutorials.',
                    'button_text' => 'Join Community',
                    'style' => 'gradient'
                )),
                'position' => 'after_content',
                'active' => 0,
                'created_at' => current_time('mysql')
            )
        );
        
        foreach ($default_widgets as $widget) {
            $wpdb->insert(AI_NEWSLETTER_PRO_WIDGETS_TABLE, $widget);
        }
    }
}

// Initialize the plugin
function ai_newsletter_pro_init() {
    return AI_Newsletter_Pro::get_instance();
}

// Start the plugin
ai_newsletter_pro_init();

// Plugin utility functions
function ai_newsletter_pro_get_option($key, $default = '') {
    $settings = get_option('ai_newsletter_pro_settings', array());
    return $settings[$key] ?? $default;
}

function ai_newsletter_pro_update_option($key, $value) {
    $settings = get_option('ai_newsletter_pro_settings', array());
    $settings[$key] = $value;
    return update_option('ai_newsletter_pro_settings', $settings);
}

// Scheduled AI curation hook
add_action('ai_newsletter_pro_auto_curate', function() {
    if (ai_newsletter_pro_get_option('ai')['auto_ai_curation'] ?? false) {
        $ai_curator = new AI_Newsletter_Pro_AI_Curator();
        $ai_curator->auto_generate_newsletter();
    }
});