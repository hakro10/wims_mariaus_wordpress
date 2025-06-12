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
    // Create tables
    warehouse_inventory_create_tables();
    
    // Update table structures
    update_sales_table_structure();
    update_inventory_items_table_structure();
    
    // Create profit tracking table
    create_profit_tracking_table();
    
    // Add roles and capabilities
    add_warehouse_roles();
    add_warehouse_capabilities();
    
    // Populate sample data if needed
    // populate_sample_profit_data();
}
add_action('after_setup_theme', 'warehouse_inventory_setup');

// Enqueue styles and scripts
function warehouse_inventory_scripts() {
    wp_enqueue_style('warehouse-inventory-style', get_stylesheet_uri(), array(), '1.0.0');
    wp_enqueue_script('warehouse-inventory-script', get_template_directory_uri() . '/assets/js/warehouse.js', array('jquery'), '1.0.0', true);
    
    // Enqueue QR scanner for mobile optimization
    wp_enqueue_script('warehouse-qr-scanner', get_template_directory_uri() . '/assets/js/qr-scanner.js', array(), '1.0.0', true);
    
    // Localize script for AJAX
    wp_localize_script('warehouse-inventory-script', 'warehouse_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('warehouse_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'warehouse_inventory_scripts');

// Redirect users to warehouse dashboard after login
function warehouse_login_redirect($redirect_to, $request, $user) {
    // Check if user has errors (failed login)
    if (isset($user->errors) && !empty($user->errors)) {
        return $redirect_to;
    }
    
    // Check if user has warehouse access capabilities
    if (isset($user->ID) && (
        user_can($user->ID, 'manage_warehouse') || 
        user_can($user->ID, 'view_warehouse') || 
        user_can($user->ID, 'manage_options') ||
        in_array('administrator', $user->roles) ||
        in_array('warehouse_manager', $user->roles) ||
        in_array('warehouse_staff', $user->roles)
    )) {
        // Redirect to warehouse dashboard
        return home_url('/?tab=dashboard');
    }
    
    // For other users, use default redirect
    return $redirect_to;
}
add_filter('login_redirect', 'warehouse_login_redirect', 10, 3);

// Create custom database tables on theme activation
function warehouse_inventory_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Inventory Items table
    $table_name = $wpdb->prefix . 'wh_inventory_items';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        internal_id varchar(100) NOT NULL UNIQUE,
        serial_number varchar(100),
        description text,
        category_id mediumint(9),
        location_id mediumint(9),
        quantity int(11) NOT NULL DEFAULT 0,
        min_stock_level int(11) DEFAULT 1,
        purchase_price decimal(10,2),
        selling_price decimal(10,2),
        supplier varchar(255),
        status varchar(50) DEFAULT 'in-stock',
        qr_code_image text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by mediumint(9),
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    // Categories table
    $categories_table = $wpdb->prefix . 'wh_categories';
    $sql .= "CREATE TABLE $categories_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        color varchar(7) DEFAULT '#3b82f6',
        item_count int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    // Locations table
    $locations_table = $wpdb->prefix . 'wh_locations';
    $sql .= "CREATE TABLE $locations_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        type varchar(50) DEFAULT 'warehouse',
        description text,
        qr_code_image text,
        parent_id mediumint(9),
        level int(11) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    // Sales table
    $sales_table = $wpdb->prefix . 'wh_sales';
    $sql .= "CREATE TABLE $sales_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        inventory_item_id mediumint(9) NOT NULL,
        quantity_sold int(11) NOT NULL,
        unit_price decimal(10,2) NOT NULL,
        total_amount decimal(10,2) NOT NULL,
        customer_name varchar(255),
        customer_email varchar(255),
        sale_date datetime DEFAULT CURRENT_TIMESTAMP,
        sold_by mediumint(9),
        notes text,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    // Tasks table
    $tasks_table = $wpdb->prefix . 'wh_tasks';
    $sql .= "CREATE TABLE $tasks_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description text,
        status varchar(50) DEFAULT 'pending',
        priority varchar(20) DEFAULT 'medium',
        assigned_to mediumint(9),
        due_date datetime,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by mediumint(9),
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Insert default categories (only if they don't exist)
    $existing_categories = $wpdb->get_var("SELECT COUNT(*) FROM $categories_table");
    if ($existing_categories == 0) {
        $wpdb->insert($categories_table, array(
            'name' => 'Electronics',
            'description' => 'Electronic devices and components',
            'color' => '#3b82f6'
        ));
        
        $wpdb->insert($categories_table, array(
            'name' => 'Tools',
            'description' => 'Hand tools and equipment',
            'color' => '#10b981'
        ));
        
        $wpdb->insert($categories_table, array(
            'name' => 'Office Supplies',
            'description' => 'Office equipment and supplies',
            'color' => '#f59e0b'
        ));
    }
    
    // Insert default locations (only if they don't exist)
    $existing_locations = $wpdb->get_var("SELECT COUNT(*) FROM $locations_table");
    if ($existing_locations == 0) {
        $wpdb->insert($locations_table, array(
            'name' => 'Main Warehouse',
            'type' => 'warehouse',
            'description' => 'Primary storage facility',
            'level' => 1
        ));
        
        $wpdb->insert($locations_table, array(
            'name' => 'Section A',
            'type' => 'section',
            'description' => 'Electronics section',
            'parent_id' => 1,
            'level' => 2
        ));
    }
}

// Hook into theme activation
add_action('after_switch_theme', 'warehouse_inventory_create_tables');

// Function to update sales table structure
function update_sales_table_structure() {
    global $wpdb;
    
    $sales_table = $wpdb->prefix . 'wh_sales';
    
    // Check if columns exist before adding them
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $sales_table");
    $existing_columns = array_column($columns, 'Field');
    
    $columns_to_add = array(
        'customer_phone' => "ALTER TABLE $sales_table ADD COLUMN customer_phone VARCHAR(20) AFTER customer_email",
        'customer_address' => "ALTER TABLE $sales_table ADD COLUMN customer_address TEXT AFTER customer_phone",
        'warranty_period' => "ALTER TABLE $sales_table ADD COLUMN warranty_period VARCHAR(50) AFTER payment_status"
    );
    
    foreach ($columns_to_add as $column => $sql) {
        if (!in_array($column, $existing_columns)) {
            $wpdb->query($sql);
        }
    }
}

function update_inventory_items_table_structure() {
    global $wpdb;
    
    $items_table = $wpdb->prefix . 'wh_inventory_items';
    
    // Check if columns exist before adding them
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $items_table");
    $existing_columns = array_column($columns, 'Field');
    
    if (!in_array('tested', $existing_columns)) {
        $wpdb->query("ALTER TABLE $items_table ADD COLUMN tested BOOLEAN DEFAULT FALSE AFTER status");
    }
    
    if (!in_array('total_lot_price', $existing_columns)) {
        $wpdb->query("ALTER TABLE $items_table ADD COLUMN total_lot_price DECIMAL(10,2) DEFAULT NULL AFTER selling_price");
    }
}

// Run table update on theme activation and admin init
add_action('after_switch_theme', 'update_sales_table_structure');
add_action('admin_init', 'update_sales_table_structure');
add_action('after_switch_theme', 'update_inventory_items_table_structure');
add_action('admin_init', 'update_inventory_items_table_structure');

// Function to create profit tracking table
function create_profit_tracking_table() {
    global $wpdb;
    $profit_table = $wpdb->prefix . 'wh_profit_tracking';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$profit_table'");
    
    if (!$table_exists) {
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $profit_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            date_period date NOT NULL,
            period_type varchar(10) NOT NULL DEFAULT 'daily',
            total_sales decimal(10,2) DEFAULT 0,
            total_cost decimal(10,2) DEFAULT 0,
            total_profit decimal(10,2) DEFAULT 0,
            profit_margin decimal(5,2) DEFAULT 0,
            sales_count int(11) DEFAULT 0,
            items_sold int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_period (date_period, period_type),
            INDEX idx_date (date_period),
            INDEX idx_type (period_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log("Profit tracking table created successfully");
    }
}

// Run profit table creation
add_action('after_switch_theme', 'create_profit_tracking_table');
add_action('admin_init', 'create_profit_tracking_table');
add_action('admin_init', 'populate_sample_profit_data');

// Function to populate sample profit data (for testing)
function populate_sample_profit_data() {
    global $wpdb;
    $profit_table = $wpdb->prefix . 'wh_profit_tracking';
    
    // Check if we already have data
    $existing_data = $wpdb->get_var("SELECT COUNT(*) FROM $profit_table");
    if ($existing_data > 0) {
        return; // Don't add sample data if we already have records
    }
    
    // Add sample daily data for the last 7 days
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $sales = rand(500, 2000);
        $cost = $sales * 0.6; // 60% cost ratio
        $profit = $sales - $cost;
        $margin = ($profit / $sales) * 100;
        
        $wpdb->insert(
            $profit_table,
            array(
                'date_period' => $date,
                'period_type' => 'daily',
                'total_sales' => $sales,
                'total_cost' => $cost,
                'total_profit' => $profit,
                'profit_margin' => $margin,
                'sales_count' => rand(3, 8),
                'items_sold' => rand(5, 15)
            ),
            array('%s', '%s', '%f', '%f', '%f', '%f', '%d', '%d')
        );
    }
    
    // Add sample monthly data for current month
    $this_month = date('Y-m-01');
    $monthly_sales = rand(8000, 15000);
    $monthly_cost = $monthly_sales * 0.6;
    $monthly_profit = $monthly_sales - $monthly_cost;
    $monthly_margin = ($monthly_profit / $monthly_sales) * 100;
    
    $wpdb->insert(
        $profit_table,
        array(
            'date_period' => $this_month,
            'period_type' => 'monthly',
            'total_sales' => $monthly_sales,
            'total_cost' => $monthly_cost,
            'total_profit' => $monthly_profit,
            'profit_margin' => $monthly_margin,
            'sales_count' => rand(25, 45),
            'items_sold' => rand(50, 100)
        ),
        array('%s', '%s', '%f', '%f', '%f', '%f', '%d', '%d')
    );
}

// AJAX handlers for inventory operations
add_action('wp_ajax_add_inventory_item', 'handle_add_inventory_item');
add_action('wp_ajax_update_inventory_item', 'handle_update_inventory_item');
add_action('wp_ajax_delete_inventory_item', 'handle_delete_inventory_item');
add_action('wp_ajax_sell_inventory_item', 'handle_sell_inventory_item');
add_action('wp_ajax_sell_inventory_item_detailed', 'handle_sell_inventory_item_detailed');
add_action('wp_ajax_get_inventory_item', 'handle_get_inventory_item');
add_action('wp_ajax_get_inventory_items', 'handle_get_inventory_items');
add_action('wp_ajax_get_sale_details', 'handle_get_sale_details');
add_action('wp_ajax_record_sale', 'handle_record_sale');
add_action('wp_ajax_get_profit_data', 'handle_get_profit_data');
add_action('wp_ajax_rebuild_profit_data', 'handle_rebuild_profit_data');
add_action('wp_ajax_add_category', 'handle_add_category');
add_action('wp_ajax_delete_category', 'handle_delete_category');
add_action('wp_ajax_add_location', 'handle_add_location');
add_action('wp_ajax_get_category_items', 'handle_get_category_items');
add_action('wp_ajax_fix_purchase_prices', 'handle_fix_purchase_prices');
add_action('wp_ajax_debug_profit_data', 'handle_debug_profit_data');

function handle_get_category_items() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('view_warehouse')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $category_id = intval($_POST['category_id']);
    
    $items = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wh_inventory_items WHERE category_id = %d",
        $category_id
    ));
    
    if ($items) {
        wp_send_json_success($items);
    } else {
        wp_send_json_error('No items found for this category');
    }
}

