<?php
/**
 * Shortcodes class for AI Newsletter Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Newsletter_Pro_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init_shortcodes'));
    }
    
    /**
     * Initialize shortcodes
     */
    public function init_shortcodes() {
        add_shortcode('ai_newsletter_form', array($this, 'newsletter_form_shortcode'));
        add_shortcode('ai_newsletter_count', array($this, 'subscriber_count_shortcode'));
        add_shortcode('ai_newsletter_widget', array($this, 'newsletter_widget_shortcode'));
    }
    
    /**
     * Newsletter form shortcode
     * Usage: [ai_newsletter_form style="inline" title="Subscribe" button="Join Now"]
     */
    public function newsletter_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'inline',
            'title' => __('Subscribe to our Newsletter', 'ai-newsletter-pro'),
            'subtitle' => __('Get updates delivered to your inbox', 'ai-newsletter-pro'),
            'button' => __('Subscribe', 'ai-newsletter-pro'),
            'placeholder' => __('Enter your email', 'ai-newsletter-pro'),
            'class' => '',
            'show_privacy' => 'true',
            'redirect' => '',
            'source' => 'shortcode'
        ), $atts, 'ai_newsletter_form');
        
        $widget_id = 'shortcode-' . uniqid();
        $show_privacy = $atts['show_privacy'] === 'true';
        
        ob_start();
        ?>
        <div class="ai-newsletter-shortcode-form ai-newsletter-inline <?php echo esc_attr($atts['class']); ?>" id="<?php echo esc_attr($widget_id); ?>">
            <div class="ai-newsletter-inline-content">
                <?php if (!empty($atts['title'])): ?>
                    <h3 class="ai-newsletter-inline-title"><?php echo esc_html($atts['title']); ?></h3>
                <?php endif; ?>
                
                <?php if (!empty($atts['subtitle'])): ?>
                    <p class="ai-newsletter-inline-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
                <?php endif; ?>
                
                <form class="ai-newsletter-form" data-widget-id="<?php echo esc_attr($widget_id); ?>" data-redirect="<?php echo esc_url($atts['redirect']); ?>">
                    <div class="ai-newsletter-form-group">
                        <input type="email" 
                               name="email" 
                               placeholder="<?php echo esc_attr($atts['placeholder']); ?>" 
                               required>
                        <button type="submit">
                            <span><?php echo esc_html($atts['button']); ?></span>
                        </button>
                    </div>
                    
                    <?php if ($show_privacy): ?>
                        <div class="ai-newsletter-privacy">
                            <?php echo $this->get_privacy_text(); ?>
                        </div>
                    <?php endif; ?>
                    
                    <input type="hidden" name="source" value="<?php echo esc_attr($atts['source']); ?>">
                </form>
            </div>
        </div>
        
        <style>
        .ai-newsletter-shortcode-form {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: #f9fafb;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .ai-newsletter-shortcode-form .ai-newsletter-inline-title {
            margin: 0 0 1rem 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #111827;
        }
        
        .ai-newsletter-shortcode-form .ai-newsletter-inline-subtitle {
            margin: 0 0 1.5rem 0;
            color: #6b7280;
            line-height: 1.6;
        }
        
        .ai-newsletter-shortcode-form .ai-newsletter-form-group {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .ai-newsletter-shortcode-form input[type="email"] {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .ai-newsletter-shortcode-form input[type="email"]:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .ai-newsletter-shortcode-form button {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .ai-newsletter-shortcode-form button:hover {
            transform: translateY(-1px);
        }
        
        .ai-newsletter-privacy {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }
        
        .ai-newsletter-message {
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .ai-newsletter-message.success {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #10b981;
        }
        
        .ai-newsletter-message.error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #ef4444;
        }
        
        @media (max-width: 640px) {
            .ai-newsletter-shortcode-form .ai-newsletter-form-group {
                flex-direction: column;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Subscriber count shortcode
     * Usage: [ai_newsletter_count format="number" prefix="Join" suffix="subscribers"]
     */
    public function subscriber_count_shortcode($atts) {
        $atts = shortcode_atts(array(
            'format' => 'number',
            'prefix' => '',
            'suffix' => '',
            'status' => 'subscribed',
            'class' => ''
        ), $atts, 'ai_newsletter_count');
        
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE status = %s",
            $atts['status']
        ));
        
        $count = intval($count);
        
        // Format the number
        switch ($atts['format']) {
            case 'short':
                if ($count >= 1000000) {
                    $formatted = round($count / 1000000, 1) . 'M';
                } elseif ($count >= 1000) {
                    $formatted = round($count / 1000, 1) . 'K';
                } else {
                    $formatted = number_format($count);
                }
                break;
            case 'words':
                $formatted = $this->number_to_words($count);
                break;
            default:
                $formatted = number_format($count);
        }
        
        $output = '';
        if (!empty($atts['prefix'])) {
            $output .= esc_html($atts['prefix']) . ' ';
        }
        
        $output .= '<span class="ai-newsletter-count ' . esc_attr($atts['class']) . '">' . $formatted . '</span>';
        
        if (!empty($atts['suffix'])) {
            $output .= ' ' . esc_html($atts['suffix']);
        }
        
        return $output;
    }
    
    /**
     * Newsletter widget shortcode
     * Usage: [ai_newsletter_widget id="123"] or [ai_newsletter_widget type="popup"]
     */
    public function newsletter_widget_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'type' => '',
            'title' => '',
            'subtitle' => '',
            'button' => ''
        ), $atts, 'ai_newsletter_widget');
        
        if (!empty($atts['id'])) {
            // Load specific widget by ID
            return $this->render_widget_by_id($atts['id']);
        } elseif (!empty($atts['type'])) {
            // Create inline widget of specified type
            return $this->render_widget_by_type($atts['type'], $atts);
        }
        
        return '<p class="ai-newsletter-error">' . __('Widget ID or type required', 'ai-newsletter-pro') . '</p>';
    }
    
    /**
     * Render widget by ID
     */
    private function render_widget_by_id($widget_id) {
        global $wpdb;
        
        $widget = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . AI_NEWSLETTER_PRO_WIDGETS_TABLE . " WHERE id = %d AND active = 1",
            $widget_id
        ));
        
        if (!$widget) {
            return '<p class="ai-newsletter-error">' . __('Widget not found or inactive', 'ai-newsletter-pro') . '</p>';
        }
        
        if (class_exists('AI_Newsletter_Pro_Widget_Manager')) {
            $widget_manager = new AI_Newsletter_Pro_Widget_Manager();
            return $widget_manager->get_widget_html($widget);
        }
        
        return '<p class="ai-newsletter-error">' . __('Widget manager not available', 'ai-newsletter-pro') . '</p>';
    }
    
    /**
     * Render widget by type
     */
    private function render_widget_by_type($type, $atts) {
        $widget_id = 'shortcode-widget-' . uniqid();
        
        $defaults = array(
            'title' => __('Subscribe to our Newsletter', 'ai-newsletter-pro'),
            'subtitle' => __('Get the latest updates delivered to your inbox', 'ai-newsletter-pro'),
            'button' => __('Subscribe Now', 'ai-newsletter-pro')
        );
        
        $settings = array_merge($defaults, array_filter($atts));
        
        ob_start();
        
        switch ($type) {
            case 'popup':
                $this->render_popup_widget($widget_id, $settings);
                break;
            case 'floating':
                $this->render_floating_widget($widget_id, $settings);
                break;
            case 'banner':
                $this->render_banner_widget($widget_id, $settings);
                break;
            default:
                $this->render_inline_widget($widget_id, $settings);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render popup widget (for shortcode use)
     */
    private function render_popup_widget($widget_id, $settings) {
        ?>
        <div class="ai-newsletter-shortcode-popup" id="<?php echo esc_attr($widget_id); ?>">
            <div class="ai-newsletter-popup">
                <div class="ai-newsletter-icon">📧</div>
                <h3 class="ai-newsletter-title"><?php echo esc_html($settings['title']); ?></h3>
                <p class="ai-newsletter-subtitle"><?php echo esc_html($settings['subtitle']); ?></p>
                <form class="ai-newsletter-form" data-widget-id="<?php echo esc_attr($widget_id); ?>">
                    <div class="ai-newsletter-form-group">
                        <input type="email" name="email" placeholder="<?php _e('Enter your email address', 'ai-newsletter-pro'); ?>" required>
                        <button type="submit"><?php echo esc_html($settings['button']); ?></button>
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
        ?>
        <div class="ai-newsletter-shortcode-floating" id="<?php echo esc_attr($widget_id); ?>">
            <div class="ai-newsletter-floating-header">
                <h4 class="ai-newsletter-floating-title"><?php echo esc_html($settings['title']); ?></h4>
            </div>
            <p class="ai-newsletter-floating-subtitle"><?php echo esc_html($settings['subtitle']); ?></p>
            <form class="ai-newsletter-form" data-widget-id="<?php echo esc_attr($widget_id); ?>">
                <input type="email" name="email" placeholder="<?php _e('Email', 'ai-newsletter-pro'); ?>" required>
                <button type="submit"><?php echo esc_html($settings['button']); ?></button>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render banner widget
     */
    private function render_banner_widget($widget_id, $settings) {
        ?>
        <div class="ai-newsletter-shortcode-banner" id="<?php echo esc_attr($widget_id); ?>">
            <div class="ai-newsletter-banner-content">
                <span class="ai-newsletter-banner-text"><?php echo esc_html($settings['title']); ?></span>
                <form class="ai-newsletter-form ai-newsletter-banner-form" data-widget-id="<?php echo esc_attr($widget_id); ?>">
                    <input type="email" name="email" placeholder="<?php _e('Your email', 'ai-newsletter-pro'); ?>" required>
                    <button type="submit"><?php echo esc_html($settings['button']); ?></button>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render inline widget
     */
    private function render_inline_widget($widget_id, $settings) {
        ?>
        <div class="ai-newsletter-shortcode-inline" id="<?php echo esc_attr($widget_id); ?>">
            <div class="ai-newsletter-inline-content">
                <h3 class="ai-newsletter-inline-title"><?php echo esc_html($settings['title']); ?></h3>
                <p class="ai-newsletter-inline-subtitle"><?php echo esc_html($settings['subtitle']); ?></p>
                <form class="ai-newsletter-form" data-widget-id="<?php echo esc_attr($widget_id); ?>">
                    <div class="ai-newsletter-form-group">
                        <input type="email" name="email" placeholder="<?php _e('Your email address', 'ai-newsletter-pro'); ?>" required>
                        <button type="submit"><?php echo esc_html($settings['button']); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get privacy compliance text
     */
    private function get_privacy_text() {
        $settings = get_option('ai_newsletter_pro_settings', array());
        $gdpr_enabled = $settings['general']['gdpr_compliance'] ?? true;
        
        if ($gdpr_enabled) {
            return __('By subscribing, you agree to our privacy policy and consent to receive updates from us.', 'ai-newsletter-pro');
        }
        
        return __('We respect your privacy. Unsubscribe at any time.', 'ai-newsletter-pro');
    }
    
    /**
     * Convert number to words (basic implementation)
     */
    private function number_to_words($number) {
        if ($number == 0) return 'zero';
        
        $ones = array(
            '', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
            'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen',
            'seventeen', 'eighteen', 'nineteen'
        );
        
        $tens = array(
            '', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'
        );
        
        if ($number < 20) {
            return $ones[$number];
        } elseif ($number < 100) {
            return $tens[intval($number / 10)] . ($number % 10 != 0 ? ' ' . $ones[$number % 10] : '');
        } elseif ($number < 1000) {
            return $ones[intval($number / 100)] . ' hundred' . ($number % 100 != 0 ? ' ' . $this->number_to_words($number % 100) : '');
        } elseif ($number < 1000000) {
            return $this->number_to_words(intval($number / 1000)) . ' thousand' . ($number % 1000 != 0 ? ' ' . $this->number_to_words($number % 1000) : '');
        }
        
        return number_format($number); // Fallback for very large numbers
    }
}

// Initialize shortcodes
new AI_Newsletter_Pro_Shortcodes();