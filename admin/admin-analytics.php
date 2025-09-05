<?php
/**
 * Admin Analytics Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get analytics data
global $wpdb;

// Date range filter
$date_range = sanitize_text_field($_GET['range'] ?? '30');
$date_filter = '';
switch ($date_range) {
    case '7':
        $date_filter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case '30':
        $date_filter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case '90':
        $date_filter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
        break;
    default:
        $date_filter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

// Get basic stats
$total_subscribers = $wpdb->get_var("SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE status = 'subscribed'");
$new_subscribers = $wpdb->get_var("SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE status = 'subscribed' " . $date_filter);

// Get widget stats
$widgets = $wpdb->get_results("SELECT type, SUM(impressions) as total_impressions, SUM(conversions) as total_conversions FROM " . AI_NEWSLETTER_PRO_WIDGETS_TABLE . " GROUP BY type");

// Calculate conversion rates
$widget_stats = array();
foreach ($widgets as $widget) {
    $conversion_rate = $widget->total_impressions > 0 ? ($widget->total_conversions / $widget->total_impressions) * 100 : 0;
    $widget_stats[$widget->type] = array(
        'impressions' => $widget->total_impressions,
        'conversions' => $widget->total_conversions,
        'rate' => $conversion_rate
    );
}

// Get top performing widgets
$top_widgets = $wpdb->get_results("SELECT * FROM " . AI_NEWSLETTER_PRO_WIDGETS_TABLE . " WHERE active = 1 ORDER BY conversions DESC LIMIT 5");

// Get subscription sources
$sources = $wpdb->get_results("SELECT source, COUNT(*) as count FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE status = 'subscribed' GROUP BY source ORDER BY count DESC LIMIT 10");
?>

<style>
.ai-newsletter-analytics {
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

.ai-newsletter-filters {
    background: white;
    padding: 1.5rem;
    border-radius: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.ai-newsletter-date-filters {
    display: flex;
    gap: 0.5rem;
}

.ai-newsletter-date-filter {
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    border: 2px solid transparent;
    border-radius: 0.5rem;
    text-decoration: none;
    color: #374151;
    font-weight: 500;
    transition: all 0.2s;
}

.ai-newsletter-date-filter.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.ai-newsletter-date-filter:hover {
    background: #e5e7eb;
    color: #111827;
}

.ai-newsletter-date-filter.active:hover {
    background: #5a67d8;
    color: white;
}

.ai-newsletter-stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.ai-newsletter-stat-card {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    transition: transform 0.2s;
    position: relative;
    overflow: hidden;
}

.ai-newsletter-stat-card:hover {
    transform: translateY(-2px);
}

.ai-newsletter-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.ai-newsletter-stat-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    display: block;
}

.ai-newsletter-stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.ai-newsletter-stat-label {
    color: #6b7280;
    font-weight: 500;
    margin-bottom: 0.5rem;
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

.ai-newsletter-stat-change.negative {
    color: #dc2626;
}

.ai-newsletter-analytics-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.ai-newsletter-chart-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.ai-newsletter-card-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ai-newsletter-card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.ai-newsletter-card-body {
    padding: 1.5rem;
}

.ai-newsletter-chart-placeholder {
    height: 300px;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    font-weight: 500;
    border: 2px dashed #d1d5db;
}

.ai-newsletter-widget-performance {
    display: space-y-1rem;
}

.ai-newsletter-widget-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    border: 1px solid #e5e7eb;
}

.ai-newsletter-widget-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.ai-newsletter-widget-type-icon {
    font-size: 1.5rem;
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.ai-newsletter-widget-details h4 {
    margin: 0 0 0.25rem 0;
    color: #111827;
    font-size: 0.9rem;
    font-weight: 600;
}

.ai-newsletter-widget-meta {
    font-size: 0.75rem;
    color: #6b7280;
}

.ai-newsletter-widget-stats {
    text-align: right;
}

.ai-newsletter-conversion-rate {
    font-size: 1.25rem;
    font-weight: 700;
    color: #059669;
    margin-bottom: 0.25rem;
}

.ai-newsletter-conversion-count {
    font-size: 0.75rem;
    color: #6b7280;
}

.ai-newsletter-sources-list {
    max-height: 400px;
    overflow-y: auto;
}

.ai-newsletter-source-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.ai-newsletter-source-item:last-child {
    border-bottom: none;
}

.ai-newsletter-source-name {
    font-weight: 500;
    color: #374151;
}

.ai-newsletter-source-count {
    font-weight: 600;
    color: #667eea;
}

.ai-newsletter-progress-bar {
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 0.5rem;
}

.ai-newsletter-progress-fill {
    height: 100%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    transition: width 0.3s ease;
}

.ai-newsletter-no-data {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}

.ai-newsletter-no-data h3 {
    margin-bottom: 1rem;
    color: #374151;
}

@media (max-width: 768px) {
    .ai-newsletter-analytics-grid {
        grid-template-columns: 1fr;
    }
    
    .ai-newsletter-filters {
        flex-direction: column;
        align-items: stretch;
    }
    
    .ai-newsletter-date-filters {
        justify-content: center;
    }
    
    .ai-newsletter-widget-item {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}
</style>

<div class="ai-newsletter-analytics">
    <div class="ai-newsletter-header">
        <h1><?php _e('Analytics & Reports', 'ai-newsletter-pro'); ?></h1>
        <p><?php _e('Track your newsletter performance and subscriber growth', 'ai-newsletter-pro'); ?></p>
    </div>

    <!-- Main Analytics Grid -->
    <div class="ai-newsletter-analytics-grid">
        
        <!-- Performance Chart -->
        <div class="ai-newsletter-chart-card">
            <div class="ai-newsletter-card-header">
                <h3 class="ai-newsletter-card-title"><?php _e('Subscriber Growth', 'ai-newsletter-pro'); ?></h3>
                <select onchange="updateChart(this.value)" style="padding: 0.5rem; border-radius: 0.25rem; border: 1px solid #d1d5db;">
                    <option value="subscribers"><?php _e('New Subscribers', 'ai-newsletter-pro'); ?></option>
                    <option value="conversions"><?php _e('Widget Conversions', 'ai-newsletter-pro'); ?></option>
                    <option value="impressions"><?php _e('Widget Views', 'ai-newsletter-pro'); ?></option>
                </select>
            </div>
            <div class="ai-newsletter-card-body">
                <div class="ai-newsletter-chart-placeholder">
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📈</div>
                        <div><?php _e('Interactive chart will be displayed here', 'ai-newsletter-pro'); ?></div>
                        <div style="font-size: 0.875rem; color: #9ca3af; margin-top: 0.5rem;">
                            <?php _e('Chart.js integration coming in next update', 'ai-newsletter-pro'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performing Widgets -->
        <div class="ai-newsletter-chart-card">
            <div class="ai-newsletter-card-header">
                <h3 class="ai-newsletter-card-title"><?php _e('Widget Performance', 'ai-newsletter-pro'); ?></h3>
            </div>
            <div class="ai-newsletter-card-body">
                <?php if (!empty($top_widgets)): ?>
                    <div class="ai-newsletter-widget-performance">
                        <?php 
                        $widget_icons = array(
                            'popup' => '🎯',
                            'inline' => '📝', 
                            'floating' => '🎈',
                            'banner' => '📢'
                        );
                        
                        foreach ($top_widgets as $widget): 
                            $settings = json_decode($widget->settings, true);
                            $widget_title = $settings['title'] ?? 'Untitled Widget';
                            $conversion_rate = $widget->impressions > 0 ? ($widget->conversions / $widget->impressions) * 100 : 0;
                        ?>
                            <div class="ai-newsletter-widget-item">
                                <div class="ai-newsletter-widget-info">
                                    <div class="ai-newsletter-widget-type-icon">
                                        <?php echo $widget_icons[$widget->type] ?? '📧'; ?>
                                    </div>
                                    <div class="ai-newsletter-widget-details">
                                        <h4><?php echo esc_html($widget_title); ?></h4>
                                        <div class="ai-newsletter-widget-meta">
                                            <?php echo ucfirst($widget->type); ?> • <?php echo number_format($widget->impressions); ?> views
                                        </div>
                                    </div>
                                </div>
                                <div class="ai-newsletter-widget-stats">
                                    <div class="ai-newsletter-conversion-rate"><?php echo number_format($conversion_rate, 1); ?>%</div>
                                    <div class="ai-newsletter-conversion-count"><?php echo number_format($widget->conversions); ?> conversions</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="ai-newsletter-no-data">
                        <h3><?php _e('No Widget Data Yet', 'ai-newsletter-pro'); ?></h3>
                        <p><?php _e('Create and activate widgets to see performance data here.', 'ai-newsletter-pro'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-widgets'); ?>" style="color: #667eea; text-decoration: none; font-weight: 500;">
                            <?php _e('Create Your First Widget →', 'ai-newsletter-pro'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Additional Analytics -->
    <div class="ai-newsletter-analytics-grid">
        
        <!-- Subscription Sources -->
        <div class="ai-newsletter-chart-card">
            <div class="ai-newsletter-card-header">
                <h3 class="ai-newsletter-card-title"><?php _e('Subscription Sources', 'ai-newsletter-pro'); ?></h3>
            </div>
            <div class="ai-newsletter-card-body">
                <?php if (!empty($sources)): ?>
                    <div class="ai-newsletter-sources-list">
                        <?php 
                        $max_count = max(array_column($sources, 'count'));
                        foreach ($sources as $source): 
                            $percentage = $max_count > 0 ? ($source->count / $max_count) * 100 : 0;
                            $source_name = $source->source === 'unknown' ? __('Direct/Unknown', 'ai-newsletter-pro') : ucfirst(str_replace('_', ' ', $source->source));
                        ?>
                            <div class="ai-newsletter-source-item">
                                <div>
                                    <div class="ai-newsletter-source-name"><?php echo esc_html($source_name); ?></div>
                                    <div class="ai-newsletter-progress-bar">
                                        <div class="ai-newsletter-progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                    </div>
                                </div>
                                <div class="ai-newsletter-source-count"><?php echo number_format($source->count); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="ai-newsletter-no-data">
                        <h3><?php _e('No Subscription Data', 'ai-newsletter-pro'); ?></h3>
                        <p><?php _e('Start collecting subscribers to see source analytics.', 'ai-newsletter-pro'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Widget Type Performance -->
        <div class="ai-newsletter-chart-card">
            <div class="ai-newsletter-card-header">
                <h3 class="ai-newsletter-card-title"><?php _e('Widget Type Performance', 'ai-newsletter-pro'); ?></h3>
            </div>
            <div class="ai-newsletter-card-body">
                <?php if (!empty($widget_stats)): ?>
                    <div class="ai-newsletter-widget-performance">
                        <?php 
                        $widget_type_names = array(
                            'popup' => __('Popup Modals', 'ai-newsletter-pro'),
                            'inline' => __('Inline Forms', 'ai-newsletter-pro'),
                            'floating' => __('Floating Widgets', 'ai-newsletter-pro'),
                            'banner' => __('Banner Forms', 'ai-newsletter-pro')
                        );
                        
                        foreach ($widget_stats as $type => $stats): 
                        ?>
                            <div class="ai-newsletter-widget-item">
                                <div class="ai-newsletter-widget-info">
                                    <div class="ai-newsletter-widget-type-icon">
                                        <?php echo $widget_icons[$type] ?? '📧'; ?>
                                    </div>
                                    <div class="ai-newsletter-widget-details">
                                        <h4><?php echo $widget_type_names[$type] ?? ucfirst($type); ?></h4>
                                        <div class="ai-newsletter-widget-meta">
                                            <?php echo number_format($stats['impressions']); ?> views • <?php echo number_format($stats['conversions']); ?> conversions
                                        </div>
                                    </div>
                                </div>
                                <div class="ai-newsletter-widget-stats">
                                    <div class="ai-newsletter-conversion-rate"><?php echo number_format($stats['rate'], 1); ?>%</div>
                                    <div class="ai-newsletter-conversion-count"><?php _e('conversion rate', 'ai-newsletter-pro'); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="ai-newsletter-no-data">
                        <h3><?php _e('No Widget Performance Data', 'ai-newsletter-pro'); ?></h3>
                        <p><?php _e('Widget performance will appear here once you have active widgets.', 'ai-newsletter-pro'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Export and Actions -->
    <div class="ai-newsletter-chart-card">
        <div class="ai-newsletter-card-header">
            <h3 class="ai-newsletter-card-title"><?php _e('🔄 Export & Actions', 'ai-newsletter-pro'); ?></h3>
        </div>
        <div class="ai-newsletter-card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-subscribers&action=export'); ?>" 
                   style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #f9fafb; border-radius: 0.5rem; text-decoration: none; color: #374151; border: 1px solid #e5e7eb; transition: all 0.2s;"
                   onmouseover="this.style.background='#f3f4f6'"
                   onmouseout="this.style.background='#f9fafb'">
                    <span style="font-size: 1.5rem;">📥</span>
                    <div>
                        <div style="font-weight: 600;"><?php _e('Export Subscribers', 'ai-newsletter-pro'); ?></div>
                        <div style="font-size: 0.875rem; color: #6b7280;"><?php _e('Download CSV of all subscribers', 'ai-newsletter-pro'); ?></div>
                    </div>
                </a>

                <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-analytics&action=export&type=analytics'); ?>" 
                   style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #f9fafb; border-radius: 0.5rem; text-decoration: none; color: #374151; border: 1px solid #e5e7eb; transition: all 0.2s;"
                   onmouseover="this.style.background='#f3f4f6'"
                   onmouseout="this.style.background='#f9fafb'">
                    <span style="font-size: 1.5rem;">📊</span>
                    <div>
                        <div style="font-weight: 600;"><?php _e('Export Analytics', 'ai-newsletter-pro'); ?></div>
                        <div style="font-size: 0.875rem; color: #6b7280;"><?php _e('Download performance report', 'ai-newsletter-pro'); ?></div>
                    </div>
                </a>

                <a href="#" onclick="refreshData()" 
                   style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #f9fafb; border-radius: 0.5rem; text-decoration: none; color: #374151; border: 1px solid #e5e7eb; transition: all 0.2s;"
                   onmouseover="this.style.background='#f3f4f6'"
                   onmouseout="this.style.background='#f9fafb'">
                    <span style="font-size: 1.5rem;">🔄</span>
                    <div>
                        <div style="font-weight: 600;"><?php _e('Refresh Data', 'ai-newsletter-pro'); ?></div>
                        <div style="font-size: 0.875rem; color: #6b7280;"><?php _e('Update analytics dashboard', 'ai-newsletter-pro'); ?></div>
                    </div>
                </a>

                <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-settings'); ?>" 
                   style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #f9fafb; border-radius: 0.5rem; text-decoration: none; color: #374151; border: 1px solid #e5e7eb; transition: all 0.2s;"
                   onmouseover="this.style.background='#f3f4f6'"
                   onmouseout="this.style.background='#f9fafb'">
                    <span style="font-size: 1.5rem;">⚙️</span>
                    <div>
                        <div style="font-weight: 600;"><?php _e('Analytics Settings', 'ai-newsletter-pro'); ?></div>
                        <div style="font-size: 0.875rem; color: #6b7280;"><?php _e('Configure tracking options', 'ai-newsletter-pro'); ?></div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Insights and Recommendations -->
    <div class="ai-newsletter-chart-card">
        <div class="ai-newsletter-card-header">
            <h3 class="ai-newsletter-card-title"><?php _e('💡 Insights & Recommendations', 'ai-newsletter-pro'); ?></h3>
        </div>
        <div class="ai-newsletter-card-body">
            <div style="display: grid; gap: 1rem;">
                
                <?php if ($overall_rate < 2): ?>
                    <div style="padding: 1rem; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 0.5rem;">
                        <div style="font-weight: 600; color: #92400e; margin-bottom: 0.5rem;">
                            <?php _e('🎯 Low Conversion Rate', 'ai-newsletter-pro'); ?>
                        </div>
                        <div style="color: #78350f; font-size: 0.9rem;">
                            <?php _e('Your conversion rate is below average (2-4%). Try testing different widget designs, improving your value proposition, or adjusting trigger timing.', 'ai-newsletter-pro'); ?>
                        </div>
                    </div>
                <?php elseif ($overall_rate > 5): ?>
                    <div style="padding: 1rem; background: #ecfdf5; border-left: 4px solid #10b981; border-radius: 0.5rem;">
                        <div style="font-weight: 600; color: #065f46; margin-bottom: 0.5rem;">
                            <?php _e('🚀 Excellent Performance!', 'ai-newsletter-pro'); ?>
                        </div>
                        <div style="color: #047857; font-size: 0.9rem;">
                            <?php _e('Your conversion rate is above average! Consider expanding successful widgets to more pages or testing new widget types.', 'ai-newsletter-pro'); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($total_subscribers < 100): ?>
                    <div style="padding: 1rem; background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 0.5rem;">
                        <div style="font-weight: 600; color: #1e40af; margin-bottom: 0.5rem;">
                            <?php _e('📈 Growing Your List', 'ai-newsletter-pro'); ?>
                        </div>
                        <div style="color: #1e3a8a; font-size: 0.9rem;">
                            <?php _e('You\'re building your subscriber base! Consider adding more widget types, optimizing your lead magnets, or promoting your newsletter on social media.', 'ai-newsletter-pro'); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (empty($widget_stats['popup'])): ?>
                    <div style="padding: 1rem; background: #f3e8ff; border-left: 4px solid #8b5cf6; border-radius: 0.5rem;">
                        <div style="font-weight: 600; color: #6b21a8; margin-bottom: 0.5rem;">
                            <?php _e('🎯 Try Popup Widgets', 'ai-newsletter-pro'); ?>
                        </div>
                        <div style="color: #581c87; font-size: 0.9rem;">
                            <?php _e('Popup widgets typically have the highest conversion rates. Consider creating a popup with exit-intent or scroll triggers.', 'ai-newsletter-pro'); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="padding: 1rem; background: #f9fafb; border-left: 4px solid #6b7280; border-radius: 0.5rem;">
                    <div style="font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        <?php _e('📊 Industry Benchmarks', 'ai-newsletter-pro'); ?>
                    </div>
                    <div style="color: #4b5563; font-size: 0.9rem;">
                        <?php _e('Average email signup conversion rates: Popup modals (2-4%), Inline forms (0.5-2%), Floating widgets (1-3%). Your performance may vary based on niche and audience.', 'ai-newsletter-pro'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateChart(chartType) {
    // Placeholder for chart update functionality
    console.log('Updating chart to show:', chartType);
    // In the full version, this would update the chart display
}

function refreshData() {
    // Show loading state
    const refreshBtn = event.target.closest('a');
    const originalContent = refreshBtn.innerHTML;
    refreshBtn.innerHTML = refreshBtn.innerHTML.replace('🔄', '⏳');
    
    // Simulate refresh
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

// Auto-refresh data every 5 minutes
setInterval(() => {
    // In the full version, this would fetch updated data via AJAX
    console.log('Auto-refreshing analytics data...');
}, 5 * 60 * 1000);

// Initialize tooltips and interactive elements
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to stat cards
    document.querySelectorAll('.ai-newsletter-stat-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
            this.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
        });
    });
    
    // Animate progress bars
    document.querySelectorAll('.ai-newsletter-progress-fill').forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 500);
    });
});
</script>

    <!-- Date Range Filters -->
    <div class="ai-newsletter-filters">
        <h3 style="margin: 0; color: #111827;"><?php _e('📊 Performance Overview', 'ai-newsletter-pro'); ?></h3>
        <div class="ai-newsletter-date-filters">
            <a href="<?php echo add_query_arg('range', '7'); ?>" 
               class="ai-newsletter-date-filter <?php echo $date_range === '7' ? 'active' : ''; ?>">
                <?php _e('Last 7 days', 'ai-newsletter-pro'); ?>
            </a>
            <a href="<?php echo add_query_arg('range', '30'); ?>" 
               class="ai-newsletter-date-filter <?php echo $date_range === '30' ? 'active' : ''; ?>">
                <?php _e('Last 30 days', 'ai-newsletter-pro'); ?>
            </a>
            <a href="<?php echo add_query_arg('range', '90'); ?>" 
               class="ai-newsletter-date-filter <?php echo $date_range === '90' ? 'active' : ''; ?>">
                <?php _e('Last 90 days', 'ai-newsletter-pro'); ?>
            </a>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="ai-newsletter-stats-overview">
        <div class="ai-newsletter-stat-card">
            <span class="ai-newsletter-stat-icon">👥</span>
            <div class="ai-newsletter-stat-number"><?php echo number_format($total_subscribers); ?></div>
            <div class="ai-newsletter-stat-label"><?php _e('Total Subscribers', 'ai-newsletter-pro'); ?></div>
            <div class="ai-newsletter-stat-change positive">
                <span>↗</span>
                <span><?php echo number_format($new_subscribers); ?> <?php _e('new this period', 'ai-newsletter-pro'); ?></span>
            </div>
        </div>

        <div class="ai-newsletter-stat-card">
            <span class="ai-newsletter-stat-icon">🎯</span>
            <div class="ai-newsletter-stat-number">
                <?php
                $total_impressions = array_sum(array_column($widget_stats, 'impressions'));
                $total_conversions = array_sum(array_column($widget_stats, 'conversions'));
                $overall_rate = $total_impressions > 0 ? ($total_conversions / $total_impressions) * 100 : 0;
                echo number_format($overall_rate, 1);
                ?>%
            </div>
            <div class="ai-newsletter-stat-label"><?php _e('Conversion Rate', 'ai-newsletter-pro'); ?></div>
            <div class="ai-newsletter-stat-change">
                <span>📈</span>
                <span><?php echo number_format($total_conversions); ?> <?php _e('total conversions', 'ai-newsletter-pro'); ?></span>
            </div>
        </div>

        <div class="ai-newsletter-stat-card">
            <span class="ai-newsletter-stat-icon">👁️</span>
            <div class="ai-newsletter-stat-number"><?php echo number_format($total_impressions); ?></div>
            <div class="ai-newsletter-stat-label"><?php _e('Widget Views', 'ai-newsletter-pro'); ?></div>
            <div class="ai-newsletter-stat-change">
                <span>📊</span>
                <span><?php echo count($widgets); ?> <?php _e('active widgets', 'ai-newsletter-pro'); ?></span>
            </div>
        </div>

        <div class="ai-newsletter-stat-card">
            <span class="ai-newsletter-stat-icon">📧</span>
            <div class="ai-newsletter-stat-number">
                <?php
                $campaigns_sent = $wpdb->get_var("SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_CAMPAIGNS_TABLE . " WHERE status = 'sent'");
                echo number_format($campaigns_sent);
                ?>
            </div>
            <div class="ai-newsletter-stat-label"><?php _e('Campaigns Sent', 'ai-newsletter-pro'); ?></div>
            <div class="ai-newsletter-stat-change">
                <span>✉️</span>
                <span><?php _e('email marketing', 'ai-newsletter-pro'); ?></span>
            </div>
        </div>
    </div>