function handle_add_inventory_item() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'wh_inventory_items';
    
    $name = sanitize_text_field($_POST['name']);
    $internal_id = sanitize_text_field($_POST['internal_id']);
    $description = sanitize_textarea_field($_POST['description']);
    $category_id = intval($_POST['category_id']);
    $location_id = intval($_POST['location_id']);
    $quantity = intval($_POST['quantity']);
    $purchase_price = floatval($_POST['purchase_price']);
    $selling_price = floatval($_POST['selling_price']);
    $total_lot_price = floatval($_POST['total_lot_price']);
    $tested = isset($_POST['tested']) ? 1 : 0;
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'name' => $name,
            'internal_id' => $internal_id,
            'description' => $description,
            'category_id' => $category_id,
            'location_id' => $location_id,
            'quantity' => $quantity,
            'purchase_price' => $purchase_price,
            'selling_price' => $selling_price,
            'total_lot_price' => $total_lot_price,
            'created_by' => get_current_user_id(),
            'status' => $quantity > 0 ? 'in-stock' : 'out-of-stock',
            'tested' => $tested
        )
    );
    
    if ($result) {
        $item_id = $wpdb->insert_id;
        
        // Automatically generate QR code for the new item
        $qr_data = json_encode([
            'type' => 'item',
            'id' => $item_id,
            'internal_id' => $internal_id,
            'name' => $name,
            'quantity' => $quantity,
            'tested' => $tested
        ]);
        
        $qr_url = generate_qr_code_url($qr_data);
        
        // Update the item with QR code URL
        $wpdb->update(
            $table_name,
            array('qr_code_image' => $qr_url),
            array('id' => $item_id),
            array('%s'),
            array('%d')
        );
        
        wp_send_json_success(array('id' => $item_id, 'qr_code' => $qr_url));
    } else {
        wp_send_json_error('Failed to add item');
    }
}

