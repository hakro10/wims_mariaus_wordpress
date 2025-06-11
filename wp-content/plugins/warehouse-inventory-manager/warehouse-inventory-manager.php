<?php
/**
 * Plugin Name: Warehouse Inventory Manager
 * Plugin URI: https://example.com/warehouse-inventory-manager
 * Description: Complete warehouse inventory management system with dashboard, items, categories, locations, sales tracking, and QR codes.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: warehouse-inventory
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WH_INVENTORY_VERSION', '1.0.0');
define('WH_INVENTORY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WH_INVENTORY_PLUGIN_URL', plugin_dir_url(__FILE__));

// Main plugin class
class WarehouseInventoryManager {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('warehouse-inventory', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        
        // AJAX handlers
        $this->setup_ajax_handlers();
        
        // Custom post types and taxonomies
        $this->register_post_types();
        
        // Add shortcodes
        add_shortcode('warehouse_dashboard', array($this, 'dashboard_shortcode'));
        add_shortcode('warehouse_inventory', array($this, 'inventory_shortcode'));
    }
    
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        add_option('wh_inventory_version', WH_INVENTORY_VERSION);
        add_option('wh_inventory_settings', array(
            'currency' => 'USD',
            'low_stock_threshold' => 5,
            'enable_qr_codes' => true,
            'enable_barcode_scanning' => false,
        ));
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Enhanced inventory items table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wh_inventory_items (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            internal_id varchar(100) NOT NULL UNIQUE,
            sku varchar(100),
            barcode varchar(100),
            serial_number varchar(100),
            description text,
            category_id mediumint(9),
            location_id mediumint(9),
            quantity int(11) NOT NULL DEFAULT 0,
            reserved_quantity int(11) NOT NULL DEFAULT 0,
            min_stock_level int(11) DEFAULT 1,
            max_stock_level int(11),
            purchase_price decimal(10,2),
            selling_price decimal(10,2),
            cost_price decimal(10,2),
            supplier_id mediumint(9),
            supplier_sku varchar(100),
            weight decimal(8,2),
            dimensions varchar(100),
            unit varchar(20) DEFAULT 'pieces',
            status varchar(50) DEFAULT 'active',
            stock_status varchar(50) DEFAULT 'in-stock',
            image_url varchar(500),
            qr_code_image text,
            notes text,
            last_counted_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by mediumint(9),
            updated_by mediumint(9),
            PRIMARY KEY (id),
            INDEX idx_internal_id (internal_id),
            INDEX idx_category (category_id),
            INDEX idx_location (location_id),
            INDEX idx_status (status),
            INDEX idx_stock_status (stock_status)
        ) $charset_collate;";
        
        // Enhanced categories table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wh_categories (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            parent_id mediumint(9),
            color varchar(7) DEFAULT '#3b82f6',
            icon varchar(50),
            sort_order int(11) DEFAULT 0,
            item_count int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_slug (slug),
            INDEX idx_parent (parent_id),
            INDEX idx_active (is_active)
        ) $charset_collate;";
        
        // Enhanced locations table with hierarchy
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wh_locations (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            code varchar(50),
            type varchar(50) DEFAULT 'storage',
            description text,
            parent_id mediumint(9),
            level int(11) DEFAULT 1,
            path varchar(500),
            address text,
            contact_person varchar(255),
            phone varchar(50),
            email varchar(255),
            capacity int(11),
            current_capacity int(11) DEFAULT 0,
            zone varchar(100),
            aisle varchar(50),
            rack varchar(50),
            shelf varchar(50),
            bin varchar(50),
            qr_code_image text,
            barcode varchar(100),
            temperature_controlled tinyint(1) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_parent (parent_id),
            INDEX idx_type (type),
            INDEX idx_active (is_active),
            INDEX idx_code (code)
        ) $charset_collate;";
        
        // Stock movements table for tracking inventory changes
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wh_stock_movements (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            item_id mediumint(9) NOT NULL,
            movement_type varchar(50) NOT NULL,
            quantity_before int(11) NOT NULL,
            quantity_changed int(11) NOT NULL,
            quantity_after int(11) NOT NULL,
            unit_cost decimal(10,2),
            total_cost decimal(10,2),
            reference_type varchar(50),
            reference_id mediumint(9),
            location_from mediumint(9),
            location_to mediumint(9),
            reason varchar(255),
            notes text,
            performed_by mediumint(9),
            performed_at datetime DEFAULT CURRENT_TIMESTAMP,
            batch_id varchar(100),
            PRIMARY KEY (id),
            INDEX idx_item (item_id),
            INDEX idx_type (movement_type),
            INDEX idx_date (performed_at),
            INDEX idx_batch (batch_id)
        ) $charset_collate;";
        
        // Enhanced sales table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wh_sales (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            sale_number varchar(100) NOT NULL UNIQUE,
            item_id mediumint(9) NOT NULL,
            quantity_sold int(11) NOT NULL,
            unit_price decimal(10,2) NOT NULL,
            discount_amount decimal(10,2) DEFAULT 0,
            tax_amount decimal(10,2) DEFAULT 0,
            total_amount decimal(10,2) NOT NULL,
            customer_name varchar(255),
            customer_email varchar(255),
            customer_phone varchar(50),
            customer_address text,
            payment_method varchar(50),
            payment_status varchar(50) DEFAULT 'pending',
            delivery_method varchar(50),
            delivery_status varchar(50),
            delivery_address text,
            tracking_number varchar(100),
            sale_date datetime DEFAULT CURRENT_TIMESTAMP,
            delivery_date datetime,
            sold_by mediumint(9),
            notes text,
            metadata text,
            PRIMARY KEY (id),
            UNIQUE KEY unique_sale_number (sale_number),
            INDEX idx_item (item_id),
            INDEX idx_date (sale_date),
            INDEX idx_customer (customer_email),
            INDEX idx_status (payment_status)
        ) $charset_collate;";
        
        // Suppliers table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wh_suppliers (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            company varchar(255),
            email varchar(255),
            phone varchar(50),
            address text,
            contact_person varchar(255),
            tax_id varchar(100),
            payment_terms varchar(100),
            currency varchar(10) DEFAULT 'USD',
            website varchar(255),
            notes text,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_active (is_active),
            INDEX idx_email (email)
        ) $charset_collate;";
        
        // Team management table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wh_team_members (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9),
            username varchar(100) NOT NULL UNIQUE,
            email varchar(255) NOT NULL,
            first_name varchar(100),
            last_name varchar(100),
            role varchar(50) DEFAULT 'warehouse_employee',
            status varchar(20) DEFAULT 'active',
            phone varchar(50),
            department varchar(100),
            position varchar(100),
            hire_date date,
            last_login datetime,
            permissions text,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by mediumint(9),
            PRIMARY KEY (id),
            INDEX idx_user_id (user_id),
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_status (status)
        ) $charset_collate;";

        // Tasks management table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wh_tasks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            status varchar(50) DEFAULT 'pending',
            priority varchar(20) DEFAULT 'medium',
            assigned_to mediumint(9),
            created_by mediumint(9),
            due_date datetime,
            completed_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            estimated_hours decimal(5,2),
            actual_hours decimal(5,2),
            tags varchar(500),
            dependencies text,
            completion_notes text,
            PRIMARY KEY (id),
            INDEX idx_status (status),
            INDEX idx_assigned_to (assigned_to),
            INDEX idx_created_by (created_by),
            INDEX idx_due_date (due_date),
            INDEX idx_priority (priority)
        ) $charset_collate;";

        // Task history table for completed tasks
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wh_task_history (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            task_id mediumint(9) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            priority varchar(20),
            assigned_to mediumint(9),
            created_by mediumint(9),
            due_date datetime,
            completed_at datetime NOT NULL,
            created_at datetime,
            actual_hours decimal(5,2),
            completion_notes text,
            archived_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_task_id (task_id),
            INDEX idx_assigned_to (assigned_to),
            INDEX idx_completed_at (completed_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Insert default data
        $this->insert_default_data();
    }
    
    private function create_team_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wh_team_members (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9),
            username varchar(100) NOT NULL UNIQUE,
            email varchar(255) NOT NULL,
            first_name varchar(100),
            last_name varchar(100),
            role varchar(50) DEFAULT 'warehouse_employee',
            status varchar(20) DEFAULT 'active',
            phone varchar(50),
            department varchar(100),
            position varchar(100),
            hire_date date,
            last_login datetime,
            permissions text,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by mediumint(9),
            PRIMARY KEY (id),
            INDEX idx_user_id (user_id),
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function insert_default_data() {
        global $wpdb;
        
        // Default categories
        $categories = array(
            array('Electronics', 'electronics', 'Electronic devices and components', '#3b82f6', 'laptop'),
            array('Tools', 'tools', 'Hand tools and equipment', '#10b981', 'tools'),
            array('Office Supplies', 'office-supplies', 'Office equipment and supplies', '#f59e0b', 'briefcase'),
            array('Safety Equipment', 'safety-equipment', 'Safety gear and protective equipment', '#ef4444', 'shield-alt'),
            array('Consumables', 'consumables', 'Consumable items and supplies', '#8b5cf6', 'shopping-bag')
        );
        
        foreach ($categories as $cat) {
            $wpdb->insert(
                $wpdb->prefix . 'wh_categories',
                array(
                    'name' => $cat[0],
                    'slug' => $cat[1],
                    'description' => $cat[2],
                    'color' => $cat[3],
                    'icon' => $cat[4]
                )
            );
        }
        
        // Default locations
        $locations = array(
            array('Main Warehouse', 'WH001', 'warehouse', 'Primary storage facility', 1),
            array('Section A', 'SEC-A', 'section', 'Electronics section', 2, 1),
            array('Section B', 'SEC-B', 'section', 'Tools section', 2, 1),
            array('Aisle A1', 'A1', 'aisle', 'First aisle in Section A', 3, 2),
            array('Aisle A2', 'A2', 'aisle', 'Second aisle in Section A', 3, 2)
        );
        
        foreach ($locations as $loc) {
            $wpdb->insert(
                $wpdb->prefix . 'wh_locations',
                array(
                    'name' => $loc[0],
                    'code' => $loc[1],
                    'type' => $loc[2],
                    'description' => $loc[3],
                    'level' => $loc[4],
                    'parent_id' => isset($loc[5]) ? $loc[5] : null
                )
            );
        }
    }
    
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            'Warehouse Inventory',
            'Warehouse',
            'manage_options',
            'warehouse-inventory',
            array($this, 'admin_page'),
            'dashicons-store',
            30
        );
        
        // Submenu pages
        add_submenu_page('warehouse-inventory', 'Dashboard', 'Dashboard', 'manage_options', 'warehouse-inventory');
        add_submenu_page('warehouse-inventory', 'Items', 'Items', 'manage_options', 'warehouse-items', array($this, 'items_page'));
        add_submenu_page('warehouse-inventory', 'Categories', 'Categories', 'manage_options', 'warehouse-categories', array($this, 'categories_page'));
        add_submenu_page('warehouse-inventory', 'Locations', 'Locations', 'manage_options', 'warehouse-locations', array($this, 'locations_page'));
        add_submenu_page('warehouse-inventory', 'Sales', 'Sales', 'manage_options', 'warehouse-sales', array($this, 'sales_page'));
        add_submenu_page('warehouse-inventory', 'Reports', 'Reports', 'manage_options', 'warehouse-reports', array($this, 'reports_page'));
        add_submenu_page('warehouse-inventory', 'Settings', 'Settings', 'manage_options', 'warehouse-settings', array($this, 'settings_page'));
    }
    
    public function admin_scripts($hook) {
        if (strpos($hook, 'warehouse') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('warehouse-admin', WH_INVENTORY_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WH_INVENTORY_VERSION, true);
            wp_enqueue_style('warehouse-admin', WH_INVENTORY_PLUGIN_URL . 'assets/css/admin.css', array(), WH_INVENTORY_VERSION);
            
            wp_localize_script('warehouse-admin', 'warehouse_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('warehouse_nonce'),
            ));
        }
    }
    
    public function frontend_scripts() {
        // Always enqueue for warehouse theme
        wp_enqueue_script('jquery');
        wp_enqueue_script('warehouse-frontend', WH_INVENTORY_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), WH_INVENTORY_VERSION, true);
        wp_enqueue_style('warehouse-frontend', WH_INVENTORY_PLUGIN_URL . 'assets/css/frontend.css', array(), WH_INVENTORY_VERSION);
        
        wp_localize_script('warehouse-frontend', 'warehouse_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('warehouse_nonce'),
        ));
        
        // Also localize for inline scripts
        wp_localize_script('jquery', 'warehouseAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('warehouse_nonce'),
        ));
    }
    
    private function setup_ajax_handlers() {
        // Public and private AJAX handlers
        $handlers = array(
            'get_inventory_items',
            'add_inventory_item',
            'update_inventory_item',
            'delete_inventory_item',
            'get_categories',
            'add_category',
            'update_category',
            'delete_category',
            'get_locations',
            'add_location',
            'update_location',
            'delete_location',
            'record_sale',
            'get_sales',
            'generate_qr_code',
            'get_dashboard_stats',
            'export_inventory',
            'import_inventory',
            'get_team_members',
            'add_team_member',
            'update_team_member',
            'delete_team_member',
            'reset_user_password',
            'get_all_tasks',
            'get_task_history',
            'add_task',
            'update_task_status',
            'move_task_to_history',
            'delete_task'
        );
        
        foreach ($handlers as $handler) {
            add_action('wp_ajax_' . $handler, array($this, 'handle_' . $handler));
            add_action('wp_ajax_nopriv_' . $handler, array($this, 'handle_' . $handler));
        }
    }
    
    private function register_post_types() {
        // Custom post types for extended functionality if needed
    }
    
    // Admin pages
    public function admin_page() {
        include WH_INVENTORY_PLUGIN_DIR . 'includes/admin/dashboard.php';
    }
    
    public function items_page() {
        include WH_INVENTORY_PLUGIN_DIR . 'includes/admin/items.php';
    }
    
    public function categories_page() {
        include WH_INVENTORY_PLUGIN_DIR . 'includes/admin/categories.php';
    }
    
    public function locations_page() {
        include WH_INVENTORY_PLUGIN_DIR . 'includes/admin/locations.php';
    }
    
    public function sales_page() {
        include WH_INVENTORY_PLUGIN_DIR . 'includes/admin/sales.php';
    }
    
    public function reports_page() {
        include WH_INVENTORY_PLUGIN_DIR . 'includes/admin/reports.php';
    }
    
    public function settings_page() {
        include WH_INVENTORY_PLUGIN_DIR . 'includes/admin/settings.php';
    }
    
    // Shortcodes
    public function dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_role' => 'subscriber',
            'show_stats' => 'true',
            'show_recent' => 'true'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<p>Please log in to view the warehouse dashboard.</p>';
        }
        
        ob_start();
        include WH_INVENTORY_PLUGIN_DIR . 'includes/shortcodes/dashboard.php';
        return ob_get_clean();
    }
    
    public function inventory_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'location' => '',
            'per_page' => 12,
            'show_search' => 'true',
            'show_filters' => 'true'
        ), $atts);
        
        ob_start();
        include WH_INVENTORY_PLUGIN_DIR . 'includes/shortcodes/inventory.php';
        return ob_get_clean();
    }
    
    // AJAX handlers
    public function handle_get_dashboard_stats() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        global $wpdb;
        
        $stats = array(
            'total_items' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wh_inventory_items WHERE status = 'active'"),
            'in_stock' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wh_inventory_items WHERE stock_status = 'in-stock' AND status = 'active'"),
            'low_stock' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wh_inventory_items WHERE quantity <= min_stock_level AND quantity > 0 AND status = 'active'"),
            'out_of_stock' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wh_inventory_items WHERE quantity = 0 AND status = 'active'"),
            'total_value' => $wpdb->get_var("SELECT SUM(quantity * cost_price) FROM {$wpdb->prefix}wh_inventory_items WHERE status = 'active'"),
            'categories_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wh_categories WHERE is_active = 1"),
            'locations_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wh_locations WHERE is_active = 1"),
            'sales_today' => $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(total_amount) FROM {$wpdb->prefix}wh_sales WHERE DATE(sale_date) = %s",
                current_time('Y-m-d')
            )),
            'sales_this_month' => $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(total_amount) FROM {$wpdb->prefix}wh_sales WHERE YEAR(sale_date) = %d AND MONTH(sale_date) = %d",
                current_time('Y'), current_time('n')
            ))
        );
        
        wp_send_json_success($stats);
    }
    
    public function handle_get_inventory_items() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        global $wpdb;
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $category = isset($_POST['category']) ? intval($_POST['category']) : 0;
        $location = isset($_POST['location']) ? intval($_POST['location']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT i.*, c.name as category_name, l.name as location_name, s.name as supplier_name
                FROM {$wpdb->prefix}wh_inventory_items i
                LEFT JOIN {$wpdb->prefix}wh_categories c ON i.category_id = c.id
                LEFT JOIN {$wpdb->prefix}wh_locations l ON i.location_id = l.id
                LEFT JOIN {$wpdb->prefix}wh_suppliers s ON i.supplier_id = s.id
                WHERE i.status = 'active'";
        
        $params = array();
        
        if (!empty($search)) {
            $sql .= " AND (i.name LIKE %s OR i.internal_id LIKE %s OR i.sku LIKE %s OR i.barcode LIKE %s)";
            $search_term = '%' . $search . '%';
            $params = array_merge($params, array($search_term, $search_term, $search_term, $search_term));
        }
        
        if ($category > 0) {
            $sql .= " AND i.category_id = %d";
            $params[] = $category;
        }
        
        if ($location > 0) {
            $sql .= " AND i.location_id = %d";
            $params[] = $location;
        }
        
        if (!empty($status)) {
            $sql .= " AND i.stock_status = %s";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY i.updated_at DESC LIMIT %d OFFSET %d";
        $params = array_merge($params, array($per_page, $offset));
        
        $items = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        // Get total count for pagination
        $count_sql = str_replace('SELECT i.*, c.name as category_name, l.name as location_name, s.name as supplier_name', 'SELECT COUNT(*)', $sql);
        $count_sql = preg_replace('/ORDER BY.*LIMIT.*/', '', $count_sql);
        $total = $wpdb->get_var($wpdb->prepare($count_sql, array_slice($params, 0, -2)));
        
        wp_send_json_success(array(
            'items' => $items,
            'total' => intval($total),
            'pages' => ceil($total / $per_page),
            'current_page' => $page
        ));
    }
    
    public function handle_add_inventory_item() {
        global $wpdb;
        
        $name = sanitize_text_field($_POST['name']);
        $internal_id = sanitize_text_field($_POST['internal_id']);
        $quantity = intval($_POST['quantity']);
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'wh_inventory_items',
            array(
                'name' => $name,
                'internal_id' => $internal_id,
                'quantity' => $quantity,
                'status' => $quantity > 0 ? 'in-stock' : 'out-of-stock'
            )
        );
        
        if ($result) {
            wp_send_json_success(array('id' => $wpdb->insert_id));
        } else {
            wp_send_json_error('Failed to add item');
        }
    }
    
    // Team Management AJAX Handlers
    public function handle_get_team_members() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Check if table exists
        $table_name = $wpdb->prefix . 'wh_team_members';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            // Table doesn't exist, return empty array
            wp_send_json_success(array());
            return;
        }
        
        $members = $wpdb->get_results("
            SELECT tm.*, u.user_login, u.user_email, u.display_name
            FROM {$wpdb->prefix}wh_team_members tm
            LEFT JOIN {$wpdb->users} u ON tm.user_id = u.ID
            WHERE tm.status = 'active'
            ORDER BY tm.created_at DESC
        ");
        
        // Handle case where query fails
        if ($members === false) {
            error_log('Failed to get team members: ' . $wpdb->last_error);
            wp_send_json_success(array()); // Return empty array instead of error
        } else {
            wp_send_json_success($members ? $members : array());
        }
    }
    
    public function handle_add_team_member() {
        // Check nonce with better error handling
        if (!wp_verify_nonce($_POST['nonce'], 'warehouse_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Validate required fields
        if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['first_name']) || empty($_POST['last_name'])) {
            wp_send_json_error('All required fields must be filled');
        }
        
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $role = sanitize_text_field($_POST['role']);
        $password = wp_generate_password(12, false);
        
        // Validate email format
        if (!is_email($email)) {
            wp_send_json_error('Invalid email address');
        }
        
        // Check if username or email already exists
        if (username_exists($username) || email_exists($email)) {
            wp_send_json_error('Username or email already exists');
        }
        
        // Check if username already exists in team members table
        $existing_member = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wh_team_members WHERE username = %s",
            $username
        ));
        
        if ($existing_member) {
            wp_send_json_error('Team member with this username already exists');
        }
        
        // Create WordPress user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }
        
        // Update user metadata
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
            'role' => $role
        ));
        
        // Ensure team members table exists
        $table_name = $wpdb->prefix . 'wh_team_members';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            error_log('Team members table does not exist, creating...');
            $this->create_team_table();
            
            // Verify table creation
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            if (!$table_exists) {
                wp_send_json_error('Failed to create team members table');
            }
            error_log('Team members table created successfully');
        }
        
        // Add to team members table
        $data = array(
            'user_id' => $user_id,
            'username' => $username,
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => $role,
            'status' => 'active',
            'created_by' => get_current_user_id()
        );
        
        $result = $wpdb->insert($table_name, $data);
        
        // Debug: Log the insertion details
        error_log('Team member insertion - Result: ' . var_export($result, true));
        error_log('Team member insertion - Last Error: ' . $wpdb->last_error);
        error_log('Team member insertion - Insert ID: ' . $wpdb->insert_id);
        
        if ($result !== false && $wpdb->insert_id > 0) {
            // Success case
            wp_send_json_success(array(
                'id' => $wpdb->insert_id,
                'user_id' => $user_id,
                'password' => $password,
                'message' => 'Team member added successfully'
            ));
        } else {
            // Check if user was actually inserted despite the error
            $check_user = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE username = %s",
                $username
            ));
            
            if ($check_user) {
                // User was inserted successfully despite the error
                wp_send_json_success(array(
                    'id' => $check_user->id,
                    'user_id' => $user_id,
                    'password' => $password,
                    'message' => 'Team member added successfully'
                ));
            } else {
                // Actually failed, clean up
                wp_delete_user($user_id);
                wp_send_json_error('Failed to add team member to database');
            }
        }
    }
    
    public function handle_update_team_member() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $member_id = intval($_POST['member_id']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $role = sanitize_text_field($_POST['role']);
        $phone = sanitize_text_field($_POST['phone']);
        $department = sanitize_text_field($_POST['department']);
        $position = sanitize_text_field($_POST['position']);
        
        // Get member info
        $member = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wh_team_members WHERE id = %d", $member_id));
        
        if (!$member) {
            wp_send_json_error('Team member not found');
        }
        
        // Update WordPress user
        wp_update_user(array(
            'ID' => $member->user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
            'user_email' => $email,
            'role' => $role
        ));
        
        // Update team member record
        $result = $wpdb->update(
            $wpdb->prefix . 'wh_team_members',
            array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'role' => $role,
                'phone' => $phone,
                'department' => $department,
                'position' => $position,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $member_id)
        );
        
        if ($result !== false) {
            wp_send_json_success('Team member updated successfully');
        } else {
            wp_send_json_error('Failed to update team member');
        }
    }
    
    public function handle_delete_team_member() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $member_id = intval($_POST['member_id']);
        
        // Get member info
        $member = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wh_team_members WHERE id = %d", $member_id));
        
        if (!$member) {
            wp_send_json_error('Team member not found');
        }
        
        // Don't allow deleting yourself
        if ($member->user_id == get_current_user_id()) {
            wp_send_json_error('Cannot delete your own account');
        }
        
        // Delete from team members table
        $result = $wpdb->delete(
            $wpdb->prefix . 'wh_team_members',
            array('id' => $member_id)
        );
        
        // Delete WordPress user completely
        if ($result !== false) {
            wp_delete_user($member->user_id);
        }
        
        if ($result !== false) {
            wp_send_json_success('Team member removed successfully');
        } else {
            wp_send_json_error('Failed to remove team member');
        }
    }
    
    public function handle_reset_user_password() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $member_id = intval($_POST['member_id']);
        global $wpdb;
        
        // Get member info
        $member = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wh_team_members WHERE id = %d", $member_id));
        
        if (!$member) {
            wp_send_json_error('Team member not found');
        }
        
        // Generate new password
        $new_password = wp_generate_password(12, false);
        
        // Update user password
        wp_set_password($new_password, $member->user_id);
        
        wp_send_json_success(array(
            'password' => $new_password,
            'message' => 'Password reset successfully'
        ));
    }

    // Task Management AJAX Handlers
    public function handle_get_all_tasks() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        global $wpdb;
        
        $tasks = $wpdb->get_results("
            SELECT t.*, 
                   u1.display_name as assigned_to_name,
                   u2.display_name as created_by_name
            FROM {$wpdb->prefix}wh_tasks t
            LEFT JOIN {$wpdb->users} u1 ON t.assigned_to = u1.ID
            LEFT JOIN {$wpdb->users} u2 ON t.created_by = u2.ID
            WHERE t.status != 'archived'
            ORDER BY t.priority DESC, t.due_date ASC
        ");
        
        wp_send_json_success($tasks);
    }
    
    public function handle_get_task_history() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        global $wpdb;
        
        $history = $wpdb->get_results("
            SELECT t.*, 
                   u1.display_name as assigned_to_name,
                   u2.display_name as created_by_name
            FROM {$wpdb->prefix}wh_tasks t
            LEFT JOIN {$wpdb->users} u1 ON t.assigned_to = u1.ID
            LEFT JOIN {$wpdb->users} u2 ON t.created_by = u2.ID
            WHERE t.status = 'archived'
            ORDER BY t.completed_at DESC
            LIMIT 50
        ");
        
        wp_send_json_success($history);
    }
    
    public function handle_add_task() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        global $wpdb;
        
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $assigned_to = intval($_POST['assigned_to']);
        $priority = sanitize_text_field($_POST['priority']);
        $due_date = sanitize_text_field($_POST['due_date']);
        
        if (empty($title)) {
            wp_send_json_error('Task title is required');
        }
        
        $due_date = !empty($due_date) ? $due_date : null;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'wh_tasks',
            array(
                'title' => $title,
                'description' => $description,
                'assigned_to' => $assigned_to,
                'priority' => $priority,
                'due_date' => $due_date,
                'created_by' => get_current_user_id(),
                'status' => 'pending'
            )
        );
        
        if ($result) {
            $task_id = $wpdb->insert_id;
            $task = $wpdb->get_row($wpdb->prepare("
                SELECT t.*, 
                       u1.display_name as assigned_to_name,
                       u2.display_name as created_by_name
                FROM {$wpdb->prefix}wh_tasks t
                LEFT JOIN {$wpdb->users} u1 ON t.assigned_to = u1.ID
                LEFT JOIN {$wpdb->users} u2 ON t.created_by = u2.ID
                WHERE t.id = %d
            ", $task_id));
            
            wp_send_json_success(array(
                'task' => $task,
                'message' => 'Task created successfully'
            ));
        } else {
            wp_send_json_error('Failed to create task');
        }
    }
    
    public function handle_update_task_status() {
        error_log('Update task status called with: ' . print_r($_POST, true));
        
        try {
            check_ajax_referer('warehouse_nonce', 'nonce');
        } catch (Exception $e) {
            error_log('Nonce verification failed: ' . $e->getMessage());
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        
        // Check if completed_at column exists, if not add it
        $this->ensure_task_table_updated();
        
        $task_id = intval($_POST['task_id']);
        $status = sanitize_text_field($_POST['status']);
        
        error_log("Updating task $task_id to status $status");
        
        $valid_statuses = array('pending', 'in_progress', 'completed', 'archived');
        if (!in_array($status, $valid_statuses)) {
            error_log("Invalid status: $status");
            wp_send_json_error('Invalid status');
        }
        
        $update_data = array('status' => $status);
        
        if ($status === 'completed') {
            $update_data['completed_at'] = current_time('mysql');
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'wh_tasks',
            $update_data,
            array('id' => $task_id)
        );
        
        error_log("Update result: $result, Last error: " . $wpdb->last_error);
        
        if ($result !== false) {
            wp_send_json_success('Task status updated successfully');
        } else {
            error_log("Database error: " . $wpdb->last_error);
            wp_send_json_error('Failed to update task status: ' . $wpdb->last_error);
        }
    }
    
    private function ensure_task_table_updated() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wh_tasks';
        
        // Check if completed_at column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'completed_at'");
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN completed_at datetime NULL AFTER due_date");
        }
        
        // Check if other missing columns exist
        $columns_to_add = array(
            'estimated_hours' => 'decimal(5,2) NULL',
            'actual_hours' => 'decimal(5,2) NULL',
            'tags' => 'varchar(500) NULL',
            'dependencies' => 'text NULL',
            'completion_notes' => 'text NULL'
        );
        
        foreach ($columns_to_add as $column => $definition) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE '{$column}'");
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN {$column} {$definition}");
            }
        }
    }
    
    public function handle_move_task_to_history() {
        try {
            check_ajax_referer('warehouse_nonce', 'nonce');
        } catch (Exception $e) {
            wp_send_json_error('Security check failed: ' . $e->getMessage());
        }
        
        global $wpdb;
        
        $task_id = intval($_POST['task_id']);
        
        // Just archive the task - skip moving to history table for now
        $result = $wpdb->update(
            $wpdb->prefix . 'wh_tasks',
            array('status' => 'archived'),
            array('id' => $task_id)
        );
        
        if ($result !== false) {
            wp_send_json_success('Task archived successfully');
        } else {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }
    }
    
    private function ensure_history_table_exists() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wh_task_history';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        if (!$table_exists) {
            // Create the table
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                task_id mediumint(9) NOT NULL,
                title varchar(255) NOT NULL,
                description text,
                priority varchar(20),
                assigned_to mediumint(9),
                created_by mediumint(9),
                due_date datetime,
                completed_at datetime NOT NULL,
                created_at datetime,
                actual_hours decimal(5,2),
                completion_notes text,
                archived_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_task_id (task_id),
                INDEX idx_assigned_to (assigned_to),
                INDEX idx_completed_at (completed_at)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            error_log("Created task history table");
        }
    }
    
    public function handle_delete_task() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        global $wpdb;
        
        $task_id = intval($_POST['task_id']);
        
        // Check if user can delete tasks (only admins or task creators)
        $task = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wh_tasks WHERE id = %d", $task_id));
        
        if (!$task) {
            wp_send_json_error('Task not found');
        }
        
        if (!current_user_can('manage_options') && $task->created_by != get_current_user_id()) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'wh_tasks',
            array('id' => $task_id)
        );
        
        if ($result !== false) {
            wp_send_json_success('Task deleted successfully');
        } else {
            wp_send_json_error('Failed to delete task');
        }
    }
}

// Helper functions for tasks
function get_all_tasks() {
    global $wpdb;
    
    return $wpdb->get_results("
        SELECT t.*, 
               u1.display_name as assigned_to_name,
               u2.display_name as created_by_name
        FROM {$wpdb->prefix}wh_tasks t
        LEFT JOIN {$wpdb->users} u1 ON t.assigned_to = u1.ID
        LEFT JOIN {$wpdb->users} u2 ON t.created_by = u2.ID
        WHERE t.status != 'archived'
        ORDER BY t.priority DESC, t.due_date ASC
    ");
}

function get_task_history() {
    global $wpdb;
    
    return $wpdb->get_results("
        SELECT th.*, 
               u1.display_name as assigned_to_name,
               u2.display_name as created_by_name
        FROM {$wpdb->prefix}wh_task_history th
        LEFT JOIN {$wpdb->users} u1 ON th.assigned_to = u1.ID
        LEFT JOIN {$wpdb->users} u2 ON th.created_by = u2.ID
        ORDER BY th.completed_at DESC
        LIMIT 50
    ");
}

// Initialize the plugin
new WarehouseInventoryManager(); 