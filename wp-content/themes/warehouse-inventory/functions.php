<?php
/**
 * Warehouse Inventory Management Theme Functions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Theme setup
function warehouse_inventory_setup() {
    // Add theme support for various features
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'warehouse-inventory'),
        'footer' => __('Footer Menu', 'warehouse-inventory'),
    ));
}
add_action('after_setup_theme', 'warehouse_inventory_setup');

// Enqueue styles and scripts
function warehouse_inventory_scripts() {
    // Enqueue main theme styles (higher priority to override plugin styles)
    wp_enqueue_style('warehouse-inventory-style', get_stylesheet_uri(), array(), '2.0.0');
    wp_enqueue_style('warehouse-inventory-assets', get_template_directory_uri() . '/assets/css/style.css', array(), '2.6.0');
    wp_enqueue_script('warehouse-inventory-script', get_template_directory_uri() . '/assets/js/warehouse.js', array('jquery'), '1.0.0', true);
    
    // Localize script for AJAX
    wp_localize_script('warehouse-inventory-script', 'warehouse_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('warehouse_nonce'),
        'current_user_id' => get_current_user_id(),
    ));
}
add_action('wp_enqueue_scripts', 'warehouse_inventory_scripts');

// Remove custom database tables creation from theme activation
function warehouse_inventory_remove_create_tables() {
    remove_action('after_switch_theme', 'warehouse_inventory_create_tables');
}
add_action('after_switch_theme', 'warehouse_inventory_remove_create_tables', 1);

// Remove database migration function from theme
function warehouse_inventory_remove_migrate_database() {
    remove_action('after_switch_theme', 'warehouse_inventory_migrate_database');
    remove_action('admin_init', 'warehouse_inventory_migrate_database');
}
add_action('after_switch_theme', 'warehouse_inventory_remove_migrate_database', 1);

// AJAX handlers are now handled by the plugin

// Get dashboard stats
function get_dashboard_stats() {
    global $wpdb;
    
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wh_inventory_items");
    $in_stock = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wh_inventory_items WHERE status = 'in-stock'");
    $low_stock = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wh_inventory_items WHERE quantity <= min_stock_level AND quantity > 0");
    $out_of_stock = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wh_inventory_items WHERE quantity = 0");
    $total_value = $wpdb->get_var("SELECT SUM(quantity * purchase_price) FROM {$wpdb->prefix}wh_inventory_items");
    
    $sales_today = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(total_amount) FROM {$wpdb->prefix}wh_sales WHERE DATE(sale_date) = %s",
        current_time('Y-m-d')
    ));
    
    $pending_tasks = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wh_tasks WHERE status = 'pending'");
    $categories_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wh_categories");
    
    return array(
        'total_items' => intval($total_items),
        'in_stock' => intval($in_stock),
        'low_stock' => intval($low_stock),
        'out_of_stock' => intval($out_of_stock),
        'total_value' => floatval($total_value),
        'sales_today' => floatval($sales_today),
        'pending_tasks' => intval($pending_tasks),
        'categories_count' => intval($categories_count),
    );
}

// Get all categories with hierarchical structure
function get_all_categories() {
    global $wpdb;
    $categories = $wpdb->get_results("
        SELECT c.*, 
               (SELECT COUNT(*) FROM {$wpdb->prefix}wh_inventory_items WHERE category_id = c.id) as item_count,
               parent.name as parent_name
        FROM {$wpdb->prefix}wh_categories c
        LEFT JOIN {$wpdb->prefix}wh_categories parent ON c.parent_id = parent.id
        WHERE c.is_active = 1
        ORDER BY c.parent_id IS NULL DESC, c.parent_id, c.sort_order, c.name
    ");
    return $categories ?: array();
}

// Get categories as hierarchical tree
function get_categories_tree($parent_id = null) {
    global $wpdb;
    
    $where = $parent_id ? "parent_id = $parent_id" : "parent_id IS NULL";
    
    $categories = $wpdb->get_results("
        SELECT c.*, 
               (SELECT COUNT(*) FROM {$wpdb->prefix}wh_inventory_items WHERE category_id = c.id) as item_count
        FROM {$wpdb->prefix}wh_categories c
        WHERE c.is_active = 1 AND $where
        ORDER BY c.sort_order, c.name
    ");
    
    foreach ($categories as &$category) {
        $category->children = get_categories_tree($category->id);
    }
    
    return $categories ?: array();
}

// Get category breadcrumb path
function get_category_path($category_id) {
    global $wpdb;
    
    $path = array();
    $current_id = $category_id;
    
    while ($current_id) {
        $category = $wpdb->get_row($wpdb->prepare(
            "SELECT id, name, parent_id FROM {$wpdb->prefix}wh_categories WHERE id = %d",
            $current_id
        ));
        
        if ($category) {
            array_unshift($path, $category);
            $current_id = $category->parent_id;
        } else {
            break;
        }
    }
    
    return $path;
}

// Get all locations
function get_all_locations() {
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wh_locations ORDER BY level, name");
}

// Get locations as hierarchical tree
function get_locations_tree($parent_id = null) {
    global $wpdb;
    
    $where = $parent_id ? "parent_id = $parent_id" : "(parent_id IS NULL OR parent_id = 0)";
    
    $locations = $wpdb->get_results("
        SELECT l.*,
               (SELECT COUNT(*) FROM {$wpdb->prefix}wh_inventory_items WHERE location_id = l.id) as item_count
        FROM {$wpdb->prefix}wh_locations l
        WHERE $where
        ORDER BY l.level, l.name
    ");
    
    foreach ($locations as &$location) {
        $location->children = get_locations_tree($location->id);
    }
    
    return $locations ?: array();
}

// Get location breadcrumb path
function get_location_path($location_id) {
    global $wpdb;
    
    $path = array();
    $current_id = $location_id;
    
    while ($current_id) {
        $location = $wpdb->get_row($wpdb->prepare(
            "SELECT id, name, parent_id FROM {$wpdb->prefix}wh_locations WHERE id = %d",
            $current_id
        ));
        
        if ($location) {
            array_unshift($path, $location);
            $current_id = $location->parent_id;
        } else {
            break;
        }
    }
    
    return $path;
}

// Get all tasks
function get_all_tasks() {
    global $wpdb;
    $tasks = $wpdb->get_results("
        SELECT t.*, u.display_name as assigned_to_name 
        FROM {$wpdb->prefix}wh_tasks t
        LEFT JOIN {$wpdb->prefix}users u ON t.assigned_to = u.ID
        ORDER BY t.created_at DESC
    ");
    return $tasks ?: array();
}

// Get task history
function get_task_history() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wh_task_history';
    $six_months_ago = date('Y-m-d H:i:s', strtotime('-6 months'));
    
    // Check if table exists
    if (!$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name))) {
        return array();
    }
    
    // Try old schema first (most likely to work) - only get last 6 months
    $history = $wpdb->get_results($wpdb->prepare("
        SELECT h.*, 
               h.title as task_title, 
               COALESCE(u.display_name, 'System') as user_name,
               COALESCE(u.display_name, 'System') as assigned_to_name,
               'completed' as action,
               h.completed_at as created_at
        FROM {$wpdb->prefix}wh_task_history h
        LEFT JOIN {$wpdb->prefix}users u ON h.completed_by = u.ID
        WHERE h.completed_at >= %s OR (h.completed_at IS NULL AND h.created_at >= %s)
        ORDER BY h.completed_at DESC
        LIMIT 50
    ", $six_months_ago, $six_months_ago));
    
    // If old schema query worked, return results
    if (!$wpdb->last_error && $history !== false) {
        return $history ?: array();
    }
    
    // Clear the error and try new schema - only get last 6 months
    $wpdb->last_error = '';
    
    $history = $wpdb->get_results($wpdb->prepare("
        SELECT h.*, 
               t.title as task_title, 
               COALESCE(u.display_name, 'System') as user_name,
               COALESCE(u.display_name, 'System') as assigned_to_name,
               COALESCE(h.action, 'completed') as action,
               h.created_at
        FROM {$wpdb->prefix}wh_task_history h
        LEFT JOIN {$wpdb->prefix}wh_tasks t ON h.task_id = t.id
        LEFT JOIN {$wpdb->prefix}users u ON h.user_id = u.ID
        WHERE h.created_at >= %s
        ORDER BY h.created_at DESC
        LIMIT 50
    ", $six_months_ago));
    
    // If new schema worked, return results
    if (!$wpdb->last_error && $history !== false) {
        return $history ?: array();
    }
    
    // Clear error and try minimal fallback - only get last 6 months
    $wpdb->last_error = '';
    
    $history = $wpdb->get_results($wpdb->prepare("
        SELECT h.*, 
               COALESCE(h.title, 'Unknown Task') as task_title,
               'System' as user_name,
               'System' as assigned_to_name,
               'completed' as action,
               COALESCE(h.completed_at, h.created_at) as created_at
        FROM {$wpdb->prefix}wh_task_history h
        WHERE (h.completed_at >= %s OR (h.completed_at IS NULL AND h.created_at >= %s))
        ORDER BY COALESCE(h.completed_at, h.created_at) DESC
        LIMIT 50
    ", $six_months_ago, $six_months_ago));
    
    return $history ?: array();
}

// Get team members
function get_team_members() {
    global $wpdb;
    $members = $wpdb->get_results("
        SELECT u.ID, u.display_name, u.user_email, u.user_registered,
               um.meta_value as role_name,
               um2.meta_value as department,
               um3.meta_value as phone
        FROM {$wpdb->prefix}users u
        LEFT JOIN {$wpdb->prefix}usermeta um ON u.ID = um.user_id AND um.meta_key = 'wp_capabilities'
        LEFT JOIN {$wpdb->prefix}usermeta um2 ON u.ID = um2.user_id AND um2.meta_key = 'department'
        LEFT JOIN {$wpdb->prefix}usermeta um3 ON u.ID = um3.user_id AND um3.meta_key = 'phone'
        WHERE um.meta_value LIKE '%warehouse%'
        ORDER BY u.display_name
    ");
    return $members ?: array();
}

// Custom user roles for warehouse management
function add_warehouse_roles() {
    add_role('warehouse_manager', 'Warehouse Manager', array(
        'read' => true,
        'edit_posts' => true,
        'delete_posts' => true,
        'manage_warehouse' => true,
        'view_warehouse' => true,
        'edit_inventory' => true,
        'delete_inventory' => true,
        'manage_team' => true,
    ));
    
    add_role('warehouse_employee', 'Warehouse Employee', array(
        'read' => true,
        'view_warehouse' => true,
        'edit_inventory' => true,
    ));
}
add_action('after_switch_theme', 'add_warehouse_roles');
add_action('init', 'add_warehouse_roles'); // Also run on init to ensure roles exist

// Remove warehouse roles on theme deactivation
function remove_warehouse_roles() {
    remove_role('warehouse_manager');
    remove_role('warehouse_employee');
}
add_action('switch_theme', 'remove_warehouse_roles');

// Custom capabilities
function add_warehouse_capabilities() {
    $manager = get_role('warehouse_manager');
    $employee = get_role('warehouse_employee');
    $admin = get_role('administrator');
    
    if ($manager) {
        $manager->add_cap('manage_warehouse');
        $manager->add_cap('view_warehouse');
        $manager->add_cap('edit_inventory');
        $manager->add_cap('delete_inventory');
    }
    
    if ($employee) {
        $employee->add_cap('view_warehouse');
        $employee->add_cap('edit_inventory');
    }
    
    if ($admin) {
        $admin->add_cap('manage_warehouse');
        $admin->add_cap('view_warehouse');
        $admin->add_cap('edit_inventory');
        $admin->add_cap('delete_inventory');
    }
}
add_action('after_switch_theme', 'add_warehouse_capabilities');

// Track user login times for team management
function track_user_login($user_login, $user) {
    global $wpdb;
    
    // Update last login in team members table
    $wpdb->update(
        $wpdb->prefix . 'wh_team_members',
        array('last_login' => current_time('mysql')),
        array('user_id' => $user->ID)
    );
}
add_action('wp_login', 'track_user_login', 10, 2);

// Ensure team member record exists when user is created
function sync_team_member_on_user_creation($user_id) {
    global $wpdb;
    
    $user = get_user_by('id', $user_id);
    if (!$user) return;
    
    // Check if already exists in team table
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}wh_team_members WHERE user_id = %d",
        $user_id
    ));
    
    if (!$exists) {
        $wpdb->insert(
            $wpdb->prefix . 'wh_team_members',
            array(
                'user_id' => $user_id,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->roles[0] ?? 'warehouse_employee',
                'status' => 'active',
                'created_by' => get_current_user_id()
            )
        );
    }
}
add_action('user_register', 'sync_team_member_on_user_creation');

// Restrict access to non-warehouse users
function restrict_warehouse_access() {
    if (is_admin() && !current_user_can('administrator')) {
        return;
    }
    
    // Check if we're on the warehouse page
    if (is_page() && get_post()->post_name === 'warehouse') {
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
        
        if (!current_user_can('view_warehouse') && !current_user_can('manage_options')) {
            wp_die('You do not have permission to access this page.');
        }
    }
}
add_action('template_redirect', 'restrict_warehouse_access');

// Team management handlers are in the plugin

// AJAX Handlers for Tasks Management
add_action('wp_ajax_add_task', 'handle_add_task');
function handle_add_task() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    global $wpdb;
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'wh_tasks',
        array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'priority' => sanitize_text_field($_POST['priority']),
            'assigned_to' => intval($_POST['assigned_to']),
            'due_date' => sanitize_text_field($_POST['due_date']),
            'status' => 'pending',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        )
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to create task');
    }
    
    wp_send_json_success('Task created successfully');
}

add_action('wp_ajax_delete_task', 'handle_delete_task');
function handle_delete_task() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    global $wpdb;
    
    $task_id = intval($_POST['task_id']);
    
    $result = $wpdb->delete(
        $wpdb->prefix . 'wh_tasks',
        array('id' => $task_id),
        array('%d')
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to delete task');
    }
    
    wp_send_json_success('Task deleted successfully');
}

add_action('wp_ajax_update_task_status', 'handle_update_task_status');
function handle_update_task_status() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    global $wpdb;
    
    $task_id = intval($_POST['task_id']);
    $status = sanitize_text_field($_POST['status']);
    
    // Validate status
    $valid_statuses = array('pending', 'in_progress', 'completed', 'archived');
    if (!in_array($status, $valid_statuses)) {
        wp_send_json_error('Invalid status');
    }
    
    $result = $wpdb->update(
        $wpdb->prefix . 'wh_tasks',
        array(
            'status' => $status,
            'updated_at' => current_time('mysql')
        ),
        array('id' => $task_id),
        array('%s', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to update task status');
    }
    
    // Skip adding to task history for status changes
    // Only add to history when task is completed and moved to archive
    
    wp_send_json_success('Task status updated successfully');
}

add_action('wp_ajax_move_task_to_history', 'handle_move_task_to_history');
function handle_move_task_to_history() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    global $wpdb;
    
    $task_id = intval($_POST['task_id']);
    
    // Archive the task
    $result = $wpdb->update(
        $wpdb->prefix . 'wh_tasks',
        array(
            'status' => 'archived',
            'completed_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array('id' => $task_id),
        array('%s', '%s', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to archive task');
    }
    
    // Get task details for history
    $task = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wh_tasks WHERE id = %d", $task_id));
    
    if ($task) {
        // Add to task history using current schema
        $history_data = array(
            'original_task_id' => $task_id,
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority,
            'assigned_to' => $task->assigned_to,
            'created_by' => $task->created_by,
            'completed_by' => get_current_user_id(),
            'due_date' => $task->due_date,
            'created_at' => $task->created_at,
            'completed_at' => current_time('mysql'),
            'completion_notes' => 'Task archived from completed status'
        );
        
        $wpdb->insert(
            $wpdb->prefix . 'wh_task_history',
            $history_data
        );
    }
    
    wp_send_json_success('Task archived successfully');
}

add_action('wp_ajax_get_task_history', 'handle_get_task_history');
function handle_get_task_history() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Periodically clean up old data (10% chance on each call)
    if (rand(1, 10) === 1) {
        cleanup_old_task_history();
    }
    
    try {
        $history = get_task_history();
        
        // Transform the data to match what JavaScript expects
        $formatted_history = array();
        if ($history) {
            foreach ($history as $item) {
                $formatted_history[] = array(
                    'id' => $item->id ?? '',
                    'title' => $item->task_title ?? $item->title ?? 'Unknown Task',
                    'description' => $item->description ?? '',
                    'priority' => $item->priority ?? 'medium',
                    'assigned_to_name' => $item->user_name ?? 'System',
                    'completed_at' => $item->created_at ?? $item->completed_at ?? '',
                    'created_at' => $item->created_at ?? $item->completed_at ?? ''
                );
            }
        }
        
        wp_send_json_success($formatted_history);
        
    } catch (Exception $e) {
        error_log('Task history error: ' . $e->getMessage());
        wp_send_json_error('Failed to load task history: ' . $e->getMessage());
    }
}

// Team Chat Functions
add_action('wp_ajax_get_chat_messages', 'handle_get_chat_messages');
function handle_get_chat_messages() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    global $wpdb;
    
    // Create table if it doesn't exist
    create_chat_table();
    
    // Periodically clean up old data (10% chance on each call)
    if (rand(1, 10) === 1) {
        cleanup_old_chat_messages();
    }
    
    // Only get messages from the last 6 months
    $six_months_ago = date('Y-m-d H:i:s', strtotime('-6 months'));
    
    $messages = $wpdb->get_results($wpdb->prepare("
        SELECT cm.*, u.display_name as user_name 
        FROM {$wpdb->prefix}wh_chat_messages cm
        LEFT JOIN {$wpdb->prefix}users u ON cm.user_id = u.ID
        WHERE cm.created_at >= %s
        ORDER BY cm.created_at ASC
        LIMIT 50
    ", $six_months_ago));
    
    if ($wpdb->last_error) {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    }
    
    wp_send_json_success($messages ?: array());
}

add_action('wp_ajax_send_chat_message', 'handle_send_chat_message');
function handle_send_chat_message() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $message = sanitize_textarea_field($_POST['message']);
    if (empty($message)) {
        wp_send_json_error('Message cannot be empty');
    }
    
    global $wpdb;
    
    // Create table if it doesn't exist
    create_chat_table();
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'wh_chat_messages',
        array(
            'user_id' => get_current_user_id(),
            'message' => $message,
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s')
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to save message: ' . $wpdb->last_error);
    }
    
    wp_send_json_success('Message sent successfully');
}

function create_chat_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wh_chat_messages';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        message text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Data Retention Functions - Keep data for 6 months only
function cleanup_old_data() {
    cleanup_old_task_history();
    cleanup_old_chat_messages();
}

function cleanup_old_task_history() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wh_task_history';
    
    // Delete records older than 6 months
    $six_months_ago = date('Y-m-d H:i:s', strtotime('-6 months'));
    
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM $table_name WHERE completed_at < %s OR (completed_at IS NULL AND created_at < %s)",
        $six_months_ago,
        $six_months_ago
    ));
    
    if ($deleted !== false) {
        error_log("Cleaned up $deleted old task history records older than 6 months");
    }
    
    return $deleted;
}

function cleanup_old_chat_messages() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'wh_chat_messages';
    
    // Check if table exists
    if (!$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name))) {
        return 0;
    }
    
    // Delete messages older than 6 months
    $six_months_ago = date('Y-m-d H:i:s', strtotime('-6 months'));
    
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM $table_name WHERE created_at < %s",
        $six_months_ago
    ));
    
    if ($deleted !== false) {
        error_log("Cleaned up $deleted old chat messages older than 6 months");
    }
    
    return $deleted;
}

// Schedule cleanup to run daily
add_action('wp', 'schedule_data_cleanup');
function schedule_data_cleanup() {
    if (!wp_next_scheduled('warehouse_daily_cleanup')) {
        wp_schedule_event(time(), 'daily', 'warehouse_daily_cleanup');
    }
}

add_action('warehouse_daily_cleanup', 'cleanup_old_data');

// Clean up on theme switch to ensure we don't leave scheduled events
add_action('switch_theme', 'unschedule_data_cleanup');
function unschedule_data_cleanup() {
    wp_clear_scheduled_hook('warehouse_daily_cleanup');
}

// Manual cleanup AJAX handler for admin
add_action('wp_ajax_manual_cleanup_data', 'handle_manual_cleanup_data');
function handle_manual_cleanup_data() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $task_history_deleted = cleanup_old_task_history();
    $chat_messages_deleted = cleanup_old_chat_messages();
    
    $message = sprintf(
        'Cleanup completed. Deleted %d old task history records and %d old chat messages (older than 6 months).',
        $task_history_deleted,
        $chat_messages_deleted
    );
    
    wp_send_json_success($message);
}

// AJAX Handlers for Categories Management
add_action('wp_ajax_add_category', 'handle_add_category');
function handle_add_category() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    global $wpdb;
    
    $name = sanitize_text_field($_POST['name']);
    $slug = sanitize_title($name);
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $color = sanitize_hex_color($_POST['color'] ?? '#3b82f6');
    $parent_id = intval($_POST['parent_id'] ?? 0);
    $icon = sanitize_text_field($_POST['icon'] ?? 'tag');
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'wh_categories',
        array(
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'color' => $color,
            'parent_id' => $parent_id > 0 ? $parent_id : null,
            'icon' => $icon,
            'is_active' => 1,
            'sort_order' => 0
        ),
        array('%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d')
    );
    
    if ($result === false) {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    }
    
    wp_send_json_success('Category created successfully');
}

add_action('wp_ajax_update_category', 'handle_update_category');
function handle_update_category() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    global $wpdb;
    
    $category_id = intval($_POST['category_id']);
    $name = sanitize_text_field($_POST['name']);
    $slug = sanitize_title($name);
    $description = sanitize_textarea_field($_POST['description']);
    $color = sanitize_hex_color($_POST['color']);
    $parent_id = intval($_POST['parent_id']);
    $icon = sanitize_text_field($_POST['icon'] ?? 'tag');
    
    // Prevent self-reference and circular references
    if ($parent_id == $category_id) {
        wp_send_json_error('Category cannot be its own parent');
    }
    
    // Check for circular reference
    if ($parent_id > 0) {
        $path = get_category_path($parent_id);
        foreach ($path as $ancestor) {
            if ($ancestor->id == $category_id) {
                wp_send_json_error('Cannot create circular reference');
            }
        }
    }
    
    $result = $wpdb->update(
        $wpdb->prefix . 'wh_categories',
        array(
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'color' => $color,
            'parent_id' => $parent_id > 0 ? $parent_id : null,
            'icon' => $icon
        ),
        array('id' => $category_id),
        array('%s', '%s', '%s', '%s', '%d', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to update category');
    }
    
    wp_send_json_success('Category updated successfully');
}

add_action('wp_ajax_delete_category', 'handle_delete_category');
function handle_delete_category() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    global $wpdb;
    
    $category_id = intval($_POST['category_id']);
    
    // Check if category has items
    $item_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wh_inventory_items WHERE category_id = %d",
        $category_id
    ));
    
    if ($item_count > 0) {
        wp_send_json_error('Cannot delete category with items. Please move items to another category first.');
    }
    
    // Check if category has subcategories
    $subcategory_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wh_categories WHERE parent_id = %d AND is_active = 1",
        $category_id
    ));
    
    if ($subcategory_count > 0) {
        wp_send_json_error('Cannot delete category with subcategories. Please move or delete subcategories first.');
    }
    
    // Soft delete by updating is_active status
    $result = $wpdb->update(
        $wpdb->prefix . 'wh_categories',
        array('is_active' => 0),
        array('id' => $category_id),
        array('%d'),
        array('%d')
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to delete category');
    }
    
    wp_send_json_success('Category deleted successfully');
}

add_action('wp_ajax_get_category_data', 'handle_get_category_data');
function handle_get_category_data() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    global $wpdb;
    
    $category_id = intval($_POST['category_id']);
    $category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wh_categories WHERE id = %d", $category_id));
    
    if (!$category) {
        wp_send_json_error('Category not found');
    }
    
    wp_send_json_success($category);
}

// AJAX Handlers for Locations Management
add_action('wp_ajax_add_location', 'handle_add_location');
function handle_add_location() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    global $wpdb;
    
    $name = sanitize_text_field($_POST['name']);
    $code = sanitize_text_field($_POST['code']);
    $parent_id = intval($_POST['parent_id']);
    
    // Calculate level and path
    $level = 1;
    $full_path = $name;
    
    if ($parent_id > 0) {
        $parent = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wh_locations WHERE id = %d", $parent_id));
        if ($parent) {
            $level = $parent->level + 1;
            $full_path = $parent->full_path . ' > ' . $name;
        }
    }
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'wh_locations',
        array(
            'name' => $name,
            'code' => $code,
            'description' => sanitize_textarea_field($_POST['description']),
            'type' => sanitize_text_field($_POST['type']),
            'parent_id' => $parent_id ?: null,
            'level' => $level,
            'full_path' => $full_path,
            'created_at' => current_time('mysql')
        )
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to create location');
    }
    
    wp_send_json_success('Location created successfully');
}

add_action('wp_ajax_update_location', 'handle_update_location');
function handle_update_location() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    global $wpdb;
    
    $location_id = intval($_POST['location_id']);
    $name = sanitize_text_field($_POST['name']);
    $code = sanitize_text_field($_POST['code']);
    $parent_id = intval($_POST['parent_id']);
    
    // Calculate level and path
    $level = 1;
    $full_path = $name;
    
    if ($parent_id > 0) {
        $parent = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wh_locations WHERE id = %d", $parent_id));
        if ($parent) {
            $level = $parent->level + 1;
            $full_path = $parent->full_path . ' > ' . $name;
        }
    }
    
    $result = $wpdb->update(
        $wpdb->prefix . 'wh_locations',
        array(
            'name' => $name,
            'code' => $code,
            'description' => sanitize_textarea_field($_POST['description']),
            'type' => sanitize_text_field($_POST['type']),
            'parent_id' => $parent_id ?: null,
            'level' => $level,
            'full_path' => $full_path,
            'updated_at' => current_time('mysql')
        ),
        array('id' => $location_id),
        array('%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to update location');
    }
    
    wp_send_json_success('Location updated successfully');
}

add_action('wp_ajax_get_location_data', 'handle_get_location_data');
function handle_get_location_data() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    global $wpdb;
    
    $location_id = intval($_POST['location_id']);
    $location = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wh_locations WHERE id = %d", $location_id));
    
    if (!$location) {
        wp_send_json_error('Location not found');
    }
    
    wp_send_json_success($location);
}

// Get hierarchical categories for select dropdowns
function get_hierarchical_categories_for_select($selected_id = null, $exclude_id = null) {
    global $wpdb;
    
    // Check if is_active column exists
    $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}wh_categories LIKE 'is_active'");
    $where_clause = !empty($columns) ? "WHERE c.is_active = 1" : "";
    
    $categories = $wpdb->get_results("
        SELECT c.*, 
               parent.name as parent_name
        FROM {$wpdb->prefix}wh_categories c
        LEFT JOIN {$wpdb->prefix}wh_categories parent ON c.parent_id = parent.id
        {$where_clause}
        ORDER BY c.parent_id IS NULL DESC, c.parent_id, 
                 " . (!empty($columns) ? "c.sort_order," : "") . " c.name
    ");
    
    if (!$categories) {
        return '<option value="">No categories available</option>';
    }
    
    $options = '<option value="">Select Category</option>';
    
    foreach ($categories as $category) {
        // Skip excluded category (used when editing to prevent circular references)
        if ($exclude_id && $category->id == $exclude_id) {
            continue;
        }
        
        $indent = '';
        $display_name = $category->name;
        
        // Add visual hierarchy indication
        if ($category->parent_id) {
            $indent = '&nbsp;&nbsp;&nbsp;&nbsp;↳ ';
            $display_name = $category->parent_name . ' → ' . $category->name;
        }
        
        $selected = ($selected_id && $selected_id == $category->id) ? 'selected' : '';
        
        $options .= sprintf(
            '<option value="%d" %s data-parent-id="%s" data-level="%s">%s%s</option>',
            $category->id,
            $selected,
            $category->parent_id ?: '',
            $category->parent_id ? '1' : '0',
            $indent,
            esc_html($display_name)
        );
    }
    
    return $options;
}

// Clear any existing transients for task history fix
delete_transient('wh_task_history_fixed');
?> 