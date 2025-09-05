<?php
/**
 * Popup Modal Widget Template
 * 
 * This template can be overridden by copying it to yourtheme/ai-newsletter-pro/widgets/popup-modal.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extract variables
$widget_id = $args['widget_id'] ?? 'ai-newsletter-popup-' . uniqid();
$settings = $args['settings'] ?? array();

// Default settings
$defaults = array(
    'title' => __('Join our Newsletter', 'ai-newsletter-pro'),
    'subtitle' => __('Get updates delivered to your inbox.', 'ai-newsletter-pro'),
    'button_text' => __('Subscribe', 'ai-newsletter-pro'),
    'placeholder' => __('Enter your email address', 'ai-newsletter-pro'),
    'trigger' => 'time',
    'trigger_value' => 5000,
    'show_icon' => true,
    'icon' => '📧',
    'show_trust_badge' => true,
    'trust_text' => __('🔒 No spam, unsubscribe anytime', 'ai-newsletter-pro'),
    'design_style' => 'modern',
    'color_scheme' => 'default'
);

$settings = wp_parse_args($settings, $defaults);

// Color schemes
$color_schemes = array(
    'default' => array(
        'primary' => '#3b82f6',
        'secondary' => '#1d4ed8',
        'background' => '#ffffff',
        'text' => '#111827'
    ),
    'dark' => array(
        'primary' => '#6366f1',
        'secondary' => '#4f46e5',
        'background' => '#1f2937',
        'text' => '#f9fafb'
    ),
    'green' => array(
        'primary' => '#10b981',
        'secondary' => '#059669',
        'background' => '#ffffff',
        'text' => '#111827'
    ),
    'purple' => array(
        'primary' => '#8b5cf6',
        'secondary' => '#7c3aed',
        'background' => '#ffffff',
        'text' => '#111827'
    )
);

$colors = $color_schemes[$settings['color_scheme']] ?? $color_schemes['default'];
?>

<div id="<?php echo esc_attr($widget_id); ?>" 
     class="ai-newsletter-popup-overlay ai-newsletter-design-<?php echo esc_attr($settings['design_style']); ?>" 
     style="display: none;" 
     data-trigger="<?php echo esc_attr($settings['trigger']); ?>" 
     data-trigger-value="<?php echo esc_attr($settings['trigger_value']); ?>">
     
    <div class="ai-newsletter-popup" style="background-color: <?php echo esc_attr($colors['background']); ?>; color: <?php echo esc_attr($colors['text']); ?>;">
        
        <!-- Close Button -->
        <button class="ai-newsletter-close" 
                onclick="aiNewsletterClosePopup('<?php echo esc_attr($widget_id); ?>')" 
                aria-label="<?php esc_attr_e('Close popup', 'ai-newsletter-pro'); ?>">
            ×
        </button>
        
        <!-- Icon -->
        <?php if ($settings['show_icon'] && !empty($settings['icon'])): ?>
            <div class="ai-newsletter-icon" style="background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?>, <?php echo esc_attr($colors['secondary']); ?>);">
                <?php echo esc_html($settings['icon']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Title -->
        <h3 class="ai-newsletter-title" style="color: <?php echo esc_attr($colors['text']); ?>;">
            <?php echo esc_html($settings['title']); ?>
        </h3>
        
        <!-- Subtitle -->
        <?php if (!empty($settings['subtitle'])): ?>
            <p class="ai-newsletter-subtitle" style="color: <?php echo esc_attr($colors['text']); ?>;">
                <?php echo esc_html($settings['subtitle']); ?>
            </p>
        <?php endif; ?>
        
        <!-- Form -->
        <form class="ai-newsletter-form" data-widget-id="<?php echo esc_attr($widget_id); ?>" novalidate>
            <div class="ai-newsletter-form-group">
                <input type="email" 
                       name="email" 
                       placeholder="<?php echo esc_attr($settings['placeholder']); ?>" 
                       required 
                       aria-label="<?php esc_attr_e('Email address', 'ai-newsletter-pro'); ?>">
                       
                <button type="submit" 
                        style="background: linear-gradient(135deg, <?php echo esc_attr($colors['primary']); ?>, <?php echo esc_attr($colors['secondary']); ?>);">
                    <span><?php echo esc_html($settings['button_text']); ?></span>
                </button>
            </div>
            
            <!-- Hidden fields -->
            <input type="hidden" name="source" value="popup-<?php echo esc_attr($widget_id); ?>">
            <input type="hidden" name="widget_type" value="popup">
        </form>
        
        <!-- Trust Badge -->
        <?php if ($settings['show_trust_badge'] && !empty($settings['trust_text'])): ?>
            <div class="ai-newsletter-trust" style="color: <?php echo esc_attr($colors['text']); ?>;">
                <?php echo esc_html($settings['trust_text']); ?>
            </div>
        <?php endif; ?>
        
        <!-- GDPR Compliance -->
        <?php
        $general_settings = get_option('ai_newsletter_pro_settings', array())['general'] ?? array();
        if ($general_settings['gdpr_compliance'] ?? true):
        ?>
            <div class="ai-newsletter-gdpr" style="color: <?php echo esc_attr($colors['text']); ?>;">
                <small>
                    <?php _e('By subscribing, you agree to our privacy policy and consent to receive updates.', 'ai-newsletter-pro'); ?>
                    <a href="<?php echo esc_url(get_privacy_policy_url()); ?>" target="_blank" style="color: <?php echo esc_attr($colors['primary']); ?>;">
                        <?php _e('Privacy Policy', 'ai-newsletter-pro'); ?>
                    </a>
                </small>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Popup-specific styles that aren't in main CSS */
.ai-newsletter-design-modern .ai-newsletter-popup {
    transform: scale(0.9);
    transition: transform 0.3s ease-out;
}

.ai-newsletter-design-modern.ai-newsletter-popup-overlay[style*="flex"] .ai-newsletter-popup {
    transform: scale(1);
}

.ai-newsletter-design-minimal .ai-newsletter-popup {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

.ai-newsletter-design-bold .ai-newsletter-popup {
    border: 3px solid <?php echo esc_attr($colors['primary']); ?>;
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);
}

.ai-newsletter-gdpr {
    margin-top: 1rem;
    font-size: 0.75rem;
    line-height: 1.4;
    opacity: 0.8;
}

.ai-newsletter-gdpr a {
    text-decoration: underline;
}

.ai-newsletter-gdpr a:hover {
    text-decoration: none;
}

/* Mobile responsiveness */
@media (max-width: 480px) {
    .ai-newsletter-popup {
        margin: 1rem;
        padding: 2rem 1.5rem;
        max-width: calc(100vw - 2rem);
    }
    
    .ai-newsletter-form-group {
        flex-direction: column;
        gap: 1rem;
    }
    
    .ai-newsletter-title {
        font-size: 1.25rem;
    }
}

/* Animation for entrance */
@keyframes ai-newsletter-popup-entrance {
    0% {
        opacity: 0;
        transform: translateY(30px) scale(0.9);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.ai-newsletter-popup-overlay[style*="flex"] .ai-newsletter-popup {
    animation: ai-newsletter-popup-entrance 0.4s ease-out;
}
</style>

<?php
/**
 * Hook for additional popup customizations
 */
do_action('ai_newsletter_popup_template_loaded', $widget_id, $settings);
?>