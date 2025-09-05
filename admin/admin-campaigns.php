<?php
/**
 * Admin Campaigns Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current action
$current_action = $_GET['action'] ?? 'list';
$campaign_id = $_GET['campaign_id'] ?? 0;

// Handle form submissions
if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'ai_newsletter_pro_campaigns')) {
    switch ($_POST['action']) {
        case 'create_campaign':
            $title = sanitize_text_field($_POST['title']);
            $subject = sanitize_text_field($_POST['subject']);
            $content = wp_kses_post($_POST['content']);
            
            global $wpdb;
            $result = $wpdb->insert(
                AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE,
                array(
                    'title' => $title,
                    'subject' => $subject,
                    'content' => $content,
                    'status' => 'draft',
                    'type' => 'manual',
                    'created_by' => get_current_user_id(),
                    'created_at' => current_time('mysql')
                )
            );
            
            if ($result) {
                echo '<div class="notice notice-success"><p>' . __('Campaign created successfully!', 'ai-newsletter-pro') . '</p></div>';
            }
            break;
            
        case 'send_campaign':
            // Handle campaign sending
            echo '<div class="notice notice-info"><p>' . __('Campaign sending functionality will be implemented in the next update.', 'ai-newsletter-pro') . '</p></div>';
            break;
    }
}

// Get campaigns
global $wpdb;
$campaigns = $wpdb->get_results("SELECT * FROM " . AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE . " ORDER BY created_at DESC");
?>

<div class="wrap ai-newsletter-campaigns">
    <style>
        .ai-newsletter-campaigns {
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
        
        .ai-newsletter-campaigns-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .ai-newsletter-campaign-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            transition: transform 0.2s;
        }
        
        .ai-newsletter-campaign-card:hover {
            transform: translateY(-2px);
        }
        
        .ai-newsletter-campaign-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .ai-newsletter-campaign-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
        }
        
        .ai-newsletter-campaign-meta {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .ai-newsletter-campaign-body {
            padding: 1.5rem;
        }
        
        .ai-newsletter-campaign-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .ai-newsletter-stat {
            text-align: center;
        }
        
        .ai-newsletter-stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .ai-newsletter-stat-label {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
        }
        
        .ai-newsletter-campaign-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .ai-newsletter-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .ai-newsletter-btn-primary {
            background: #667eea;
            color: white;
        }
        
        .ai-newsletter-btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .ai-newsletter-btn:hover {
            transform: translateY(-1px);
        }
        
        .ai-newsletter-create-section {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .ai-newsletter-form-group {
            margin-bottom: 1.5rem;
        }
        
        .ai-newsletter-form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .ai-newsletter-form-group input,
        .ai-newsletter-form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
        }
        
        .ai-newsletter-status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .ai-newsletter-status-draft {
            background: #f3f4f6;
            color: #374151;
        }
        
        .ai-newsletter-status-sent {
            background: #dcfce7;
            color: #166534;
        }
        
        .ai-newsletter-status-sending {
            background: #fef3c7;
            color: #92400e;
        }
    </style>

    <div class="ai-newsletter-header">
        <h1><?php _e('Email Campaigns', 'ai-newsletter-pro'); ?></h1>
        <p><?php _e('Create, manage, and send newsletter campaigns to your subscribers', 'ai-newsletter-pro'); ?></p>
    </div>

    <?php if ($current_action === 'new'): ?>
        <!-- Create New Campaign -->
        <div class="ai-newsletter-create-section">
            <h2><?php _e('Create New Campaign', 'ai-newsletter-pro'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('ai_newsletter_pro_campaigns'); ?>
                <input type="hidden" name="action" value="create_campaign">
                
                <div class="ai-newsletter-form-group">
                    <label for="title"><?php _e('Campaign Title', 'ai-newsletter-pro'); ?></label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="ai-newsletter-form-group">
                    <label for="subject"><?php _e('Email Subject', 'ai-newsletter-pro'); ?></label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                
                <div class="ai-newsletter-form-group">
                    <label for="content"><?php _e('Email Content', 'ai-newsletter-pro'); ?></label>
                    <textarea id="content" name="content" rows="10" required></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="ai-newsletter-btn ai-newsletter-btn-primary">
                        <?php _e('Create Campaign', 'ai-newsletter-pro'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-campaigns'); ?>" class="ai-newsletter-btn ai-newsletter-btn-secondary">
                        <?php _e('Cancel', 'ai-newsletter-pro'); ?>
                    </a>
                </div>
            </form>
        </div>
        
    <?php else: ?>
        <!-- Campaigns List -->
        <div style="margin-bottom: 2rem; text-align: center;">
            <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-campaigns&action=new'); ?>" class="ai-newsletter-btn ai-newsletter-btn-primary" style="padding: 1rem 2rem; font-size: 1rem;">
                <?php _e('📝 Create New Campaign', 'ai-newsletter-pro'); ?>
            </a>
        </div>

        <?php if (!empty($campaigns)): ?>
            <div class="ai-newsletter-campaigns-grid">
                <?php foreach ($campaigns as $campaign): ?>
                    <div class="ai-newsletter-campaign-card">
                        <div class="ai-newsletter-campaign-header">
                            <h3 class="ai-newsletter-campaign-title"><?php echo esc_html($campaign->title); ?></h3>
                            <div class="ai-newsletter-campaign-meta">
                                <span class="ai-newsletter-status-badge ai-newsletter-status-<?php echo esc_attr($campaign->status); ?>">
                                    <?php echo esc_html(ucfirst($campaign->status)); ?>
                                </span>
                                • <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($campaign->created_at))); ?>
                            </div>
                        </div>
                        
                        <div class="ai-newsletter-campaign-body">
                            <div class="ai-newsletter-campaign-stats">
                                <div class="ai-newsletter-stat">
                                    <div class="ai-newsletter-stat-number"><?php echo number_format($campaign->recipients_count ?? 0); ?></div>
                                    <div class="ai-newsletter-stat-label"><?php _e('Recipients', 'ai-newsletter-pro'); ?></div>
                                </div>
                                <div class="ai-newsletter-stat">
                                    <div class="ai-newsletter-stat-number"><?php echo number_format($campaign->opened_count ?? 0); ?></div>
                                    <div class="ai-newsletter-stat-label"><?php _e('Opens', 'ai-newsletter-pro'); ?></div>
                                </div>
                                <div class="ai-newsletter-stat">
                                    <div class="ai-newsletter-stat-number"><?php echo number_format($campaign->clicked_count ?? 0); ?></div>
                                    <div class="ai-newsletter-stat-label"><?php _e('Clicks', 'ai-newsletter-pro'); ?></div>
                                </div>
                            </div>
                            
                            <div class="ai-newsletter-campaign-actions">
                                <a href="#" class="ai-newsletter-btn ai-newsletter-btn-secondary">
                                    <?php _e('Edit', 'ai-newsletter-pro'); ?>
                                </a>
                                <?php if ($campaign->status === 'draft'): ?>
                                    <a href="#" class="ai-newsletter-btn ai-newsletter-btn-primary" onclick="sendCampaign(<?php echo $campaign->id; ?>)">
                                        <?php _e('Send', 'ai-newsletter-pro'); ?>
                                    </a>
                                <?php endif; ?>
                                <a href="#" class="ai-newsletter-btn ai-newsletter-btn-secondary">
                                    <?php _e('Preview', 'ai-newsletter-pro'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                <h3><?php _e('No campaigns yet', 'ai-newsletter-pro'); ?></h3>
                <p><?php _e('Create your first email campaign to start engaging with your subscribers.', 'ai-newsletter-pro'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-campaigns&action=new'); ?>" class="ai-newsletter-btn ai-newsletter-btn-primary">
                    <?php _e('Create Your First Campaign', 'ai-newsletter-pro'); ?>
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function sendCampaign(campaignId) {
    if (confirm('<?php _e('Are you sure you want to send this campaign?', 'ai-newsletter-pro'); ?>')) {
        // Send campaign functionality
        alert('<?php _e('Campaign sending functionality coming in next update!', 'ai-newsletter-pro'); ?>');
    }
}
</script>