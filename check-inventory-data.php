<?php
// Check Inventory Data and Add Test Items
require_once('wp-config.php');

// Connect to database directly
$connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

echo "<h1>Inventory Database Check</h1>";
echo "<h2>Current Data:</h2>";

// Check current items
$result = $connection->query("SELECT COUNT(*) as count FROM wp_wh_inventory_items");
$row = $result->fetch_assoc();
echo "<p><strong>Total items in wp_wh_inventory_items:</strong> " . $row['count'] . "</p>";

// Check categories
$result = $connection->query("SELECT COUNT(*) as count FROM wp_wh_categories");
$row = $result->fetch_assoc();
echo "<p><strong>Total categories:</strong> " . $row['count'] . "</p>";

// Show actual items if any
echo "<h3>Current Items:</h3>";
$result = $connection->query("SELECT * FROM wp_wh_inventory_items LIMIT 10");
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Internal ID</th><th>Quantity</th><th>Status</th><th>Stock Status</th><th>Category ID</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['internal_id'] . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . ($row['stock_status'] ?? 'N/A') . "</td>";
        echo "<td>" . $row['category_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No items found in database.</p>";
    
    // Add test data
    echo "<h3>Adding Test Data...</h3>";
    
    // Get category IDs
    $categories = $connection->query("SELECT id, name FROM wp_wh_categories LIMIT 5");
    $cat_automotive = null;
    $cat_electronics = null;
    
    while($cat = $categories->fetch_assoc()) {
        if (stripos($cat['name'], 'automotive') !== false) {
            $cat_automotive = $cat['id'];
        }
        if (stripos($cat['name'], 'electronics') !== false) {
            $cat_electronics = $cat['id'];
        }
    }
    
    // Add test items
    $test_items = [
        [
            'name' => 'Car Battery',
            'internal_id' => 'AUTO-001',
            'quantity' => 5,
            'category_id' => $cat_automotive,
            'status' => 'active',
            'stock_status' => 'in-stock'
        ],
        [
            'name' => 'Brake Pads',
            'internal_id' => 'AUTO-002', 
            'quantity' => 10,
            'category_id' => $cat_automotive,
            'status' => 'active',
            'stock_status' => 'in-stock'
        ],
        [
            'name' => 'Laptop',
            'internal_id' => 'ELEC-001',
            'quantity' => 3,
            'category_id' => $cat_electronics,
            'status' => 'active',
            'stock_status' => 'in-stock'
        ],
        [
            'name' => 'Smartphone',
            'internal_id' => 'ELEC-002',
            'quantity' => 8,
            'category_id' => $cat_electronics,
            'status' => 'active',
            'stock_status' => 'in-stock'
        ],
        [
            'name' => 'Wireless Mouse',
            'internal_id' => 'ELEC-003',
            'quantity' => 15,
            'category_id' => $cat_electronics,
            'status' => 'active',
            'stock_status' => 'in-stock'
        ]
    ];
    
    foreach ($test_items as $item) {
        $stmt = $connection->prepare("INSERT INTO wp_wh_inventory_items (name, internal_id, quantity, category_id, status, stock_status, min_stock_level, purchase_price, selling_price, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, 10.00, 15.00, NOW(), NOW())");
        $stmt->bind_param("ssiiss", $item['name'], $item['internal_id'], $item['quantity'], $item['category_id'], $item['status'], $item['stock_status']);
        
        if ($stmt->execute()) {
            echo "<p>✅ Added: " . $item['name'] . "</p>";
        } else {
            echo "<p>❌ Failed to add: " . $item['name'] . " - " . $stmt->error . "</p>";
        }
    }
    
    echo "<p><strong>Test data added! Now refresh your inventory page.</strong></p>";
}

$connection->close();
?>
<p><a href="?refresh=1">Refresh This Page</a> | <a href="/wp-admin">Go to WordPress Admin</a></p> 