<?php
/**
 * Plugin Name: AI Newsletter Pro
 * Plugin URI: https://sawahsolutions.com/ai-newsletter-pro
 * Description: A comprehensive newsletter plugin with AI-powered content curation, multiple widget layouts, and seamless email service integrations.
 * Version: 1.0.3
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
define('AI_NEWSLETTER_PRO_VERSION', '1.0.3');
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
    }
    
    /**
     * Load plugin dependencies - Only load files that exist
     */
    private function load_dependencies() {
        // Core classes - only load if files exist
        $core_files = array(
            'includes/class-database.php',
            'includes/class-widget-manager.php',
            'includes/class-subscriber-manager.php'
        );
        
        foreach ($core_files as $file) {
            $file_path = AI_NEWSLETTER_PRO_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        // Admin classes - only if in admin and file exists
        if (is_admin()) {
            $admin_file = AI_NEWSLETTER_PRO_PLUGIN_DIR . 'admin/class-admin.php';
            if (file_exists($admin_file)) {
                require_once $admin_file;
            }
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables if database class exists
        if (class_exists('AI_Newsletter_Pro_Database')) {
            $database = new AI_Newsletter_Pro_Database();
            $database->create_tables();
        } else {
            // Create tables manually if class doesn't exist
            $this->create_basic_tables();
        }
        
        // Create default settings
        $this->create_default_settings();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
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
        // Initialize components only if classes exist
        if (class_exists('AI_Newsletter_Pro_Widget_Manager')) {
            new AI_Newsletter_Pro_Widget_Manager();
        }
        
        // Add admin menu
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('AI Newsletter Pro', 'ai-newsletter-pro'),
            __('Newsletter Pro', 'ai-newsletter-pro'),
            'manage_options',
            'ai-newsletter-pro',
            array($this, 'dashboard_page'),
            'dashicons-email-alt',
            30
        );
        
        add_submenu_page(
            'ai-newsletter-pro',
            __('Dashboard', 'ai-newsletter-pro'),
            __('Dashboard', 'ai-newsletter-pro'),
            'manage_options',
            'ai-newsletter-pro',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'ai-newsletter-pro',
            __('Subscribers', 'ai-newsletter-pro'),
            __('Subscribers', 'ai-newsletter-pro'),
            'manage_options',
            'ai-newsletter-pro-subscribers',
            array($this, 'subscribers_page')
        );
        
        add_submenu_page(
            'ai-newsletter-pro',
            __('Settings', 'ai-newsletter-pro'),
            __('Settings', 'ai-newsletter-pro'),
            'manage_options',
            'ai-newsletter-pro-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('AI Newsletter Pro Dashboard', 'ai-newsletter-pro') . '</h1>';
        echo '<div class="notice notice-success"><p>' . __('Plugin activated successfully! Start by adding the missing files.', 'ai-newsletter-pro') . '</p></div>';
        
        echo '<h2>' . __('Quick Setup', 'ai-newsletter-pro') . '</h2>';
        echo '<p>' . __('To unlock all features, add these files to your plugin directory:', 'ai-newsletter-pro') . '</p>';
        
        $required_files = array(
            'includes/class-database.php' => __('Database management', 'ai-newsletter-pro'),
            'includes/class-widget-manager.php' => __('Widget system', 'ai-newsletter-pro'),
            'includes/class-subscriber-manager.php' => __('Subscriber handling', 'ai-newsletter-pro'),
            'admin/class-admin.php' => __('Advanced admin features', 'ai-newsletter-pro'),
            'public/css/newsletter-widgets.css' => __('Widget styling', 'ai-newsletter-pro'),
            'public/js/newsletter-widgets.js' => __('Widget functionality', 'ai-newsletter-pro')
        );
        
        echo '<ul>';
        foreach ($required_files as $file => $description) {
            $exists = file_exists(AI_NEWSLETTER_PRO_PLUGIN_DIR . $file);
            $status = $exists ? '✅' : '❌';
            echo '<li>' . $status . ' <code>' . $file . '</code> - ' . $description . '</li>';
        }
        echo '</ul>';
        
        echo '</div>';
    }
    
    /**
     * Subscribers page
     */
    public function subscribers_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Subscribers', 'ai-newsletter-pro') . '</h1>';
        echo '<p>' . __('Subscriber management will be available once you add the subscriber manager files.', 'ai-newsletter-pro') . '</p>';
        echo '</div>';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Settings', 'ai-newsletter-pro') . '</h1>';
        echo '<p>' . __('Plugin settings will be available once you add the admin files.', 'ai-newsletter-pro') . '</p>';
        echo '</div>';
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        // Only load if files exist
        $css_file = AI_NEWSLETTER_PRO_PLUGIN_URL . 'public/css/newsletter-widgets.css';
        $js_file = AI_NEWSLETTER_PRO_PLUGIN_URL . 'public/js/newsletter-widgets.js';
        
        if (file_exists(AI_NEWSLETTER_PRO_PLUGIN_DIR . 'public/css/newsletter-widgets.css')) {
            wp_enqueue_style(
                'ai-newsletter-pro-widgets',
                $css_file,
                array(),
                AI_NEWSLETTER_PRO_VERSION
            );
        }
        
        if (file_exists(AI_NEWSLETTER_PRO_PLUGIN_DIR . 'public/js/newsletter-widgets.js')) {
            wp_enqueue_script(
                'ai-newsletter-pro-widgets',
                $js_file,
                array('jquery'),
                AI_NEWSLETTER_PRO_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('ai-newsletter-pro-widgets', 'ai_newsletter_pro_ajax', array(
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
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'ai-newsletter-pro') === false) {
            return;
        }
        
        // Basic admin styling
        wp_add_inline_style('wp-admin', '
            .ai-newsletter-pro .notice { margin: 20px 0; }
            .ai-newsletter-pro ul { margin: 20px 0; }
            .ai-newsletter-pro li { margin: 10px 0; font-family: monospace; }
        ');
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
        
        // Use subscriber manager if available, otherwise basic handling
        if (class_exists('AI_Newsletter_Pro_Subscriber_Manager')) {
            $subscriber_manager = new AI_Newsletter_Pro_Subscriber_Manager();
            $result = $subscriber_manager->add_subscriber($email, '', $source);
            
            if ($result['success']) {
                wp_send_json_success(array('message' => __('Successfully subscribed!', 'ai-newsletter-pro')));
            } else {
                wp_send_json_error(array('message' => $result['message']));
            }
        } else {
            // Basic email storage until full system is set up
            $this->basic_subscriber_handling($email, $source);
            wp_send_json_success(array('message' => __('Successfully subscribed!', 'ai-newsletter-pro')));
        }
    }
    
    /**
     * Basic subscriber handling when full system isn't available
     */
    private function basic_subscriber_handling($email, $source) {
        global $wpdb;
        
        // Check if basic table exists
        $table_name = AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            // Insert subscriber
            $wpdb->insert(
                $table_name,
                array(
                    'email' => $email,
                    'source' => $source,
                    'status' => 'subscribed',
                    'subscribed_at' => current_time('mysql')
                )
            );
        }
    }
    
    /**
     * Create basic database tables
     */
    private function create_basic_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Basic subscribers table
        $subscribers_table = "CREATE TABLE " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            name varchar(255) DEFAULT '',
            status enum('subscribed','unsubscribed','pending','bounced') DEFAULT 'subscribed',
            source varchar(100) DEFAULT 'unknown',
            subscribed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($subscribers_table);
    }
    
    /**
     * Create default settings
     */
    private function create_default_settings() {
        $default_settings = array(
            'general' => array(
                'double_optin' => false,
                'gdpr_compliance' => true,
                'from_name' => get_bloginfo('name'),
                'from_email' => get_option('admin_email')
            )
        );
        
        if (!get_option('ai_newsletter_pro_settings')) {
            update_option('ai_newsletter_pro_settings', $default_settings);
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