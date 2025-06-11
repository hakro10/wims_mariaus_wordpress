<?php
// Debug file to check task functionality specifically
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>Task Debug Check</h1>";

// Check if get_all_tasks function exists
if (function_exists('get_all_tasks')) {
    echo "✅ get_all_tasks function exists<br>";
    
    try {
        $tasks = get_all_tasks();
        echo "<h2>Tasks Data:</h2>";
        echo "Total tasks found: " . count($tasks) . "<br><br>";
        
        if (!empty($tasks)) {
            echo "<h3>Task Details:</h3>";
            foreach ($tasks as $task) {
                echo "ID: {$task->id}, Title: {$task->title}, Status: {$task->status}, Priority: {$task->priority}<br>";
            }
            
            // Group tasks by status like the template does
            $grouped_tasks = array(
                'pending' => array(),
                'in_progress' => array(), 
                'completed' => array()
            );
            
            foreach ($tasks as $task) {
                $status = str_replace('-', '_', $task->status);
                if (isset($grouped_tasks[$status])) {
                    $grouped_tasks[$status][] = $task;
                }
            }
            
            echo "<h3>Grouped Tasks:</h3>";
            echo "Pending: " . count($grouped_tasks['pending']) . "<br>";
            echo "In Progress: " . count($grouped_tasks['in_progress']) . "<br>";
            echo "Completed: " . count($grouped_tasks['completed']) . "<br>";
            
        } else {
            echo "❌ No tasks found in database<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error getting tasks: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ get_all_tasks function does not exist<br>";
}

// Check if template file exists
$template_file = get_template_directory() . '/template-parts/task-card-template.php';
if (file_exists($template_file)) {
    echo "✅ Task card template exists<br>";
    
    // Check file permissions
    if (is_readable($template_file)) {
        echo "✅ Template file is readable<br>";
    } else {
        echo "❌ Template file is not readable<br>";
    }
} else {
    echo "❌ Task card template file missing<br>";
}

// Test template syntax
echo "<h2>Template Syntax Test:</h2>";
ob_start();
$error = null;
try {
    // Create a dummy task to test template
    $task = (object) array(
        'id' => 999,
        'title' => 'Test Task',
        'description' => 'Test description',
        'status' => 'pending',
        'priority' => 'medium',
        'assigned_to' => 1,
        'due_date' => date('Y-m-d'),
        'created_at' => date('Y-m-d H:i:s')
    );
    
    include $template_file;
} catch (Exception $e) {
    $error = $e->getMessage();
}
$template_output = ob_get_clean();

if ($error) {
    echo "❌ Template error: " . $error . "<br>";
} else {
    echo "✅ Template loads without PHP errors<br>";
    echo "<h3>Template Output Preview:</h3>";
    echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";
    echo htmlentities(substr($template_output, 0, 200)) . "...";
    echo "</div>";
}
?> 