function handle_get_inventory_items() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    global $wpdb;
    
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $category = isset($_POST['category']) ? intval($_POST['category']) : 0;
    
    $sql = "SELECT i.*, c.name as category_name, l.name as location_name 
            FROM {$wpdb->prefix}wh_inventory_items i
            LEFT JOIN {$wpdb->prefix}wh_categories c ON i.category_id = c.id
            LEFT JOIN {$wpdb->prefix}wh_locations l ON i.location_id = l.id
            WHERE 1=1";
    
    if (!empty($search)) {
        $sql .= $wpdb->prepare(" AND (i.name LIKE %s OR i.internal_id LIKE %s)", 
            '%' . $search . '%', '%' . $search . '%');
    }
    
    if ($category > 0) {
        $sql .= $wpdb->prepare(" AND i.category_id = %d", $category);
    }
    
    $sql .= " ORDER BY i.created_at DESC";
    
    $items = $wpdb->get_results($sql);
    
    wp_send_json_success($items);
}

function handle_get_sale_details() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('view_warehouse')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $sale_id = intval($_POST['sale_id']);
    
    $sale = $wpdb->get_row($wpdb->prepare(
        "SELECT s.*, i.name as item_name, i.internal_id, u.display_name as sold_by_name
         FROM {$wpdb->prefix}wh_sales s
         LEFT JOIN {$wpdb->prefix}wh_inventory_items i ON s.item_id = i.id
         LEFT JOIN {$wpdb->prefix}users u ON s.sold_by = u.ID
         WHERE s.id = %d", 
        $sale_id
    ));
    
    if ($sale) {
        wp_send_json_success($sale);
    } else {
        wp_send_json_error('Sale not found');
    }
}

function handle_record_sale() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_inventory')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $items_table = $wpdb->prefix . 'wh_inventory_items';
    $sales_table = $wpdb->prefix . 'wh_sales';
    
    $item_id = intval($_POST['item_id']);
    $quantity_sold = intval($_POST['quantity']);
    $unit_price = floatval($_POST['unit_price']);
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $customer_email = sanitize_email($_POST['customer_email']);
    $payment_method = sanitize_text_field($_POST['payment_method']);
    $notes = sanitize_textarea_field($_POST['notes']);
    
    // Get current item details
    $item = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $items_table WHERE id = %d", 
        $item_id
    ));
    
    if (!$item) {
        wp_send_json_error('Item not found');
        return;
    }
    
    if ($item->quantity < $quantity_sold) {
        wp_send_json_error('Not enough stock available. Current stock: ' . $item->quantity);
        return;
    }
    
    // Generate sale number
    $sale_number = 'SALE-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Ensure unique sale number
    while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $sales_table WHERE sale_number = %s", $sale_number))) {
        $sale_number = 'SALE-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    $total_amount = $quantity_sold * $unit_price;
    
    // Start transaction
    $wpdb->query('START TRANSACTION');
    
    try {
        // Record the sale
        $sale_result = $wpdb->insert(
            $sales_table,
            array(
                'sale_number' => $sale_number,
                'item_id' => $item_id,
                'quantity_sold' => $quantity_sold,
                'unit_price' => $unit_price,
                'total_amount' => $total_amount,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'payment_method' => $payment_method,
                'payment_status' => 'completed',
                'notes' => $notes,
                'sold_by' => get_current_user_id(),
                'sale_date' => current_time('mysql')
            )
        );
        
        if (!$sale_result) {
            throw new Exception('Failed to record sale');
        }
        
        // Update inventory
        $new_quantity = $item->quantity - $quantity_sold;
        $new_status = $new_quantity == 0 ? 'out-of-stock' : 
                     ($new_quantity <= $item->min_stock_level ? 'low-stock' : 'in-stock');
        
        $update_result = $wpdb->update(
            $items_table,
            array(
                'quantity' => $new_quantity,
                'status' => $new_status
            ),
            array('id' => $item_id),
            array('%d', '%s'),
            array('%d')
        );
        
        if ($update_result === false) {
            throw new Exception('Failed to update inventory');
        }
        
        // Commit transaction
        $wpdb->query('COMMIT');
        
        // Update profit tracking after successful sale - use current time for consistency
        $sale_date = current_time('mysql');
        update_profit_tracking($item_id, $quantity_sold, $unit_price, $sale_date);
        
        wp_send_json_success(array(
            'sale_number' => $sale_number,
            'message' => 'Sale recorded successfully!'
        ));
        
    } catch (Exception $e) {
        // Rollback transaction
        $wpdb->query('ROLLBACK');
        wp_send_json_error('Transaction failed: ' . $e->getMessage());
    }
}

