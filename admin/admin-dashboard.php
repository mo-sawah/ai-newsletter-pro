<?php
/**
 * Admin Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
$database = new AI_Newsletter_Pro_Database();
$stats = $database->get_stats();

// Format numbers
$total_subscribers = number_format($stats['total_subscribers'] ?? 0);
$avg_open_rate = number_format($stats['avg_open_rate'] ?? 0, 1);
$avg_click_rate = number_format($stats['avg_click_rate'] ?? 0, 1);
$conversion_rate = number_format($stats['conversion_rate'] ?? 0, 1);
?>

<div class="wrap ai-newsletter-admin">
    <div class="ai-newsletter-header">
        <h1><?php _e('AI Newsletter Pro Dashboard', 'ai-newsletter-pro'); ?></h1>
        <div class="ai-newsletter-header-actions">
            <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-campaigns&action=new'); ?>" class="button button-primary">
                <?php _e('Create Campaign', 'ai-newsletter-pro'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-widgets'); ?>" class="button">
                <?php _e('Manage Widgets', 'ai-newsletter-pro'); ?>
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="ai-newsletter-stats-grid">
        <div class="ai-newsletter-stat-card">
            <div class="ai-newsletter-stat-icon">👥</div>
            <div class="ai-newsletter-stat-content">
                <h3 class="ai-newsletter-stat-number"><?php echo $total_subscribers; ?></h3>
                <p class="ai-newsletter-stat-label"><?php _e('Total Subscribers', 'ai-newsletter-pro'); ?></p>
                <span class="ai-newsletter-stat-change positive">
                    <span class="dashicons dashicons-arrow-up-alt"></span>
                    <?php echo number_format($stats['subscribers_last_30_days'] ?? 0); ?> <?php _e('this month', 'ai-newsletter-pro'); ?>
                </span>
            </div>
        </div>

        <div class="ai-newsletter-stat-card">
            <div class="ai-newsletter-stat-icon">📧</div>
            <div class="ai-newsletter-stat-content">
                <h3 class="ai-newsletter-stat-number"><?php echo $avg_open_rate; ?>%</h3>
                <p class="ai-newsletter-stat-label"><?php _e('Average Open Rate', 'ai-newsletter-pro'); ?></p>
                <span class="ai-newsletter-stat-change">
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php _e('Industry avg: 21.3%', 'ai-newsletter-pro'); ?>
                </span>
            </div>
        </div>

        <div class="ai-newsletter-stat-card">
            <div class="ai-newsletter-stat-icon">🎯</div>
            <div class="ai-newsletter-stat-content">
                <h3 class="ai-newsletter-stat-number"><?php echo $conversion_rate; ?>%</h3>
                <p class="ai-newsletter-stat-label"><?php _e('Widget Conversion Rate', 'ai-newsletter-pro'); ?></p>
                <span class="ai-newsletter-stat-change positive">
                    <span class="dashicons dashicons-arrow-up-alt"></span>
                    <?php echo number_format($stats['total_widget_conversions'] ?? 0); ?> <?php _e('conversions', 'ai-newsletter-pro'); ?>
                </span>
            </div>
        </div>

        <div class="ai-newsletter-stat-card">
            <div class="ai-newsletter-stat-icon">📊</div>
            <div class="ai-newsletter-stat-content">
                <h3 class="ai-newsletter-stat-number"><?php echo number_format($stats['sent_campaigns'] ?? 0); ?></h3>
                <p class="ai-newsletter-stat-label"><?php _e('Campaigns Sent', 'ai-newsletter-pro'); ?></p>
                <span class="ai-newsletter-stat-change">
                    <span class="dashicons dashicons-email-alt"></span>
                    <?php echo number_format($stats['total_campaigns'] ?? 0); ?> <?php _e('total', 'ai-newsletter-pro'); ?>
                </span>
            </div>
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
                    $recent_activities = $wpdb->get_results(
                        "SELECT * FROM " . AI_NEWSLETTER_PRO_ANALYTICS_TABLE . " 
                         ORDER BY created_at DESC 
                         LIMIT 5"
                    );
                    
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
                        echo '<p class="ai-newsletter-no-activity">' . __('No recent activity', 'ai-newsletter-pro') . '</p>';
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
                    <div class="ai-newsletter-ai-status active">
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
                    <div class="ai-newsletter-ai-status inactive">
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

        <!-- Performance Overview -->
        <div class="ai-newsletter-dashboard-card ai-newsletter-full-width">
            <div class="ai-newsletter-card-header">
                <h2><?php _e('Performance Overview', 'ai-newsletter-pro'); ?></h2>
                <div class="ai-newsletter-chart-controls">
                    <select id="ai-newsletter-chart-period">
                        <option value="7"><?php _e('Last 7 days', 'ai-newsletter-pro'); ?></option>
                        <option value="30" selected><?php _e('Last 30 days', 'ai-newsletter-pro'); ?></option>
                        <option value="90"><?php _e('Last 90 days', 'ai-newsletter-pro'); ?></option>
                    </select>
                </div>
            </div>
            <div class="ai-newsletter-card-body">
                <div class="ai-newsletter-chart-container">
                    <canvas id="ai-newsletter-performance-chart" width="800" height="300"></canvas>
                </div>
                <div class="ai-newsletter-chart-legend">
                    <div class="ai-newsletter-legend-item">
                        <span class="ai-newsletter-legend-color" style="background: #3b82f6;"></span>
                        <span><?php _e('New Subscribers', 'ai-newsletter-pro'); ?></span>
                    </div>
                    <div class="ai-newsletter-legend-item">
                        <span class="ai-newsletter-legend-color" style="background: #10b981;"></span>
                        <span><?php _e('Email Opens', 'ai-newsletter-pro'); ?></span>
                    </div>
                    <div class="ai-newsletter-legend-item">
                        <span class="ai-newsletter-legend-color" style="background: #f59e0b;"></span>
                        <span><?php _e('Widget Conversions', 'ai-newsletter-pro'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Welcome Modal for First Time Users -->
    <?php if (get_option('ai_newsletter_pro_first_time', true)): ?>
    <div id="ai-newsletter-welcome-modal" class="ai-newsletter-modal">
        <div class="ai-newsletter-modal-content">
            <div class="ai-newsletter-modal-header">
                <h2><?php _e('Welcome to AI Newsletter Pro!', 'ai-newsletter-pro'); ?></h2>
                <button class="ai-newsletter-modal-close">&times;</button>
            </div>
            <div class="ai-newsletter-modal-body">
                <div class="ai-newsletter-welcome-steps">
                    <div class="ai-newsletter-welcome-step">
                        <div class="ai-newsletter-step-icon">🎨</div>
                        <h3><?php _e('1. Create Your First Widget', 'ai-newsletter-pro'); ?></h3>
                        <p><?php _e('Design beautiful signup forms that convert visitors into subscribers.', 'ai-newsletter-pro'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-widgets'); ?>" class="button button-primary">
                            <?php _e('Create Widget', 'ai-newsletter-pro'); ?>
                        </a>
                    </div>
                    
                    <div class="ai-newsletter-welcome-step">
                        <div class="ai-newsletter-step-icon">🔗</div>
                        <h3><?php _e('2. Connect Email Service', 'ai-newsletter-pro'); ?></h3>
                        <p><?php _e('Integrate with Mailchimp, ConvertKit, or other email providers.', 'ai-newsletter-pro'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-integrations'); ?>" class="button">
                            <?php _e('Connect Service', 'ai-newsletter-pro'); ?>
                        </a>
                    </div>
                    
                    <div class="ai-newsletter-welcome-step">
                        <div class="ai-newsletter-step-icon">🤖</div>
                        <h3><?php _e('3. Enable AI Assistant', 'ai-newsletter-pro'); ?></h3>
                        <p><?php _e('Let AI automatically curate and generate your newsletters.', 'ai-newsletter-pro'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-settings'); ?>" class="button">
                            <?php _e('Setup AI', 'ai-newsletter-pro'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php 
    update_option('ai_newsletter_pro_first_time', false);
    endif; 
    ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Welcome modal
    $('#ai-newsletter-welcome-modal').show();
    
    $('.ai-newsletter-modal-close, .ai-newsletter-modal').on('click', function(e) {
        if (e.target === this) {
            $('#ai-newsletter-welcome-modal').fadeOut();
        }
    });
    
    // Performance chart (placeholder - would integrate with Chart.js)
    const ctx = document.getElementById('ai-newsletter-performance-chart');
    if (ctx) {
        // Initialize chart here
        console.log('Chart would be initialized here with Chart.js');
    }
    
    // Chart period selector
    $('#ai-newsletter-chart-period').on('change', function() {
        // Reload chart data
        console.log('Loading data for period:', $(this).val());
    });
});
</script>