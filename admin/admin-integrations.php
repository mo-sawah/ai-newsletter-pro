<?php
/**
 * Admin Integrations Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'ai_newsletter_pro_integrations')) {
    $settings = get_option('ai_newsletter_pro_settings', array());
    
    // Update integration settings
    $integrations = $_POST['integrations'] ?? array();
    $settings['integrations'] = $integrations;
    
    update_option('ai_newsletter_pro_settings', $settings);
    
    echo '<div class="notice notice-success"><p>' . __('Integration settings saved successfully!', 'ai-newsletter-pro') . '</p></div>';
}

// Get current settings
$settings = get_option('ai_newsletter_pro_settings', array());
$integrations = $settings['integrations'] ?? array();

// Email service providers
$email_services = array(
    'mailchimp' => array(
        'name' => 'Mailchimp',
        'icon' => '📬',
        'description' => 'Connect with the world\'s largest marketing automation platform',
        'fields' => array(
            'api_key' => array('label' => 'API Key', 'type' => 'text', 'required' => true),
            'list_id' => array('label' => 'Audience ID', 'type' => 'text', 'required' => true)
        ),
        'color' => '#ffe01b'
    ),
    'convertkit' => array(
        'name' => 'ConvertKit',
        'icon' => '🚀',
        'description' => 'Email marketing for online creators',
        'fields' => array(
            'api_key' => array('label' => 'API Key', 'type' => 'text', 'required' => true),
            'form_id' => array('label' => 'Form ID', 'type' => 'text', 'required' => true)
        ),
        'color' => '#fb6970'
    ),
    'zoho' => array(
        'name' => 'Zoho Campaigns',
        'icon' => '📊',
        'description' => 'Email marketing by Zoho',
        'fields' => array(
            'client_id' => array('label' => 'Client ID', 'type' => 'text', 'required' => true),
            'client_secret' => array('label' => 'Client Secret', 'type' => 'password', 'required' => true),
            'refresh_token' => array('label' => 'Refresh Token', 'type' => 'text', 'required' => false)
        ),
        'color' => '#c83c3c'
    ),
    'sendgrid' => array(
        'name' => 'SendGrid',
        'icon' => '✉️',
        'description' => 'Email delivery service by Twilio',
        'fields' => array(
            'api_key' => array('label' => 'API Key', 'type' => 'password', 'required' => true),
            'list_id' => array('label' => 'List ID', 'type' => 'text', 'required' => false)
        ),
        'color' => '#1a82e2'
    ),
    'activecampaign' => array(
        'name' => 'ActiveCampaign',
        'icon' => '📮',
        'description' => 'Customer experience automation platform',
        'fields' => array(
            'api_url' => array('label' => 'API URL', 'type' => 'url', 'required' => true),
            'api_key' => array('label' => 'API Key', 'type' => 'password', 'required' => true),
            'list_id' => array('label' => 'List ID', 'type' => 'text', 'required' => false)
        ),
        'color' => '#356ae6'
    ),
    'mailerlite' => array(
        'name' => 'MailerLite',
        'icon' => '📨',
        'description' => 'Simple email marketing tools',
        'fields' => array(
            'api_key' => array('label' => 'API Key', 'type' => 'password', 'required' => true),
            'group_id' => array('label' => 'Group ID', 'type' => 'text', 'required' => false)
        ),
        'color' => '#09c269'
    )
);
?>

<style>
.ai-newsletter-integrations {
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

.ai-newsletter-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.ai-newsletter-integrations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.ai-newsletter-integration-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 2px solid #e5e7eb;
    overflow: hidden;
    transition: all 0.3s;
    position: relative;
}

.ai-newsletter-integration-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.ai-newsletter-integration-card.connected {
    border-color: #10b981;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(5, 150, 105, 0.05) 100%);
}

.ai-newsletter-integration-header {
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border-bottom: 1px solid #e5e7eb;
    position: relative;
}

.ai-newsletter-service-icon {
    font-size: 2.5rem;
    width: 60px;
    height: 60px;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
}

.ai-newsletter-service-info h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
}

.ai-newsletter-service-description {
    margin: 0;
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.4;
}

.ai-newsletter-connection-status {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ai-newsletter-connection-status.connected {
    background: #dcfce7;
    color: #166534;
}

.ai-newsletter-connection-status.disconnected {
    background: #fee2e2;
    color: #991b1b;
}

.ai-newsletter-integration-body {
    padding: 1.5rem;
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

.ai-newsletter-form-group .required {
    color: #ef4444;
}

.ai-newsletter-form-group input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 0.9rem;
    transition: all 0.2s;
    background: white;
}

.ai-newsletter-form-group input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.ai-newsletter-toggle-container {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
}

.ai-newsletter-toggle {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.ai-newsletter-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.ai-newsletter-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.ai-newsletter-toggle-slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

.ai-newsletter-toggle input:checked + .ai-newsletter-toggle-slider {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.ai-newsletter-toggle input:checked + .ai-newsletter-toggle-slider:before {
    transform: translateX(26px);
}

.ai-newsletter-toggle-label {
    font-weight: 500;
    color: #374151;
}

.ai-newsletter-integration-footer {
    padding: 1rem 1.5rem;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ai-newsletter-test-btn {
    background: #f3f4f6;
    color: #374151;
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}

.ai-newsletter-test-btn:hover {
    background: #e5e7eb;
    color: #111827;
}

.ai-newsletter-docs-link {
    color: #3b82f6;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
}

.ai-newsletter-docs-link:hover {
    color: #1d4ed8;
}

.ai-newsletter-save-section {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
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

.ai-newsletter-help-section {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 1px solid #0ea5e9;
    border-radius: 1rem;
    padding: 2rem;
    margin-top: 2rem;
}

.ai-newsletter-help-section h3 {
    margin: 0 0 1rem 0;
    color: #0c4a6e;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.ai-newsletter-help-section p {
    margin: 0;
    color: #0369a1;
    line-height: 1.6;
}

.ai-newsletter-help-links {
    margin-top: 1rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.ai-newsletter-help-links a {
    color: #0ea5e9;
    text-decoration: none;
    font-weight: 500;
    padding: 0.5rem 1rem;
    background: rgba(14, 165, 233, 0.1);
    border-radius: 0.375rem;
    transition: all 0.2s;
}

.ai-newsletter-help-links a:hover {
    background: rgba(14, 165, 233, 0.2);
    color: #0284c7;
}

@media (max-width: 768px) {
    .ai-newsletter-integrations-grid {
        grid-template-columns: 1fr;
    }
    
    .ai-newsletter-integration-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .ai-newsletter-connection-status {
        position: static;
        margin-top: 1rem;
    }
    
    .ai-newsletter-help-links {
        flex-direction: column;
    }
}
</style>

<div class="ai-newsletter-integrations">
    <div class="ai-newsletter-header">
        <h1><?php _e('Email Service Integrations', 'ai-newsletter-pro'); ?></h1>
        <p><?php _e('Connect with your favorite email marketing platform to sync subscribers automatically', 'ai-newsletter-pro'); ?></p>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('ai_newsletter_pro_integrations'); ?>
        
        <div class="ai-newsletter-integrations-grid">
            <?php foreach ($email_services as $service_key => $service): 
                $service_config = $integrations[$service_key] ?? array();
                $is_enabled = !empty($service_config['enabled']);
                $is_configured = $is_enabled && !empty($service_config['api_key']);
            ?>
                <div class="ai-newsletter-integration-card <?php echo $is_configured ? 'connected' : ''; ?>">
                    <div class="ai-newsletter-integration-header">
                        <div class="ai-newsletter-service-icon" style="background-color: <?php echo $service['color']; ?>20;">
                            <?php echo $service['icon']; ?>
                        </div>
                        <div class="ai-newsletter-service-info">
                            <h3><?php echo esc_html($service['name']); ?></h3>
                            <p class="ai-newsletter-service-description"><?php echo esc_html($service['description']); ?></p>
                        </div>
                        <div class="ai-newsletter-connection-status <?php echo $is_configured ? 'connected' : 'disconnected'; ?>">
                            <?php echo $is_configured ? __('Connected', 'ai-newsletter-pro') : __('Not Connected', 'ai-newsletter-pro'); ?>
                        </div>
                    </div>

                    <div class="ai-newsletter-integration-body">
                        <!-- Enable Toggle -->
                        <div class="ai-newsletter-toggle-container">
                            <label class="ai-newsletter-toggle">
                                <input type="checkbox" 
                                       name="integrations[<?php echo $service_key; ?>][enabled]" 
                                       value="1" 
                                       <?php checked($is_enabled); ?>>
                                <span class="ai-newsletter-toggle-slider"></span>
                            </label>
                            <span class="ai-newsletter-toggle-label">
                                <?php printf(__('Enable %s Integration', 'ai-newsletter-pro'), $service['name']); ?>
                            </span>
                        </div>

                        <!-- Configuration Fields -->
                        <div class="ai-newsletter-config-fields" style="<?php echo $is_enabled ? '' : 'opacity: 0.5; pointer-events: none;'; ?>">
                            <?php foreach ($service['fields'] as $field_key => $field): ?>
                                <div class="ai-newsletter-form-group">
                                    <label for="<?php echo $service_key . '_' . $field_key; ?>">
                                        <?php echo esc_html($field['label']); ?>
                                        <?php if ($field['required']): ?>
                                            <span class="required">*</span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="<?php echo esc_attr($field['type']); ?>" 
                                           id="<?php echo $service_key . '_' . $field_key; ?>"
                                           name="integrations[<?php echo $service_key; ?>][<?php echo $field_key; ?>]" 
                                           value="<?php echo esc_attr($service_config[$field_key] ?? ''); ?>"
                                           placeholder="<?php echo esc_attr($field['label']); ?>"
                                           <?php echo $field['required'] ? 'required' : ''; ?>>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="ai-newsletter-integration-footer">
                        <a href="#" class="ai-newsletter-test-btn" onclick="testConnection('<?php echo $service_key; ?>')">
                            <?php _e('🔍 Test Connection', 'ai-newsletter-pro'); ?>
                        </a>
                        <a href="#" class="ai-newsletter-docs-link">
                            <?php printf(__('📖 %s Setup Guide', 'ai-newsletter-pro'), $service['name']); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Save Button -->
        <div class="ai-newsletter-save-section">
            <h3 style="margin: 0 0 1rem 0; color: #111827;"><?php _e('Save Your Integration Settings', 'ai-newsletter-pro'); ?></h3>
            <p style="margin: 0 0 2rem 0; color: #6b7280;">
                <?php _e('Click save to apply your integration settings. Subscribers will automatically sync to your connected email services.', 'ai-newsletter-pro'); ?>
            </p>
            <button type="submit" name="submit" class="ai-newsletter-save-btn">
                <?php _e('💾 Save Integration Settings', 'ai-newsletter-pro'); ?>
            </button>
        </div>
    </form>

    <!-- Help Section -->
    <div class="ai-newsletter-help-section">
        <h3>
            <span>💡</span>
            <?php _e('Need Help Setting Up Integrations?', 'ai-newsletter-pro'); ?>
        </h3>
        <p>
            <?php _e('Each email service has its own setup process. We\'ve created detailed guides to help you connect your accounts quickly and securely. API keys are stored securely and never shared.', 'ai-newsletter-pro'); ?>
        </p>
        <div class="ai-newsletter-help-links">
            <a href="#" onclick="showSetupGuide('mailchimp')">
                <?php _e('Mailchimp Setup', 'ai-newsletter-pro'); ?>
            </a>
            <a href="#" onclick="showSetupGuide('convertkit')">
                <?php _e('ConvertKit Setup', 'ai-newsletter-pro'); ?>
            </a>
            <a href="#" onclick="showSetupGuide('sendgrid')">
                <?php _e('SendGrid Setup', 'ai-newsletter-pro'); ?>
            </a>
            <a href="#" onclick="showSetupGuide('general')">
                <?php _e('General Troubleshooting', 'ai-newsletter-pro'); ?>
            </a>
        </div>
    </div>

    <!-- Integration Status Summary -->
    <div class="ai-newsletter-save-section" style="margin-top: 2rem;">
        <h3 style="margin: 0 0 1rem 0; color: #111827;"><?php _e('Integration Status Summary', 'ai-newsletter-pro'); ?></h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
            <?php
            $connected_services = 0;
            foreach ($email_services as $service_key => $service) {
                $service_config = $integrations[$service_key] ?? array();
                $is_configured = !empty($service_config['enabled']) && !empty($service_config['api_key']);
                if ($is_configured) $connected_services++;
                
                $status_color = $is_configured ? '#10b981' : '#6b7280';
                $status_text = $is_configured ? __('Connected', 'ai-newsletter-pro') : __('Not Connected', 'ai-newsletter-pro');
                ?>
                <div style="padding: 1rem; background: <?php echo $is_configured ? '#ecfdf5' : '#f9fafb'; ?>; border-radius: 0.5rem; border: 1px solid <?php echo $is_configured ? '#10b981' : '#e5e7eb'; ?>; text-align: center;">
                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;"><?php echo $service['icon']; ?></div>
                    <div style="font-weight: 600; margin-bottom: 0.25rem;"><?php echo $service['name']; ?></div>
                    <div style="font-size: 0.875rem; color: <?php echo $status_color; ?>; font-weight: 500;">
                        <?php echo $status_text; ?>
                    </div>
                </div>
            <?php } ?>
        </div>
        
        <div style="margin-top: 2rem; padding: 1rem; background: <?php echo $connected_services > 0 ? '#ecfdf5' : '#fef3c7'; ?>; border-radius: 0.5rem; border: 1px solid <?php echo $connected_services > 0 ? '#10b981' : '#f59e0b'; ?>;">
            <strong><?php printf(__('📊 Status: %d of %d services connected', 'ai-newsletter-pro'), $connected_services, count($email_services)); ?></strong>
            <?php if ($connected_services === 0): ?>
                <p style="margin: 0.5rem 0 0 0; color: #92400e;">
                    <?php _e('⚠️ No email services connected. Subscribers will only be stored locally until you connect a service.', 'ai-newsletter-pro'); ?>
                </p>
            <?php elseif ($connected_services === 1): ?>
                <p style="margin: 0.5rem 0 0 0; color: #059669;">
                    <?php _e('✅ Great! Your subscribers will automatically sync to your connected email service.', 'ai-newsletter-pro'); ?>
                </p>
            <?php else: ?>
                <p style="margin: 0.5rem 0 0 0; color: #059669;">
                    <?php _e('🚀 Excellent! Multiple integrations provide redundancy and flexibility for your email marketing.', 'ai-newsletter-pro'); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Enable/disable configuration fields based on toggle state
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.ai-newsletter-toggle input[type="checkbox"]');
    
    toggles.forEach(function(toggle) {
        const card = toggle.closest('.ai-newsletter-integration-card');
        const configFields = card.querySelector('.ai-newsletter-config-fields');
        
        function updateFields() {
            if (toggle.checked) {
                configFields.style.opacity = '1';
                configFields.style.pointerEvents = 'auto';
                card.classList.add('enabled');
            } else {
                configFields.style.opacity = '0.5';
                configFields.style.pointerEvents = 'none';
                card.classList.remove('enabled');
            }
        }
        
        toggle.addEventListener('change', updateFields);
        updateFields(); // Initialize state
    });
});

function testConnection(serviceKey) {
    // Get form data for this service
    const formData = new FormData();
    const serviceInputs = document.querySelectorAll(`input[name*="integrations[${serviceKey}]"]`);
    
    serviceInputs.forEach(input => {
        if (input.type !== 'checkbox' || input.checked) {
            formData.append(input.name, input.value);
        }
    });
    
    // For now, show a test message. In the full version, this would make an AJAX call
    alert(`<?php _e('Testing connection to', 'ai-newsletter-pro'); ?> ${serviceKey}...\n\n<?php _e('Connection testing functionality will be available in the next update. This will verify your API credentials and connection status.', 'ai-newsletter-pro'); ?>`);
    
    // Future implementation:
    // testServiceConnection(serviceKey, formData);
}

function showSetupGuide(service) {
    let guideContent = '';
    
    switch(service) {
        case 'mailchimp':
            guideContent = `<?php _e('Mailchimp Setup Guide:', 'ai-newsletter-pro'); ?>
            
1. <?php _e('Log into your Mailchimp account', 'ai-newsletter-pro'); ?>
2. <?php _e('Go to Account → Extras → API Keys', 'ai-newsletter-pro'); ?>
3. <?php _e('Create a new API key and copy it', 'ai-newsletter-pro'); ?>
4. <?php _e('Go to Audience → Settings → Audience name and defaults', 'ai-newsletter-pro'); ?>
5. <?php _e('Copy your Audience ID', 'ai-newsletter-pro'); ?>`;
            break;
            
        case 'convertkit':
            guideContent = `<?php _e('ConvertKit Setup Guide:', 'ai-newsletter-pro'); ?>
            
1. <?php _e('Log into your ConvertKit account', 'ai-newsletter-pro'); ?>
2. <?php _e('Go to Settings → Advanced → API', 'ai-newsletter-pro'); ?>
3. <?php _e('Copy your API Key', 'ai-newsletter-pro'); ?>
4. <?php _e('Go to Forms and copy the Form ID you want to use', 'ai-newsletter-pro'); ?>`;
            break;
            
        case 'sendgrid':
            guideContent = `<?php _e('SendGrid Setup Guide:', 'ai-newsletter-pro'); ?>
            
1. <?php _e('Log into your SendGrid account', 'ai-newsletter-pro'); ?>
2. <?php _e('Go to Settings → API Keys', 'ai-newsletter-pro'); ?>
3. <?php _e('Create a new API Key with Full Access', 'ai-newsletter-pro'); ?>
4. <?php _e('Copy the API key (you won\'t be able to see it again)', 'ai-newsletter-pro'); ?>`;
            break;
            
        default:
            guideContent = `<?php _e('General Integration Tips:', 'ai-newsletter-pro'); ?>
            
• <?php _e('Always use API keys, never passwords', 'ai-newsletter-pro'); ?>
• <?php _e('Test your connection after setup', 'ai-newsletter-pro'); ?>
• <?php _e('Keep your API keys secure and private', 'ai-newsletter-pro'); ?>
• <?php _e('Check your email service documentation for specific requirements', 'ai-newsletter-pro'); ?>`;
    }
    
    alert(guideContent);
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const enabledServices = document.querySelectorAll('.ai-newsletter-toggle input[type="checkbox"]:checked');
    let hasValidConfig = false;
    
    enabledServices.forEach(function(toggle) {
        const card = toggle.closest('.ai-newsletter-integration-card');
        const requiredFields = card.querySelectorAll('input[required]');
        let serviceValid = true;
        
        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                serviceValid = false;
                field.style.borderColor = '#ef4444';
            } else {
                field.style.borderColor = '#e5e7eb';
            }
        });
        
        if (serviceValid && enabledServices.length > 0) {
            hasValidConfig = true;
        }
    });
    
    if (enabledServices.length > 0 && !hasValidConfig) {
        e.preventDefault();
        alert('<?php _e('Please fill in all required fields for enabled integrations.', 'ai-newsletter-pro'); ?>');
    }
});
</script>