// Function to update profit tracking
function update_profit_tracking($item_id, $quantity_sold, $unit_price, $sale_date = null) {
    global $wpdb;
    
    // Get item cost price
    $item = $wpdb->get_row($wpdb->prepare(
        "SELECT purchase_price FROM {$wpdb->prefix}wh_inventory_items WHERE id = %d", 
        $item_id
    ));
    
    error_log("Profit tracking debug - Item ID: $item_id, Item data: " . print_r($item, true));
    
    if (!$item || !$item->purchase_price) {
        error_log("Profit tracking skipped - No item found or no purchase price. Item: " . print_r($item, true));
        return; // Can't calculate profit without cost price
    }
    
    $cost_price = $item->purchase_price;
    $sale_amount = $quantity_sold * $unit_price;
    $cost_amount = $quantity_sold * $cost_price;
    $profit_amount = $sale_amount - $cost_amount;
    
    error_log("Profit calculation - Sale: $sale_amount, Cost: $cost_amount, Profit: $profit_amount");
    
    // Use provided sale date or current date - ensure consistent timezone handling
    if ($sale_date) {
        // Convert to WordPress timezone
        $sale_date_obj = new DateTime($sale_date, new DateTimeZone('UTC'));
        $sale_date_obj->setTimezone(new DateTimeZone(wp_timezone_string()));
        $today = $sale_date_obj->format('Y-m-d');
        $this_month = $sale_date_obj->format('Y-m-01');
        
        // Debug logging
        error_log("Profit tracking - Sale date: $sale_date, Converted to: $today, Month: $this_month");
    } else {
        $today = current_time('Y-m-d');
        $this_month = current_time('Y-m-01');
        
        // Debug logging
        error_log("Profit tracking - Using current time: $today, Month: $this_month");
    }
    
    // Update daily profit
    update_profit_record($today, 'daily', $sale_amount, $cost_amount, $profit_amount, 1, $quantity_sold);
    
    // Update monthly profit
    update_profit_record($this_month, 'monthly', $sale_amount, $cost_amount, $profit_amount, 1, $quantity_sold);
}

function update_profit_record($date_period, $period_type, $sale_amount, $cost_amount, $profit_amount, $sales_count, $items_sold) {
    global $wpdb;
    $profit_table = $wpdb->prefix . 'wh_profit_tracking';
    
    error_log("Update profit record - Period: $date_period, Type: $period_type, Sale: $sale_amount, Cost: $cost_amount, Profit: $profit_amount");
    
    // Check if record exists
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $profit_table WHERE date_period = %s AND period_type = %s",
        $date_period, $period_type
    ));
    
    if ($existing) {
        // Update existing record
        $new_total_sales = $existing->total_sales + $sale_amount;
        $new_total_cost = $existing->total_cost + $cost_amount;
        $new_total_profit = $existing->total_profit + $profit_amount;
        $new_sales_count = $existing->sales_count + $sales_count;
        $new_items_sold = $existing->items_sold + $items_sold;
        $new_profit_margin = $new_total_sales > 0 ? ($new_total_profit / $new_total_sales) * 100 : 0;
        
        error_log("Updating existing record - New Sales: $new_total_sales, New Cost: $new_total_cost, New Profit: $new_total_profit, New Margin: $new_profit_margin");
        
        $result = $wpdb->update(
            $profit_table,
            array(
                'total_sales' => $new_total_sales,
                'total_cost' => $new_total_cost,
                'total_profit' => $new_total_profit,
                'profit_margin' => $new_profit_margin,
                'sales_count' => $new_sales_count,
                'items_sold' => $new_items_sold
            ),
            array(
                'date_period' => $date_period,
                'period_type' => $period_type
            ),
            array('%f', '%f', '%f', '%f', '%d', '%d'),
            array('%s', '%s')
        );
        
        error_log("Update result: " . ($result !== false ? 'Success' : 'Failed - ' . $wpdb->last_error));
    } else {
        // Create new record
        $profit_margin = $sale_amount > 0 ? ($profit_amount / $sale_amount) * 100 : 0;
        
        error_log("Creating new record - Sales: $sale_amount, Cost: $cost_amount, Profit: $profit_amount, Margin: $profit_margin");
        
        $result = $wpdb->insert(
            $profit_table,
            array(
                'date_period' => $date_period,
                'period_type' => $period_type,
                'total_sales' => $sale_amount,
                'total_cost' => $cost_amount,
                'total_profit' => $profit_amount,
                'profit_margin' => $profit_margin,
                'sales_count' => $sales_count,
                'items_sold' => $items_sold
            ),
            array('%s', '%s', '%f', '%f', '%f', '%f', '%d', '%d')
        );
        
        error_log("Insert result: " . ($result !== false ? 'Success' : 'Failed - ' . $wpdb->last_error));
    }
}

function handle_get_profit_data() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('view_warehouse')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $profit_table = $wpdb->prefix . 'wh_profit_tracking';
    
    $period_type = isset($_POST['period_type']) ? sanitize_text_field($_POST['period_type']) : 'daily';
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : current_time('Y-m-d');
    
    if ($period_type === 'monthly') {
        $date = date('Y-m-01', strtotime($date)); // First day of month
    }
    
    error_log("Get profit data - Requested date: $date, Period: $period_type");
    
    $profit_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $profit_table WHERE date_period = %s AND period_type = %s",
        $date, $period_type
    ));
    
    error_log("Query result: " . print_r($profit_data, true));
    
    if (!$profit_data) {
        // Return zero data if no records found
        $profit_data = (object) array(
            'total_sales' => 0,
            'total_cost' => 0,
            'total_profit' => 0,
            'profit_margin' => 0,
            'sales_count' => 0,
            'items_sold' => 0
        );
        error_log("No profit data found, returning zeros");
    }
    
    wp_send_json_success($profit_data);
}

