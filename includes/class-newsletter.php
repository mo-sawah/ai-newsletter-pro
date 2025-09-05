<?php
/**
 * Main Newsletter class for AI Newsletter Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Newsletter_Pro_Newsletter {
    
    private $version;
    private $plugin_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->version = AI_NEWSLETTER_PRO_VERSION;
        $this->plugin_name = 'ai-newsletter-pro';
        
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core classes
        $this->load_file('includes/class-database.php');
        $this->load_file('includes/class-subscriber-manager.php');
        $this->load_file('includes/class-campaign-manager.php');
        $this->load_file('includes/class-widget-manager.php');
        $this->load_file('includes/class-analytics.php');
        $this->load_file('includes/class-ai-curator.php');
        $this->load_file('includes/class-email-services.php');
        $this->load_file('includes/class-shortcodes.php');
        
        // Admin classes
        if (is_admin()) {
            $this->load_file('admin/class-admin.php');
        }
        
        // Public classes
        $this->load_file('public/class-public.php');
        
        // Integration classes
        $this->load_file('integrations/class-mailchimp.php');
        $this->load_file('integrations/class-convertkit.php');
        $this->load_file('integrations/class-zoho.php');
        $this->load_file('integrations/class-sendgrid.php');
        $this->load_file('integrations/class-activecampaign.php');
    }
    
    /**
     * Load file if it exists
     */
    private function load_file($file) {
        $file_path = AI_NEWSLETTER_PRO_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
    
    /**
     * Define admin hooks
     */
    private function define_admin_hooks() {
        if (!is_admin()) {
            return;
        }
        
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'maybe_upgrade_database'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
    }
    
    /**
     * Define public hooks
     */
    private function define_public_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_action('init', array($this, 'load_plugin_textdomain'));
        add_action('widgets_init', array($this, 'register_widgets'));
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook) {
        if (strpos($hook, 'ai-newsletter-pro') === false) {
            return;
        }
        
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            AI_NEWSLETTER_PRO_PLUGIN_URL . 'public/css/newsletter-admin.css',
            array(),
            $this->version,
            'all'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ai-newsletter-pro') === false) {
            return;
        }
        
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            AI_NEWSLETTER_PRO_PLUGIN_URL . 'public/js/newsletter-admin.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script(
            $this->plugin_name . '-admin',
            'ai_newsletter_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_newsletter_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'ai-newsletter-pro'),
                    'saving' => __('Saving...', 'ai-newsletter-pro'),
                    'saved' => __('Saved!', 'ai-newsletter-pro'),
                    'error' => __('Error occurred', 'ai-newsletter-pro')
                )
            )
        );
    }
    
    /**
     * Enqueue public styles
     */
    public function enqueue_public_styles() {
        wp_enqueue_style(
            $this->plugin_name . '-widgets',
            AI_NEWSLETTER_PRO_PLUGIN_URL . 'public/css/newsletter-widgets.css',
            array(),
            $this->version,
            'all'
        );
        
        wp_enqueue_style(
            $this->plugin_name . '-frontend',
            AI_NEWSLETTER_PRO_PLUGIN_URL . 'public/css/newsletter-frontend.css',
            array(),
            $this->version,
            'all'
        );
    }
    
    /**
     * Enqueue public scripts
     */
    public function enqueue_public_scripts() {
        wp_enqueue_script(
            $this->plugin_name . '-widgets',
            AI_NEWSLETTER_PRO_PLUGIN_URL . 'public/js/newsletter-widgets.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_enqueue_script(
            $this->plugin_name . '-frontend',
            AI_NEWSLETTER_PRO_PLUGIN_URL . 'public/js/newsletter-frontend.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script(
            $this->plugin_name . '-widgets',
            'ai_newsletter_pro_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_newsletter_pro_nonce'),
                'messages' => array(
                    'success' => __('Thank you for subscribing!', 'ai-newsletter-pro'),
                    'error' => __('Something went wrong. Please try again.', 'ai-newsletter-pro'),
                    'invalid_email' => __('Please enter a valid email address.', 'ai-newsletter-pro'),
                    'already_subscribed' => __('You are already subscribed!', 'ai-newsletter-pro')
                )
            )
        );
    }
    
    /**
     * Load plugin text domain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'ai-newsletter-pro',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
    
    /**
     * Register widgets
     */
    public function register_widgets() {
        // Register WordPress widgets if widget classes exist
        if (class_exists('AI_Newsletter_Pro_Widget')) {
            register_widget('AI_Newsletter_Pro_Widget');
        }
    }
    
    /**
     * Maybe upgrade database
     */
    public function maybe_upgrade_database() {
        $current_version = get_option('ai_newsletter_pro_db_version', '0.0.0');
        
        if (version_compare($current_version, $this->version, '<')) {
            if (class_exists('AI_Newsletter_Pro_Database')) {
                $database = new AI_Newsletter_Pro_Database();
                $database->maybe_update_database();
            }
        }
    }
    
    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        // Check if this is our admin page
        if (!isset($_GET['page']) || strpos($_GET['page'], 'ai-newsletter-pro') !== 0) {
            return;
        }
        
        // Show setup notice for new installations
        if (!get_option('ai_newsletter_pro_setup_complete')) {
            $this->show_setup_notice();
        }
        
        // Show API key notice if AI features are not configured
        $settings = get_option('ai_newsletter_pro_settings', array());
        if (empty($settings['ai']['openai_api_key'])) {
            $this->show_ai_setup_notice();
        }
    }
    
    /**
     * Show setup notice
     */
    private function show_setup_notice() {
        ?>
        <div class="notice notice-info is-dismissible">
            <h3><?php _e('Welcome to AI Newsletter Pro!', 'ai-newsletter-pro'); ?></h3>
            <p><?php _e('Get started by configuring your newsletter settings and creating your first widget.', 'ai-newsletter-pro'); ?></p>
            <p>
                <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-settings'); ?>" class="button button-primary">
                    <?php _e('Configure Settings', 'ai-newsletter-pro'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-widgets'); ?>" class="button">
                    <?php _e('Create Widget', 'ai-newsletter-pro'); ?>
                </a>
                <button type="button" class="button-link" onclick="dismissSetupNotice()" style="margin-left: 10px;">
                    <?php _e('Dismiss', 'ai-newsletter-pro'); ?>
                </button>
            </p>
        </div>
        <script>
        function dismissSetupNotice() {
            jQuery.post(ajaxurl, {
                action: 'ai_newsletter_dismiss_setup_notice',
                nonce: '<?php echo wp_create_nonce('ai_newsletter_admin_nonce'); ?>'
            });
            jQuery('.notice').fadeOut();
        }
        </script>
        <?php
    }
    
    /**
     * Show AI setup notice
     */
    private function show_ai_setup_notice() {
        ?>
        <div class="notice notice-warning">
            <h4><?php _e('AI Features Available', 'ai-newsletter-pro'); ?></h4>
            <p><?php _e('Add your OpenAI API key to unlock AI-powered content curation and automated newsletter generation.', 'ai-newsletter-pro'); ?></p>
            <p>
                <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-settings#ai'); ?>" class="button">
                    <?php _e('Setup AI Features', 'ai-newsletter-pro'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Get plugin version
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Get plugin name
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }
    
    /**
     * Run the plugin
     */
    public function run() {
        // Initialize database if needed
        if (class_exists('AI_Newsletter_Pro_Database')) {
            $database = new AI_Newsletter_Pro_Database();
            $database->maybe_update_database();
        }
        
        // Initialize email services
        if (class_exists('AI_Newsletter_Pro_Email_Services')) {
            $email_services = new AI_Newsletter_Pro_Email_Services();
            $email_services->schedule_regular_sync();
        }
        
        // Initialize widgets
        if (class_exists('AI_Newsletter_Pro_Widget_Manager')) {
            new AI_Newsletter_Pro_Widget_Manager();
        }
        
        // Initialize shortcodes
        if (class_exists('AI_Newsletter_Pro_Shortcodes')) {
            new AI_Newsletter_Pro_Shortcodes();
        }
        
        // Initialize public functionality
        if (class_exists('AI_Newsletter_Pro_Public')) {
            new AI_Newsletter_Pro_Public();
        }
    }
}

// AJAX handler for dismissing setup notice
add_action('wp_ajax_ai_newsletter_dismiss_setup_notice', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'ai_newsletter_admin_nonce')) {
        wp_die('Security check failed');
    }
    
    update_option('ai_newsletter_pro_setup_complete', true);
    wp_send_json_success();
});