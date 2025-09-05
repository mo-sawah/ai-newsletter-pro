<?php
/**
 * Admin Widgets Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get widgets from database
global $wpdb;
$widgets = $wpdb->get_results("SELECT * FROM " . AI_NEWSLETTER_PRO_WIDGETS_TABLE . " ORDER BY id DESC");
?>

<style>
.ai-newsletter-widgets {
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

.ai-newsletter-widget-types {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.ai-newsletter-widget-type {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 2px solid #e5e7eb;
    transition: all 0.3s;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.ai-newsletter-widget-type:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    border-color: #667eea;
}

.ai-newsletter-widget-type::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.ai-newsletter-widget-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    display: block;
}

.ai-newsletter-widget-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #111827;
}

.ai-newsletter-widget-description {
    color: #6b7280;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.ai-newsletter-create-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
}

.ai-newsletter-create-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    color: white;
}

.ai-newsletter-existing-widgets {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.ai-newsletter-section-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ai-newsletter-section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.ai-newsletter-widgets-list {
    padding: 1.5rem;
}

.ai-newsletter-widget-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    transition: all 0.2s;
    background: #fafafa;
}

.ai-newsletter-widget-item:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.ai-newsletter-widget-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.ai-newsletter-widget-type-icon {
    font-size: 2rem;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.ai-newsletter-widget-details h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #111827;
}

.ai-newsletter-widget-meta {
    font-size: 0.875rem;
    color: #6b7280;
    display: flex;
    gap: 1rem;
    align-items: center;
}

.ai-newsletter-widget-status {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.ai-newsletter-widget-status.active {
    background: #dcfce7;
    color: #166534;
}

.ai-newsletter-widget-status.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.ai-newsletter-widget-stats {
    display: flex;
    gap: 1rem;
    align-items: center;
    color: #6b7280;
    font-size: 0.875rem;
}

.ai-newsletter-widget-actions {
    display: flex;
    gap: 0.5rem;
}

.ai-newsletter-action-btn {
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.ai-newsletter-action-btn.edit {
    background: #dbeafe;
    color: #1e40af;
    border-color: #3b82f6;
}

.ai-newsletter-action-btn.delete {
    background: #fee2e2;
    color: #991b1b;
    border-color: #ef4444;
}

.ai-newsletter-action-btn.preview {
    background: #f3e8ff;
    color: #7c3aed;
    border-color: #8b5cf6;
}

.ai-newsletter-action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ai-newsletter-no-widgets {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}

.ai-newsletter-no-widgets h3 {
    margin-bottom: 1rem;
    color: #374151;
    font-size: 1.5rem;
}

.ai-newsletter-no-widgets p {
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

.ai-newsletter-preview-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    z-index: 999999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.ai-newsletter-preview-content {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    max-width: 600px;
    width: 100%;
    max-height: 80vh;
    overflow: auto;
    position: relative;
}

.ai-newsletter-preview-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #f3f4f6;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

@media (max-width: 768px) {
    .ai-newsletter-widget-types {
        grid-template-columns: 1fr;
    }
    
    .ai-newsletter-widget-item {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .ai-newsletter-widget-info {
        flex-direction: column;
        text-align: center;
    }
    
    .ai-newsletter-widget-meta {
        justify-content: center;
    }
}
</style>

<div class="ai-newsletter-widgets">
    <div class="ai-newsletter-header">
        <h1><?php _e('Newsletter Widgets', 'ai-newsletter-pro'); ?></h1>
        <p><?php _e('Create beautiful subscription forms that convert visitors into subscribers', 'ai-newsletter-pro'); ?></p>
    </div>

    <!-- Widget Types -->
    <div class="ai-newsletter-widget-types">
        <div class="ai-newsletter-widget-type">
            <span class="ai-newsletter-widget-icon">🎯</span>
            <h3 class="ai-newsletter-widget-title"><?php _e('Popup Modal', 'ai-newsletter-pro'); ?></h3>
            <p class="ai-newsletter-widget-description">
                <?php _e('Eye-catching modal that appears based on time, scroll, or exit intent. Perfect for maximum visibility and conversions.', 'ai-newsletter-pro'); ?>
            </p>
            <a href="#" class="ai-newsletter-create-btn" onclick="createWidget('popup')">
                <?php _e('Create Popup', 'ai-newsletter-pro'); ?>
            </a>
        </div>

        <div class="ai-newsletter-widget-type">
            <span class="ai-newsletter-widget-icon">📝</span>
            <h3 class="ai-newsletter-widget-title"><?php _e('Inline Form', 'ai-newsletter-pro'); ?></h3>
            <p class="ai-newsletter-widget-description">
                <?php _e('Embed subscription forms within your content. Great for blog posts and landing pages without being intrusive.', 'ai-newsletter-pro'); ?>
            </p>
            <a href="#" class="ai-newsletter-create-btn" onclick="createWidget('inline')">
                <?php _e('Create Inline Form', 'ai-newsletter-pro'); ?>
            </a>
        </div>

        <div class="ai-newsletter-widget-type">
            <span class="ai-newsletter-widget-icon">🎈</span>
            <h3 class="ai-newsletter-widget-title"><?php _e('Floating Widget', 'ai-newsletter-pro'); ?></h3>
            <p class="ai-newsletter-widget-description">
                <?php _e('Sticky floating form that stays visible as users scroll. Subtle yet effective for ongoing engagement.', 'ai-newsletter-pro'); ?>
            </p>
            <a href="#" class="ai-newsletter-create-btn" onclick="createWidget('floating')">
                <?php _e('Create Floating Widget', 'ai-newsletter-pro'); ?>
            </a>
        </div>

        <div class="ai-newsletter-widget-type">
            <span class="ai-newsletter-widget-icon">📢</span>
            <h3 class="ai-newsletter-widget-title"><?php _e('Top/Bottom Banner', 'ai-newsletter-pro'); ?></h3>
            <p class="ai-newsletter-widget-description">
                <?php _e('Full-width banners at the top or bottom of your site. Perfect for announcements and promotions.', 'ai-newsletter-pro'); ?>
            </p>
            <a href="#" class="ai-newsletter-create-btn" onclick="createWidget('banner')">
                <?php _e('Create Banner', 'ai-newsletter-pro'); ?>
            </a>
        </div>
    </div>

    <!-- Existing Widgets -->
    <div class="ai-newsletter-existing-widgets">
        <div class="ai-newsletter-section-header">
            <h2 class="ai-newsletter-section-title"><?php _e('Your Widgets', 'ai-newsletter-pro'); ?></h2>
            <span class="ai-newsletter-widget-count">
                <?php printf(__('%d widgets created', 'ai-newsletter-pro'), count($widgets)); ?>
            </span>
        </div>

        <div class="ai-newsletter-widgets-list">
            <?php if (!empty($widgets)): ?>
                <?php foreach ($widgets as $widget): 
                    $settings = json_decode($widget->settings, true);
                    $widget_title = $settings['title'] ?? 'Untitled Widget';
                    $widget_icons = array(
                        'popup' => '🎯',
                        'inline' => '📝', 
                        'floating' => '🎈',
                        'banner' => '📢'
                    );
                ?>
                    <div class="ai-newsletter-widget-item">
                        <div class="ai-newsletter-widget-info">
                            <div class="ai-newsletter-widget-type-icon">
                                <?php echo $widget_icons[$widget->type] ?? '📧'; ?>
                            </div>
                            <div class="ai-newsletter-widget-details">
                                <h3><?php echo esc_html($widget_title); ?></h3>
                                <div class="ai-newsletter-widget-meta">
                                    <span class="ai-newsletter-widget-status <?php echo $widget->active ? 'active' : 'inactive'; ?>">
                                        <?php echo $widget->active ? __('Active', 'ai-newsletter-pro') : __('Inactive', 'ai-newsletter-pro'); ?>
                                    </span>
                                    <span><?php echo ucfirst($widget->type); ?> Widget</span>
                                    <span><?php echo esc_html($widget->position ?: 'Default Position'); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="ai-newsletter-widget-stats">
                            <span>👁️ <?php echo number_format($widget->impressions); ?> views</span>
                            <span>✅ <?php echo number_format($widget->conversions); ?> conversions</span>
                            <?php if ($widget->impressions > 0): ?>
                                <span>📈 <?php echo number_format(($widget->conversions / $widget->impressions) * 100, 1); ?>% rate</span>
                            <?php endif; ?>
                        </div>

                        <div class="ai-newsletter-widget-actions">
                            <a href="#" class="ai-newsletter-action-btn preview" onclick="previewWidget(<?php echo $widget->id; ?>)">
                                <?php _e('Preview', 'ai-newsletter-pro'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-widgets&action=edit&widget_id=' . $widget->id); ?>" 
                               class="ai-newsletter-action-btn edit">
                                <?php _e('Edit', 'ai-newsletter-pro'); ?>
                            </a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ai-newsletter-pro-widgets&action=delete&widget_id=' . $widget->id), 'ai_newsletter_pro_admin'); ?>" 
                               class="ai-newsletter-action-btn delete"
                               onclick="return confirm('<?php _e('Are you sure you want to delete this widget?', 'ai-newsletter-pro'); ?>')">
                                <?php _e('Delete', 'ai-newsletter-pro'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="ai-newsletter-no-widgets">
                    <h3><?php _e('No widgets created yet', 'ai-newsletter-pro'); ?></h3>
                    <p><?php _e('Create your first newsletter widget to start collecting subscribers. Choose from popup modals, inline forms, floating widgets, or banners.', 'ai-newsletter-pro'); ?></p>
                    <a href="#" class="ai-newsletter-create-btn" onclick="createWidget('popup')">
                        <?php _e('🚀 Create Your First Widget', 'ai-newsletter-pro'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Setup Guide -->
    <div class="ai-newsletter-existing-widgets" style="margin-top: 2rem;">
        <div class="ai-newsletter-section-header">
            <h2 class="ai-newsletter-section-title"><?php _e('Quick Setup Guide', 'ai-newsletter-pro'); ?></h2>
        </div>
        <div class="ai-newsletter-widgets-list">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div style="padding: 1.5rem; background: #f9fafb; border-radius: 0.5rem; border: 1px solid #e5e7eb;">
                    <h4 style="margin: 0 0 1rem 0; color: #111827; display: flex; align-items: center; gap: 0.5rem;">
                        <span>1️⃣</span> <?php _e('Create Widget', 'ai-newsletter-pro'); ?>
                    </h4>
                    <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">
                        <?php _e('Choose a widget type and customize the design, content, and triggers to match your site.', 'ai-newsletter-pro'); ?>
                    </p>
                </div>
                
                <div style="padding: 1.5rem; background: #f9fafb; border-radius: 0.5rem; border: 1px solid #e5e7eb;">
                    <h4 style="margin: 0 0 1rem 0; color: #111827; display: flex; align-items: center; gap: 0.5rem;">
                        <span>2️⃣</span> <?php _e('Configure Triggers', 'ai-newsletter-pro'); ?>
                    </h4>
                    <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">
                        <?php _e('Set up when and where your widgets appear - time delays, scroll triggers, or exit intent.', 'ai-newsletter-pro'); ?>
                    </p>
                </div>
                
                <div style="padding: 1.5rem; background: #f9fafb; border-radius: 0.5rem; border: 1px solid #e5e7eb;">
                    <h4 style="margin: 0 0 1rem 0; color: #111827; display: flex; align-items: center; gap: 0.5rem;">
                        <span>3️⃣</span> <?php _e('Monitor Performance', 'ai-newsletter-pro'); ?>
                    </h4>
                    <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">
                        <?php _e('Track impressions, conversions, and optimize your widgets for better performance.', 'ai-newsletter-pro'); ?>
                    </p>
                </div>
            </div>
            
            <div style="margin-top: 2rem; padding: 1.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 0.75rem; color: white; text-align: center;">
                <h4 style="margin: 0 0 1rem 0; font-size: 1.1rem;"><?php _e('💡 Pro Tip', 'ai-newsletter-pro'); ?></h4>
                <p style="margin: 0; opacity: 0.9;">
                    <?php _e('Use multiple widget types for different pages! Popups work great on blog posts, while floating widgets are perfect for product pages.', 'ai-newsletter-pro'); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div id="ai-newsletter-preview-modal" class="ai-newsletter-preview-modal">
    <div class="ai-newsletter-preview-content">
        <button class="ai-newsletter-preview-close" onclick="closePreview()">&times;</button>
        <div id="ai-newsletter-preview-body">
            <!-- Preview content will be loaded here -->
        </div>
    </div>
</div>

<script>
function createWidget(type) {
    // For now, show an alert. In the full version, this would open a widget builder
    alert('<?php _e('Widget builder coming soon! This will open a visual editor to create your', 'ai-newsletter-pro'); ?> ' + type + ' widget.');
    
    // Future implementation would redirect to widget builder:
    // window.location.href = '<?php echo admin_url('admin.php?page=ai-newsletter-pro-widgets&action=create&type='); ?>' + type;
}

function previewWidget(widgetId) {
    // Show preview modal
    const modal = document.getElementById('ai-newsletter-preview-modal');
    const previewBody = document.getElementById('ai-newsletter-preview-body');
    
    // For now, show a placeholder. In the full version, this would load the actual widget preview
    previewBody.innerHTML = `
        <h3><?php _e('Widget Preview', 'ai-newsletter-pro'); ?></h3>
        <p><?php _e('Preview functionality coming soon! This will show exactly how your widget appears to visitors.', 'ai-newsletter-pro'); ?></p>
        <div style="background: #f3f4f6; padding: 2rem; border-radius: 0.5rem; text-align: center; margin: 1rem 0;">
            <p style="margin: 0; color: #6b7280;">Widget ID: ${widgetId}</p>
        </div>
    `;
    
    modal.style.display = 'flex';
    
    // Future implementation would load actual widget preview:
    // loadWidgetPreview(widgetId);
}

function closePreview() {
    document.getElementById('ai-newsletter-preview-modal').style.display = 'none';
}

// Close modal on outside click
document.getElementById('ai-newsletter-preview-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePreview();
    }
});

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.keyCode === 27) {
        closePreview();
    }
});
</script>