function handle_get_inventory_item() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('view_warehouse')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $item_id = intval($_POST['item_id']);
    
    $item = $wpdb->get_row($wpdb->prepare(
        "SELECT i.*, c.name as category_name, l.name as location_name 
         FROM {$wpdb->prefix}wh_inventory_items i
         LEFT JOIN {$wpdb->prefix}wh_categories c ON i.category_id = c.id
         LEFT JOIN {$wpdb->prefix}wh_locations l ON i.location_id = l.id
         WHERE i.id = %d", 
        $item_id
    ));
    
    if ($item) {
        wp_send_json_success($item);
    } else {
        wp_send_json_error('Item not found');
    }
}

function handle_delete_inventory_item() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('delete_inventory')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'wh_inventory_items';
    
    $item_id = intval($_POST['item_id']);
    
    $result = $wpdb->delete(
        $table_name,
        array('id' => $item_id),
        array('%d')
    );
    
    if ($result) {
        wp_send_json_success('Item deleted successfully');
    } else {
        wp_send_json_error('Failed to delete item');
    }
}

function handle_sell_inventory_item() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_inventory')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $items_table = $wpdb->prefix . 'wh_inventory_items';
    $sales_table = $wpdb->prefix . 'wh_sales';
    
    $item_id = intval($_POST['item_id']);
    $quantity_sold = intval($_POST['quantity']);
    
    // Get current item details
    $item = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $items_table WHERE id = %d", 
        $item_id
    ));
    
    if (!$item) {
        wp_send_json_error('Item not found');
        return;
    }
    
    if ($item->quantity < $quantity_sold) {
        wp_send_json_error('Not enough stock available. Current stock: ' . $item->quantity);
        return;
    }
    
    // Calculate new quantity and status
    $new_quantity = $item->quantity - $quantity_sold;
    $new_status = $new_quantity == 0 ? 'out-of-stock' : 
                 ($new_quantity <= $item->min_stock_level ? 'low-stock' : 'in-stock');
    
    // Update inventory
    $update_result = $wpdb->update(
        $items_table,
        array(
            'quantity' => $new_quantity,
            'status' => $new_status
        ),
        array('id' => $item_id),
        array('%d', '%s'),
        array('%d')
    );
    
    if ($update_result === false) {
        wp_send_json_error('Failed to update inventory');
        return;
    }
    
    // Record the sale
    $sale_amount = $item->selling_price ? ($item->selling_price * $quantity_sold) : 0;
    
    $sale_result = $wpdb->insert(
        $sales_table,
        array(
            'item_id' => $item_id,
            'quantity_sold' => $quantity_sold,
            'unit_price' => $item->selling_price,
            'total_amount' => $sale_amount,
            'sale_date' => current_time('mysql'),
            'sold_by' => get_current_user_id()
        ),
        array('%d', '%d', '%f', '%f', '%s', '%d')
    );
    
    if ($sale_result) {
        wp_send_json_success(array(
            'message' => 'Sale recorded successfully',
            'new_quantity' => $new_quantity,
            'new_status' => $new_status
        ));
    } else {
        wp_send_json_error('Sale processed but failed to record transaction');
    }
}

function handle_sell_inventory_item_detailed() {
    error_log('Sell item detailed handler called');
    error_log('POST data: ' . print_r($_POST, true));
    
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_inventory')) {
        error_log('User lacks edit_inventory capability');
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $items_table = $wpdb->prefix . 'wh_inventory_items';
    $sales_table = $wpdb->prefix . 'wh_sales';
    
    // Check if sales table exists and has required columns
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$sales_table'");
    if (!$table_exists) {
        error_log('Sales table does not exist: ' . $sales_table);
        wp_send_json_error('Sales table not found. Please contact administrator.');
        return;
    }
    
    // Get form data
    $item_id = intval($_POST['item_id']);
    $quantity_sold = intval($_POST['quantity']);
    $unit_price = floatval($_POST['unit_price']);
    $total_amount = floatval($_POST['total_amount']);
    
    error_log("Form data - Item ID: $item_id, Quantity: $quantity_sold, Unit Price: $unit_price, Total: $total_amount");
    
    // Validate basic data
    if (!$item_id || $quantity_sold <= 0 || $unit_price <= 0) {
        error_log('Invalid form data received');
        wp_send_json_error('Invalid form data. Please check all required fields.');
        return;
    }
    
    // Buyer information
    $buyer_name = sanitize_text_field($_POST['buyer_name']);
    $buyer_phone = sanitize_text_field($_POST['buyer_phone']);
    $buyer_email = sanitize_email($_POST['buyer_email']);
    $buyer_address = sanitize_textarea_field($_POST['buyer_address']);
    
    // Sale details
    $payment_method = sanitize_text_field($_POST['payment_method']);
    $warranty_period = sanitize_text_field($_POST['warranty_period']);
    $sale_notes = sanitize_textarea_field($_POST['sale_notes']);
    $sale_date = sanitize_text_field($_POST['sale_date']);
    
    // Convert sale_date to MySQL format
    $sale_date_mysql = date('Y-m-d H:i:s', strtotime($sale_date));
    
    // Get current item details
    $item = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $items_table WHERE id = %d", 
        $item_id
    ));
    
    if (!$item) {
        wp_send_json_error('Item not found');
        return;
    }
    
    if ($item->quantity < $quantity_sold) {
        wp_send_json_error('Not enough stock available. Current stock: ' . $item->quantity);
        return;
    }
    
    // Generate unique sale number
    $sale_number = 'SALE-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Check if sale number exists and regenerate if needed
    while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $sales_table WHERE sale_number = %s", $sale_number))) {
        $sale_number = 'SALE-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    // Calculate warranty expiry date
    $warranty_expiry = null;
    if ($warranty_period) {
        switch ($warranty_period) {
            case '30_days':
                $warranty_expiry = date('Y-m-d H:i:s', strtotime('+30 days', strtotime($sale_date_mysql)));
                break;
            case '90_days':
                $warranty_expiry = date('Y-m-d H:i:s', strtotime('+90 days', strtotime($sale_date_mysql)));
                break;
            case '6_months':
                $warranty_expiry = date('Y-m-d H:i:s', strtotime('+6 months', strtotime($sale_date_mysql)));
                break;
            case '1_year':
                $warranty_expiry = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($sale_date_mysql)));
                break;
            case '2_years':
                $warranty_expiry = date('Y-m-d H:i:s', strtotime('+2 years', strtotime($sale_date_mysql)));
                break;
        }
    }
    
    // Start transaction
    $wpdb->query('START TRANSACTION');
    
    try {
        // Update inventory
        $new_quantity = $item->quantity - $quantity_sold;
        $new_status = $new_quantity == 0 ? 'out-of-stock' : 
                     ($new_quantity <= $item->min_stock_level ? 'low-stock' : 'in-stock');
        
        $update_result = $wpdb->update(
            $items_table,
            array(
                'quantity' => $new_quantity,
                'status' => $new_status
            ),
            array('id' => $item_id),
            array('%d', '%s'),
            array('%d')
        );
        
        if ($update_result === false) {
            throw new Exception('Failed to update inventory');
        }
        
        // Record the sale - using only confirmed existing columns
        $sale_data = array(
            'sale_number' => $sale_number,
            'item_id' => $item_id,
            'quantity_sold' => $quantity_sold,
            'unit_price' => $unit_price,
            'total_amount' => $total_amount,
            'customer_name' => $buyer_name,
            'customer_phone' => $buyer_phone,
            'customer_email' => $buyer_email,
            'customer_address' => $buyer_address,
            'payment_method' => $payment_method,
            'payment_status' => 'completed',
            'sale_date' => $sale_date_mysql,
            'sold_by' => get_current_user_id(),
            'notes' => $sale_notes
        );
        
        error_log('Attempting to insert sale data: ' . print_r($sale_data, true));
        
        // Add warranty info to metadata
        $metadata = array(
            'warranty_period' => $warranty_period,
            'warranty_expiry' => $warranty_expiry,
            'item_name' => $item->name,
            'item_internal_id' => $item->internal_id
        );
        $sale_data['metadata'] = json_encode($metadata);
        
        $sale_result = $wpdb->insert($sales_table, $sale_data);
        
        if ($sale_result === false) {
            error_log('Database insert failed. Last error: ' . $wpdb->last_error);
            error_log('Last query: ' . $wpdb->last_query);
            throw new Exception('Failed to record sale: ' . $wpdb->last_error);
        }
        
        error_log('Sale inserted successfully with ID: ' . $wpdb->insert_id);
        
        // Commit transaction
        $wpdb->query('COMMIT');
        
        // Update profit tracking after successful sale - use the actual sale date
        update_profit_tracking($item_id, $quantity_sold, $unit_price, $sale_date_mysql);
        
        wp_send_json_success(array(
            'message' => 'Sale completed successfully',
            'sale_number' => $sale_number,
            'new_quantity' => $new_quantity,
            'new_status' => $new_status,
            'warranty_expiry' => $warranty_expiry
        ));
        
    } catch (Exception $e) {
        // Rollback transaction
        $wpdb->query('ROLLBACK');
        error_log('Sale transaction failed: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        wp_send_json_error('Transaction failed: ' . $e->getMessage());
    }
}

