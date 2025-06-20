<?php
/**
 * Dashboard Template Part
 */

$stats = get_dashboard_stats();
?>

<div class="dashboard-content">
    <!-- Dashboard Stats -->
    <div class="dashboard-stats">
        <div class="stat-card clickable" onclick="navigateWithFilter('inventory', 'all')" title="View all inventory items">
            <div class="stat-icon blue">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['total_items']); ?></div>
            <div class="stat-label">Total Items</div>
        </div>
        
        <div class="stat-card clickable" onclick="navigateWithFilter('inventory', 'in-stock')" title="View items in stock">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['in_stock']); ?></div>
            <div class="stat-label">In Stock</div>
        </div>
        
        <div class="stat-card clickable" onclick="navigateWithFilter('inventory', 'low-stock')" title="View low stock items">
            <div class="stat-icon yellow">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['low_stock']); ?></div>
            <div class="stat-label">Low Stock</div>
        </div>
        
        <div class="stat-card clickable" onclick="navigateWithFilter('inventory', 'out-of-stock')" title="View out of stock items">
            <div class="stat-icon red">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['out_of_stock']); ?></div>
            <div class="stat-label">Out of Stock</div>
        </div>
        
        <div class="stat-card clickable" onclick="navigateWithFilter('inventory', 'all')" title="View all items with total value">
            <div class="stat-icon blue">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-value">$<?php echo number_format($stats['total_value'], 2); ?></div>
            <div class="stat-label">Total Value</div>
        </div>
        
        <div class="stat-card clickable" onclick="navigateToTab('sales')" title="View today's sales">
            <div class="stat-icon green">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-value">$<?php echo number_format($stats['sales_today'], 2); ?></div>
            <div class="stat-label">Sales Today</div>
        </div>
        
        <div class="stat-card clickable" onclick="navigateToTab('tasks')" title="View pending tasks">
            <div class="stat-icon yellow">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['pending_tasks']); ?></div>
            <div class="stat-label">Pending Tasks</div>
        </div>
        
        <div class="stat-card clickable" onclick="navigateToTab('categories')" title="Manage categories">
            <div class="stat-icon blue">
                <i class="fas fa-tags"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['categories_count']); ?></div>
            <div class="stat-label">Categories</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="navigateToTab('inventory')">
                <i class="fas fa-plus"></i> Add New Item
            </button>
            <button class="btn btn-secondary" onclick="navigateToTab('categories')">
                <i class="fas fa-tag"></i> Add Category
            </button>
            <button class="btn btn-secondary" onclick="navigateToTab('locations')">
                <i class="fas fa-map-marker-alt"></i> Add Location
            </button>
            <button class="btn btn-secondary" onclick="navigateToTab('inventory')">
                <i class="fas fa-eye"></i> View All Items
            </button>
        </div>
    </div>

    <!-- Recent Items -->
    <div class="recent-items">
        <h2>Recently Added Items</h2>
        <?php
        global $wpdb;
        $recent_items = $wpdb->get_results("
            SELECT i.*, c.name as category_name, l.name as location_name 
            FROM {$wpdb->prefix}wh_inventory_items i
            LEFT JOIN {$wpdb->prefix}wh_categories c ON i.category_id = c.id
            LEFT JOIN {$wpdb->prefix}wh_locations l ON i.location_id = l.id
            ORDER BY i.created_at DESC 
            LIMIT 6
        ");
        
        if ($recent_items): ?>
            <div class="inventory-grid">
                <?php foreach ($recent_items as $item): ?>
                    <div class="inventory-item">
                        <div class="item-header">
                            <div class="item-info">
                                <h3><?php echo esc_html($item->name); ?></h3>
                                <div class="item-id">ID: <?php echo esc_html($item->internal_id); ?></div>
                            </div>
                            <div class="item-actions">
                                <button class="btn-icon" onclick="editItem(<?php echo $item->id; ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon" onclick="sellItem(<?php echo $item->id; ?>)" title="Sell">
                                    <i class="fas fa-shopping-cart"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="item-details">
                            <div class="detail-item">
                                <div class="detail-label">Quantity</div>
                                <div class="detail-value"><?php echo number_format($item->quantity); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Status</div>
                                <div class="detail-value">
                                    <span class="status-badge status-<?php echo esc_attr($item->status); ?>">
                                        <?php echo esc_html(ucwords(str_replace('-', ' ', $item->status))); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Category</div>
                                <div class="detail-value"><?php echo esc_html($item->category_name ?: 'Uncategorized'); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Location</div>
                                <div class="detail-value"><?php echo esc_html($item->location_name ?: 'No location'); ?></div>
                            </div>
                        </div>
                        
                        <?php if ($item->purchase_price || $item->selling_price): ?>
                        <div class="item-pricing">
                            <?php if ($item->purchase_price): ?>
                                <span class="price-label">Cost: $<?php echo number_format($item->purchase_price, 2); ?></span>
                            <?php endif; ?>
                            <?php if ($item->selling_price): ?>
                                <span class="price-label">Sell: $<?php echo number_format($item->selling_price, 2); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-boxes" style="font-size: 4rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                <h3>No items yet</h3>
                <p>Start by adding your first inventory item.</p>
                <button class="btn btn-primary" onclick="navigateToTab('inventory')">
                    <i class="fas fa-plus"></i> Add First Item
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Low Stock Alerts -->
    <?php
    $low_stock_items = $wpdb->get_results("
        SELECT i.*, c.name as category_name 
        FROM {$wpdb->prefix}wh_inventory_items i
        LEFT JOIN {$wpdb->prefix}wh_categories c ON i.category_id = c.id
        WHERE i.quantity <= i.min_stock_level AND i.quantity > 0
        ORDER BY i.quantity ASC
        LIMIT 5
    ");
    
    if ($low_stock_items): ?>
        <div class="low-stock-alerts">
            <h2>⚠️ Low Stock Alerts</h2>
            <div class="alert-list">
                <?php foreach ($low_stock_items as $item): ?>
                    <div class="alert-item" onclick="viewItemDetails(<?php echo $item->id; ?>)" title="Click to search for this item">
                        <div class="alert-info">
                            <strong><?php echo esc_html($item->name); ?></strong>
                            <span class="item-category"><?php echo esc_html($item->category_name ?: 'Uncategorized'); ?></span>
                        </div>
                        <div class="alert-quantity">
                            <span class="current-stock"><?php echo $item->quantity; ?></span>
                            <span class="min-level">/ <?php echo $item->min_stock_level; ?> min</span>
                        </div>
                        <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); restockItem(<?php echo $item->id; ?>)" title="Restock this item">
                            Restock
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.quick-actions {
    margin: 2rem 0;
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    padding: 1.5rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}

.recent-items {
    margin: 2rem 0;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.item-pricing {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
}

.price-label {
    font-size: 0.875rem;
    color: #6b7280;
}

.low-stock-alerts {
    margin: 2rem 0;
    background: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 8px;
    padding: 1.5rem;
}

.alert-list {
    margin-top: 1rem;
}

.alert-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem;
    background: white;
    border-radius: 6px;
    margin-bottom: 0.5rem;
    transition: all 0.2s ease;
    cursor: pointer;
}

.alert-item:hover {
    background: #f8fafc;
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.alert-info strong {
    display: block;
    color: #1f2937;
}

.item-category {
    font-size: 0.875rem;
    color: #6b7280;
}

.alert-quantity {
    font-weight: 600;
}

.current-stock {
    color: #dc2626;
}

.min-level {
    color: #6b7280;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
}
</style>

<script>
// Dashboard navigation functions
function navigateToTab(tab) {
    console.log('Navigating to tab:', tab);
    window.location.href = '?tab=' + tab;
}

// Navigate with filter for inventory
function navigateWithFilter(tab, filter) {
    console.log('Navigating to tab:', tab, 'with filter:', filter);
    if (tab === 'inventory') {
        // Store the filter preference for when the inventory page loads
        localStorage.setItem('dashboard_filter', filter);
        window.location.href = '?tab=' + tab;
    } else {
        window.location.href = '?tab=' + tab;
    }
}

function openModal(modalId) {
    switch(modalId) {
        case 'add-item-modal':
            window.location.href = '?tab=inventory';
            break;
        case 'add-category-modal':
            window.location.href = '?tab=categories';
            break;
        case 'add-location-modal':
            window.location.href = '?tab=locations';
            break;
        default:
            console.log('Modal not found:', modalId);
    }
}

// Item action functions
function editItem(itemId) {
    window.location.href = '?tab=inventory&action=edit&item_id=' + itemId;
}

function sellItem(itemId) {
    window.location.href = '?tab=inventory&action=sell&item_id=' + itemId;
}

function restockItem(itemId) {
    const quantity = prompt('Enter quantity to add to stock:');
    if (quantity && !isNaN(quantity) && parseInt(quantity) > 0) {
        // Get current item details first
        fetch(warehouse_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_inventory_item',
                nonce: warehouse_ajax.nonce,
                item_id: itemId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = data.data;
                const newQuantity = parseInt(item.quantity) + parseInt(quantity);
                
                // Update the item with new quantity
                const updateData = new URLSearchParams({
                    action: 'update_inventory_item',
                    nonce: warehouse_ajax.nonce,
                    item_id: itemId,
                    name: item.name,
                    internal_id: item.internal_id,
                    description: item.description,
                    category_id: item.category_id,
                    location_id: item.location_id,
                    quantity: newQuantity,
                    purchase_price: item.purchase_price,
                    selling_price: item.selling_price,
                    min_stock_level: item.min_stock_level
                });
                
                return fetch(warehouse_ajax.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: updateData
                });
            } else {
                throw new Error('Failed to get item details');
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Stock updated successfully! Added ' + quantity + ' items.');
                location.reload();
            } else {
                alert('Error updating stock: ' + (data.data || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating stock. Please try again.');
        });
    }
}

// View item details function
function viewItemDetails(itemId) {
    // Navigate to inventory page and search for the specific item
    localStorage.setItem('dashboard_search_item', itemId);
    window.location.href = '?tab=inventory';
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard loaded');
});
</script> 