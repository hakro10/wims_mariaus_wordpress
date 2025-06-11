<?php
// Debug file to check warehouse database tables and functions
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>Warehouse Debug Check</h1>";

// Check if tables exist
global $wpdb;

$tables = array(
    'wh_inventory_items',
    'wh_categories', 
    'wh_locations',
    'wh_tasks',
    'wh_sales',
    'wh_suppliers',
    'wh_task_history'
);

echo "<h2>Database Tables Check:</h2>";
foreach ($tables as $table) {
    $table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    echo $exists ? "✅ $table_name exists<br>" : "❌ $table_name missing<br>";
}

echo "<h2>Function Check:</h2>";
echo function_exists('get_dashboard_stats') ? "✅ get_dashboard_stats exists<br>" : "❌ get_dashboard_stats missing<br>";
echo function_exists('get_all_tasks') ? "✅ get_all_tasks exists<br>" : "❌ get_all_tasks missing<br>";

echo "<h2>Plugin Check:</h2>";
echo class_exists('WarehouseInventoryManager') ? "✅ Plugin class loaded<br>" : "❌ Plugin class missing<br>";

if (function_exists('get_dashboard_stats')) {
    echo "<h2>Dashboard Stats Test:</h2>";
    try {
        $stats = get_dashboard_stats();
        echo "<pre>";
        print_r($stats);
        echo "</pre>";
    } catch (Exception $e) {
        echo "❌ Error getting stats: " . $e->getMessage();
    }
}

echo "<h2>Data Count Check:</h2>";
foreach ($tables as $table) {
    $table_name = $wpdb->prefix . $table;
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "$table: $count records<br>";
}
?> 