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
    wp_enqueue_style('warehouse-inventory-style', get_stylesheet_uri(), array(), '1.0.0');
    wp_enqueue_script('warehouse-inventory-script', get_template_directory_uri() . '/assets/js/warehouse.js', array('jquery'), '1.0.0', true);
    
    // Localize script for AJAX
    wp_localize_script('warehouse-inventory-script', 'warehouse_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('warehouse_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'warehouse_inventory_scripts');

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
    
    // Insert default categories
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
    
    // Insert default locations
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

// Hook into theme activation
add_action('after_switch_theme', 'warehouse_inventory_create_tables');

// Function to update sales table structure
function update_sales_table_structure() {
    global $wpdb;
    $sales_table = $wpdb->prefix . 'wh_sales';
    
    // Check if sale_number column exists
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $sales_table LIKE 'sale_number'");
    
    if (empty($column_exists)) {
        // Add missing columns to match plugin structure
        $wpdb->query("ALTER TABLE $sales_table 
            ADD COLUMN sale_number varchar(100) NOT NULL UNIQUE AFTER id,
            ADD COLUMN item_id mediumint(9) NOT NULL AFTER sale_number,
            ADD COLUMN discount_amount decimal(10,2) DEFAULT 0 AFTER unit_price,
            ADD COLUMN tax_amount decimal(10,2) DEFAULT 0 AFTER discount_amount,
            ADD COLUMN customer_phone varchar(50) AFTER customer_email,
            ADD COLUMN customer_address text AFTER customer_phone,
            ADD COLUMN payment_method varchar(50) AFTER customer_address,
            ADD COLUMN payment_status varchar(50) DEFAULT 'completed' AFTER payment_method,
            ADD COLUMN delivery_method varchar(50) AFTER payment_status,
            ADD COLUMN delivery_status varchar(50) AFTER delivery_method,
            ADD COLUMN delivery_address text AFTER delivery_status,
            ADD COLUMN tracking_number varchar(100) AFTER delivery_address,
            ADD COLUMN delivery_date datetime AFTER tracking_number,
            ADD COLUMN warranty_period varchar(50) AFTER delivery_date,
            ADD COLUMN warranty_expiry_date datetime AFTER warranty_period,
            ADD COLUMN metadata text AFTER notes,
            ADD INDEX idx_sale_number (sale_number),
            ADD INDEX idx_item (item_id),
            ADD INDEX idx_payment_status (payment_status)");
        
        // Update existing records to have item_id = inventory_item_id
        $wpdb->query("UPDATE $sales_table SET item_id = inventory_item_id WHERE item_id IS NULL OR item_id = 0");
        
        error_log("Sales table structure updated successfully");
    }
}

// Run table update on theme activation and admin init
add_action('after_switch_theme', 'update_sales_table_structure');
add_action('admin_init', 'update_sales_table_structure');

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
            'created_by' => get_current_user_id(),
            'status' => $quantity > 0 ? 'in-stock' : 'out-of-stock'
        )
    );
    
    if ($result) {
        wp_send_json_success(array('id' => $wpdb->insert_id));
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
            'quantity' => $quantity_sold,
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
    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wh_categories ORDER BY name");
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
?> 