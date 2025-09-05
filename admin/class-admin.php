<?php
/**
 * Admin class for AI Newsletter Pro - Fixed Version
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
        // Check if advanced dashboard exists, otherwise use basic version
        $dashboard_file = AI_NEWSLETTER_PRO_PLUGIN_DIR . 'admin/admin-dashboard.php';
        
        if (file_exists($dashboard_file)) {
            // Get stats if database class exists
            if (class_exists('AI_Newsletter_Pro_Database')) {
                $database = new AI_Newsletter_Pro_Database();
                $stats = $database->get_stats();
            } else {
                $stats = array();
            }
            include $dashboard_file;
        } else {
            $this->basic_dashboard_page();
        }
    }
    
    /**
     * Basic dashboard page (fallback)
     */
    private function basic_dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('AI Newsletter Pro Dashboard', 'ai-newsletter-pro'); ?></h1>
            
            <div class="notice notice-info">
                <p><strong><?php _e('Welcome to AI Newsletter Pro!', 'ai-newsletter-pro'); ?></strong></p>
                <p><?php _e('Your plugin is working! Add more files to unlock advanced features.', 'ai-newsletter-pro'); ?></p>
            </div>
            
            <div class="card">
                <h2><?php _e('Quick Setup Guide', 'ai-newsletter-pro'); ?></h2>
                <p><?php _e('To unlock all features, add these files to your plugin directory:', 'ai-newsletter-pro'); ?></p>
                
                <?php
                $required_files = array(
                    'admin/admin-dashboard.php' => __('Advanced Dashboard', 'ai-newsletter-pro'),
                    'admin/admin-subscribers.php' => __('Subscriber Management', 'ai-newsletter-pro'),
                    'admin/admin-campaigns.php' => __('Campaign Management', 'ai-newsletter-pro'),
                    'admin/admin-widgets.php' => __('Widget Management', 'ai-newsletter-pro'),
                    'admin/admin-integrations.php' => __('Email Service Integrations', 'ai-newsletter-pro'),
                    'admin/admin-analytics.php' => __('Analytics & Reports', 'ai-newsletter-pro'),
                    'admin/admin-settings.php' => __('Settings Panel', 'ai-newsletter-pro'),
                    'includes/class-database.php' => __('Database Management', 'ai-newsletter-pro'),
                    'includes/class-widget-manager.php' => __('Widget System', 'ai-newsletter-pro'),
                    'includes/class-subscriber-manager.php' => __('Subscriber Handling', 'ai-newsletter-pro'),
                    'public/css/newsletter-widgets.css' => __('Widget Styling', 'ai-newsletter-pro'),
                    'public/js/newsletter-widgets.js' => __('Widget Functionality', 'ai-newsletter-pro')
                );
                
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>' . __('File', 'ai-newsletter-pro') . '</th><th>' . __('Description', 'ai-newsletter-pro') . '</th><th>' . __('Status', 'ai-newsletter-pro') . '</th></tr></thead>';
                echo '<tbody>';
                
                foreach ($required_files as $file => $description) {
                    $exists = file_exists(AI_NEWSLETTER_PRO_PLUGIN_DIR . $file);
                    $status = $exists ? '<span style="color: green;">✅ ' . __('Installed', 'ai-newsletter-pro') . '</span>' : '<span style="color: red;">❌ ' . __('Missing', 'ai-newsletter-pro') . '</span>';
                    echo '<tr>';
                    echo '<td><code>' . esc_html($file) . '</code></td>';
                    echo '<td>' . esc_html($description) . '</td>';
                    echo '<td>' . $status . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
                ?>
                
                <h3><?php _e('Basic Subscriber Management', 'ai-newsletter-pro'); ?></h3>
                <p><?php _e('Even with basic setup, you can view subscribers:', 'ai-newsletter-pro'); ?></p>
                
                <?php
                // Show basic subscriber count if table exists
                global $wpdb;
                $table_name = AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE;
                
                if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                    $subscriber_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'subscribed'");
                    echo '<div class="notice notice-success inline">';
                    echo '<p><strong>' . sprintf(__('You have %d active subscribers!', 'ai-newsletter-pro'), $subscriber_count) . '</strong></p>';
                    echo '</div>';
                    
                    // Show recent subscribers
                    $recent_subscribers = $wpdb->get_results("SELECT email, subscribed_at FROM $table_name ORDER BY subscribed_at DESC LIMIT 5");
                    
                    if ($recent_subscribers) {
                        echo '<h4>' . __('Recent Subscribers', 'ai-newsletter-pro') . '</h4>';
                        echo '<ul>';
                        foreach ($recent_subscribers as $subscriber) {
                            echo '<li>' . esc_html($subscriber->email) . ' - ' . esc_html($subscriber->subscribed_at) . '</li>';
                        }
                        echo '</ul>';
                    }
                } else {
                    echo '<div class="notice notice-warning inline">';
                    echo '<p>' . __('Database tables not found. Please deactivate and reactivate the plugin.', 'ai-newsletter-pro') . '</p>';
                    echo '</div>';
                }
                ?>
            </div>
            
            <div class="card">
                <h2><?php _e('Test Subscription Form', 'ai-newsletter-pro'); ?></h2>
                <p><?php _e('Test the basic subscription functionality:', 'ai-newsletter-pro'); ?></p>
                
                <form id="test-subscription-form" style="max-width: 400px;">
                    <p>
                        <input type="email" id="test-email" placeholder="<?php _e('Enter email address', 'ai-newsletter-pro'); ?>" style="width: 70%; padding: 8px;" required>
                        <button type="submit" class="button button-primary" style="padding: 8px 16px;"><?php _e('Subscribe', 'ai-newsletter-pro'); ?></button>
                    </p>
                    <div id="test-result"></div>
                </form>
                
                <script>
                jQuery(document).ready(function($) {
                    $('#test-subscription-form').on('submit', function(e) {
                        e.preventDefault();
                        
                        var email = $('#test-email').val();
                        var $result = $('#test-result');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'ai_newsletter_subscribe',
                                email: email,
                                source: 'admin-test',
                                nonce: '<?php echo wp_create_nonce('ai_newsletter_pro_nonce'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                                    $('#test-email').val('');
                                } else {
                                    $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                                }
                            },
                            error: function() {
                                $result.html('<div class="notice notice-error inline"><p><?php _e('Error occurred. Please try again.', 'ai-newsletter-pro'); ?></p></div>');
                            }
                        });
                    });
                });
                </script>
            </div>
        </div>
        <?php
    }
    
    /**
     * Subscribers page
     */
    public function subscribers_page() {
        $subscribers_file = AI_NEWSLETTER_PRO_PLUGIN_DIR . 'admin/admin-subscribers.php';
        
        if (file_exists($subscribers_file)) {
            include $subscribers_file;
        } else {
            $this->basic_subscribers_page();
        }
    }
    
    /**
     * Basic subscribers page (fallback)
     */
    private function basic_subscribers_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Subscribers', 'ai-newsletter-pro'); ?></h1>
            
            <?php
            global $wpdb;
            $table_name = AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE;
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                $subscribers = $wpdb->get_results("SELECT * FROM $table_name ORDER BY subscribed_at DESC LIMIT 50");
                
                if ($subscribers) {
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr><th>' . __('Email', 'ai-newsletter-pro') . '</th><th>' . __('Status', 'ai-newsletter-pro') . '</th><th>' . __('Source', 'ai-newsletter-pro') . '</th><th>' . __('Date', 'ai-newsletter-pro') . '</th></tr></thead>';
                    echo '<tbody>';
                    
                    foreach ($subscribers as $subscriber) {
                        echo '<tr>';
                        echo '<td>' . esc_html($subscriber->email) . '</td>';
                        echo '<td>' . esc_html($subscriber->status) . '</td>';
                        echo '<td>' . esc_html($subscriber->source) . '</td>';
                        echo '<td>' . esc_html($subscriber->subscribed_at) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table>';
                } else {
                    echo '<p>' . __('No subscribers yet. Start collecting emails with your subscription forms!', 'ai-newsletter-pro') . '</p>';
                }
            } else {
                echo '<div class="notice notice-error"><p>' . __('Subscribers table not found. Please check your database setup.', 'ai-newsletter-pro') . '</p></div>';
            }
            ?>
            
            <p><em><?php _e('Add the full subscriber management files to unlock advanced features like import/export, segmentation, and detailed analytics.', 'ai-newsletter-pro'); ?></em></p>
        </div>
        <?php
    }
    
    /**
     * Campaigns page
     */
    public function campaigns_page() {
        $campaigns_file = AI_NEWSLETTER_PRO_PLUGIN_DIR . 'admin/admin-campaigns.php';
        
        if (file_exists($campaigns_file)) {
            include $campaigns_file;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . __('Campaigns', 'ai-newsletter-pro') . '</h1>';
            echo '<div class="notice notice-info"><p>' . __('Campaign management will be available once you add the campaigns management files.', 'ai-newsletter-pro') . '</p></div>';
            echo '<p>' . __('Create and manage email campaigns, newsletters, and automated sequences.', 'ai-newsletter-pro') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Widgets page
     */
    public function widgets_page() {
        $widgets_file = AI_NEWSLETTER_PRO_PLUGIN_DIR . 'admin/admin-widgets.php';
        
        if (file_exists($widgets_file)) {
            include $widgets_file;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . __('Widgets', 'ai-newsletter-pro') . '</h1>';
            echo '<div class="notice notice-info"><p>' . __('Widget management will be available once you add the widget management files.', 'ai-newsletter-pro') . '</p></div>';
            echo '<p>' . __('Design and customize popup forms, inline forms, floating widgets, and banner notifications.', 'ai-newsletter-pro') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Templates page
     */
    public function templates_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Email Templates', 'ai-newsletter-pro') . '</h1>';
        echo '<div class="notice notice-info"><p>' . __('Email template management coming in the next update.', 'ai-newsletter-pro') . '</p></div>';
        echo '</div>';
    }
    
    /**
     * Integrations page
     */
    public function integrations_page() {
        $integrations_file = AI_NEWSLETTER_PRO_PLUGIN_DIR . 'admin/admin-integrations.php';
        
        if (file_exists($integrations_file)) {
            include $integrations_file;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . __('Email Service Integrations', 'ai-newsletter-pro') . '</h1>';
            echo '<div class="notice notice-info"><p>' . __('Integration management will be available once you add the integration files.', 'ai-newsletter-pro') . '</p></div>';
            echo '<p>' . __('Connect with Mailchimp, ConvertKit, Zoho Campaigns, SendGrid, ActiveCampaign, and more.', 'ai-newsletter-pro') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        $analytics_file = AI_NEWSLETTER_PRO_PLUGIN_DIR . 'admin/admin-analytics.php';
        
        if (file_exists($analytics_file)) {
            include $analytics_file;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . __('Analytics & Reports', 'ai-newsletter-pro') . '</h1>';
            echo '<div class="notice notice-info"><p>' . __('Analytics will be available once you add the analytics files.', 'ai-newsletter-pro') . '</p></div>';
            echo '<p>' . __('Track subscriber growth, conversion rates, email performance, and widget effectiveness.', 'ai-newsletter-pro') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $settings_file = AI_NEWSLETTER_PRO_PLUGIN_DIR . 'admin/admin-settings.php';
        
        if (file_exists($settings_file)) {
            include $settings_file;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . __('Settings', 'ai-newsletter-pro') . '</h1>';
            echo '<div class="notice notice-info"><p>' . __('Advanced settings will be available once you add the settings files.', 'ai-newsletter-pro') . '</p></div>';
            echo '<p>' . __('Configure general settings, AI options, GDPR compliance, and email preferences.', 'ai-newsletter-pro') . '</p>';
            echo '</div>';
        }
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
        
        // Check if this is a newsletter pro page
        if (!isset($_GET['page']) || strpos($_GET['page'], 'ai-newsletter-pro') !== 0) {
            return;
        }
        
        // Check for missing core files
        $missing_files = array();
        $core_files = array(
            'includes/class-database.php' => __('Database Management', 'ai-newsletter-pro'),
            'includes/class-widget-manager.php' => __('Widget System', 'ai-newsletter-pro'),
            'public/css/newsletter-widgets.css' => __('Widget Styling', 'ai-newsletter-pro'),
            'public/js/newsletter-widgets.js' => __('Widget Functionality', 'ai-newsletter-pro')
        );
        
        foreach ($core_files as $file => $description) {
            if (!file_exists(AI_NEWSLETTER_PRO_PLUGIN_DIR . $file)) {
                $missing_files[] = $description;
            }
        }
        
        if (!empty($missing_files) && count($missing_files) > 2) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . __('AI Newsletter Pro:', 'ai-newsletter-pro') . '</strong> ' . 
                 sprintf(__('Add core files to unlock full functionality. Missing: %s', 'ai-newsletter-pro'), 
                 implode(', ', array_slice($missing_files, 0, 3))) . '</p>';
            echo '</div>';
        }
    }
}

// Initialize admin
new AI_Newsletter_Pro_Admin();