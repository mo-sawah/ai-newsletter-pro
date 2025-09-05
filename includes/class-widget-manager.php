<?php
/**
 * Widget Manager class for AI Newsletter Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Newsletter_Pro_Widget_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_footer', array($this, 'render_widgets'));
        add_filter('the_content', array($this, 'add_inline_widget'));
        add_action('wp_ajax_ai_newsletter_widget_impression', array($this, 'track_impression'));
        add_action('wp_ajax_nopriv_ai_newsletter_widget_impression', array($this, 'track_impression'));
    }
    
    /**
     * Render widgets in footer
     */
    public function render_widgets() {
        if (is_admin() || wp_doing_ajax()) {
            return;
        }
        
        $widgets = $this->get_active_widgets();
        
        foreach ($widgets as $widget) {
            $this->render_widget($widget);
        }
    }
    
    /**
     * Add inline widget to content
     */
    public function add_inline_widget($content) {
        if (!is_single() || is_admin()) {
            return $content;
        }
        
        $inline_widgets = $this->get_active_widgets('inline');
        
        foreach ($inline_widgets as $widget) {
            $widget_html = $this->get_widget_html($widget);
            
            switch ($widget->position) {
                case 'before_content':
                    $content = $widget_html . $content;
                    break;
                case 'after_content':
                    $content = $content . $widget_html;
                    break;
                case 'middle_content':
                    // Insert in the middle of content
                    $paragraphs = explode('</p>', $content);
                    $middle = floor(count($paragraphs) / 2);
                    array_splice($paragraphs, $middle, 0, $widget_html);
                    $content = implode('</p>', $paragraphs);
                    break;
            }
        }
        
        return $content;
    }
    
    /**
     * Get active widgets
     */
    public function get_active_widgets($type = null) {
        global $wpdb;
        
        $sql = "SELECT * FROM " . AI_NEWSLETTER_PRO_WIDGETS_TABLE . " WHERE active = 1";
        
        if ($type) {
            $sql .= $wpdb->prepare(" AND type = %s", $type);
        }
        
        $sql .= " ORDER BY id ASC";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Render individual widget
     */
    public function render_widget($widget) {
        if ($widget->type === 'inline') {
            return; // Inline widgets are handled separately
        }
        
        echo $this->get_widget_html($widget);
    }
    
    /**
     * Get widget HTML
     */
    public function get_widget_html($widget) {
        $settings = json_decode($widget->settings, true);
        $widget_id = 'ai-newsletter-widget-' . $widget->id;
        
        ob_start();
        
        switch ($widget->type) {
            case 'popup':
                $this->render_popup_widget($widget_id, $settings);
                break;
            case 'floating':
                $this->render_floating_widget($widget_id, $settings);
                break;
            case 'banner':
                $this->render_banner_widget($widget_id, $settings);
                break;
            case 'inline':
                $this->render_inline_widget($widget_id, $settings);
                break;
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render popup widget
     */
    private function render_popup_widget($widget_id, $settings) {
        $title = $settings['title'] ?? 'Join our Newsletter';
        $subtitle = $settings['subtitle'] ?? 'Get updates delivered to your inbox.';
        $button_text = $settings['button_text'] ?? 'Subscribe';
        $trigger = $settings['trigger'] ?? 'time';
        $trigger_value = $settings['trigger_value'] ?? 5000;
        
        ?>
        <div id="<?php echo esc_attr($widget_id); ?>" class="ai-newsletter-popup-overlay" style="display: none;" 
             data-trigger="<?php echo esc_attr($trigger); ?>" 
             data-trigger-value="<?php echo esc_attr($trigger_value); ?>">
            <div class="ai-newsletter-popup">
                <button class="ai-newsletter-close" onclick="aiNewsletterClosePopup('<?php echo esc_attr($widget_id); ?>')">×</button>
                <div class="ai-newsletter-icon">📧</div>
                <h3 class="ai-newsletter-title"><?php echo esc_html($title); ?></h3>
                <p class="ai-newsletter-subtitle"><?php echo esc_html($subtitle); ?></p>
                <form class="ai-newsletter-form" data-widget-id="<?php echo esc_attr($widget_id); ?>">
                    <div class="ai-newsletter-form-group">
                        <input type="email" name="email" placeholder="<?php _e('Enter your email address', 'ai-newsletter-pro'); ?>" required>
                        <button type="submit"><?php echo esc_html($button_text); ?></button>
                    </div>
                </form>
                <div class="ai-newsletter-trust"><?php _e('🔒 No spam, unsubscribe anytime', 'ai-newsletter-pro'); ?></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render floating widget
     */
    private function render_floating_widget($widget_id, $settings) {
        $title = $settings['title'] ?? 'Newsletter';
        $subtitle = $settings['subtitle'] ?? 'Weekly digest of our best content';
        $button_text = $settings['button_text'] ?? 'Subscribe';
        $position = $settings['position'] ?? 'bottom-right';
        
        ?>
        <div id="<?php echo esc_attr($widget_id); ?>" class="ai-newsletter-floating ai-newsletter-floating-<?php echo esc_attr($position); ?>">
            <div class="ai-newsletter-floating-header">
                <h4 class="ai-newsletter-floating-title"><?php echo esc_html($title); ?></h4>
                <button class="ai-newsletter-close" onclick="aiNewsletterCloseFloating('<?php echo esc_attr($widget_id); ?>')">×</button>
            </div>
            <p class="ai-newsletter-floating-subtitle"><?php echo esc_html($subtitle); ?></p>
            <form class="ai-newsletter-form" data-widget-id="<?php echo esc_attr($widget_id); ?>">
                <input type="email" name="email" placeholder="<?php _e('Email', 'ai-newsletter-pro'); ?>" required>
                <button type="submit"><?php echo esc_html($button_text); ?></button>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render banner widget
     */
    private function render_banner_widget($widget_id, $settings) {
        $text = $settings['text'] ?? 'Subscribe to our newsletter for weekly updates';
        $button_text = $settings['button_text'] ?? 'Subscribe';
        $position = $settings['position'] ?? 'top';
        
        ?>
        <div id="<?php echo esc_attr($widget_id); ?>" class="ai-newsletter-banner ai-newsletter-banner-<?php echo esc_attr($position); ?>">
            <div class="ai-newsletter-banner-content">
                <span class="ai-newsletter-banner-text"><?php echo esc_html($text); ?></span>
                <form class="ai-newsletter-form ai-newsletter-banner-form" data-widget-id="<?php echo esc_attr($widget_id); ?>">
                    <input type="email" name="email" placeholder="<?php _e('Your email', 'ai-newsletter-pro'); ?>" required>
                    <button type="submit"><?php echo esc_html($button_text); ?></button>
                </form>
                <button class="ai-newsletter-close" onclick="aiNewsletterCloseBanner('<?php echo esc_attr($widget_id); ?>')">×</button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render inline widget
     */
    private function render_inline_widget($widget_id, $settings) {
        $title = $settings['title'] ?? 'Love This Content?';
        $subtitle = $settings['subtitle'] ?? 'Subscribe for weekly insights and exclusive content.';
        $button_text = $settings['button_text'] ?? 'Join Community';
        $style = $settings['style'] ?? 'gradient';
        
        ?>
        <div id="<?php echo esc_attr($widget_id); ?>" class="ai-newsletter-inline ai-newsletter-inline-<?php echo esc_attr($style); ?>">
            <div class="ai-newsletter-inline-content">
                <h3 class="ai-newsletter-inline-title"><?php echo esc_html($title); ?></h3>
                <p class="ai-newsletter-inline-subtitle"><?php echo esc_html($subtitle); ?></p>
                <form class="ai-newsletter-form" data-widget-id="<?php echo esc_attr($widget_id); ?>">
                    <div class="ai-newsletter-form-group">
                        <input type="email" name="email" placeholder="<?php _e('Your email address', 'ai-newsletter-pro'); ?>" required>
                        <button type="submit"><?php echo esc_html($button_text); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Track widget impression
     */
    public function track_impression() {
        if (!wp_verify_nonce($_POST['nonce'], 'ai_newsletter_pro_nonce')) {
            wp_die('Security check failed');
        }
        
        $widget_id = intval($_POST['widget_id']);
        
        if ($widget_id) {
            global $wpdb;
            
            // Update widget impression count
            $wpdb->query($wpdb->prepare(
                "UPDATE " . AI_NEWSLETTER_PRO_WIDGETS_TABLE . " 
                 SET impressions = impressions + 1 
                 WHERE id = %d",
                $widget_id
            ));
            
            // Log analytics event
            $wpdb->insert(
                AI_NEWSLETTER_PRO_ANALYTICS_TABLE,
                array(
                    'event_type' => 'widget_view',
                    'widget_id' => $widget_id,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'ip_address' => $this->get_client_ip(),
                    'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
                    'created_at' => current_time('mysql')
                )
            );
        }
        
        wp_send_json_success();
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Create new widget
     */
    public function create_widget($type, $settings, $position = '', $active = 1) {
        global $wpdb;
        
        return $wpdb->insert(
            AI_NEWSLETTER_PRO_WIDGETS_TABLE,
            array(
                'type' => $type,
                'settings' => json_encode($settings),
                'position' => $position,
                'active' => $active,
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Update widget
     */
    public function update_widget($widget_id, $settings = null, $position = null, $active = null) {
        global $wpdb;
        
        $update_data = array('updated_at' => current_time('mysql'));
        
        if ($settings !== null) {
            $update_data['settings'] = json_encode($settings);
        }
        
        if ($position !== null) {
            $update_data['position'] = $position;
        }
        
        if ($active !== null) {
            $update_data['active'] = $active;
        }
        
        return $wpdb->update(
            AI_NEWSLETTER_PRO_WIDGETS_TABLE,
            $update_data,
            array('id' => $widget_id)
        );
    }
    
    /**
     * Delete widget
     */
    public function delete_widget($widget_id) {
        global $wpdb;
        
        return $wpdb->delete(
            AI_NEWSLETTER_PRO_WIDGETS_TABLE,
            array('id' => $widget_id)
        );
    }
    
    /**
     * Get widget by ID
     */
    public function get_widget($widget_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . AI_NEWSLETTER_PRO_WIDGETS_TABLE . " WHERE id = %d",
            $widget_id
        ));
    }
}