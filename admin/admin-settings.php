<?php
/**
 * Admin Settings Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'ai_newsletter_pro_settings')) {
    $settings = array(
        'general' => array(
            'double_optin' => isset($_POST['double_optin']),
            'gdpr_compliance' => isset($_POST['gdpr_compliance']),
            'from_name' => sanitize_text_field($_POST['from_name']),
            'from_email' => sanitize_email($_POST['from_email']),
            'reply_to' => sanitize_email($_POST['reply_to'])
        ),
        'widgets' => array(
            'popup_enabled' => isset($_POST['popup_enabled']),
            'popup_delay' => intval($_POST['popup_delay']),
            'popup_scroll_trigger' => intval($_POST['popup_scroll_trigger']),
            'floating_enabled' => isset($_POST['floating_enabled']),
            'banner_enabled' => isset($_POST['banner_enabled'])
        ),
        'ai' => array(
            'openai_api_key' => sanitize_text_field($_POST['openai_api_key']),
            'content_selection_criteria' => sanitize_text_field($_POST['content_selection_criteria']),
            'newsletter_frequency' => sanitize_text_field($_POST['newsletter_frequency']),
            'max_articles' => intval($_POST['max_articles'])
        )
    );
    
    update_option('ai_newsletter_pro_settings', $settings);
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'ai-newsletter-pro') . '</p></div>';
}

// Get current settings
$settings = get_option('ai_newsletter_pro_settings', array());
$general = $settings['general'] ?? array();
$widgets = $settings['widgets'] ?? array();
$ai = $settings['ai'] ?? array();
?>

<style>
.ai-newsletter-settings {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.ai-newsletter-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    margin: -20px -20px 2rem -2px;
    border-radius: 0 0 1rem 1rem;
}

.ai-newsletter-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2.5rem;
    font-weight: 700;
}

.ai-newsletter-settings-tabs {
    background: white;
    border-radius: 1rem 1rem 0 0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    display: flex;
    overflow: hidden;
    margin-bottom: 0;
}

.ai-newsletter-tab {
    flex: 1;
    padding: 1rem 1.5rem;
    background: #f9fafb;
    border: none;
    cursor: pointer;
    font-weight: 500;
    color: #6b7280;
    transition: all 0.2s;
    border-right: 1px solid #e5e7eb;
}

.ai-newsletter-tab:last-child {
    border-right: none;
}

.ai-newsletter-tab.active {
    background: white;
    color: #111827;
    box-shadow: inset 0 -2px 0 #667eea;
}

.ai-newsletter-tab:hover:not(.active) {
    background: #f3f4f6;
    color: #374151;
}

.ai-newsletter-settings-content {
    background: white;
    border-radius: 0 0 1rem 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    border-top: none;
    padding: 2rem;
}

.ai-newsletter-tab-panel {
    display: none;
}

.ai-newsletter-tab-panel.active {
    display: block;
}

.ai-newsletter-form-section {
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #e5e7eb;
}

.ai-newsletter-form-section:last-child {
    border-bottom: none;
}

.ai-newsletter-section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.ai-newsletter-section-description {
    color: #6b7280;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.ai-newsletter-form-group {
    margin-bottom: 1.5rem;
}

.ai-newsletter-form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.ai-newsletter-form-group input,
.ai-newsletter-form-group select,
.ai-newsletter-form-group textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 0.9rem;
    transition: all 0.2s;
    background: white;
}

.ai-newsletter-form-group input:focus,
.ai-newsletter-form-group select:focus,
.ai-newsletter-form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.ai-newsletter-checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
    margin-bottom: 1rem;
}

.ai-newsletter-checkbox {
    width: 18px;
    height: 18px;
    accent-color: #667eea;
}

.ai-newsletter-checkbox-label {
    font-weight: 500;
    color: #374151;
    cursor: pointer;
}

.ai-newsletter-checkbox-description {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.ai-newsletter-save-section {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e5e7eb;
    text-align: center;
}

.ai-newsletter-save-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 2rem;
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.ai-newsletter-save-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.ai-newsletter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.ai-newsletter-alert {
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.ai-newsletter-alert.info {
    background: #eff6ff;
    border: 1px solid #3b82f6;
    color: #1e40af;
}

.ai-newsletter-alert.warning {
    background: #fffbeb;
    border: 1px solid #f59e0b;
    color: #92400e;
}

@media (max-width: 768px) {
    .ai-newsletter-settings-tabs {
        flex-direction: column;
    }
    
    .ai-newsletter-tab {
        border-right: none;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .ai-newsletter-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="ai-newsletter-settings">
    <div class="ai-newsletter-header">
        <h1><?php _e('Plugin Settings', 'ai-newsletter-pro'); ?></h1>
        <p><?php _e('Configure general settings, widget behavior, and AI features', 'ai-newsletter-pro'); ?></p>
    </div>

    <!-- Settings Tabs -->
    <div class="ai-newsletter-settings-tabs">
        <button class="ai-newsletter-tab active" onclick="switchTab('general')">
            <?php _e('🔧 General Settings', 'ai-newsletter-pro'); ?>
        </button>
        <button class="ai-newsletter-tab" onclick="switchTab('widgets')">
            <?php _e('🎨 Widget Settings', 'ai-newsletter-pro'); ?>
        </button>
        <button class="ai-newsletter-tab" onclick="switchTab('ai')">
            <?php _e('🤖 AI Configuration', 'ai-newsletter-pro'); ?>
        </button>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('ai_newsletter_pro_settings'); ?>
        
        <div class="ai-newsletter-settings-content">
            
            <!-- General Settings Tab -->
            <div id="general-tab" class="ai-newsletter-tab-panel active">
                <div class="ai-newsletter-form-section">
                    <h3 class="ai-newsletter-section-title">
                        <span>📧</span>
                        <?php _e('Email Settings', 'ai-newsletter-pro'); ?>
                    </h3>
                    <p class="ai-newsletter-section-description">
                        <?php _e('Configure sender information and email preferences for your newsletters.', 'ai-newsletter-pro'); ?>
                    </p>
                    
                    <div class="ai-newsletter-grid">
                        <div class="ai-newsletter-form-group">
                            <label for="from_name"><?php _e('From Name', 'ai-newsletter-pro'); ?></label>
                            <input type="text" id="from_name" name="from_name" 
                                   value="<?php echo esc_attr($general['from_name'] ?? get_bloginfo('name')); ?>"
                                   placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                        </div>
                        
                        <div class="ai-newsletter-form-group">
                            <label for="from_email"><?php _e('From Email', 'ai-newsletter-pro'); ?></label>
                            <input type="email" id="from_email" name="from_email" 
                                   value="<?php echo esc_attr($general['from_email'] ?? get_option('admin_email')); ?>"
                                   placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                        </div>
                        
                        <div class="ai-newsletter-form-group">
                            <label for="reply_to"><?php _e('Reply-To Email', 'ai-newsletter-pro'); ?></label>
                            <input type="email" id="reply_to" name="reply_to" 
                                   value="<?php echo esc_attr($general['reply_to'] ?? get_option('admin_email')); ?>"
                                   placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                        </div>
                    </div>
                </div>

                <div class="ai-newsletter-form-section">
                    <h3 class="ai-newsletter-section-title">
                        <span>🔒</span>
                        <?php _e('Subscription Settings', 'ai-newsletter-pro'); ?>
                    </h3>
                    <p class="ai-newsletter-section-description">
                        <?php _e('Control how new subscribers are handled and ensure compliance with regulations.', 'ai-newsletter-pro'); ?>
                    </p>
                    
                    <div class="ai-newsletter-checkbox-group">
                        <input type="checkbox" id="double_optin" name="double_optin" class="ai-newsletter-checkbox"
                               <?php checked($general['double_optin'] ?? false); ?>>
                        <label for="double_optin" class="ai-newsletter-checkbox-label">
                            <?php _e('Enable Double Opt-in', 'ai-newsletter-pro'); ?>
                            <div class="ai-newsletter-checkbox-description">
                                <?php _e('Require email confirmation before adding subscribers to your list', 'ai-newsletter-pro'); ?>
                            </div>
                        </label>
                    </div>
                    
                    <div class="ai-newsletter-checkbox-group">
                        <input type="checkbox" id="gdpr_compliance" name="gdpr_compliance" class="ai-newsletter-checkbox"
                               <?php checked($general['gdpr_compliance'] ?? true); ?>>
                        <label for="gdpr_compliance" class="ai-newsletter-checkbox-label">
                            <?php _e('GDPR Compliance Mode', 'ai-newsletter-pro'); ?>
                            <div class="ai-newsletter-checkbox-description">
                                <?php _e('Add privacy disclaimers and enable data export/deletion features', 'ai-newsletter-pro'); ?>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Widget Settings Tab -->
            <div id="widgets-tab" class="ai-newsletter-tab-panel">
                <div class="ai-newsletter-form-section">
                    <h3 class="ai-newsletter-section-title">
                        <span>🎯</span>
                        <?php _e('Popup Settings', 'ai-newsletter-pro'); ?>
                    </h3>
                    <p class="ai-newsletter-section-description">
                        <?php _e('Configure default behavior for popup newsletter forms.', 'ai-newsletter-pro'); ?>
                    </p>
                    
                    <div class="ai-newsletter-checkbox-group">
                        <input type="checkbox" id="popup_enabled" name="popup_enabled" class="ai-newsletter-checkbox"
                               <?php checked($widgets['popup_enabled'] ?? true); ?>>
                        <label for="popup_enabled" class="ai-newsletter-checkbox-label">
                            <?php _e('Enable Popup Widgets', 'ai-newsletter-pro'); ?>
                            <div class="ai-newsletter-checkbox-description">
                                <?php _e('Allow popup newsletter forms to be displayed on your site', 'ai-newsletter-pro'); ?>
                            </div>
                        </label>
                    </div>
                    
                    <div class="ai-newsletter-grid">
                        <div class="ai-newsletter-form-group">
                            <label for="popup_delay"><?php _e('Default Popup Delay (milliseconds)', 'ai-newsletter-pro'); ?></label>
                            <input type="number" id="popup_delay" name="popup_delay" 
                                   value="<?php echo esc_attr($widgets['popup_delay'] ?? 5000); ?>"
                                   min="0" step="1000">
                        </div>
                        
                        <div class="ai-newsletter-form-group">
                            <label for="popup_scroll_trigger"><?php _e('Scroll Trigger (%)', 'ai-newsletter-pro'); ?></label>
                            <input type="number" id="popup_scroll_trigger" name="popup_scroll_trigger" 
                                   value="<?php echo esc_attr($widgets['popup_scroll_trigger'] ?? 50); ?>"
                                   min="0" max="100">
                        </div>
                    </div>
                </div>

                <div class="ai-newsletter-form-section">
                    <h3 class="ai-newsletter-section-title">
                        <span>🎈</span>
                        <?php _e('Other Widget Types', 'ai-newsletter-pro'); ?>
                    </h3>
                    <p class="ai-newsletter-section-description">
                        <?php _e('Enable or disable different types of newsletter widgets.', 'ai-newsletter-pro'); ?>
                    </p>
                    
                    <div class="ai-newsletter-checkbox-group">
                        <input type="checkbox" id="floating_enabled" name="floating_enabled" class="ai-newsletter-checkbox"
                               <?php checked($widgets['floating_enabled'] ?? false); ?>>
                        <label for="floating_enabled" class="ai-newsletter-checkbox-label">
                            <?php _e('Enable Floating Widgets', 'ai-newsletter-pro'); ?>
                            <div class="ai-newsletter-checkbox-description">
                                <?php _e('Allow floating newsletter forms that stick to screen corners', 'ai-newsletter-pro'); ?>
                            </div>
                        </label>
                    </div>
                    
                    <div class="ai-newsletter-checkbox-group">
                        <input type="checkbox" id="banner_enabled" name="banner_enabled" class="ai-newsletter-checkbox"
                               <?php checked($widgets['banner_enabled'] ?? false); ?>>
                        <label for="banner_enabled" class="ai-newsletter-checkbox-label">
                            <?php _e('Enable Banner Widgets', 'ai-newsletter-pro'); ?>
                            <div class="ai-newsletter-checkbox-description">
                                <?php _e('Allow banner newsletter forms at the top or bottom of pages', 'ai-newsletter-pro'); ?>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- AI Configuration Tab -->
            <div id="ai-tab" class="ai-newsletter-tab-panel">
                <div class="ai-newsletter-alert info">
                    <strong><?php _e('🤖 AI Features', 'ai-newsletter-pro'); ?></strong><br>
                    <?php _e('AI features require an OpenAI API key. These features help automatically curate content and generate newsletters from your blog posts.', 'ai-newsletter-pro'); ?>
                </div>
                
                <div class="ai-newsletter-form-section">
                    <h3 class="ai-newsletter-section-title">
                        <span>🔑</span>
                        <?php _e('API Configuration', 'ai-newsletter-pro'); ?>
                    </h3>
                    <p class="ai-newsletter-section-description">
                        <?php _e('Configure your OpenAI API key to enable AI-powered content curation and newsletter generation.', 'ai-newsletter-pro'); ?>
                    </p>
                    
                    <div class="ai-newsletter-form-group">
                        <label for="openai_api_key"><?php _e('OpenAI API Key', 'ai-newsletter-pro'); ?></label>
                        <input type="password" id="openai_api_key" name="openai_api_key" 
                               value="<?php echo esc_attr($ai['openai_api_key'] ?? ''); ?>"
                               placeholder="sk-...">
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: #6b7280;">
                            <?php printf(__('Get your API key from %s', 'ai-newsletter-pro'), '<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>'); ?>
                        </p>
                    </div>
                </div>

                <div class="ai-newsletter-form-section">
                    <h3 class="ai-newsletter-section-title">
                        <span>🎯</span>
                        <?php _e('Content Curation Settings', 'ai-newsletter-pro'); ?>
                    </h3>
                    <p class="ai-newsletter-section-description">
                        <?php _e('Configure how AI selects and curates content for your automated newsletters.', 'ai-newsletter-pro'); ?>
                    </p>
                    
                    <div class="ai-newsletter-grid">
                        <div class="ai-newsletter-form-group">
                            <label for="content_selection_criteria"><?php _e('Content Selection Criteria', 'ai-newsletter-pro'); ?></label>
                            <select id="content_selection_criteria" name="content_selection_criteria">
                                <option value="engagement" <?php selected($ai['content_selection_criteria'] ?? 'engagement', 'engagement'); ?>>
                                    <?php _e('High Engagement Posts', 'ai-newsletter-pro'); ?>
                                </option>
                                <option value="recent" <?php selected($ai['content_selection_criteria'] ?? 'engagement', 'recent'); ?>>
                                    <?php _e('Most Recent Posts', 'ai-newsletter-pro'); ?>
                                </option>
                                <option value="quality" <?php selected($ai['content_selection_criteria'] ?? 'engagement', 'quality'); ?>>
                                    <?php _e('AI-Assessed Quality', 'ai-newsletter-pro'); ?>
                                </option>
                                <option value="mixed" <?php selected($ai['content_selection_criteria'] ?? 'engagement', 'mixed'); ?>>
                                    <?php _e('Mixed Strategy', 'ai-newsletter-pro'); ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="ai-newsletter-form-group">
                            <label for="newsletter_frequency"><?php _e('Auto-Newsletter Frequency', 'ai-newsletter-pro'); ?></label>
                            <select id="newsletter_frequency" name="newsletter_frequency">
                                <option value="disabled" <?php selected($ai['newsletter_frequency'] ?? 'weekly', 'disabled'); ?>>
                                    <?php _e('Disabled', 'ai-newsletter-pro'); ?>
                                </option>
                                <option value="daily" <?php selected($ai['newsletter_frequency'] ?? 'weekly', 'daily'); ?>>
                                    <?php _e('Daily', 'ai-newsletter-pro'); ?>
                                </option>
                                <option value="weekly" <?php selected($ai['newsletter_frequency'] ?? 'weekly', 'weekly'); ?>>
                                    <?php _e('Weekly', 'ai-newsletter-pro'); ?>
                                </option>
                                <option value="monthly" <?php selected($ai['newsletter_frequency'] ?? 'weekly', 'monthly'); ?>>
                                    <?php _e('Monthly', 'ai-newsletter-pro'); ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="ai-newsletter-form-group">
                            <label for="max_articles"><?php _e('Max Articles per Newsletter', 'ai-newsletter-pro'); ?></label>
                            <input type="number" id="max_articles" name="max_articles" 
                                   value="<?php echo esc_attr($ai['max_articles'] ?? 5); ?>"
                                   min="1" max="20">
                        </div>
                    </div>
                </div>

                <?php if (empty($ai['openai_api_key'])): ?>
                    <div class="ai-newsletter-alert warning">
                        <strong><?php _e('⚠️ API Key Required', 'ai-newsletter-pro'); ?></strong><br>
                        <?php _e('Add your OpenAI API key above to enable AI features. Without an API key, newsletters must be created manually.', 'ai-newsletter-pro'); ?>
                    </div>
                <?php else: ?>
                    <div class="ai-newsletter-alert info">
                        <strong><?php _e('✅ AI Features Enabled', 'ai-newsletter-pro'); ?></strong><br>
                        <?php _e('Your AI assistant is ready to help curate content and generate newsletters automatically.', 'ai-newsletter-pro'); ?>
                    </div>
                <?php endif; ?>
                
                <div class="ai-newsletter-form-section">
                    <h3 class="ai-newsletter-section-title">
                        <span>🎨</span>
                        <?php _e('AI Writing Style', 'ai-newsletter-pro'); ?>
                    </h3>
                    <p class="ai-newsletter-section-description">
                        <?php _e('Configure the tone and style for AI-generated newsletter content.', 'ai-newsletter-pro'); ?>
                    </p>
                    
                    <div class="ai-newsletter-grid">
                        <div class="ai-newsletter-form-group">
                            <label for="ai_tone"><?php _e('Writing Tone', 'ai-newsletter-pro'); ?></label>
                            <select id="ai_tone" name="ai_tone">
                                <option value="professional" <?php selected($ai['ai_tone'] ?? 'professional', 'professional'); ?>>
                                    <?php _e('Professional', 'ai-newsletter-pro'); ?>
                                </option>
                                <option value="friendly" <?php selected($ai['ai_tone'] ?? 'professional', 'friendly'); ?>>
                                    <?php _e('Friendly', 'ai-newsletter-pro'); ?>
                                </option>
                                <option value="casual" <?php selected($ai['ai_tone'] ?? 'professional', 'casual'); ?>>
                                    <?php _e('Casual', 'ai-newsletter-pro'); ?>
                                </option>
                                <option value="enthusiastic" <?php selected($ai['ai_tone'] ?? 'professional', 'enthusiastic'); ?>>
                                    <?php _e('Enthusiastic', 'ai-newsletter-pro'); ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="ai-newsletter-form-group">
                            <label for="ai_length"><?php _e('Summary Length', 'ai-newsletter-pro'); ?></label>
                            <select id="ai_length" name="ai_length">
                                <option value="brief" <?php selected($ai['ai_length'] ?? 'medium', 'brief'); ?>>
                                    <?php _e('Brief (1-2 sentences)', 'ai-newsletter-pro'); ?>
                                </option>
                                <option value="medium" <?php selected($ai['ai_length'] ?? 'medium', 'medium'); ?>>
                                    <?php _e('Medium (2-3 sentences)', 'ai-newsletter-pro'); ?>
                                </option>
                                <option value="detailed" <?php selected($ai['ai_length'] ?? 'medium', 'detailed'); ?>>
                                    <?php _e('Detailed (3-4 sentences)', 'ai-newsletter-pro'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="ai-newsletter-save-section">
            <h3 style="margin: 0 0 1rem 0; color: #111827;"><?php _e('Save Your Settings', 'ai-newsletter-pro'); ?></h3>
            <p style="margin: 0 0 2rem 0; color: #6b7280;">
                <?php _e('Click save to apply your settings. Changes will take effect immediately for new subscribers and future newsletters.', 'ai-newsletter-pro'); ?>
            </p>
            <button type="submit" name="submit" class="ai-newsletter-save-btn">
                <?php _e('💾 Save All Settings', 'ai-newsletter-pro'); ?>
            </button>
        </div>
    </form>
</div>

<script>
function switchTab(tabName) {
    // Hide all tab panels
    document.querySelectorAll('.ai-newsletter-tab-panel').forEach(panel => {
        panel.classList.remove('active');
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('.ai-newsletter-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected tab panel
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Add active class to clicked tab
    event.target.classList.add('active');
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const fromEmail = document.getElementById('from_email').value;
    const replyTo = document.getElementById('reply_to').value;
    
    // Validate email addresses
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (fromEmail && !emailRegex.test(fromEmail)) {
        e.preventDefault();
        alert('<?php _e('Please enter a valid From Email address.', 'ai-newsletter-pro'); ?>');
        document.getElementById('from_email').focus();
        return;
    }
    
    if (replyTo && !emailRegex.test(replyTo)) {
        e.preventDefault();
        alert('<?php _e('Please enter a valid Reply-To Email address.', 'ai-newsletter-pro'); ?>');
        document.getElementById('reply_to').focus();
        return;
    }
    
    // Validate OpenAI API key format
    const apiKey = document.getElementById('openai_api_key').value;
    if (apiKey && !apiKey.startsWith('sk-')) {
        const confirm = window.confirm('<?php _e('The API key format looks incorrect. OpenAI API keys typically start with "sk-". Do you want to save anyway?', 'ai-newsletter-pro'); ?>');
        if (!confirm) {
            e.preventDefault();
            document.getElementById('openai_api_key').focus();
            return;
        }
    }
});

// Auto-save draft functionality
let autoSaveTimeout;
document.querySelectorAll('input, select, textarea').forEach(input => {
    input.addEventListener('change', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(() => {
            // Auto-save could be implemented here
            console.log('Auto-saving settings...');
        }, 2000);
    });
});

// Show/hide AI settings based on API key
document.getElementById('openai_api_key').addEventListener('input', function() {
    const aiSections = document.querySelectorAll('#ai-tab .ai-newsletter-form-section:not(:first-child)');
    const hasApiKey = this.value.trim().length > 0;
    
    aiSections.forEach(section => {
        section.style.opacity = hasApiKey ? '1' : '0.5';
        section.style.pointerEvents = hasApiKey ? 'auto' : 'none';
    });
});

// Initialize AI sections state
document.addEventListener('DOMContentLoaded', function() {
    const apiKeyInput = document.getElementById('openai_api_key');
    const event = new Event('input');
    apiKeyInput.dispatchEvent(event);
});
</script>