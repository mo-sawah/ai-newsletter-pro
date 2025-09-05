<?php
/**
 * Admin class for AI Newsletter Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Newsletter_Pro_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('AI Newsletter Pro', 'ai-newsletter-pro'),
            __('Newsletter Pro', 'ai-newsletter-pro'),
            'manage_options',
            'ai-newsletter-pro',
            array($this, 'dashboard_page'),
            'dashicons-email-alt',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'ai-newsletter-pro',
            __('Dashboard', 'ai-newsletter-pro'),
            __('Dashboard', 'ai-newsletter-pro'),
            'manage_options',
            'ai-newsletter-pro',
            array($this, 'dashboard_page')
        );
        
        // Subscribers submenu
        add_submenu_page(
            'ai-newsletter-pro',
            __('Subscribers', 'ai-newsletter-pro'),
            __('Subscribers', 'ai-newsletter-pro'),
            'manage_options',
            'ai-newsletter-pro-subscribers',
            array($this, 'subscribers_page')
        );
        
        // Campaigns submenu
        add_submenu_page(
            'ai-newsletter-pro',
            __('Campaigns', 'ai-newsletter-pro'),
            __('Campaigns', 'ai-newsletter-pro'),
            'manage_options',
            'ai-newsletter-pro-campaigns',
            array($this, 'campaigns_page')
        );
        
        // Widgets submenu
        add_submenu_page(
            'ai-newsletter-pro',
            __('Widgets', 'ai-newsletter-pro'),
            __('Widgets', 'ai-newsletter-pro'),
            'manage_options',
            'ai-newsletter-pro-widgets',
            array($this, 'widgets_page')
        );
        
        // Templates submenu
        add_submenu_page(
            'ai-newsletter-pro',
            __('Templates', 'ai-newsletter-pro'),
            __('Templates', 'ai-newsletter-pro'),
            'manage_options',
            'ai-newsletter-pro-templates',
            array($this, 'templates_page')
        );
        
        // Integrations submenu
        add_submenu_page(
            'ai-newsletter-pro',
            __('Integrations', 'ai-newsletter-pro'),
            __('Integrations', 'ai-newsletter-pro'),
            'manage_options',
            'ai-newsletter-pro-integrations',
            array($this, 'integrations_page')
        );
        
        // Analytics submenu
        add_submenu_page(
            'ai-newsletter-pro',
            __('Analytics', 'ai-newsletter-pro'),
            __('Analytics', 'ai-newsletter-pro'),
            'manage_options',
            'ai-newsletter-pro-analytics',
            array($this, 'analytics_page')
        );
        
        // Settings submenu
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
     * Initialize settings
     */
    public function init_settings() {
        register_setting('ai_newsletter_pro_settings', 'ai_newsletter_pro_settings');
        
        // General settings section
        add_settings_section(
            'ai_newsletter_pro_general',
            __('General Settings', 'ai-newsletter-pro'),
            array($this, 'general_section_callback'),
            'ai_newsletter_pro_settings'
        );
        
        // Widget settings section
        add_settings_section(
            'ai_newsletter_pro_widgets',
            __('Widget Settings', 'ai-newsletter-pro'),
            array($this, 'widgets_section_callback'),
            'ai_newsletter_pro_settings'
        );
        
        // AI settings section
        add_settings_section(
            'ai_newsletter_pro_ai',
            __('AI Configuration', 'ai-newsletter-pro'),
            array($this, 'ai_section_callback'),
            'ai_newsletter_pro_settings'
        );
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $database = new AI_Newsletter_Pro_Database();
        $stats = $database->get_stats();
        
        include AI_NEWSLETTER_PRO_PLUGIN_DIR . 'admin/admin-dashboard.php';
    }
    
    /**
     * Subscribers page
     */
    public function subscribers_page() {
        include AI_NEWSLETTER_PRO_PLUGIN_DIR . 'admin/admin-subscribers.php';
    }
    
    /**
     * Campaigns page
     */
    public function campaigns_page() {
        include AI_NEWSLETTER_PRO_PLUGIN_DIR . 'admin/admin-campaigns.php';
    }
    
    /**
     * Widgets page
     */
    public function widgets_page() {
        include AI_NEWSLETTER_PRO_PLUGIN_DIR . 'admin/admin-widgets.php';
    }
    
    /**
     * Templates page
     */
    public function templates_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Email Templates', 'ai-newsletter-pro') . '</h1>';
        echo '<p>' . __('Email template management coming in the next update.', 'ai-newsletter-pro') . '</p>';
        echo '</div>';
    }
    
    /**
     * Integrations page
     */
    public function integrations_page() {
        include AI_NEWSLETTER_PRO_PLUGIN_DIR . 'admin/admin-integrations.php';
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        include AI_NEWSLETTER_PRO_PLUGIN_DIR . 'admin/admin-analytics.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        include AI_NEWSLETTER_PRO_PLUGIN_DIR . 'admin/admin-settings.php';
    }
    
    /**
     * General settings section callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general newsletter settings.', 'ai-newsletter-pro') . '</p>';
    }
    
    /**
     * Widget settings section callback
     */
    public function widgets_section_callback() {
        echo '<p>' . __('Configure newsletter widget behavior.', 'ai-newsletter-pro') . '</p>';
    }
    
    /**
     * AI settings section callback
     */
    public function ai_section_callback() {
        echo '<p>' . __('Configure AI-powered content curation.', 'ai-newsletter-pro') . '</p>';
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        $settings = get_option('ai_newsletter_pro_settings', array());
        
        // Check if any email service is configured
        $has_integration = false;
        if (isset($settings['integrations'])) {
            foreach ($settings['integrations'] as $service => $config) {
                if (!empty($config['enabled'])) {
                    $has_integration = true;
                    break;
                }
            }
        }
        
        if (!$has_integration && isset($_GET['page']) && strpos($_GET['page'], 'ai-newsletter-pro') === 0) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>' . sprintf(
                __('No email service integration is configured. <a href="%s">Set up an integration</a> to start sending newsletters.', 'ai-newsletter-pro'),
                admin_url('admin.php?page=ai-newsletter-pro-integrations')
            ) . '</p>';
            echo '</div>';
        }
    }
}

// Initialize admin
new AI_Newsletter_Pro_Admin();