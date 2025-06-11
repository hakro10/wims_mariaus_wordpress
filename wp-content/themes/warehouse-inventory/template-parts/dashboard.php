<?php
/**
 * Dashboard Template Part
 */

$stats = get_dashboard_stats();
?>

<div class="dashboard-content">
    <!-- Dashboard Stats -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['total_items']); ?></div>
            <div class="stat-label">Total Items</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['in_stock']); ?></div>
            <div class="stat-label">In Stock</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['low_stock']); ?></div>
            <div class="stat-label">Low Stock</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['out_of_stock']); ?></div>
            <div class="stat-label">Out of Stock</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-value">$<?php echo number_format($stats['total_value'], 2); ?></div>
            <div class="stat-label">Total Value</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-value">$<?php echo number_format($stats['sales_today'], 2); ?></div>
            <div class="stat-label">Sales Today</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['pending_tasks']); ?></div>
            <div class="stat-label">Pending Tasks</div>
        </div>
        
        <div class="stat-card">
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
            <button class="btn btn-primary" onclick="openModal('add-item-modal')">
                <i class="fas fa-plus"></i> Add New Item
            </button>
            <button class="btn btn-secondary" onclick="openModal('add-category-modal')">
                <i class="fas fa-tag"></i> Add Category
            </button>
            <button class="btn btn-secondary" onclick="openModal('add-location-modal')">
                <i class="fas fa-map-marker-alt"></i> Add Location
            </button>
            <a href="?tab=inventory" class="btn btn-secondary">
                <i class="fas fa-eye"></i> View All Items
            </a>
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
                <button class="btn btn-primary" onclick="openModal('add-item-modal')">
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
                    <div class="alert-item">
                        <div class="alert-info">
                            <strong><?php echo esc_html($item->name); ?></strong>
                            <span class="item-category"><?php echo esc_html($item->category_name ?: 'Uncategorized'); ?></span>
                        </div>
                        <div class="alert-quantity">
                            <span class="current-stock"><?php echo $item->quantity; ?></span>
                            <span class="min-level">/ <?php echo $item->min_stock_level; ?> min</span>
                        </div>
                        <button class="btn btn-primary btn-sm" onclick="restockItem(<?php echo $item->id; ?>)">
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