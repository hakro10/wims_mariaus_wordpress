<?php
/**
 * Inventory Management Template Part
 */

$categories = get_all_categories();
$locations = get_all_locations();
?>

<div class="inventory-content">
    <!-- Search and Filters -->
    <div class="search-filters">
        <div class="search-row">
            <div class="search-input">
                <input type="text" id="inventory-search" placeholder="Search items..." class="form-input">
                <i class="fas fa-search search-icon"></i>
            </div>
            
            <select id="category-filter" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category->id; ?>"><?php echo esc_html($category->name); ?></option>
                <?php endforeach; ?>
            </select>
            
            <select id="status-filter" class="form-select">
                <option value="">All Status</option>
                <option value="in-stock">In Stock</option>
                <option value="low-stock">Low Stock</option>
                <option value="out-of-stock">Out of Stock</option>
                <option value="tested">Tested</option>
                <option value="untested">Untested</option>
            </select>
            
            <select id="location-filter" class="form-select">
                <option value="">All Locations</option>
                <?php foreach ($locations as $location): ?>
                    <option value="<?php echo $location->id; ?>" title="<?php echo esc_attr($location->full_path); ?>">
                        <?php echo str_repeat('└─ ', $location->level - 1) . esc_html($location->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button class="btn btn-secondary" onclick="clearAllFilters()" title="Clear all search filters">
                <i class="fas fa-times"></i> Clear Filters
            </button>
            
            <button class="btn btn-primary" onclick="openModal('add-item-modal')">
                <i class="fas fa-plus"></i> Add Item
            </button>
        </div>
    </div>

    <!-- Inventory Grid -->
    <div id="inventory-grid" class="inventory-grid">
        <!-- Items will be loaded via AJAX -->
    </div>
</div>

<!-- Add Item Modal -->
<div id="add-item-modal" class="inventory-modal-overlay" style="display: none;">
    <div class="inventory-modal">
        <div class="inventory-modal-header">
            <h3>Add New Item</h3>
            <button type="button" id="inventory-modal-close-x" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <div class="inventory-modal-body">
            <form id="add-item-form">
                <table style="width:100%;border-spacing:0;">
                    <tr>
                        <td style="padding:10px 0;">
                            <label style="display:block;margin-bottom:5px;font-weight:bold;">Item Name *</label>
                            <input type="text" name="name" required placeholder="Enter item name"
                                   style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <label style="display:block;margin-bottom:5px;font-weight:bold;">Internal ID *</label>
                            <input type="text" name="internal_id" required placeholder="Enter internal ID"
                                   style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <label style="display:block;margin-bottom:5px;font-weight:bold;">Description</label>
                            <textarea name="description" rows="3" placeholder="Enter description (optional)"
                                      style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;resize:vertical;"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <table style="width:100%;border-spacing:10px 0;">
                                <tr>
                                    <td style="width:50%;">
                                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Category</label>
                                        <select name="category_id" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category->id; ?>"><?php echo esc_html($category->name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td style="width:50%;">
                                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Location</label>
                                        <select name="location_id" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                                            <option value="">Select Location</option>
                                            <?php foreach ($locations as $location): ?>
                                                <option value="<?php echo $location->id; ?>" title="<?php echo esc_attr($location->full_path); ?>">
                                                    <?php echo str_repeat('└─ ', $location->level - 1) . esc_html($location->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <table style="width:100%;border-spacing:10px 0;">
                                <tr>
                                    <td style="width:50%;">
                                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Quantity *</label>
                                        <input type="number" name="quantity" min="0" required placeholder="0"
                                               style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                                    </td>
                                    <td style="width:50%;">
                                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Min Stock Level</label>
                                        <input type="number" name="min_stock_level" min="1" value="1"
                                               style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <table style="width:100%;border-spacing:10px 0;">
                                <tr>
                                    <td style="width:50%;">
                                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Purchase Price (per unit)</label>
                                        <input type="number" name="purchase_price" step="0.01" min="0" placeholder="0.00" id="purchase-price-input"
                                               style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                                    </td>
                                    <td style="width:50%;">
                                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Selling Price</label>
                                        <input type="number" name="selling_price" step="0.01" min="0" placeholder="0.00"
                                               style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Total Lot Price Section -->
                    <tr>
                        <td style="padding:10px 0;">
                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:15px;">
                                <label style="display:block;margin-bottom:10px;font-weight:bold;color:#374151;">💰 Total Lot Price</label>
                                <input type="number" name="total_lot_price" step="0.01" min="0" placeholder="0.00" id="total-lot-price-input"
                                       style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;margin-bottom:5px;">
                                <small style="color:#64748b;font-style:italic;">Total amount paid for this batch/lot of items</small>
                                
                                <div id="lot-calculations" style="display:none;margin-top:15px;background:white;border-radius:6px;padding:15px;border:1px solid #e2e8f0;">
                                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                                        <span style="color:#475569;">Per unit cost:</span>
                                        <span id="per-unit-cost" style="font-weight:500;color:#1e293b;">$0.00</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                                        <span style="color:#475569;">Total quantity:</span>
                                        <span id="total-quantity" style="font-weight:500;color:#1e293b;">0</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;padding:12px 0 8px 0;border-top:2px solid #e2e8f0;margin-top:8px;font-weight:600;color:#1e293b;">
                                        <span>Total lot cost:</span>
                                        <span id="total-lot-cost" style="color:#059669;font-size:1.1em;">$0.00</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <label style="display:block;margin-bottom:5px;font-weight:bold;">Supplier</label>
                            <input type="text" name="supplier" placeholder="Enter supplier name"
                                   style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <label style="display:flex;align-items:center;font-weight:bold;cursor:pointer;">
                                <input type="checkbox" name="tested" value="1" style="margin-right:8px;transform:scale(1.2);">
                                ✅ Item has been tested
                            </label>
                            <small style="color:#6b7280;font-style:italic;margin-left:24px;">Check this box if the item has been tested and verified to be working properly</small>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="inventory-modal-footer">
            <button type="button" id="inventory-modal-cancel-btn"
                    style="padding:10px 20px;margin-right:10px;background:#f5f5f5;border:1px solid #ccc;border-radius:4px;cursor:pointer;">Cancel</button>
            <button type="button" id="inventory-modal-submit-btn"
                    style="padding:10px 20px;background:#007cba;color:white;border:none;border-radius:4px;cursor:pointer;">Add Item</button>
        </div>
    </div>
</div>

<!-- Sell Item Modal -->
<div id="sell-item-modal" class="inventory-modal-overlay" style="display: none;">
    <div class="inventory-modal" style="width: 700px;">
        <div class="inventory-modal-header">
            <h3>Sell Item</h3>
            <button type="button" id="sell-modal-close-x" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <div class="inventory-modal-body">
            <div id="sell-item-info" style="background:#f8f9fa;padding:15px;border-radius:6px;margin-bottom:20px;">
                <!-- Item info will be populated here -->
            </div>
            
            <form id="sell-item-form">
                <input type="hidden" id="sell-item-id" name="item_id">
                
                <table style="width:100%;border-spacing:0;">
                    <!-- Buyer Information -->
                    <tr>
                        <td colspan="2" style="padding:15px 0 10px 0;">
                            <h4 style="margin:0;color:#374151;border-bottom:2px solid #e5e7eb;padding-bottom:8px;">👤 Buyer Information</h4>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <table style="width:100%;border-spacing:10px 0;">
                                <tr>
                                    <td style="width:50%;">
                                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Buyer Name *</label>
                                        <input type="text" name="buyer_name" required placeholder="Enter buyer's full name"
                                               style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                                    </td>
                                    <td style="width:50%;">
                                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Phone Number *</label>
                                        <input type="tel" name="buyer_phone" required placeholder="Enter phone number"
                                               style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <label style="display:block;margin-bottom:5px;font-weight:bold;">Buyer Email</label>
                            <input type="email" name="buyer_email" placeholder="Enter email address (optional)"
                                   style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <label style="display:block;margin-bottom:5px;font-weight:bold;">Buyer Address</label>
                            <textarea name="buyer_address" rows="2" placeholder="Enter buyer's address (optional)"
                                      style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;resize:vertical;"></textarea>
                        </td>
                    </tr>
                    
                    <!-- Sale Details -->
                    <tr>
                        <td colspan="2" style="padding:15px 0 10px 0;">
                            <h4 style="margin:0;color:#374151;border-bottom:2px solid #e5e7eb;padding-bottom:8px;">💰 Sale Details</h4>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <table style="width:100%;border-spacing:10px 0;">
                                <tr>
                                    <td style="width:33%;">
                                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Quantity *</label>
                                        <input type="number" name="quantity" min="1" required placeholder="1"
                                               style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;"
                                               onchange="calculateTotal()">
                                    </td>
                                    <td style="width:33%;">
                                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Unit Price *</label>
                                        <input type="number" name="unit_price" step="0.01" min="0" required placeholder="0.00"
                                               style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;"
                                               onchange="calculateTotal()">
                                    </td>
                                    <td style="width:33%;">
                                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Total Amount</label>
                                        <input type="number" name="total_amount" step="0.01" readonly
                                               style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;background:#f9f9f9;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <table style="width:100%;border-spacing:10px 0;">
                                <tr>
                                    <td style="width:50%;">
                                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Payment Method</label>
                                        <select name="payment_method" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                                            <option value="cash">Cash</option>
                                            <option value="card">Credit/Debit Card</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                            <option value="check">Check</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </td>
                                    <td style="width:50%;">
                                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Warranty Period</label>
                                        <select name="warranty_period" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                                            <option value="">No Warranty</option>
                                            <option value="30_days">30 Days</option>
                                            <option value="90_days">90 Days</option>
                                            <option value="6_months">6 Months</option>
                                            <option value="1_year">1 Year</option>
                                            <option value="2_years">2 Years</option>
                                            <option value="custom">Custom</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <label style="display:block;margin-bottom:5px;font-weight:bold;">Sale Notes</label>
                            <textarea name="sale_notes" rows="3" placeholder="Additional notes about this sale (optional)"
                                      style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;resize:vertical;"></textarea>
                        </td>
                    </tr>
                    
                    <!-- Seller Information -->
                    <tr>
                        <td colspan="2" style="padding:15px 0 10px 0;">
                            <h4 style="margin:0;color:#374151;border-bottom:2px solid #e5e7eb;padding-bottom:8px;">🏪 Seller Information</h4>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <table style="width:100%;border-spacing:10px 0;">
                                <tr>
                                    <td style="width:50%;">
                                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Sold By</label>
                                        <input type="text" name="sold_by_name" readonly value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>"
                                               style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;background:#f9f9f9;">
                                    </td>
                                    <td style="width:50%;">
                                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Sale Date</label>
                                        <input type="datetime-local" name="sale_date" value="<?php echo date('Y-m-d\TH:i'); ?>"
                                               style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="inventory-modal-footer">
            <button type="button" id="sell-modal-cancel-btn"
                    style="padding:10px 20px;margin-right:10px;background:#f5f5f5;border:1px solid #ccc;border-radius:4px;cursor:pointer;">Cancel</button>
            <button type="button" id="sell-modal-submit-btn"
                    style="padding:10px 20px;background:#059669;color:white;border:none;border-radius:4px;cursor:pointer;">Complete Sale</button>
        </div>
    </div>
</div>

<script>
// Load inventory items
function loadInventoryItems() {
    const search = document.getElementById('inventory-search').value;
    const category = document.getElementById('category-filter').value;
    const status = document.getElementById('status-filter').value;
    const location = document.getElementById('location-filter').value;
    
    jQuery.post(warehouse_ajax.ajax_url, {
        action: 'get_inventory_items',
        nonce: warehouse_ajax.nonce,
        search: search,
        category: category,
        status: status,
        location: location
    }, function(response) {
        if (response.success) {
            renderInventoryGrid(response.data);
        }
    });
}

// Function to filter by specific location (can be called from other pages)
function filterByLocation(locationId, locationName) {
    // Set the location filter
    document.getElementById('location-filter').value = locationId;
    
    // Clear other filters for focus
    document.getElementById('inventory-search').value = '';
    document.getElementById('category-filter').value = '';
    document.getElementById('status-filter').value = '';
    
    // Load items for this location
    loadInventoryItems();
    
    // Show a notification
    if (locationName) {
        showNotification(`Showing items in: ${locationName}`, 'info');
    }
}

// Simple notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        background: ${type === 'info' ? '#3b82f6' : '#10b981'};
        color: white;
        border-radius: 6px;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideInRight 0.3s ease-out;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Render inventory grid
function renderInventoryGrid(items) {
    const grid = document.getElementById('inventory-grid');
    
    if (items.length === 0) {
        grid.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="fas fa-search" style="font-size: 4rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                <h3>No items found</h3>
                <p>Try adjusting your search or filters.</p>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = items.map(item => `
        <div class="inventory-item">
            <div class="item-header">
                <div class="item-info">
                    <h3>${item.name}</h3>
                    <div class="item-id">ID: ${item.internal_id}</div>
                </div>
                <div class="item-actions">
                    <button class="btn-icon" onclick="editItem(${item.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon" onclick="sellItem(${item.id})" title="Sell">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                    <button class="btn-icon" onclick="deleteItem(${item.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <div class="item-details">
                <div class="detail-item">
                    <div class="detail-label">Quantity</div>
                    <div class="detail-value">${item.quantity}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-${item.status}">
                            ${item.status.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                        </span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Category</div>
                    <div class="detail-value">${item.category_name || 'Uncategorized'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Location</div>
                    <div class="detail-value">${item.location_path || item.location_name || 'No location'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Tested</div>
                    <div class="detail-value">
                        <span class="tested-badge ${item.tested == '1' ? 'tested-yes' : 'tested-no'}">
                            ${item.tested == '1' ? '✅ Tested' : '❌ Not Tested'}
                        </span>
                    </div>
                </div>
            </div>
            
            ${item.purchase_price || item.selling_price || item.total_lot_price ? `
                <div class="item-pricing">
                    ${item.purchase_price ? `<span class="price-label">Cost: $${parseFloat(item.purchase_price).toFixed(2)}</span>` : ''}
                    ${item.selling_price ? `<span class="price-label">Sell: $${parseFloat(item.selling_price).toFixed(2)}</span>` : ''}
                    ${item.total_lot_price ? `<span class="price-label lot-price">Batch: $${parseFloat(item.total_lot_price).toFixed(2)}</span>` : ''}
                </div>
            ` : ''}
        </div>
    `).join('');
}

// Submit add/edit item form
function submitAddItem() {
    const form = document.getElementById('add-item-form');
    const formData = new FormData(form);
    const submitBtn = document.getElementById('inventory-modal-submit-btn');
    const isEditMode = submitBtn.getAttribute('data-mode') === 'edit';
    const itemId = submitBtn.getAttribute('data-item-id');
    
    // Convert FormData to object for AJAX
    const data = {
        action: isEditMode ? 'update_inventory_item' : 'add_inventory_item',
        nonce: warehouse_ajax.nonce
    };
    
    // Add item ID for edit mode
    if (isEditMode && itemId) {
        data.item_id = itemId;
    }
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Don't send purchase_price in edit mode to prevent changes
    if (isEditMode) {
        delete data.purchase_price;
    }
    
    console.log('Submitting form data:', data);
    
    jQuery.post(warehouse_ajax.ajax_url, data, function(response) {
        if (response.success) {
            closeModal('add-item-modal');
            loadInventoryItems();
            alert(isEditMode ? 'Item updated successfully!' : 'Item added successfully!');
        } else {
            alert('Error: ' + response.data);
        }
    });
}

// Clear all filters function
function clearAllFilters() {
    document.getElementById('inventory-search').value = '';
    document.getElementById('category-filter').value = '';
    document.getElementById('status-filter').value = '';
    document.getElementById('location-filter').value = '';
    loadInventoryItems();
}

// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    if (modalId === 'add-item-modal') {
        resetAddItemModal();
    }
}

// Reset modal to add mode
function resetAddItemModal() {
    const form = document.getElementById('add-item-form');
    if (form) form.reset();
    
    // Reset modal title
    document.querySelector('#add-item-modal .inventory-modal-header h3').textContent = 'Add New Item';
    
    // Reset submit button
    const submitBtn = document.getElementById('inventory-modal-submit-btn');
    submitBtn.textContent = 'Add Item';
    submitBtn.removeAttribute('data-mode');
    submitBtn.removeAttribute('data-item-id');
    
    // Reset purchase price field to editable
    const purchasePriceInput = document.getElementById('purchase-price-input');
    purchasePriceInput.readOnly = false;
    purchasePriceInput.style.backgroundColor = '';
    purchasePriceInput.style.color = '';
    purchasePriceInput.title = '';
}

// Initialize event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Search and filter handlers
    document.getElementById('inventory-search').addEventListener('input', loadInventoryItems);
    document.getElementById('category-filter').addEventListener('change', loadInventoryItems);
    document.getElementById('status-filter').addEventListener('change', loadInventoryItems);
    document.getElementById('location-filter').addEventListener('change', loadInventoryItems);
    
    // Modal event listeners
    const xButton = document.getElementById('inventory-modal-close-x');
    if (xButton) {
        xButton.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Inventory modal X button clicked');
            closeModal('add-item-modal');
        });
    }
    
    const cancelButton = document.getElementById('inventory-modal-cancel-btn');
    if (cancelButton) {
        cancelButton.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Inventory modal Cancel button clicked');
            closeModal('add-item-modal');
        });
    }
    
    const submitButton = document.getElementById('inventory-modal-submit-btn');
    if (submitButton) {
        submitButton.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Inventory modal Submit button clicked');
            submitAddItem();
        });
    }
    
    // Sell modal event listeners
    const sellCloseButton = document.getElementById('sell-modal-close-x');
    if (sellCloseButton) {
        sellCloseButton.addEventListener('click', function(e) {
            e.preventDefault();
            closeSellModal();
        });
    }
    
    const sellCancelButton = document.getElementById('sell-modal-cancel-btn');
    if (sellCancelButton) {
        sellCancelButton.addEventListener('click', function(e) {
            e.preventDefault();
            closeSellModal();
        });
    }
    
    const sellSubmitButton = document.getElementById('sell-modal-submit-btn');
    if (sellSubmitButton) {
        sellSubmitButton.addEventListener('click', function(e) {
            e.preventDefault();
            submitSellForm();
        });
    }
    
    // Load items on page load
    loadInventoryItems();
});

