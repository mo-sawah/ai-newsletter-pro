<?php
/**
 * Admin Subscribers Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current page and action
$current_action = $_GET['action'] ?? 'list';
$subscriber_id = $_GET['subscriber_id'] ?? 0;

// Handle actions
if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'ai_newsletter_pro_admin')) {
    switch ($_POST['action']) {
        case 'delete_subscriber':
            if (class_exists('AI_Newsletter_Pro_Subscriber_Manager')) {
                $subscriber_manager = new AI_Newsletter_Pro_Subscriber_Manager();
                $result = $subscriber_manager->delete_subscriber($subscriber_id);
                
                if ($result) {
                    echo '<div class="notice notice-success"><p>' . __('Subscriber deleted successfully.', 'ai-newsletter-pro') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Failed to delete subscriber.', 'ai-newsletter-pro') . '</p></div>';
                }
            }
            break;
            
        case 'bulk_delete':
            if (!empty($_POST['subscribers']) && class_exists('AI_Newsletter_Pro_Subscriber_Manager')) {
                $subscriber_manager = new AI_Newsletter_Pro_Subscriber_Manager();
                $deleted_count = 0;
                
                foreach ($_POST['subscribers'] as $id) {
                    if ($subscriber_manager->delete_subscriber(intval($id))) {
                        $deleted_count++;
                    }
                }
                
                echo '<div class="notice notice-success"><p>' . sprintf(__('%d subscribers deleted successfully.', 'ai-newsletter-pro'), $deleted_count) . '</p></div>';
            }
            break;
    }
}

// Get subscribers
global $wpdb;
$per_page = 50;
$current_page = max(1, intval($_GET['paged'] ?? 1));
$offset = ($current_page - 1) * $per_page;
$search = sanitize_text_field($_GET['s'] ?? '');
$status_filter = sanitize_text_field($_GET['status'] ?? 'all');

// Build query
$where_conditions = array('1=1');
$query_params = array();

if (!empty($search)) {
    $where_conditions[] = "(email LIKE %s OR name LIKE %s)";
    $search_term = '%' . $wpdb->esc_like($search) . '%';
    $query_params[] = $search_term;
    $query_params[] = $search_term;
}

if ($status_filter !== 'all') {
    $where_conditions[] = "status = %s";
    $query_params[] = $status_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE " . $where_clause;
if (!empty($query_params)) {
    $count_sql = $wpdb->prepare($count_sql, $query_params);
}
$total_subscribers = $wpdb->get_var($count_sql);

// Get subscribers for current page
$query_params[] = $per_page;
$query_params[] = $offset;
$sql = "SELECT * FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " 
        WHERE " . $where_clause . " 
        ORDER BY subscribed_at DESC 
        LIMIT %d OFFSET %d";

if (!empty($query_params)) {
    $sql = $wpdb->prepare($sql, $query_params);
}
$subscribers = $wpdb->get_results($sql);

// Calculate pagination
$total_pages = ceil($total_subscribers / $per_page);

// Get status counts
$status_counts = array(
    'all' => $wpdb->get_var("SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE),
    'subscribed' => $wpdb->get_var("SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE status = 'subscribed'"),
    'pending' => $wpdb->get_var("SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE status = 'pending'"),
    'unsubscribed' => $wpdb->get_var("SELECT COUNT(*) FROM " . AI_NEWSLETTER_PRO_SUBSCRIBERS_TABLE . " WHERE status = 'unsubscribed'"),
);
?>

<style>
.ai-newsletter-subscribers {
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

.ai-newsletter-toolbar {
    background: white;
    padding: 1.5rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.ai-newsletter-search-form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.ai-newsletter-search-form input {
    padding: 0.5rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    min-width: 250px;
}

.ai-newsletter-status-filters {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.ai-newsletter-status-filter {
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    border: 2px solid transparent;
    border-radius: 0.5rem;
    text-decoration: none;
    color: #374151;
    font-weight: 500;
    transition: all 0.2s;
}

.ai-newsletter-status-filter.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.ai-newsletter-status-filter:hover {
    background: #e5e7eb;
    color: #111827;
}

.ai-newsletter-status-filter.active:hover {
    background: #5a67d8;
    color: white;
}

.ai-newsletter-table-container {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.ai-newsletter-table {
    width: 100%;
    border-collapse: collapse;
}

.ai-newsletter-table th,
.ai-newsletter-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.ai-newsletter-table th {
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
    position: sticky;
    top: 0;
}

.ai-newsletter-table tbody tr:hover {
    background: #f9fafb;
}

.ai-newsletter-status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.ai-newsletter-status-badge.subscribed {
    background: #dcfce7;
    color: #166534;
}

.ai-newsletter-status-badge.pending {
    background: #fef3c7;
    color: #92400e;
}

.ai-newsletter-status-badge.unsubscribed {
    background: #fee2e2;
    color: #991b1b;
}

.ai-newsletter-actions {
    display: flex;
    gap: 0.5rem;
}

.ai-newsletter-action-btn {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}

.ai-newsletter-action-btn.edit {
    background: #dbeafe;
    color: #1e40af;
}

.ai-newsletter-action-btn.delete {
    background: #fee2e2;
    color: #991b1b;
}

.ai-newsletter-action-btn:hover {
    transform: translateY(-1px);
}

.ai-newsletter-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.ai-newsletter-pagination a,
.ai-newsletter-pagination span {
    padding: 0.5rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.25rem;
    text-decoration: none;
    color: #374151;
}

.ai-newsletter-pagination .current {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.ai-newsletter-bulk-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1rem;
}

.ai-newsletter-no-subscribers {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}

.ai-newsletter-no-subscribers h3 {
    margin-bottom: 1rem;
    color: #374151;
}

@media (max-width: 768px) {
    .ai-newsletter-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .ai-newsletter-search-form input {
        min-width: auto;
        width: 100%;
    }
    
    .ai-newsletter-table-container {
        overflow-x: auto;
    }
}
</style>

<div class="ai-newsletter-subscribers">
    <div class="ai-newsletter-header">
        <h1><?php _e('Subscribers', 'ai-newsletter-pro'); ?></h1>
        <p><?php printf(__('Manage your %s subscribers', 'ai-newsletter-pro'), number_format($total_subscribers)); ?></p>
    </div>

    <!-- Status Filters -->
    <div class="ai-newsletter-status-filters">
        <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-subscribers'); ?>" 
           class="ai-newsletter-status-filter <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
            <?php printf(__('All (%s)', 'ai-newsletter-pro'), number_format($status_counts['all'])); ?>
        </a>
        <a href="<?php echo add_query_arg('status', 'subscribed'); ?>" 
           class="ai-newsletter-status-filter <?php echo $status_filter === 'subscribed' ? 'active' : ''; ?>">
            <?php printf(__('Subscribed (%s)', 'ai-newsletter-pro'), number_format($status_counts['subscribed'])); ?>
        </a>
        <a href="<?php echo add_query_arg('status', 'pending'); ?>" 
           class="ai-newsletter-status-filter <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
            <?php printf(__('Pending (%s)', 'ai-newsletter-pro'), number_format($status_counts['pending'])); ?>
        </a>
        <a href="<?php echo add_query_arg('status', 'unsubscribed'); ?>" 
           class="ai-newsletter-status-filter <?php echo $status_filter === 'unsubscribed' ? 'active' : ''; ?>">
            <?php printf(__('Unsubscribed (%s)', 'ai-newsletter-pro'), number_format($status_counts['unsubscribed'])); ?>
        </a>
    </div>

    <!-- Toolbar -->
    <div class="ai-newsletter-toolbar">
        <form method="get" class="ai-newsletter-search-form">
            <input type="hidden" name="page" value="ai-newsletter-pro-subscribers">
            <?php if ($status_filter !== 'all'): ?>
                <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
            <?php endif; ?>
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                   placeholder="<?php _e('Search subscribers...', 'ai-newsletter-pro'); ?>">
            <button type="submit" class="button"><?php _e('Search', 'ai-newsletter-pro'); ?></button>
        </form>
        
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-subscribers&action=import'); ?>" class="button">
                <?php _e('📤 Import', 'ai-newsletter-pro'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-subscribers&action=export'); ?>" class="button">
                <?php _e('📥 Export', 'ai-newsletter-pro'); ?>
            </a>
        </div>
    </div>

    <?php if (!empty($subscribers)): ?>
        <!-- Bulk Actions -->
        <form method="post" id="bulk-action-form">
            <?php wp_nonce_field('ai_newsletter_pro_admin'); ?>
            <input type="hidden" name="action" value="bulk_delete">
            
            <div class="ai-newsletter-bulk-actions">
                <label>
                    <input type="checkbox" id="select-all"> <?php _e('Select All', 'ai-newsletter-pro'); ?>
                </label>
                <button type="submit" class="button" onclick="return confirm('<?php _e('Are you sure you want to delete selected subscribers?', 'ai-newsletter-pro'); ?>')">
                    <?php _e('Delete Selected', 'ai-newsletter-pro'); ?>
                </button>
            </div>

            <!-- Subscribers Table -->
            <div class="ai-newsletter-table-container">
                <table class="ai-newsletter-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all-header"></th>
                            <th><?php _e('Email', 'ai-newsletter-pro'); ?></th>
                            <th><?php _e('Name', 'ai-newsletter-pro'); ?></th>
                            <th><?php _e('Status', 'ai-newsletter-pro'); ?></th>
                            <th><?php _e('Source', 'ai-newsletter-pro'); ?></th>
                            <th><?php _e('Subscribed Date', 'ai-newsletter-pro'); ?></th>
                            <th><?php _e('Actions', 'ai-newsletter-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscribers as $subscriber): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="subscribers[]" value="<?php echo $subscriber->id; ?>" class="subscriber-checkbox">
                                </td>
                                <td>
                                    <strong><?php echo esc_html($subscriber->email); ?></strong>
                                </td>
                                <td><?php echo esc_html($subscriber->name ?: '—'); ?></td>
                                <td>
                                    <span class="ai-newsletter-status-badge <?php echo esc_attr($subscriber->status); ?>">
                                        <?php echo esc_html(ucfirst($subscriber->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($subscriber->source ?: 'Unknown'); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscriber->subscribed_at))); ?></td>
                                <td>
                                    <div class="ai-newsletter-actions">
                                        <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-subscribers&action=edit&subscriber_id=' . $subscriber->id); ?>" 
                                           class="ai-newsletter-action-btn edit">
                                            <?php _e('Edit', 'ai-newsletter-pro'); ?>
                                        </a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ai-newsletter-pro-subscribers&action=delete&subscriber_id=' . $subscriber->id), 'ai_newsletter_pro_admin'); ?>" 
                                           class="ai-newsletter-action-btn delete"
                                           onclick="return confirm('<?php _e('Are you sure you want to delete this subscriber?', 'ai-newsletter-pro'); ?>')">
                                            <?php _e('Delete', 'ai-newsletter-pro'); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="ai-newsletter-pagination">
                <?php
                $pagination_args = array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo; ' . __('Previous', 'ai-newsletter-pro'),
                    'next_text' => __('Next', 'ai-newsletter-pro') . ' &raquo;',
                    'total' => $total_pages,
                    'current' => $current_page
                );
                
                echo paginate_links($pagination_args);
                ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- No Subscribers -->
        <div class="ai-newsletter-table-container">
            <div class="ai-newsletter-no-subscribers">
                <h3><?php _e('No subscribers found', 'ai-newsletter-pro'); ?></h3>
                <?php if (empty($search) && $status_filter === 'all'): ?>
                    <p><?php _e('Start collecting email addresses with your newsletter widgets!', 'ai-newsletter-pro'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-widgets'); ?>" class="button button-primary">
                        <?php _e('Create Your First Widget', 'ai-newsletter-pro'); ?>
                    </a>
                <?php else: ?>
                    <p><?php _e('No subscribers match your current filters.', 'ai-newsletter-pro'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=ai-newsletter-pro-subscribers'); ?>" class="button">
                        <?php _e('Clear Filters', 'ai-newsletter-pro'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Select all functionality
    $('#select-all, #select-all-header').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.subscriber-checkbox').prop('checked', isChecked);
        $('#select-all, #select-all-header').prop('checked', isChecked);
    });
    
    // Individual checkbox change
    $('.subscriber-checkbox').on('change', function() {
        const totalCheckboxes = $('.subscriber-checkbox').length;
        const checkedCheckboxes = $('.subscriber-checkbox:checked').length;
        
        $('#select-all, #select-all-header').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
    
    // Bulk action form validation
    $('#bulk-action-form').on('submit', function() {
        if ($('.subscriber-checkbox:checked').length === 0) {
            alert('<?php _e('Please select at least one subscriber.', 'ai-newsletter-pro'); ?>');
            return false;
        }
    });
});
</script>