function handle_update_inventory_item() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_inventory')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'wh_inventory_items';
    
    $item_id = intval($_POST['item_id']);
    $name = sanitize_text_field($_POST['name']);
    $internal_id = sanitize_text_field($_POST['internal_id']);
    $description = sanitize_textarea_field($_POST['description']);
    $category_id = intval($_POST['category_id']);
    $location_id = intval($_POST['location_id']);
    $quantity = intval($_POST['quantity']);
    $min_stock_level = intval($_POST['min_stock_level']);
    $purchase_price = floatval($_POST['purchase_price']);
    $selling_price = floatval($_POST['selling_price']);
    $supplier = sanitize_text_field($_POST['supplier']);
    
    // Determine status based on quantity
    $status = $quantity == 0 ? 'out-of-stock' : 
             ($quantity <= $min_stock_level ? 'low-stock' : 'in-stock');
    
    $result = $wpdb->update(
        $table_name,
        array(
            'name' => $name,
            'internal_id' => $internal_id,
            'description' => $description,
            'category_id' => $category_id,
            'location_id' => $location_id,
            'quantity' => $quantity,
            'min_stock_level' => $min_stock_level,
            'purchase_price' => $purchase_price,
            'selling_price' => $selling_price,
            'supplier' => $supplier,
            'status' => $status
        ),
        array('id' => $item_id),
        array('%s', '%s', '%s', '%d', '%d', '%d', '%d', '%f', '%f', '%s', '%s'),
        array('%d')
    );
    
    if ($result !== false) {
        wp_send_json_success('Item updated successfully');
    } else {
        wp_send_json_error('Failed to update item');
    }
}

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

// Get all categories
function get_all_categories() {
    global $wpdb;
    
    $categories = $wpdb->get_results("
        SELECT c.*, 
               COUNT(i.id) as item_count
        FROM {$wpdb->prefix}wh_categories c
        LEFT JOIN {$wpdb->prefix}wh_inventory_items i ON c.id = i.category_id
        GROUP BY c.id
        ORDER BY c.name
    ");
    
    return $categories;
}

// Get all locations
function get_all_locations() {
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wh_locations ORDER BY level, name");
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

// Function to rebuild profit data from existing sales (for fixing timezone issues)
function rebuild_profit_data() {
    global $wpdb;
    
    // Clear existing profit data
    $profit_table = $wpdb->prefix . 'wh_profit_tracking';
    $wpdb->query("DELETE FROM $profit_table");
    
    // Get all sales with item purchase prices
    $sales = $wpdb->get_results("
        SELECT s.*, i.purchase_price 
        FROM {$wpdb->prefix}wh_sales s
        LEFT JOIN {$wpdb->prefix}wh_inventory_items i ON s.item_id = i.id
        WHERE i.purchase_price IS NOT NULL AND i.purchase_price > 0
        ORDER BY s.sale_date ASC
    ");
    
    foreach ($sales as $sale) {
        // Recalculate profit for each sale using the correct sale date
        update_profit_tracking($sale->item_id, $sale->quantity_sold, $sale->unit_price, $sale->sale_date);
    }
    
    error_log("Rebuilt profit data for " . count($sales) . " sales");
}

// Add AJAX handler for rebuilding profit data
add_action('wp_ajax_rebuild_profit_data', 'handle_rebuild_profit_data');

function handle_rebuild_profit_data() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    rebuild_profit_data();
    wp_send_json_success('Profit data rebuilt successfully');
}

// Category management handlers
function handle_add_category() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_inventory')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $categories_table = $wpdb->prefix . 'wh_categories';
    
    $name = sanitize_text_field($_POST['name']);
    $description = sanitize_textarea_field($_POST['description']);
    $color = sanitize_text_field($_POST['color']);
    
    if (empty($name)) {
        wp_send_json_error('Category name is required');
        return;
    }
    
    // Check if category already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $categories_table WHERE name = %s",
        $name
    ));
    
    if ($existing) {
        wp_send_json_error('Category with this name already exists');
        return;
    }
    
    $result = $wpdb->insert(
        $categories_table,
        array(
            'name' => $name,
            'description' => $description,
            'color' => $color,
            'created_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%s')
    );
    
    if ($result) {
        wp_send_json_success('Category added successfully');
    } else {
        wp_send_json_error('Failed to add category');
    }
}