// Edit item function
function editItem(itemId) {
    console.log('Edit item:', itemId);
    
    // Get item details first
    const formData = new FormData();
    formData.append('action', 'get_inventory_item');
    formData.append('nonce', warehouse_ajax.nonce);
    formData.append('item_id', itemId);
    
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            openEditModal(data.data);
        } else {
            alert('Error: Could not load item details');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading item details');
    });
}

// Open edit modal with pre-populated data
function openEditModal(item) {
    console.log('Opening edit modal for item:', item);
    
    // Change modal title
    document.querySelector('#add-item-modal .inventory-modal-header h3').textContent = 'Edit Item';
    
    // Change submit button text and action
    const submitBtn = document.getElementById('inventory-modal-submit-btn');
    submitBtn.textContent = 'Update Item';
    submitBtn.setAttribute('data-mode', 'edit');
    submitBtn.setAttribute('data-item-id', item.id);
    
    // Populate form fields with existing data
    document.querySelector('input[name="name"]').value = item.name || '';
    document.querySelector('input[name="internal_id"]').value = item.internal_id || '';
    document.querySelector('textarea[name="description"]').value = item.description || '';
    document.querySelector('select[name="category_id"]').value = item.category_id || '';
    document.querySelector('select[name="location_id"]').value = item.location_id || '';
    document.querySelector('input[name="quantity"]').value = item.quantity || '';
    document.querySelector('input[name="min_stock_level"]').value = item.min_stock_level || '';
    
    // Populate prices
    const purchasePriceInput = document.getElementById('purchase-price-input');
    purchasePriceInput.value = item.purchase_price || '';
    // Make purchase price readonly with visual indication
    purchasePriceInput.readOnly = true;
    purchasePriceInput.style.backgroundColor = '#f3f4f6';
    purchasePriceInput.style.color = '#6b7280';
    purchasePriceInput.title = 'Purchase price cannot be edited to maintain cost basis integrity';
    
    document.querySelector('input[name="selling_price"]').value = item.selling_price || '';
    document.getElementById('total-lot-price-input').value = item.total_lot_price || '';
    document.querySelector('input[name="supplier"]').value = item.supplier || '';
    
    // Set tested checkbox
    document.querySelector('input[name="tested"]').checked = item.tested == '1';
    
    // Show the modal
    openModal('add-item-modal');
}

