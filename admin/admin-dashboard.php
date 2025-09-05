<?php
/**
 * Admin Dashboard Template - Complete Version
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
if (class_exists('AI_Newsletter_Pro_Database')) {
    $database = new AI_Newsletter_Pro_Database();
    $stats = $database->get_stats();
} else {
    // Basic stats from database
    global $wpdb;
    $stats = array(
        'total_subscribers' => $wpdb->get_var("SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE status = 'subscribed'") ?: 0,
        'pending_subscribers' => $wpdb->get_var("SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE status = 'pending'") ?: 0,
        'total_campaigns' => 0,
        'sent_campaigns' => 0,
        'avg_open_rate' => 0,
        'avg_click_rate' => 0,
        'conversion_rate' => 0,
        'subscribers_last_30_days' => $wpdb->get_var("SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE subscribed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)") ?: 0
    );
}

// Format numbers
$total_subscribers = number_format($stats['total_subscribers'] ?? 0);
$avg_open_rate = number_format($stats['avg_open_rate'] ?? 0, 1);
$avg_click_rate = number_format($stats['avg_click_rate'] ?? 0, 1);
$conversion_rate = number_format($stats['conversion_rate'] ?? 0, 1);
?>

<style>
/* Modern Dashboard Styles */
.ai-newsletter-admin {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #f8fafc;
    margin: -20px -20px -10px -2px;
    padding: 0;
    min-height: 100vh;
}

.ai-newsletter-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    margin-bottom: 2rem;
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

.ai-newsletter-header-actions {
    margin-top: 1.5rem;
}

.ai-newsletter-header-actions .button {
    margin-right: 1rem;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
}

.ai-newsletter-header-actions .button-primary {
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: white;
    backdrop-filter: blur(10px);
}

.ai-newsletter-header-actions .button-primary:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
}

.ai-newsletter-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding: 0 2rem;
}

.ai-newsletter-stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
}

.ai-newsletter-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.ai-newsletter-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.ai-newsletter-stat-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.ai-newsletter-stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.ai-newsletter-stat-label {
    color: #6b7280;
    margin: 0.5rem 0;
    font-weight: 500;
}

.ai-newsletter-stat-change {
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.ai-newsletter-stat-change.positive {
    color: #059669;
}

.ai-newsletter-dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    padding: 0 2rem;
    margin-bottom: 2rem;
}

.ai-newsletter-dashboard-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.ai-newsletter-card-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f9fafb;
}

.ai-newsletter-card-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
}

.ai-newsletter-view-all {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.875rem;
}

.ai-newsletter-view-all:hover {
    color: #4f46e5;
}

.ai-newsletter-card-body {
    padding: 1.5rem;
}

.ai-newsletter-quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.ai-newsletter-action-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #f3f4f6;
    border-radius: 0.5rem;
    text-decoration: none;
    color: #374151;
    transition: all 0.2s;
    border: 2px solid transparent;
}

.ai-newsletter-action-btn:hover {
    background: #e5e7eb;
    color: #111827;
    transform: translateY(-1px);
    border-color: #d1d5db;
}

.ai-newsletter-action-icon {
    font-size: 1.5rem;
}

.ai-newsletter-action-text {
    font-weight: 500;
}

.ai-newsletter-activity-list {
    space-y: 1rem;
}

.ai-newsletter-activity-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.ai-newsletter-activity-item:last-child {
    border-bottom: none;
}

.ai-newsletter-activity-icon {
    font-size: 1.25rem;
    margin-top: 0.125rem;
}

.ai-newsletter-activity-content {
    flex: 1;
}

.ai-newsletter-activity-message {
    margin: 0 0 0.25rem 0;
    font-weight: 500;
    color: #374151;
}

.ai-newsletter-activity-time {
    font-size: 0.875rem;
    color: #6b7280;
}

.ai-newsletter-no-activity {
    text-align: center;
    color: #6b7280;
    font-style: italic;
    padding: 2rem;
}

.ai-newsletter-ai-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    grid-column: 1 / -1;
}

.ai-newsletter-ai-card .ai-newsletter-card-header {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
    color: white;
}