function handle_delete_category() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('delete_inventory')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $categories_table = $wpdb->prefix . 'wh_categories';
    $items_table = $wpdb->prefix . 'wh_inventory_items';
    
    $category_id = intval($_POST['category_id']);
    
    // Check if category has items
    $item_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $items_table WHERE category_id = %d",
        $category_id
    ));
    
    if ($item_count > 0) {
        wp_send_json_error("Cannot delete category. It contains $item_count items.");
        return;
    }
    
    $result = $wpdb->delete(
        $categories_table,
        array('id' => $category_id),
        array('%d')
    );
    
    if ($result) {
        wp_send_json_success('Category deleted successfully');
    } else {
        wp_send_json_error('Failed to delete category');
    }
}

// Location management handlers
function handle_add_location() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_inventory')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $locations_table = $wpdb->prefix . 'wh_locations';
    
    $name = sanitize_text_field($_POST['name']);
    $code = sanitize_text_field($_POST['code']);
    $type = sanitize_text_field($_POST['type']);
    $level = intval($_POST['level']);
    $description = sanitize_textarea_field($_POST['description']);
    
    if (empty($name)) {
        wp_send_json_error('Location name is required');
        return;
    }
    
    // Check if location already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $locations_table WHERE name = %s",
        $name
    ));
    
    if ($existing) {
        wp_send_json_error('Location with this name already exists');
        return;
    }
    
    // Check if code already exists (if provided)
    if (!empty($code)) {
        $existing_code = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $locations_table WHERE code = %s",
            $code
        ));
        
        if ($existing_code) {
            wp_send_json_error('Location with this code already exists');
            return;
        }
    }
    
    $result = $wpdb->insert(
        $locations_table,
        array(
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'level' => $level,
            'description' => $description,
            'created_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%d', '%s', '%s')
    );
    
    if ($result) {
        $location_id = $wpdb->insert_id;
        
        // Automatically generate QR code for the new location
        $qr_data = json_encode([
            'type' => 'location',
            'id' => $location_id,
            'name' => $name,
            'code' => $code ?? '',
            'location_type' => $type
        ]);
        
        $qr_url = generate_qr_code_url($qr_data);
        
        // Update the location with QR code URL
        $wpdb->update(
            $locations_table,
            array('qr_code_image' => $qr_url),
            array('id' => $location_id),
            array('%s'),
            array('%d')
        );
        
        wp_send_json_success(array(
            'message' => 'Location added successfully',
            'id' => $location_id,
            'qr_code' => $qr_url
        ));
    } else {
        wp_send_json_error('Failed to add location');
    }
}

function handle_fix_purchase_prices() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_inventory')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $items_table = $wpdb->prefix . 'wh_inventory_items';
    
    // Get all items with null or zero purchase prices
    $items = $wpdb->get_results("
        SELECT id, quantity, selling_price
        FROM $items_table
        WHERE purchase_price IS NULL OR purchase_price = 0
    ");
    
    $updated_count = 0;
    foreach ($items as $item) {
        if ($item->selling_price > 0) {
            // Set purchase price to 70% of selling price as a reasonable default
            $purchase_price = $item->selling_price * 0.7;
            $result = $wpdb->update(
                $items_table,
                array(
                    'purchase_price' => $purchase_price
                ),
                array('id' => $item->id),
                array('%f'),
                array('%d')
            );
            
            if ($result !== false) {
                $updated_count++;
            }
        }
    }
    
    wp_send_json_success("Updated purchase prices for $updated_count items");
}

function handle_debug_profit_data() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $profit_table = $wpdb->prefix . 'wh_profit_tracking';
    
    $period_type = isset($_POST['period_type']) ? sanitize_text_field($_POST['period_type']) : 'daily';
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : current_time('Y-m-d');
    
    if ($period_type === 'monthly') {
        $date = date('Y-m-01', strtotime($date)); // First day of month
    }
    
    $profit_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $profit_table WHERE date_period = %s AND period_type = %s",
        $date, $period_type
    ));
    
    if (!$profit_data) {
        // Return zero data if no records found
        $profit_data = (object) array(
            'total_sales' => 0,
            'total_cost' => 0,
            'total_profit' => 0,
            'profit_margin' => 0,
            'sales_count' => 0,
            'items_sold' => 0
        );
    }
    
    wp_send_json_success($profit_data);
}

