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
        
        // Run database migration
        $this->run_database_migration();
    }
    
    /**
     * Run database migration
     */
    private function run_database_migration() {
        require_once(WH_INVENTORY_PLUGIN_DIR . 'includes/database-migration.php');
        $migration = new WH_Database_Migration();
        try {
            $migration->run_migration();
        } catch (Exception $e) {
            error_log('Database migration failed: ' . $e->getMessage());
        }
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Insert default data
        $this->insert_default_data();
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
            // wp_enqueue_script('warehouse-admin', WH_INVENTORY_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WH_INVENTORY_VERSION, true); // File doesn't exist
            // wp_enqueue_style('warehouse-admin', WH_INVENTORY_PLUGIN_URL . 'assets/css/admin.css', array(), WH_INVENTORY_VERSION); // File doesn't exist
            
            wp_localize_script('jquery', 'warehouse_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('warehouse_nonce'),
            ));
        }
    }
    
    public function frontend_scripts() {
        if (is_page() && (has_shortcode(get_post()->post_content, 'warehouse_dashboard') || 
                         has_shortcode(get_post()->post_content, 'warehouse_inventory'))) {
            wp_enqueue_script('jquery');
            // wp_enqueue_script('warehouse-frontend', WH_INVENTORY_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), WH_INVENTORY_VERSION, true); // File doesn't exist
            // wp_enqueue_style('warehouse-frontend', WH_INVENTORY_PLUGIN_URL . 'assets/css/frontend.css', array(), WH_INVENTORY_VERSION); // File doesn't exist
            
            wp_localize_script('jquery', 'warehouse_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('warehouse_nonce'),
            ));
        }
    }
    
    private function setup_ajax_handlers() {
        // Public and private AJAX handlers
        $handlers = array(
            'get_inventory_items',
            'add_inventory_item',
            'update_inventory_item',
            'update_item_tested_status',
            'delete_inventory_item',
            'get_categories',
            'add_category',
            'update_category',
            'delete_category',
            'get_category_items',
            'get_locations',
            'add_location',
            'update_location',
            'delete_location',
            'record_sale',
            'get_dashboard_stats',
            'get_team_members',
            'add_team_member',
            'update_team_member',
            'delete_team_member',
            'reset_user_password',
            'get_profit_data',
            'rebuild_profit_data',
            'fix_purchase_prices',
            'debug_profit_data'
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
        
        if (defined('DOING_AJAX') && DOING_AJAX) {
            wp_send_json_success($stats);
        } else {
            return $stats;
        }
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
        
        $sql = "SELECT i.*, c.name as category_name, l.name as location_name
                FROM {$wpdb->prefix}wh_inventory_items i
                LEFT JOIN {$wpdb->prefix}wh_categories c ON i.category_id = c.id
                LEFT JOIN {$wpdb->prefix}wh_locations l ON i.location_id = l.id
                WHERE i.status != 'inactive'";
        
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
        $count_sql = str_replace('SELECT i.*, c.name as category_name, l.name as location_name', 'SELECT COUNT(*)', $sql);
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
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
        $quantity = intval($_POST['quantity']);
        $min_stock_level = intval($_POST['min_stock_level']);
        $purchase_price = floatval($_POST['purchase_price']);
        $selling_price = floatval($_POST['selling_price']);
        $total_lot_price = floatval($_POST['total_lot_price']);
        $supplier = isset($_POST['supplier']) ? sanitize_text_field($_POST['supplier']) : '';
        
        // Auto-generate internal_id based on category
        $prefix = 'ITEM';
        if ($category_id > 0) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}wh_categories WHERE id = %d",
                $category_id
            ));
            
            if ($category) {
                // Create prefix from category name (first 4 letters, uppercase)
                $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $category->name), 0, 4));
                if (strlen($prefix) < 2) {
                    $prefix = 'ITEM'; // Fallback if category name is too short
                }
            }
        }
        
        // Find the next available number for this prefix
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT internal_id FROM {$wpdb->prefix}wh_inventory_items 
             WHERE internal_id LIKE %s 
             ORDER BY CAST(SUBSTRING(internal_id, LENGTH(%s) + 2) AS UNSIGNED) DESC 
             LIMIT 1",
            $prefix . '-%',
            $prefix
        ));
        
        $next_number = 1;
        if ($existing) {
            // Extract number from existing ID (e.g., "AUTO-005" -> 5)
            $parts = explode('-', $existing);
            if (count($parts) >= 2) {
                $last_number = intval(end($parts));
                $next_number = $last_number + 1;
            }
        }
        
        // Format the internal ID (e.g., "AUTO-001", "ELEC-002")
        $internal_id = sprintf('%s-%03d', $prefix, $next_number);
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'wh_inventory_items',
            array(
                'name' => $name,
                'internal_id' => $internal_id,
                'description' => $description,
                'category_id' => $category_id > 0 ? $category_id : null,
                'location_id' => $location_id > 0 ? $location_id : null,
                'quantity' => $quantity,
                'min_stock_level' => $min_stock_level,
                'purchase_price' => $purchase_price,
                'selling_price' => $selling_price,
                'total_lot_price' => $total_lot_price,
                'supplier' => $supplier,
                'status' => $quantity > 0 ? 'in-stock' : 'out-of-stock',
                'created_by' => get_current_user_id()
            )
        );
        
        if ($result) {
            wp_send_json_success(array(
                'id' => $wpdb->insert_id,
                'internal_id' => $internal_id,
                'message' => 'Item added successfully'
            ));
        } else {
            wp_send_json_error('Failed to add item: ' . $wpdb->last_error);
        }
    }
    
    public function handle_update_inventory_item() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $id = intval($_POST['id']);
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
        $quantity = intval($_POST['quantity']);
        $min_stock_level = intval($_POST['min_stock_level']);
        $purchase_price = floatval($_POST['purchase_price']);
        $selling_price = floatval($_POST['selling_price']);
        $supplier = isset($_POST['supplier']) ? sanitize_text_field($_POST['supplier']) : '';
        $tested_status = sanitize_text_field($_POST['tested_status']);
        $total_lot_price = floatval($_POST['total_lot_price']);
        
        // Check if tested_status column exists, if not add it
        $table_name = $wpdb->prefix . 'wh_inventory_items';
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM `{$table_name}` LIKE %s",
            'tested_status'
        ));
        
        if (empty($column_exists)) {
            // Add the column if it doesn't exist
            $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN `tested_status` VARCHAR(20) DEFAULT 'not_tested'");
        }
        
        $update_data = array(
            'name' => $name,
            'description' => $description,
            'category_id' => $category_id > 0 ? $category_id : null,
            'location_id' => $location_id > 0 ? $location_id : null,
            'quantity' => $quantity,
            'min_stock_level' => $min_stock_level,
            'purchase_price' => $purchase_price,
            'selling_price' => $selling_price,
            'total_lot_price' => $total_lot_price,
            'supplier' => $supplier,
            'tested_status' => $tested_status,
            'status' => $quantity > 0 ? 'in-stock' : 'out-of-stock'
        );
        
        // Only add optional fields if they exist in the database
        // Note: Skipping sku, cost_price, supplier_id, unit, notes as these columns don't exist in current DB schema
        
        $result = $wpdb->update(
            $wpdb->prefix . 'wh_inventory_items',
            $update_data,
            array('id' => $id)
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Item updated successfully'));
        } else {
            wp_send_json_error('Failed to update item');
        }
    }
    
    public function handle_delete_inventory_item() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $id = intval($_POST['id']);
        
        // Soft delete by updating status to inactive
        $result = $wpdb->update(
            $wpdb->prefix . 'wh_inventory_items',
            array(
                'status' => 'inactive'
            ),
            array('id' => $id)
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Item deleted successfully'));
        } else {
            wp_send_json_error('Failed to delete item');
        }
    }
    
    public function handle_update_item_tested_status() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $item_id = intval($_POST['item_id']);
        $tested_status = sanitize_text_field($_POST['tested_status']);
        
        if (!$item_id) {
            wp_send_json_error('Invalid item ID');
        }
        
        // Validate tested status
        if (!in_array($tested_status, array('tested', 'not_tested'))) {
            wp_send_json_error('Invalid tested status');
        }
        
        // Check if tested_status column exists, if not add it
        $table_name = $wpdb->prefix . 'wh_inventory_items';
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM `{$table_name}` LIKE %s",
            'tested_status'
        ));
        
        if (empty($column_exists)) {
            // Add the column if it doesn't exist
            $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN `tested_status` VARCHAR(20) DEFAULT 'not_tested'");
        }
        
        // Update the tested status
        $result = $wpdb->update(
            $table_name,
            array(
                'tested_status' => $tested_status
            ),
            array('id' => $item_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Tested status updated successfully',
                'tested_status' => $tested_status
            ));
        } else {
            wp_send_json_error('Failed to update tested status');
        }
    }
    
    public function handle_get_categories() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('view_warehouse')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $categories = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}wh_categories
            WHERE is_active = 1
            ORDER BY sort_order, name
        ");
        
        wp_send_json_success($categories);
    }
    
    public function handle_add_category() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('manage_warehouse')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $name = sanitize_text_field($_POST['name']);
        $slug = sanitize_title($_POST['slug']);
        $description = sanitize_textarea_field($_POST['description']);
        $color = sanitize_hex_color($_POST['color']);
        $parent_id = intval($_POST['parent_id']);
        $icon = sanitize_text_field($_POST['icon']);
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'wh_categories',
            array(
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'color' => $color,
                'parent_id' => $parent_id > 0 ? $parent_id : null,
                'icon' => $icon,
                'is_active' => 1
            )
        );
        
        if ($result) {
            wp_send_json_success(array('id' => $wpdb->insert_id, 'message' => 'Category added successfully'));
        } else {
            wp_send_json_error('Failed to add category');
        }
    }
    
    public function handle_update_category() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('manage_warehouse')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $id = intval($_POST['id']);
        $name = sanitize_text_field($_POST['name']);
        $slug = sanitize_title($_POST['slug']);
        $description = sanitize_textarea_field($_POST['description']);
        $color = sanitize_hex_color($_POST['color']);
        $parent_id = intval($_POST['parent_id']);
        $icon = sanitize_text_field($_POST['icon']);
        
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
            array('id' => $id)
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Category updated successfully'));
        } else {
            wp_send_json_error('Failed to update category');
        }
    }
    
    public function handle_delete_category() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('manage_warehouse')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $id = intval($_POST['id']);
        
        // Check if category has items
        $item_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wh_inventory_items WHERE category_id = %d AND status != 'inactive'",
            $id
        ));
        
        if ($item_count > 0) {
            wp_send_json_error('Cannot delete category with items. Please move items to another category first.');
        }
        
        // Soft delete by updating is_active status
        $result = $wpdb->update(
            $wpdb->prefix . 'wh_categories',
            array('is_active' => 0),
            array('id' => $id)
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Category deleted successfully'));
        } else {
            wp_send_json_error('Failed to delete category');
        }
    }
    
    public function handle_get_category_items() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        global $wpdb;
        
        $category_id = intval($_POST['category_id']);
        
        if (!$category_id) {
            wp_send_json_error('Invalid category ID');
        }
        
        // Get all items in this category and its subcategories
        $sql = "SELECT i.*, c.name as category_name, l.name as location_name
                FROM {$wpdb->prefix}wh_inventory_items i
                LEFT JOIN {$wpdb->prefix}wh_categories c ON i.category_id = c.id
                LEFT JOIN {$wpdb->prefix}wh_locations l ON i.location_id = l.id
                WHERE (i.category_id = %d OR i.category_id IN (
                    SELECT id FROM {$wpdb->prefix}wh_categories 
                    WHERE parent_id = %d AND is_active = 1
                ))
                ORDER BY i.name ASC";
        
        $items = $wpdb->get_results($wpdb->prepare($sql, $category_id, $category_id));
        
        wp_send_json_success($items);
    }
    
    public function handle_get_locations() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('view_warehouse')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $locations = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}wh_locations
            WHERE is_active = 1
            ORDER BY level, name
        ");
        
        wp_send_json_success($locations);
    }
    
    public function handle_add_location() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('manage_warehouse')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $name = sanitize_text_field($_POST['name']);
        $code = sanitize_text_field($_POST['code']);
        $type = sanitize_text_field($_POST['type']);
        $description = sanitize_textarea_field($_POST['description']);
        $parent_id = intval($_POST['parent_id']);
        $address = sanitize_textarea_field($_POST['address']);
        $capacity = intval($_POST['capacity']);
        $zone = sanitize_text_field($_POST['zone']);
        $aisle = sanitize_text_field($_POST['aisle']);
        $rack = sanitize_text_field($_POST['rack']);
        $shelf = sanitize_text_field($_POST['shelf']);
        
        // Calculate level based on parent
        $level = 1;
        if ($parent_id > 0) {
            $parent_level = $wpdb->get_var($wpdb->prepare(
                "SELECT level FROM {$wpdb->prefix}wh_locations WHERE id = %d",
                $parent_id
            ));
            $level = $parent_level + 1;
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'wh_locations',
            array(
                'name' => $name,
                'code' => $code,
                'type' => $type,
                'description' => $description,
                'parent_id' => $parent_id > 0 ? $parent_id : null,
                'level' => $level,
                'address' => $address,
                'capacity' => $capacity,
                'zone' => $zone,
                'aisle' => $aisle,
                'rack' => $rack,
                'shelf' => $shelf,
                'is_active' => 1
            )
        );
        
        if ($result) {
            wp_send_json_success(array('id' => $wpdb->insert_id, 'message' => 'Location added successfully'));
        } else {
            wp_send_json_error('Failed to add location');
        }
    }
    
    public function handle_update_location() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('manage_warehouse')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $id = intval($_POST['id']);
        $name = sanitize_text_field($_POST['name']);
        $code = sanitize_text_field($_POST['code']);
        $type = sanitize_text_field($_POST['type']);
        $description = sanitize_textarea_field($_POST['description']);
        $parent_id = intval($_POST['parent_id']);
        $address = sanitize_textarea_field($_POST['address']);
        $capacity = intval($_POST['capacity']);
        $zone = sanitize_text_field($_POST['zone']);
        $aisle = sanitize_text_field($_POST['aisle']);
        $rack = sanitize_text_field($_POST['rack']);
        $shelf = sanitize_text_field($_POST['shelf']);
        
        // Calculate level based on parent
        $level = 1;
        if ($parent_id > 0) {
            $parent_level = $wpdb->get_var($wpdb->prepare(
                "SELECT level FROM {$wpdb->prefix}wh_locations WHERE id = %d",
                $parent_id
            ));
            $level = $parent_level + 1;
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'wh_locations',
            array(
                'name' => $name,
                'code' => $code,
                'type' => $type,
                'description' => $description,
                'parent_id' => $parent_id > 0 ? $parent_id : null,
                'level' => $level,
                'address' => $address,
                'capacity' => $capacity,
                'zone' => $zone,
                'aisle' => $aisle,
                'rack' => $rack,
                'shelf' => $shelf
            ),
            array('id' => $id)
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Location updated successfully'));
        } else {
            wp_send_json_error('Failed to update location');
        }
    }
    
    public function handle_delete_location() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('manage_warehouse')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $id = intval($_POST['id']);
        
        // Check if location has items
        $item_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wh_inventory_items WHERE location_id = %d AND status = 'active'",
            $id
        ));
        
        if ($item_count > 0) {
            wp_send_json_error('Cannot delete location with items. Please move items to another location first.');
        }
        
        // Check if location has child locations
        $child_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wh_locations WHERE parent_id = %d AND is_active = 1",
            $id
        ));
        
        if ($child_count > 0) {
            wp_send_json_error('Cannot delete location with child locations. Please delete or move child locations first.');
        }
        
        // Soft delete by updating is_active status
        $result = $wpdb->update(
            $wpdb->prefix . 'wh_locations',
            array('is_active' => 0),
            array('id' => $id)
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Location deleted successfully'));
        } else {
            wp_send_json_error('Failed to delete location');
        }
    }
    
    // Team Management AJAX Handlers
    public function handle_get_team_members() {
        error_log('=== HANDLE_GET_TEAM_MEMBERS CALLED ===');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);
        error_log('Is AJAX: ' . (defined('DOING_AJAX') && DOING_AJAX ? 'YES' : 'NO'));
        
        check_ajax_referer('warehouse_nonce', 'nonce');
        error_log('Nonce verified successfully');
        
        $current_user = wp_get_current_user();
        error_log('Current user: ' . $current_user->user_login . ' (ID: ' . $current_user->ID . ')');
        error_log('User roles: ' . implode(', ', $current_user->roles));
        error_log('Has manage_options: ' . (current_user_can('manage_options') ? 'yes' : 'no'));
        error_log('Has edit_posts: ' . (current_user_can('edit_posts') ? 'yes' : 'no'));
        
        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            error_log('Permission denied for user');
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $sql = "SELECT tm.*, u.user_login, u.user_email, u.display_name
                FROM {$wpdb->prefix}wh_team_members tm
                LEFT JOIN {$wpdb->users} u ON tm.user_id = u.ID
                ORDER BY tm.created_at DESC";
        
        error_log('SQL query: ' . $sql);
        
        $members = $wpdb->get_results($sql);
        
        error_log('Members found: ' . count($members));
        error_log('Raw members data: ' . print_r($members, true));
        if ($wpdb->last_error) {
            error_log('SQL error: ' . $wpdb->last_error);
        }
        
        wp_send_json_success($members);
    }
    
    public function handle_add_team_member() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $role = sanitize_text_field($_POST['role']);
        $department = sanitize_text_field($_POST['department'] ?? '');
        $position = sanitize_text_field($_POST['position'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $password = wp_generate_password(12, false);
        
        // Check if username or email already exists
        if (username_exists($username) || email_exists($email)) {
            wp_send_json_error('Username or email already exists');
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
        
        // Add to team members table
        $result = $wpdb->insert(
            $wpdb->prefix . 'wh_team_members',
            array(
                'user_id' => $user_id,
                'username' => $username,
                'email' => $email,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'role' => $role,
                'department' => $department,
                'position' => $position,
                'phone' => $phone,
                'status' => 'active',
                'created_by' => get_current_user_id()
            )
        );
        
        if ($result) {
            wp_send_json_success(array(
                'id' => $wpdb->insert_id,
                'user_id' => $user_id,
                'password' => $password,
                'message' => 'Team member added successfully'
            ));
        } else {
            wp_send_json_error('Failed to add team member');
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
        
        // Soft delete from team members table
        $result = $wpdb->update(
            $wpdb->prefix . 'wh_team_members',
            array('status' => 'inactive'),
            array('id' => $member_id)
        );
        
        // Optionally delete WordPress user (uncomment if you want hard delete)
        // wp_delete_user($member->user_id);
        
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
    
    public function handle_record_sale() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Sanitize input data
        $item_id = intval($_POST['item_id']);
        $quantity = intval($_POST['quantity']);
        $unit_price = floatval($_POST['unit_price']);
        $customer_name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
        $customer_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'cash';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        // Validate required fields
        if (!$item_id || !$quantity || !$unit_price) {
            wp_send_json_error('Missing required fields: item_id, quantity, or unit_price');
        }
        
        if ($quantity <= 0 || $unit_price <= 0) {
            wp_send_json_error('Quantity and unit price must be greater than 0');
        }
        
        // Check if item exists and has enough stock
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wh_inventory_items WHERE id = %d",
            $item_id
        ));
        
        if (!$item) {
            wp_send_json_error('Item not found or inactive');
        }
        
        if ($item->quantity < $quantity) {
            wp_send_json_error('Insufficient stock. Available: ' . $item->quantity);
        }
        
        // Generate unique sale number
        $sale_number = 'SALE-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Check if sale number already exists and regenerate if needed
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wh_sales WHERE sale_number = %s",
            $sale_number
        ));
        
        while ($exists > 0) {
            $sale_number = 'SALE-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wh_sales WHERE sale_number = %s",
                $sale_number
            ));
        }
        
        // Calculate totals
        $total_amount = $quantity * $unit_price;
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Insert sale record
            $sale_result = $wpdb->insert(
                $wpdb->prefix . 'wh_sales',
                array(
                    'sale_number' => $sale_number,
                    'item_id' => $item_id,
                    'inventory_item_id' => $item_id,
                    'quantity_sold' => $quantity,
                    'unit_price' => $unit_price,
                    'total_amount' => $total_amount,
                    'customer_name' => $customer_name,
                    'customer_email' => $customer_email,
                    'payment_method' => $payment_method,
                    'payment_status' => 'completed',
                    'sale_date' => current_time('mysql'),
                    'sold_by' => get_current_user_id(),
                    'notes' => $notes
                ),
                array(
                    '%s', '%d', '%d', '%d', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%d', '%s'
                )
            );
            
            if (!$sale_result) {
                throw new Exception('Failed to insert sale record');
            }
            
            // Update inventory quantity
            $update_result = $wpdb->update(
                $wpdb->prefix . 'wh_inventory_items',
                array(
                    'quantity' => $item->quantity - $quantity,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $item_id),
                array('%d', '%s'),
                array('%d')
            );
            
            if ($update_result === false) {
                throw new Exception('Failed to update inventory quantity');
            }
            
            // Update stock status if quantity becomes 0
            $new_quantity = $item->quantity - $quantity;
            if ($new_quantity <= 0) {
                $wpdb->update(
                    $wpdb->prefix . 'wh_inventory_items',
                    array('stock_status' => 'out-of-stock'),
                    array('id' => $item_id),
                    array('%s'),
                    array('%d')
                );
            } elseif ($new_quantity <= $item->min_stock_level) {
                $wpdb->update(
                    $wpdb->prefix . 'wh_inventory_items',
                    array('stock_status' => 'low-stock'),
                    array('id' => $item_id),
                    array('%s'),
                    array('%d')
                );
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            wp_send_json_success(array(
                'sale_id' => $wpdb->insert_id,
                'sale_number' => $sale_number,
                'message' => 'Sale recorded successfully',
                'remaining_stock' => $new_quantity
            ));
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $wpdb->query('ROLLBACK');
            wp_send_json_error('Failed to record sale: ' . $e->getMessage());
        }
    }
    
    public function handle_get_profit_data() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $period_type = isset($_POST['period_type']) ? sanitize_text_field($_POST['period_type']) : 'daily';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : current_time('Y-m-d');
        
        // Calculate profit data for the specified date and period
        if ($period_type === 'monthly') {
            // For monthly, use first day of the month
            $date = date('Y-m-01', strtotime($date));
            
            // Get sales data for the entire month
            $sales_data = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, i.purchase_price 
                 FROM {$wpdb->prefix}wh_sales s
                 LEFT JOIN {$wpdb->prefix}wh_inventory_items i ON s.item_id = i.id
                 WHERE YEAR(s.sale_date) = %d AND MONTH(s.sale_date) = %d",
                date('Y', strtotime($date)),
                date('n', strtotime($date))
            ));
        } else {
            // For daily
            $sales_data = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, i.purchase_price 
                 FROM {$wpdb->prefix}wh_sales s
                 LEFT JOIN {$wpdb->prefix}wh_inventory_items i ON s.item_id = i.id
                 WHERE DATE(s.sale_date) = %s",
                $date
            ));
        }
        
        $total_sales = 0;
        $total_cost = 0;
        $sales_count = 0;
        $items_sold = 0;
        
        error_log('Profit calculation for date: ' . $date . ', period: ' . $period_type);
        error_log('Found sales data: ' . count($sales_data) . ' records');
        
        foreach ($sales_data as $sale) {
            $total_sales += floatval($sale->total_amount);
            $cost = floatval($sale->purchase_price) * intval($sale->quantity_sold);
            $total_cost += $cost;
            $sales_count++;
            $items_sold += intval($sale->quantity_sold);
            
            error_log("Sale ID {$sale->id}: Amount={$sale->total_amount}, Purchase Price={$sale->purchase_price}, Quantity={$sale->quantity_sold}, Cost={$cost}");
        }
        
        error_log("Totals: Sales={$total_sales}, Cost={$total_cost}, Profit=" . ($total_sales - $total_cost));
        
        $total_profit = $total_sales - $total_cost;
        $profit_margin = $total_sales > 0 ? ($total_profit / $total_sales) * 100 : 0;
        
        wp_send_json_success(array(
            'total_sales' => $total_sales,
            'total_cost' => $total_cost,
            'total_profit' => $total_profit,
            'profit_margin' => number_format($profit_margin, 2),
            'sales_count' => $sales_count,
            'items_sold' => $items_sold,
            'period_type' => $period_type,
            'date' => $date
        ));
    }
    
    public function handle_rebuild_profit_data() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        try {
            // Clear existing profit tracking data
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}wh_profit_tracking");
            
            // Get all sales grouped by date
            $sales_by_date = $wpdb->get_results(
                "SELECT DATE(sale_date) as sale_date, 
                        SUM(total_amount) as total_sales,
                        COUNT(*) as sales_count,
                        SUM(quantity_sold) as items_sold
                 FROM {$wpdb->prefix}wh_sales 
                 GROUP BY DATE(sale_date)
                 ORDER BY sale_date"
            );
            
            foreach ($sales_by_date as $daily_sales) {
                $date = $daily_sales->sale_date;
                
                // Calculate cost for this date
                $sales_data = $wpdb->get_results($wpdb->prepare(
                    "SELECT s.quantity_sold, i.purchase_price 
                     FROM {$wpdb->prefix}wh_sales s
                     LEFT JOIN {$wpdb->prefix}wh_inventory_items i ON s.item_id = i.id
                     WHERE DATE(s.sale_date) = %s",
                    $date
                ));
                
                $total_cost = 0;
                foreach ($sales_data as $sale) {
                    $total_cost += floatval($sale->purchase_price) * intval($sale->quantity_sold);
                }
                
                $total_sales = floatval($daily_sales->total_sales);
                $total_profit = $total_sales - $total_cost;
                $profit_margin = $total_sales > 0 ? ($total_profit / $total_sales) * 100 : 0;
                
                // Insert daily profit record
                $wpdb->insert(
                    $wpdb->prefix . 'wh_profit_tracking',
                    array(
                        'date_period' => $date,
                        'period_type' => 'daily',
                        'total_sales' => $total_sales,
                        'total_cost' => $total_cost,
                        'total_profit' => $total_profit,
                        'profit_margin' => $profit_margin,
                        'sales_count' => intval($daily_sales->sales_count),
                        'items_sold' => intval($daily_sales->items_sold)
                    )
                );
            }
            
            // Build monthly aggregates
            $monthly_data = $wpdb->get_results(
                "SELECT YEAR(sale_date) as year, MONTH(sale_date) as month,
                        SUM(total_amount) as total_sales,
                        COUNT(*) as sales_count,
                        SUM(quantity_sold) as items_sold
                 FROM {$wpdb->prefix}wh_sales 
                 GROUP BY YEAR(sale_date), MONTH(sale_date)
                 ORDER BY year, month"
            );
            
            foreach ($monthly_data as $monthly_sales) {
                $date = sprintf('%04d-%02d-01', $monthly_sales->year, $monthly_sales->month);
                
                // Calculate cost for this month
                $sales_data = $wpdb->get_results($wpdb->prepare(
                    "SELECT s.quantity_sold, i.purchase_price 
                     FROM {$wpdb->prefix}wh_sales s
                     LEFT JOIN {$wpdb->prefix}wh_inventory_items i ON s.item_id = i.id
                     WHERE YEAR(s.sale_date) = %d AND MONTH(s.sale_date) = %d",
                    $monthly_sales->year,
                    $monthly_sales->month
                ));
                
                $total_cost = 0;
                foreach ($sales_data as $sale) {
                    $total_cost += floatval($sale->purchase_price) * intval($sale->quantity_sold);
                }
                
                $total_sales = floatval($monthly_sales->total_sales);
                $total_profit = $total_sales - $total_cost;
                $profit_margin = $total_sales > 0 ? ($total_profit / $total_sales) * 100 : 0;
                
                // Insert monthly profit record
                $wpdb->insert(
                    $wpdb->prefix . 'wh_profit_tracking',
                    array(
                        'date_period' => $date,
                        'period_type' => 'monthly',
                        'total_sales' => $total_sales,
                        'total_cost' => $total_cost,
                        'total_profit' => $total_profit,
                        'profit_margin' => $profit_margin,
                        'sales_count' => intval($monthly_sales->sales_count),
                        'items_sold' => intval($monthly_sales->items_sold)
                    )
                );
            }
            
            wp_send_json_success('Profit data rebuilt successfully');
            
        } catch (Exception $e) {
            wp_send_json_error('Failed to rebuild profit data: ' . $e->getMessage());
        }
    }
    
    public function handle_fix_purchase_prices() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Update items with no purchase price to 70% of selling price
        $result = $wpdb->query(
            "UPDATE {$wpdb->prefix}wh_inventory_items 
             SET purchase_price = selling_price * 0.7 
             WHERE (purchase_price IS NULL OR purchase_price = 0) 
             AND selling_price > 0"
        );
        
        if ($result !== false) {
            wp_send_json_success("Updated $result items with estimated purchase prices (70% of selling price)");
        } else {
            wp_send_json_error('Failed to update purchase prices');
        }
    }
    
    public function handle_debug_profit_data() {
        check_ajax_referer('warehouse_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $period_type = isset($_POST['period_type']) ? sanitize_text_field($_POST['period_type']) : 'daily';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : current_time('Y-m-d');
        
        // Get raw sales data
        if ($period_type === 'monthly') {
            $sales_data = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, i.purchase_price, i.name as item_name
                 FROM {$wpdb->prefix}wh_sales s
                 LEFT JOIN {$wpdb->prefix}wh_inventory_items i ON s.inventory_item_id = i.id
                 WHERE YEAR(s.sale_date) = %d AND MONTH(s.sale_date) = %d",
                date('Y', strtotime($date)),
                date('n', strtotime($date))
            ));
        } else {
            $sales_data = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, i.purchase_price, i.name as item_name
                 FROM {$wpdb->prefix}wh_sales s
                 LEFT JOIN {$wpdb->prefix}wh_inventory_items i ON s.inventory_item_id = i.id
                 WHERE DATE(s.sale_date) = %s",
                $date
            ));
        }
        
        $debug_info = array(
            'period_type' => $period_type,
            'date' => $date,
            'sales_count' => count($sales_data),
            'sales_data' => $sales_data,
            'total_sales' => array_sum(array_column($sales_data, 'total_amount')),
            'total_cost' => 0,
            'total_profit' => 0,
            'profit_margin' => 0
        );
        
        // Calculate totals
        foreach ($sales_data as $sale) {
            $cost = floatval($sale->purchase_price) * intval($sale->quantity_sold);
            $debug_info['total_cost'] += $cost;
        }
        
        $debug_info['total_profit'] = $debug_info['total_sales'] - $debug_info['total_cost'];
        $debug_info['profit_margin'] = $debug_info['total_sales'] > 0 ? 
            ($debug_info['total_profit'] / $debug_info['total_sales']) * 100 : 0;
        
        wp_send_json_success($debug_info);
    }
}

// Initialize the plugin
new WarehouseInventoryManager(); 