// Sell item function
function sellItem(itemId) {
    console.log('Sell item:', itemId);
    
    // Get item details first
    const formData = new FormData();
    formData.append('action', 'get_inventory_item');
    formData.append('nonce', warehouse_ajax.nonce);
    formData.append('item_id', itemId);
    
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            openSellModal(data.data);
        } else {
            alert('Error: Could not load item details');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading item details');
    });
}

// Open sell modal with item details
function openSellModal(item) {
    // Populate item info
    document.getElementById('sell-item-info').innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h4 style="margin: 0 0 5px 0; color: #1f2937;">${item.name}</h4>
                <p style="margin: 0; color: #6b7280;">ID: ${item.internal_id} | Available: ${item.quantity} units</p>
            </div>
            <div style="text-align: right;">
                <span style="background: #059669; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                    ${item.status.replace('-', ' ').toUpperCase()}
                </span>
            </div>
        </div>
    `;
    
    // Set item ID
    document.getElementById('sell-item-id').value = item.id;
    
    // Set default values
    const quantityField = document.querySelector('#sell-item-form [name="quantity"]');
    const unitPriceField = document.querySelector('#sell-item-form [name="unit_price"]');
    
    if (quantityField) {
        quantityField.max = item.quantity;
        quantityField.value = 1; // Set default quantity to 1
    }
    
    if (unitPriceField) {
        unitPriceField.value = item.selling_price || '';
    }
    
    // Calculate total if both values are set
    calculateTotal();
    
    // Show modal
    document.getElementById('sell-item-modal').style.display = 'block';
}

// Calculate total amount
function calculateTotal() {
    const quantityField = document.querySelector('#sell-item-form [name="quantity"]');
    const unitPriceField = document.querySelector('#sell-item-form [name="unit_price"]');
    const totalField = document.querySelector('#sell-item-form [name="total_amount"]');
    
    if (!quantityField || !unitPriceField || !totalField) {
        console.error('Could not find form fields for calculation');
        return;
    }
    
    const quantity = parseFloat(quantityField.value) || 0;
    const unitPrice = parseFloat(unitPriceField.value) || 0;
    const total = quantity * unitPrice;
    
    totalField.value = total.toFixed(2);
    
    console.log('Total calculated:', {
        quantity: quantity,
        unitPrice: unitPrice,
        total: total
    });
}

// Submit sell form
function submitSellForm() {
    console.log('Submit sell form called');
    
    const form = document.getElementById('sell-item-form');
    const formData = new FormData(form);
    
    // Log all form data for debugging
    console.log('Form data entries:');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    // Validate required fields
    const buyerName = formData.get('buyer_name')?.trim();
    const buyerPhone = formData.get('buyer_phone')?.trim();
    const quantity = formData.get('quantity');
    const unitPrice = formData.get('unit_price');
    
    console.log('Form validation:', {
        buyerName: buyerName,
        buyerPhone: buyerPhone,
        quantity: quantity,
        unitPrice: unitPrice
    });
    
    // Check each field individually for better error reporting
    if (!buyerName) {
        alert('Please enter buyer name');
        document.querySelector('#sell-item-form [name="buyer_name"]').focus();
        return;
    }
    
    if (!buyerPhone) {
        alert('Please enter buyer phone number');
        document.querySelector('#sell-item-form [name="buyer_phone"]').focus();
        return;
    }
    
    if (!quantity || quantity <= 0) {
        alert('Please enter a valid quantity');
        document.querySelector('#sell-item-form [name="quantity"]').focus();
        return;
    }
    
    if (!unitPrice || unitPrice <= 0) {
        alert('Please enter a valid unit price');
        document.querySelector('#sell-item-form [name="unit_price"]').focus();
        return;
    }
    
    // Add action and nonce
    formData.append('action', 'sell_inventory_item_detailed');
    formData.append('nonce', warehouse_ajax.nonce);
    
    // Disable submit button
    const submitBtn = document.getElementById('sell-modal-submit-btn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = 'Processing...';
    submitBtn.disabled = true;
    
    console.log('Submitting sale with data:', Object.fromEntries(formData));
    
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text().then(text => {
            console.log('Raw response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON:', e);
                throw new Error('Invalid JSON response: ' + text);
            }
        });
    })
    .then(data => {
        console.log('Parsed response:', data);
        if (data.success) {
            alert('Sale completed successfully!\nSale Number: ' + data.data.sale_number);
            closeSellModal();
            loadInventoryItems(); // Refresh the grid
            // Refresh profit data if on sales page
            if (typeof loadProfitData === 'function') {
                loadProfitData();
            }
        } else {
            console.error('Sale failed:', data);
            alert('Error: ' + (data.data || data.message || 'Failed to complete sale'));
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Error completing sale: ' + error.message);
    })
    .finally(() => {
        // Re-enable button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Close sell modal
function closeSellModal() {
    document.getElementById('sell-item-modal').style.display = 'none';
    document.getElementById('sell-item-form').reset();
}

// Delete item function
function deleteItem(itemId) {
    console.log('Delete item:', itemId);
    
    if (confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('action', 'delete_inventory_item');
        formData.append('nonce', warehouse_ajax.nonce);
        formData.append('item_id', itemId);
        
        fetch(warehouse_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Item deleted successfully!');
                loadInventoryItems(); // Refresh the grid
            } else {
                alert('Error: ' + (data.data?.message || 'Failed to delete item'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting item');
        });
    }
}



function updateLotCalculations() {
    const quantity = parseFloat(document.querySelector('input[name="quantity"]').value) || 0;
    const purchasePrice = parseFloat(document.getElementById('purchase-price-input').value) || 0;
    const totalLotPrice = parseFloat(document.getElementById('total-lot-price-input').value) || 0;
    const lotCalculations = document.getElementById('lot-calculations');
    
    // Show calculations if we have quantity and at least one price
    if (quantity > 0 && (purchasePrice > 0 || totalLotPrice > 0)) {
        lotCalculations.style.display = 'block';
        
        let perUnitCost = 0;
        let totalCost = 0;
        
        if (totalLotPrice > 0) {
            // Calculate from lot price
            perUnitCost = totalLotPrice / quantity;
            totalCost = totalLotPrice;
        } else if (purchasePrice > 0) {
            // Calculate from purchase price
            perUnitCost = purchasePrice;
            totalCost = purchasePrice * quantity;
        }
        
        // Update display
        document.getElementById('per-unit-cost').textContent = '$' + perUnitCost.toFixed(2);
        document.getElementById('total-quantity').textContent = quantity;
        document.getElementById('total-lot-cost').textContent = '$' + totalCost.toFixed(2);
    } else {
        lotCalculations.style.display = 'none';
    }
}

// Track which field was last modified to determine calculation direction
let lastModifiedField = null;

function setupLotPriceCalculations() {
    const quantityInput = document.querySelector('input[name="quantity"]');
    const purchasePriceInput = document.getElementById('purchase-price-input');
    const totalLotPriceInput = document.getElementById('total-lot-price-input');
    const lotCalculations = document.getElementById('lot-calculations');
    
    // Add event listeners for auto-calculation
    if (quantityInput && purchasePriceInput && totalLotPriceInput) {
        quantityInput.addEventListener('input', function() {
            lastModifiedField = 'quantity';
            updateBidirectionalCalculations();
        });
        
        purchasePriceInput.addEventListener('input', function() {
            lastModifiedField = 'purchase_price';
            updateBidirectionalCalculations();
        });
        
        totalLotPriceInput.addEventListener('input', function() {
            lastModifiedField = 'lot_price';
            updateBidirectionalCalculations();
        });
        
        console.log('Lot price calculations setup complete');
    } else {
        console.warn('Lot price calculation elements not found');
    }
}

function updateBidirectionalCalculations() {
    const quantity = parseFloat(document.querySelector('input[name="quantity"]').value) || 0;
    const purchasePrice = parseFloat(document.getElementById('purchase-price-input').value) || 0;
    const totalLotPrice = parseFloat(document.getElementById('total-lot-price-input').value) || 0;
    const lotCalculations = document.getElementById('lot-calculations');
    const purchasePriceInput = document.getElementById('purchase-price-input');
    const isEditMode = purchasePriceInput && purchasePriceInput.readOnly;
    
    if (quantity > 0) {
        let perUnitCost = 0;
        let totalCost = 0;
        
        // In edit mode, purchase price is readonly, so only allow lot price -> per unit calculations
        if (isEditMode) {
            if (lastModifiedField === 'lot_price' && totalLotPrice > 0) {
                // User entered lot price - calculate per unit (display only, don't update purchase price)
                perUnitCost = totalLotPrice / quantity;
                totalCost = totalLotPrice;
            } else if (lastModifiedField === 'quantity') {
                // Quantity changed - recalculate based on lot price if available
                if (totalLotPrice > 0) {
                    perUnitCost = totalLotPrice / quantity;
                    totalCost = totalLotPrice;
                } else if (purchasePrice > 0) {
                    // Use existing purchase price for display
                    perUnitCost = purchasePrice;
                    totalCost = purchasePrice * quantity;
                    // Update lot price based on existing purchase price
                    document.getElementById('total-lot-price-input').value = totalCost.toFixed(2);
                }
            }
        } else {
            // Normal add mode - full bidirectional calculations
            if (lastModifiedField === 'lot_price' && totalLotPrice > 0) {
                // User entered lot price - calculate per unit
                perUnitCost = totalLotPrice / quantity;
                totalCost = totalLotPrice;
                
                // Update purchase price to match (without triggering events)
                document.getElementById('purchase-price-input').value = perUnitCost.toFixed(2);
                
            } else if (lastModifiedField === 'purchase_price' && purchasePrice > 0) {
                // User entered purchase price - calculate lot total
                perUnitCost = purchasePrice;
                totalCost = purchasePrice * quantity;
                
                // Update lot price to match (without triggering events)
                document.getElementById('total-lot-price-input').value = totalCost.toFixed(2);
                
            } else if (lastModifiedField === 'quantity') {
                // Quantity changed - recalculate based on existing values
                if (totalLotPrice > 0) {
                    perUnitCost = totalLotPrice / quantity;
                    totalCost = totalLotPrice;
                    document.getElementById('purchase-price-input').value = perUnitCost.toFixed(2);
                } else if (purchasePrice > 0) {
                    perUnitCost = purchasePrice;
                    totalCost = purchasePrice * quantity;
                    document.getElementById('total-lot-price-input').value = totalCost.toFixed(2);
                }
            }
        }
        
        // Show calculations if we have valid data
        if (perUnitCost > 0 && totalCost > 0) {
            lotCalculations.style.display = 'block';
            document.getElementById('per-unit-cost').textContent = '$' + perUnitCost.toFixed(2);
            document.getElementById('total-quantity').textContent = quantity;
            document.getElementById('total-lot-cost').textContent = '$' + totalCost.toFixed(2);
            
            // Add note in edit mode
            if (isEditMode) {
                const existingNote = lotCalculations.querySelector('.edit-mode-note');
                if (!existingNote) {
                    const note = document.createElement('div');
                    note.className = 'edit-mode-note';
                    note.style.cssText = 'font-size:0.875rem;color:#6b7280;font-style:italic;margin-top:8px;padding-top:8px;border-top:1px solid #f1f5f9;';
                    note.textContent = 'Note: Purchase price is locked to maintain cost basis integrity';
                    lotCalculations.appendChild(note);
                }
            }
        } else {
            lotCalculations.style.display = 'none';
        }
    } else {
        lotCalculations.style.display = 'none';
    }
}

// Setup lot price calculations when page loads
document.addEventListener('DOMContentLoaded', function() {
    setupLotPriceCalculations();
});
</script>

<style>
.inventory-content {
    padding: 2rem 0;
}

.search-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
}

@media (max-width: 768px) {
    .search-row {
        grid-template-columns: 1fr;
    }
}

.empty-state {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

/* Inventory Modal Styles */
.inventory-modal-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(0,0,0,0.5) !important;
    z-index: 999999 !important;
}

.inventory-modal {
    position: absolute !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    background: white !important;
    border-radius: 8px !important;
    width: 600px !important;
    max-width: 90% !important;
    max-height: 90vh !important;
    overflow-y: auto !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3) !important;
}

.inventory-modal-header {
    padding: 20px !important;
    border-bottom: 1px solid #eee !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
}

.inventory-modal-header h3 {
    margin: 0 !important;
    font-size: 18px !important;
    color: #333 !important;
}

.inventory-modal-body {
    padding: 20px !important;
    max-height: 60vh !important;
    overflow-y: auto !important;
}

.inventory-modal-footer {
    padding: 20px !important;
    border-top: 1px solid #eee !important;
    text-align: right !important;
}

/* Lot price display styling */
.price-label.lot-price {
    background: #fef3c7 !important;
    color: #d97706 !important;
    border: 1px solid #f59e0b !important;
    padding: 0.25rem 0.5rem !important;
    border-radius: 4px !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
}

/* Tested status badge styling */
.tested-badge {
    padding: 0.25rem 0.5rem !important;
    border-radius: 4px !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
}

.tested-yes {
    background: #dcfce7 !important;
    color: #166534 !important;
    border: 1px solid #22c55e !important;
}

.tested-no {
    background: #fef2f2 !important;
    color: #dc2626 !important;
    border: 1px solid #ef4444 !important;
}
</style> 