// QR Code generation handlers
function handle_generate_qr_code() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_inventory')) {
        wp_die('Unauthorized');
    }
    
    $type = sanitize_text_field($_POST['type']);
    $id = intval($_POST['id']);
    
    if (!in_array($type, ['item', 'location'])) {
        wp_send_json_error('Invalid type');
        return;
    }
    
    global $wpdb;
    
    if ($type === 'item') {
        $table = $wpdb->prefix . 'wh_inventory_items';
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        
        if (!$item) {
            wp_send_json_error('Item not found');
            return;
        }
        
        $qr_data = json_encode([
            'type' => 'item',
            'id' => $item->id,
            'internal_id' => $item->internal_id,
            'name' => $item->name,
            'quantity' => $item->quantity,
            'tested' => $item->tested ?? 0
        ]);
        
    } else {
        $table = $wpdb->prefix . 'wh_locations';
        $location = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        
        if (!$location) {
            wp_send_json_error('Location not found');
            return;
        }
        
        $qr_data = json_encode([
            'type' => 'location',
            'id' => $location->id,
            'name' => $location->name,
            'location_type' => $location->type
        ]);
    }
    
    // Generate QR code using Google Charts API (simple solution)
    $qr_url = generate_qr_code_url($qr_data);
    
    // Update database with QR code URL
    $result = $wpdb->update(
        $table,
        array('qr_code_image' => $qr_url),
        array('id' => $id),
        array('%s'),
        array('%d')
    );
    
    if ($result !== false) {
        wp_send_json_success([
            'qr_url' => $qr_url,
            'data' => $qr_data
        ]);
    } else {
        wp_send_json_error('Failed to save QR code');
    }
}

function handle_get_qr_print_data() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    $type = sanitize_text_field($_POST['type']);
    $id = intval($_POST['id']);
    
    global $wpdb;
    
    if ($type === 'item') {
        $table = $wpdb->prefix . 'wh_inventory_items';
        $item = $wpdb->get_row($wpdb->prepare("
            SELECT i.*, c.name as category_name, l.name as location_name 
            FROM $table i
            LEFT JOIN {$wpdb->prefix}wh_categories c ON i.category_id = c.id
            LEFT JOIN {$wpdb->prefix}wh_locations l ON i.location_id = l.id
            WHERE i.id = %d
        ", $id));
        
        if (!$item) {
            wp_send_json_error('Item not found');
            return;
        }
        
        $additional_info = "<p>Category: " . ($item->category_name ?: 'Uncategorized') . "</p>";
        $additional_info .= "<p>Location: " . ($item->location_name ?: 'No location') . "</p>";
        $additional_info .= "<p>Quantity: " . number_format($item->quantity) . "</p>";
        $additional_info .= "<p>Tested: " . ($item->tested ? '✅ Yes' : '❌ No') . "</p>";
        
        wp_send_json_success([
            'qr_url' => $item->qr_code_image,
            'name' => $item->name,
            'id' => $item->internal_id,
            'additional_info' => $additional_info
        ]);
        
    } else {
        $table = $wpdb->prefix . 'wh_locations';
        $location = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        
        if (!$location) {
            wp_send_json_error('Location not found');
            return;
        }
        
        $additional_info = "<p>Type: " . ucwords($location->type) . "</p>";
        if ($location->description) {
            $additional_info .= "<p>Description: " . $location->description . "</p>";
        }
        
        wp_send_json_success([
            'qr_url' => $location->qr_code_image,
            'name' => $location->name,
            'id' => $location->id,
            'additional_info' => $additional_info
        ]);
    }
}

function generate_qr_code_url($data, $size = 200) {
    // Using QR Server API for QR code generation (more reliable than Google Charts)
    $encoded_data = urlencode($data);
    return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded_data}";
}

// Bulk QR code generation handler
function handle_generate_all_qr_codes() {
    check_ajax_referer('warehouse_nonce', 'nonce');
    
    if (!current_user_can('edit_inventory')) {
        wp_die('Unauthorized');
    }
    
    $type = sanitize_text_field($_POST['type']);
    
    if (!in_array($type, ['items', 'locations'])) {
        wp_send_json_error('Invalid type');
        return;
    }
    
    global $wpdb;
    $generated_count = 0;
    $updated_count = 0;
    
    if ($type === 'items') {
        $table = $wpdb->prefix . 'wh_inventory_items';
        $items = $wpdb->get_results("SELECT * FROM $table ORDER BY id");
        
        foreach ($items as $item) {
            $qr_data = json_encode([
                'type' => 'item',
                'id' => $item->id,
                'internal_id' => $item->internal_id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'tested' => $item->tested ?? 0
            ]);
            
            $qr_url = generate_qr_code_url($qr_data);
            
            $result = $wpdb->update(
                $table,
                array('qr_code_image' => $qr_url),
                array('id' => $item->id),
                array('%s'),
                array('%d')
            );
            
            if ($result !== false) {
                if (empty($item->qr_code_image)) {
                    $generated_count++;
                } else {
                    $updated_count++;
                }
            }
        }
        
        wp_send_json_success(array(
            'message' => "Generated QR codes for $generated_count items, updated $updated_count existing codes",
            'generated' => $generated_count,
            'updated' => $updated_count
        ));
        
    } else {
        $table = $wpdb->prefix . 'wh_locations';
        $locations = $wpdb->get_results("SELECT * FROM $table ORDER BY id");
        
        foreach ($locations as $location) {
            $qr_data = json_encode([
                'type' => 'location',
                'id' => $location->id,
                'name' => $location->name,
                'code' => $location->code ?? '',
                'location_type' => $location->type
            ]);
            
            $qr_url = generate_qr_code_url($qr_data);
            
            $result = $wpdb->update(
                $table,
                array('qr_code_image' => $qr_url),
                array('id' => $location->id),
                array('%s'),
                array('%d')
            );
            
            if ($result !== false) {
                if (empty($location->qr_code_image)) {
                    $generated_count++;
                } else {
                    $updated_count++;
                }
            }
        }
        
        wp_send_json_success(array(
            'message' => "Generated QR codes for $generated_count locations, updated $updated_count existing codes",
            'generated' => $generated_count,
            'updated' => $updated_count
        ));
    }
}

// Add AJAX action hooks
add_action('wp_ajax_generate_qr_code', 'handle_generate_qr_code');
add_action('wp_ajax_nopriv_generate_qr_code', 'handle_generate_qr_code');
add_action('wp_ajax_get_qr_print_data', 'handle_get_qr_print_data');
add_action('wp_ajax_generate_all_qr_codes', 'handle_generate_all_qr_codes');
add_action('wp_ajax_nopriv_get_qr_print_data', 'handle_get_qr_print_data');
?> 