<?php
/**
 * Quick migration runner for task history table fix
 */

// Bootstrap WordPress
require_once('wp-config.php');
require_once('wp-content/plugins/warehouse-inventory-manager/includes/database-migration.php');

// Security check - only allow access if user is admin or this is localhost
if (!is_admin() && $_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1') {
    if (!current_user_can('administrator')) {
        wp_die('Unauthorized access');
    }
}

echo "<h2>Running Database Migration...</h2>\n";

try {
    $migration = new WH_Database_Migration();
    
    // Just run the task history schema fix
    echo "<p>Fixing task history table schema...</p>\n";
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'wh_task_history';
    
    // Check current structure
    echo "<h3>Current table structure:</h3>\n";
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    if ($columns) {
        echo "<ul>\n";
        foreach ($columns as $column) {
            echo "<li>{$column->Field} ({$column->Type})</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p>Table does not exist or has no columns.</p>\n";
    }
    
    // Add missing columns
    $columns_to_check = array(
        'task_id' => 'mediumint(9) NOT NULL',
        'action' => 'varchar(50) NOT NULL',
        'old_value' => 'text',
        'new_value' => 'text',
        'user_id' => 'mediumint(9) NOT NULL',
        'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP'
    );
    
    echo "<h3>Adding missing columns:</h3>\n";
    foreach ($columns_to_check as $col_name => $col_def) {
        $exists = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM $table_name LIKE %s", $col_name));
        
        if (!$exists) {
            $sql = "ALTER TABLE $table_name ADD COLUMN $col_name $col_def";
            echo "<p>Running: $sql</p>\n";
            
            $result = $wpdb->query($sql);
            if ($result === false) {
                echo "<p style='color:red;'>Error: " . $wpdb->last_error . "</p>\n";
            } else {
                echo "<p style='color:green;'>✓ Added column $col_name</p>\n";
            }
        } else {
            echo "<p>✓ Column $col_name already exists</p>\n";
        }
    }
    
    // Final structure
    echo "<h3>Final table structure:</h3>\n";
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "<ul>\n";
    foreach ($columns as $column) {
        echo "<li>{$column->Field} ({$column->Type})</li>\n";
    }
    echo "</ul>\n";
    
    echo "<h2 style='color:green;'>✓ Migration completed successfully!</h2>\n";
    
} catch (Exception $e) {
    echo "<h2 style='color:red;'>✗ Migration failed: " . $e->getMessage() . "</h2>\n";
}

echo "<p><a href='/'>← Back to site</a></p>\n";
?> 