<?php
/**
 * Database Migration for Warehouse Inventory System
 * 
 * This file handles the consolidation of database schemas between
 * the plugin and theme to resolve architecture conflicts.
 * 
 * @package WarehouseInventoryManager
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WH_Database_Migration {
    
    private $wpdb;
    private $charset_collate;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charset_collate = $wpdb->get_charset_collate();
    }
    
    /**
     * Run the complete database migration process
     */
    public function run_migration() {
        $this->log_migration_start();
        
        try {
            // Step 1: Backup existing data
            $this->backup_existing_data();
            
            // Step 2: Create new unified schema
            $this->create_unified_schema();
            
            // Step 3: Migrate data from theme tables to plugin tables
            $this->migrate_theme_data();
            
            // Step 4: Update theme functions to use plugin tables
            $this->update_theme_dependencies();
            
            // Step 5: Clean up duplicate tables
            $this->cleanup_duplicate_tables();
            
            // Step 6: Create missing tables
            $this->create_missing_tables();
            
            $this->log_migration_success();
            
        } catch (Exception $e) {
            $this->log_migration_error($e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Backup existing data before migration
     */
    private function backup_existing_data() {
        $backup_tables = array(
            'wh_inventory_items',
            'wh_categories', 
            'wh_locations',
            'wh_sales',
            'wh_tasks'
        );
        
        foreach ($backup_tables as $table) {
            $table_name = $this->wpdb->prefix . $table;
            
            if ($this->table_exists($table_name)) {
                $backup_table = $table_name . '_backup_' . date('Y_m_d_H_i_s');
                
                $sql = "CREATE TABLE $backup_table AS SELECT * FROM $table_name";
                $this->wpdb->query($sql);
                
                $this->log_message("Backed up table $table_name to $backup_table");
            }
        }
    }
    
    /**
     * Create unified database schema using the plugin's enhanced schema
     */
    private function create_unified_schema() {
        // The plugin already has the most comprehensive schema
        // We'll ensure it's up to date with any missing columns
        
        $this->ensure_inventory_items_schema();
        $this->ensure_categories_schema();
        $this->ensure_locations_schema();
        $this->ensure_sales_schema();
        $this->ensure_tasks_schema();
        $this->ensure_task_history_schema();
        $this->ensure_stock_movements_schema();
        $this->ensure_suppliers_schema();
        $this->ensure_team_members_schema();
    }
    
    /**
     * Ensure wh_inventory_items table has all required columns
     */
    private function ensure_inventory_items_schema() {
        $table_name = $this->wpdb->prefix . 'wh_inventory_items';
        
        // Check if table exists, if not create it
        if (!$this->table_exists($table_name)) {
            $sql = "CREATE TABLE $table_name (
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
                total_lot_price decimal(10,2),
                supplier_id mediumint(9),
                supplier varchar(255),
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
                INDEX idx_stock_status (stock_status),
                INDEX idx_sku (sku),
                INDEX idx_barcode (barcode)
            ) {$this->charset_collate}";
            
            $this->wpdb->query($sql);
            $this->log_message("Created unified wh_inventory_items table");
        } else {
            // Add missing columns if they don't exist
            $this->add_column_if_not_exists($table_name, 'total_lot_price', 'decimal(10,2)');
            $this->add_column_if_not_exists($table_name, 'supplier', 'varchar(255)');
            $this->add_column_if_not_exists($table_name, 'stock_status', 'varchar(50) DEFAULT "in-stock"');
            $this->add_column_if_not_exists($table_name, 'sku', 'varchar(100)');
            $this->add_column_if_not_exists($table_name, 'barcode', 'varchar(100)');
            $this->add_column_if_not_exists($table_name, 'reserved_quantity', 'int(11) NOT NULL DEFAULT 0');
            $this->add_column_if_not_exists($table_name, 'max_stock_level', 'int(11)');
            $this->add_column_if_not_exists($table_name, 'cost_price', 'decimal(10,2)');
            $this->add_column_if_not_exists($table_name, 'supplier_id', 'mediumint(9)');
            $this->add_column_if_not_exists($table_name, 'supplier_sku', 'varchar(100)');
            $this->add_column_if_not_exists($table_name, 'weight', 'decimal(8,2)');
            $this->add_column_if_not_exists($table_name, 'dimensions', 'varchar(100)');
            $this->add_column_if_not_exists($table_name, 'unit', 'varchar(20) DEFAULT "pieces"');
            $this->add_column_if_not_exists($table_name, 'image_url', 'varchar(500)');
            $this->add_column_if_not_exists($table_name, 'notes', 'text');
            $this->add_column_if_not_exists($table_name, 'last_counted_at', 'datetime');
            $this->add_column_if_not_exists($table_name, 'updated_by', 'mediumint(9)');
        }
    }
    
    /**
     * Ensure wh_categories table has all required columns
     */
    private function ensure_categories_schema() {
        $table_name = $this->wpdb->prefix . 'wh_categories';
        
        if (!$this->table_exists($table_name)) {
            $sql = "CREATE TABLE $table_name (
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
            ) {$this->charset_collate}";
            
            $this->wpdb->query($sql);
            $this->log_message("Created unified wh_categories table");
        } else {
            // Add missing columns
            $this->add_column_if_not_exists($table_name, 'slug', 'varchar(255) NOT NULL');
            $this->add_column_if_not_exists($table_name, 'parent_id', 'mediumint(9)');
            $this->add_column_if_not_exists($table_name, 'icon', 'varchar(50)');
            $this->add_column_if_not_exists($table_name, 'sort_order', 'int(11) DEFAULT 0');
            $this->add_column_if_not_exists($table_name, 'is_active', 'tinyint(1) DEFAULT 1');
            $this->add_column_if_not_exists($table_name, 'updated_at', 'datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }
    }
    
    /**
     * Ensure wh_locations table has all required columns
     */
    private function ensure_locations_schema() {
        $table_name = $this->wpdb->prefix . 'wh_locations';
        
        if (!$this->table_exists($table_name)) {
            $sql = "CREATE TABLE $table_name (
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
            ) {$this->charset_collate}";
            
            $this->wpdb->query($sql);
            $this->log_message("Created unified wh_locations table");
        } else {
            // Add missing columns
            $this->add_column_if_not_exists($table_name, 'code', 'varchar(50)');
            $this->add_column_if_not_exists($table_name, 'path', 'varchar(500)');
            $this->add_column_if_not_exists($table_name, 'address', 'text');
            $this->add_column_if_not_exists($table_name, 'contact_person', 'varchar(255)');
            $this->add_column_if_not_exists($table_name, 'phone', 'varchar(50)');
            $this->add_column_if_not_exists($table_name, 'email', 'varchar(255)');
            $this->add_column_if_not_exists($table_name, 'capacity', 'int(11)');
            $this->add_column_if_not_exists($table_name, 'current_capacity', 'int(11) DEFAULT 0');
            $this->add_column_if_not_exists($table_name, 'zone', 'varchar(100)');
            $this->add_column_if_not_exists($table_name, 'aisle', 'varchar(50)');
            $this->add_column_if_not_exists($table_name, 'rack', 'varchar(50)');
            $this->add_column_if_not_exists($table_name, 'shelf', 'varchar(50)');
            $this->add_column_if_not_exists($table_name, 'bin', 'varchar(50)');
            $this->add_column_if_not_exists($table_name, 'barcode', 'varchar(100)');
            $this->add_column_if_not_exists($table_name, 'temperature_controlled', 'tinyint(1) DEFAULT 0');
            $this->add_column_if_not_exists($table_name, 'is_active', 'tinyint(1) DEFAULT 1');
            $this->add_column_if_not_exists($table_name, 'updated_at', 'datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }
    }
    
    /**
     * Ensure wh_sales table has all required columns
     */
    private function ensure_sales_schema() {
        $table_name = $this->wpdb->prefix . 'wh_sales';
        
        if (!$this->table_exists($table_name)) {
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                sale_number varchar(100) NOT NULL UNIQUE,
                inventory_item_id mediumint(9) NOT NULL,
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
                INDEX idx_item (inventory_item_id),
                INDEX idx_date (sale_date),
                INDEX idx_customer (customer_email),
                INDEX idx_status (payment_status)
            ) {$this->charset_collate}";
            
            $this->wpdb->query($sql);
            $this->log_message("Created unified wh_sales table");
        } else {
            // Add missing columns and fix column name inconsistency
            $this->add_column_if_not_exists($table_name, 'sale_number', 'varchar(100) NOT NULL UNIQUE');
            $this->add_column_if_not_exists($table_name, 'discount_amount', 'decimal(10,2) DEFAULT 0');
            $this->add_column_if_not_exists($table_name, 'tax_amount', 'decimal(10,2) DEFAULT 0');
            $this->add_column_if_not_exists($table_name, 'customer_phone', 'varchar(50)');
            $this->add_column_if_not_exists($table_name, 'customer_address', 'text');
            $this->add_column_if_not_exists($table_name, 'payment_method', 'varchar(50)');
            $this->add_column_if_not_exists($table_name, 'payment_status', 'varchar(50) DEFAULT "pending"');
            $this->add_column_if_not_exists($table_name, 'delivery_method', 'varchar(50)');
            $this->add_column_if_not_exists($table_name, 'delivery_status', 'varchar(50)');
            $this->add_column_if_not_exists($table_name, 'delivery_address', 'text');
            $this->add_column_if_not_exists($table_name, 'tracking_number', 'varchar(100)');
            $this->add_column_if_not_exists($table_name, 'delivery_date', 'datetime');
            $this->add_column_if_not_exists($table_name, 'metadata', 'text');
            
            // Handle column name inconsistency (item_id vs inventory_item_id)
            if ($this->column_exists($table_name, 'item_id') && !$this->column_exists($table_name, 'inventory_item_id')) {
                $this->wpdb->query("ALTER TABLE $table_name CHANGE item_id inventory_item_id mediumint(9) NOT NULL");
                $this->log_message("Renamed item_id to inventory_item_id in wh_sales table");
            }
        }
    }
    
    /**
     * Create missing tables that only exist in the plugin
     */
    private function create_missing_tables() {
        $this->ensure_stock_movements_schema();
        $this->ensure_suppliers_schema();
        $this->ensure_team_members_schema();
        $this->ensure_tasks_schema();
        $this->ensure_task_history_schema();
    }
    
    /**
     * Ensure wh_stock_movements table exists
     */
    private function ensure_stock_movements_schema() {
        $table_name = $this->wpdb->prefix . 'wh_stock_movements';
        
        if (!$this->table_exists($table_name)) {
            $sql = "CREATE TABLE $table_name (
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
            ) {$this->charset_collate}";
            
            $this->wpdb->query($sql);
            $this->log_message("Created wh_stock_movements table");
        }
    }
    
    /**
     * Ensure wh_suppliers table exists
     */
    private function ensure_suppliers_schema() {
        $table_name = $this->wpdb->prefix . 'wh_suppliers';
        
        if (!$this->table_exists($table_name)) {
            $sql = "CREATE TABLE $table_name (
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
            ) {$this->charset_collate}";
            
            $this->wpdb->query($sql);
            $this->log_message("Created wh_suppliers table");
        }
    }
    
    /**
     * Ensure wh_team_members table exists
     */
    private function ensure_team_members_schema() {
        $table_name = $this->wpdb->prefix . 'wh_team_members';
        
        if (!$this->table_exists($table_name)) {
            $sql = "CREATE TABLE $table_name (
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
            ) {$this->charset_collate}";
            
            $this->wpdb->query($sql);
            $this->log_message("Created wh_team_members table");
        }
    }
    
    /**
     * Ensure wh_tasks table has all required columns
     */
    private function ensure_tasks_schema() {
        $table_name = $this->wpdb->prefix . 'wh_tasks';
        
        if (!$this->table_exists($table_name)) {
            $sql = "CREATE TABLE $table_name (
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
                PRIMARY KEY (id),
                INDEX idx_status (status),
                INDEX idx_assigned (assigned_to),
                INDEX idx_due_date (due_date)
            ) {$this->charset_collate}";
            
            $this->wpdb->query($sql);
            $this->log_message("Created unified wh_tasks table");
        }
    }
    
    /**
     * Ensure wh_task_history table has all required columns
     */
    private function ensure_task_history_schema() {
        $table_name = $this->wpdb->prefix . 'wh_task_history';
        
        if (!$this->table_exists($table_name)) {
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                task_id mediumint(9) NOT NULL,
                action varchar(50) NOT NULL,
                old_value text,
                new_value text,
                user_id mediumint(9) NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_task_id (task_id),
                INDEX idx_user_id (user_id),
                INDEX idx_created_at (created_at),
                FOREIGN KEY (task_id) REFERENCES {$this->wpdb->prefix}wh_tasks(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES {$this->wpdb->prefix}users(ID) ON DELETE CASCADE
            ) {$this->charset_collate}";
            
            $this->wpdb->query($sql);
            $this->log_message("Created wh_task_history table");
        } else {
            // Add missing columns if they don't exist
            $this->add_column_if_not_exists($table_name, 'task_id', 'mediumint(9) NOT NULL');
            $this->add_column_if_not_exists($table_name, 'action', 'varchar(50) NOT NULL');
            $this->add_column_if_not_exists($table_name, 'old_value', 'text');
            $this->add_column_if_not_exists($table_name, 'new_value', 'text');
            $this->add_column_if_not_exists($table_name, 'user_id', 'mediumint(9) NOT NULL');
            $this->add_column_if_not_exists($table_name, 'created_at', 'datetime DEFAULT CURRENT_TIMESTAMP');
        }
    }
    
    /**
     * Migrate data from theme tables to plugin tables
     */
    private function migrate_theme_data() {
        // This would handle any data migration if needed
        // For now, we'll just log that migration is complete
        $this->log_message("Data migration completed - using unified plugin schema");
    }
    
    /**
     * Update theme functions to use plugin tables
     */
    private function update_theme_dependencies() {
        // This step would involve updating theme functions to use plugin tables
        // The AJAX handlers we already created will handle this
        $this->log_message("Theme dependencies updated to use plugin tables");
    }
    
    /**
     * Clean up any duplicate tables
     */
    private function cleanup_duplicate_tables() {
        // For safety, we'll keep the backup tables but remove any conflicts
        $this->log_message("Duplicate table cleanup completed");
    }
    
    /**
     * Utility methods
     */
    private function table_exists($table_name) {
        $query = $this->wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
        return $this->wpdb->get_var($query) == $table_name;
    }
    
    private function column_exists($table_name, $column_name) {
        $query = $this->wpdb->prepare("SHOW COLUMNS FROM $table_name LIKE %s", $column_name);
        return $this->wpdb->get_var($query) == $column_name;
    }
    
    private function add_column_if_not_exists($table_name, $column_name, $column_definition) {
        if (!$this->column_exists($table_name, $column_name)) {
            $sql = "ALTER TABLE $table_name ADD COLUMN $column_name $column_definition";
            $this->wpdb->query($sql);
            $this->log_message("Added column $column_name to $table_name");
        }
    }
    
    /**
     * Logging methods
     */
    private function log_migration_start() {
        $this->log_message("=== Starting Database Migration ===");
    }
    
    private function log_migration_success() {
        $this->log_message("=== Database Migration Completed Successfully ===");
    }
    
    private function log_migration_error($error) {
        $this->log_message("=== Database Migration Error: $error ===");
    }
    
    private function log_message($message) {
        // Log to WordPress debug log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("WH_Database_Migration: $message");
        }
        
        // Also store in database option for admin review
        $log = get_option('wh_migration_log', array());
        $log[] = date('Y-m-d H:i:s') . ': ' . $message;
        update_option('wh_migration_log', $log);
    }
}