.ai-newsletter-beta-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ai-newsletter-ai-status {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.ai-newsletter-ai-icon {
    font-size: 3rem;
}

.ai-newsletter-ai-content h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.ai-newsletter-ai-content p {
    margin: 0 0 1rem 0;
    opacity: 0.9;
}

.ai-newsletter-ai-actions {
    display: flex;
    gap: 0.75rem;
}

.ai-newsletter-ai-actions .button {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-weight: 500;
}

.ai-newsletter-integrations-status {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.ai-newsletter-integration-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
}

.ai-newsletter-integration-item.connected {
    background: #ecfdf5;
    border-color: #10b981;
}

.ai-newsletter-integration-icon {
    font-size: 1.25rem;
}

.ai-newsletter-integration-name {
    flex: 1;
    font-weight: 500;
    font-size: 0.875rem;
}

.ai-newsletter-integration-status {
    font-size: 0.75rem;
    color: #6b7280;
}

.ai-newsletter-integration-item.connected .ai-newsletter-integration-status {
    color: #059669;
    font-weight: 600;
}

.ai-newsletter-no-integrations {
    grid-column: 1 / -1;
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}

.ai-newsletter-full-width {
    grid-column: 1 / -1;
}

@media (max-width: 768px) {
    .ai-newsletter-admin {
        margin: -10px;
    }
    
    .ai-newsletter-header {
        padding: 1.5rem;
    }
    
    .ai-newsletter-header h1 {
        font-size: 2rem;
    }
    
    .ai-newsletter-stats-grid,
    .ai-newsletter-dashboard-grid {
        grid-template-columns: 1fr;
        padding: 0 1rem;
    }
    
    .ai-newsletter-quick-actions {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="ai-newsletter-admin">
    <div class="ai-newsletter-header">
        <h1><?php _e('AI Newsletter Pro Dashboard', 'ai-newsletter-pro'); ?></h1>
        <p><?php _e('Manage subscribers, create campaigns, and analyze performance', 'ai-newsletter-pro'); ?></p>
        <div class="ai-newsletter-header-actions">
            <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-campaigns&action=new'); ?>" class="button button-primary">
                <?php _e('📝 Create Campaign', 'ai-newsletter-pro'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-widgets'); ?>" class="button">
                <?php _e('🎨 Manage Widgets', 'ai-newsletter-pro'); ?>
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="ai-newsletter-stats-grid">
        <div class="ai-newsletter-stat-card">
            <div class="ai-newsletter-stat-icon">👥</div>
            <h3 class="ai-newsletter-stat-number"><?php echo $total_subscribers; ?></h3>
            <p class="ai-newsletter-stat-label"><?php _e('Total Subscribers', 'ai-newsletter-pro'); ?></p>
            <span class="ai-newsletter-stat-change positive">
                <span class="dashicons dashicons-arrow-up-alt"></span>
                <?php echo number_format($stats['subscribers_last_30_days'] ?? 0); ?> <?php _e('this month', 'ai-newsletter-pro'); ?>
            </span>
        </div>

        <div class="ai-newsletter-stat-card">
            <div class="ai-newsletter-stat-icon">📧</div>
            <h3 class="ai-newsletter-stat-number"><?php echo $avg_open_rate; ?>%</h3>
            <p class="ai-newsletter-stat-label"><?php _e('Average Open Rate', 'ai-newsletter-pro'); ?></p>
            <span class="ai-newsletter-stat-change">
                <span class="dashicons dashicons-chart-line"></span>
                <?php _e('Industry avg: 21.3%', 'ai-newsletter-pro'); ?>
            </span>
        </div>

        <div class="ai-newsletter-stat-card">
            <div class="ai-newsletter-stat-icon">🎯</div>
            <h3 class="ai-newsletter-stat-number"><?php echo $conversion_rate; ?>%</h3>
            <p class="ai-newsletter-stat-label"><?php _e('Widget Conversion Rate', 'ai-newsletter-pro'); ?></p>
            <span class="ai-newsletter-stat-change positive">
                <span class="dashicons dashicons-arrow-up-alt"></span>
                <?php echo number_format($stats['total_widget_conversions'] ?? 0); ?> <?php _e('conversions', 'ai-newsletter-pro'); ?>
            </span>
        </div>

        <div class="ai-newsletter-stat-card">
            <div class="ai-newsletter-stat-icon">📊</div>
            <h3 class="ai-newsletter-stat-number"><?php echo number_format($stats['sent_campaigns'] ?? 0); ?></h3>
            <p class="ai-newsletter-stat-label"><?php _e('Campaigns Sent', 'ai-newsletter-pro'); ?></p>
            <span class="ai-newsletter-stat-change">
                <span class="dashicons dashicons-email-alt"></span>
                <?php echo number_format($stats['total_campaigns'] ?? 0); ?> <?php _e('total', 'ai-newsletter-pro'); ?>
            </span>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="ai-newsletter-dashboard-grid">
        
        <!-- Quick Actions -->
        <div class="ai-newsletter-dashboard-card">
            <div class="ai-newsletter-card-header">
                <h2><?php _e('Quick Actions', 'ai-newsletter-pro'); ?></h2>
            </div>
            <div class="ai-newsletter-card-body">
                <div class="ai-newsletter-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-campaigns&action=new'); ?>" class="ai-newsletter-action-btn">
                        <span class="ai-newsletter-action-icon">📝</span>
                        <span class="ai-newsletter-action-text"><?php _e('Create Campaign', 'ai-newsletter-pro'); ?></span>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-subscribers&action=export'); ?>" class="ai-newsletter-action-btn">
                        <span class="ai-newsletter-action-icon">📤</span>
                        <span class="ai-newsletter-action-text"><?php _e('Export Subscribers', 'ai-newsletter-pro'); ?></span>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-widgets'); ?>" class="ai-newsletter-action-btn">
                        <span class="ai-newsletter-action-icon">🎨</span>
                        <span class="ai-newsletter-action-text"><?php _e('Design Widgets', 'ai-newsletter-pro'); ?></span>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-analytics'); ?>" class="ai-newsletter-action-btn">
                        <span class="ai-newsletter-action-icon">📈</span>
                        <span class="ai-newsletter-action-text"><?php _e('View Analytics', 'ai-newsletter-pro'); ?></span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="ai-newsletter-dashboard-card">
            <div class="ai-newsletter-card-header">
                <h2><?php _e('Recent Activity', 'ai-newsletter-pro'); ?></h2>
                <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-analytics'); ?>" class="ai-newsletter-view-all">
                    <?php _e('View All', 'ai-newsletter-pro'); ?>
                </a>
            </div>
            <div class="ai-newsletter-card-body">
                <div class="ai-newsletter-activity-list">
                    <?php
                    global $wpdb;
                    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '" . AI_NEWSLETTER_PRO_ANALYTICS_TABLE . "'") == AI_NEWSLETTER_PRO_ANALYTICS_TABLE;
                    
                    if ($table_exists) {
                        $recent_activities = $wpdb->get_results(
                            "SELECT * FROM " . AI_NEWSLETTER_PRO_ANALYTICS_TABLE . " 
                             ORDER BY created_at DESC 
                             LIMIT 5"
                        );
                    } else {
                        $recent_activities = array();
                    }
                    
                    if (!empty($recent_activities)) {
                        foreach ($recent_activities as $activity) {
                            $icon = '';
                            $message = '';
                            $time_ago = human_time_diff(strtotime($activity->created_at), current_time('timestamp'));
                            
                            switch ($activity->event_type) {
                                case 'subscription':
                                    $icon = '✅';
                                    $message = __('New subscriber joined', 'ai-newsletter-pro');
                                    break;
                                case 'email_open':
                                    $icon = '📧';
                                    $message = __('Email opened', 'ai-newsletter-pro');
                                    break;
                                case 'email_click':
                                    $icon = '🔗';
                                    $message = __('Email link clicked', 'ai-newsletter-pro');
                                    break;
                                case 'widget_view':
                                    $icon = '👁️';
                                    $message = __('Widget viewed', 'ai-newsletter-pro');
                                    break;
                                default:
                                    $icon = '📊';
                                    $message = ucfirst(str_replace('_', ' ', $activity->event_type));
                            }
                            ?>
                            <div class="ai-newsletter-activity-item">
                                <span class="ai-newsletter-activity-icon"><?php echo $icon; ?></span>
                                <div class="ai-newsletter-activity-content">
                                    <p class="ai-newsletter-activity-message"><?php echo esc_html($message); ?></p>
                                    <span class="ai-newsletter-activity-time"><?php echo $time_ago . ' ' . __('ago', 'ai-newsletter-pro'); ?></span>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="ai-newsletter-no-activity">' . __('No recent activity. Start collecting subscribers!', 'ai-newsletter-pro') . '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- AI Newsletter Status -->
        <div class="ai-newsletter-dashboard-card ai-newsletter-ai-card">
            <div class="ai-newsletter-card-header">
                <h2><?php _e('AI Newsletter Assistant', 'ai-newsletter-pro'); ?></h2>
                <span class="ai-newsletter-beta-badge"><?php _e('Beta', 'ai-newsletter-pro'); ?></span>
            </div>
            <div class="ai-newsletter-card-body">
                <?php
                $ai_settings = ai_newsletter_pro_get_option('ai', array());
                $ai_enabled = !empty($ai_settings['openai_api_key']);
                
                if ($ai_enabled) {
                    ?>
                    <div class="ai-newsletter-ai-status">
                        <div class="ai-newsletter-ai-icon">🤖</div>
                        <div class="ai-newsletter-ai-content">
                            <h3><?php _e('AI Assistant Active', 'ai-newsletter-pro'); ?></h3>
                            <p><?php _e('Your AI is analyzing content and ready to generate newsletters.', 'ai-newsletter-pro'); ?></p>
                            <div class="ai-newsletter-ai-actions">
                                <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-campaigns&action=ai-generate'); ?>" class="button button-primary">
                                    <?php _e('Generate Newsletter', 'ai-newsletter-pro'); ?>
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-settings#ai'); ?>" class="button">
                                    <?php _e('Configure AI', 'ai-newsletter-pro'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="ai-newsletter-ai-status">
                        <div class="ai-newsletter-ai-icon">🤖</div>
                        <div class="ai-newsletter-ai-content">
                            <h3><?php _e('Setup AI Assistant', 'ai-newsletter-pro'); ?></h3>
                            <p><?php _e('Enable AI-powered content curation to automatically generate engaging newsletters.', 'ai-newsletter-pro'); ?></p>
                            <div class="ai-newsletter-ai-actions">
                                <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-settings#ai'); ?>" class="button button-primary">
                                    <?php _e('Setup AI', 'ai-newsletter-pro'); ?>
                                </a>
                                <a href="#" class="ai-newsletter-learn-more"><?php _e('Learn More', 'ai-newsletter-pro'); ?></a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>

        <!-- Integration Status -->
        <div class="ai-newsletter-dashboard-card">
            <div class="ai-newsletter-card-header">
                <h2><?php _e('Email Service Integrations', 'ai-newsletter-pro'); ?></h2>
                <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-integrations'); ?>" class="ai-newsletter-view-all">
                    <?php _e('Manage', 'ai-newsletter-pro'); ?>
                </a>
            </div>
            <div class="ai-newsletter-card-body">
                <div class="ai-newsletter-integrations-status">
                    <?php
                    $integrations = ai_newsletter_pro_get_option('integrations', array());
                    $services = array(
                        'mailchimp' => array('name' => 'Mailchimp', 'icon' => '📬'),
                        'convertkit' => array('name' => 'ConvertKit', 'icon' => '🚀'),
                        'zoho' => array('name' => 'Zoho Campaigns', 'icon' => '📊'),
                        'sendgrid' => array('name' => 'SendGrid', 'icon' => '✉️'),
                        'activecampaign' => array('name' => 'ActiveCampaign', 'icon' => '📮')
                    );
                    
                    $connected_count = 0;
                    foreach ($services as $service_key => $service) {
                        $is_connected = !empty($integrations[$service_key]['enabled']);
                        if ($is_connected) $connected_count++;
                        
                        $status_class = $is_connected ? 'connected' : 'disconnected';
                        $status_text = $is_connected ? __('Connected', 'ai-newsletter-pro') : __('Not Connected', 'ai-newsletter-pro');
                        ?>
                        <div class="ai-newsletter-integration-item <?php echo $status_class; ?>">
                            <span class="ai-newsletter-integration-icon"><?php echo $service['icon']; ?></span>
                            <span class="ai-newsletter-integration-name"><?php echo $service['name']; ?></span>
                            <span class="ai-newsletter-integration-status"><?php echo $status_text; ?></span>
                        </div>
                        <?php
                    }
                    
                    if ($connected_count === 0) {
                        ?>
                        <div class="ai-newsletter-no-integrations">
                            <p><?php _e('No email services connected yet.', 'ai-newsletter-pro'); ?></p>
                            <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-integrations'); ?>" class="button button-primary">
                                <?php _e('Connect Service', 'ai-newsletter-pro'); ?>
                            </a>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>