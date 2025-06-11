<?php
define('WP_USE_THEMES', false);
require_once('./wp-load.php');

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

// Tasks table
$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wh_tasks (
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

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

// Task history table
$sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wh_task_history (
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

dbDelta($sql2);

// Insert some sample tasks
$current_user_id = 1; // Admin user

$sample_tasks = array(
    array(
        'title' => 'Inventory Audit - Warehouse A',
        'description' => 'Complete physical count of all items in Warehouse A section 1-5',
        'status' => 'pending',
        'priority' => 'high',
        'assigned_to' => $current_user_id,
        'created_by' => $current_user_id,
        'due_date' => date('Y-m-d H:i:s', strtotime('+3 days'))
    ),
    array(
        'title' => 'Update Product Labels',
        'description' => 'Print and replace outdated product labels in electronics section',
        'status' => 'in_progress',
        'priority' => 'medium',
        'assigned_to' => $current_user_id,
        'created_by' => $current_user_id,
        'due_date' => date('Y-m-d H:i:s', strtotime('+1 week'))
    ),
    array(
        'title' => 'Equipment Maintenance',
        'description' => 'Schedule and perform routine maintenance on forklifts',
        'status' => 'pending',
        'priority' => 'low',
        'assigned_to' => $current_user_id,
        'created_by' => $current_user_id,
        'due_date' => date('Y-m-d H:i:s', strtotime('+2 weeks'))
    )
);

foreach ($sample_tasks as $task) {
    $wpdb->insert(
        $wpdb->prefix . 'wh_tasks',
        $task
    );
}

echo 'Tasks tables created successfully with sample data!';
?> 