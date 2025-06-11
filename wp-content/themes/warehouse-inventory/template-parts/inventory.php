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
            </select>
            
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
                                                <option value="<?php echo $location->id; ?>"><?php echo esc_html($location->name); ?></option>
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
                                        <label style="display:block;margin-bottom:5px;font-weight:bold;">Purchase Price</label>
                                        <input type="number" name="purchase_price" step="0.01" min="0" placeholder="0.00"
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
                    <tr>
                        <td style="padding:10px 0;">
                            <label style="display:block;margin-bottom:5px;font-weight:bold;">Supplier</label>
                            <input type="text" name="supplier" placeholder="Enter supplier name"
                                   style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
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
    
    jQuery.post(warehouse_ajax.ajax_url, {
        action: 'get_inventory_items',
        nonce: warehouse_ajax.nonce,
        search: search,
        category: category,
        status: status
    }, function(response) {
        if (response.success) {
            renderInventoryGrid(response.data);
        }
    });
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
                    <div class="detail-value">${item.location_name || 'No location'}</div>
                </div>
            </div>
            
            ${item.purchase_price || item.selling_price ? `
                <div class="item-pricing">
                    ${item.purchase_price ? `<span class="price-label">Cost: $${parseFloat(item.purchase_price).toFixed(2)}</span>` : ''}
                    ${item.selling_price ? `<span class="price-label">Sell: $${parseFloat(item.selling_price).toFixed(2)}</span>` : ''}
                </div>
            ` : ''}
        </div>
    `).join('');
}

// Submit add item form
function submitAddItem() {
    const form = document.getElementById('add-item-form');
    const formData = new FormData(form);
    
    // Convert FormData to object for AJAX
    const data = {
        action: 'add_inventory_item',
        nonce: warehouse_ajax.nonce
    };
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    jQuery.post(warehouse_ajax.ajax_url, data, function(response) {
        if (response.success) {
            closeModal('add-item-modal');
            form.reset();
            loadInventoryItems();
            alert('Item added successfully!');
        } else {
            alert('Error: ' + response.data);
        }
    });
}

// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    if (modalId === 'add-item-modal') {
        const form = document.getElementById('add-item-form');
        if (form) form.reset();
    }
}

// Initialize event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Search and filter handlers
    document.getElementById('inventory-search').addEventListener('input', loadInventoryItems);
    document.getElementById('category-filter').addEventListener('change', loadInventoryItems);
    document.getElementById('status-filter').addEventListener('change', loadInventoryItems);
    
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
    // TODO: Implement edit functionality
    alert('Edit functionality coming soon! Item ID: ' + itemId);
}

// Sell item function
function sellItem(itemId) {
    console.log('Sell item:', itemId);
    
    // Get item details first
    const formData = new FormData();
    formData.append('action', 'get_inventory_item');
    formData.append('nonce', warehouseAjax.nonce);
    formData.append('item_id', itemId);
    
    fetch(warehouseAjax.ajax_url, {
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
    const form = document.getElementById('sell-item-form');
    const formData = new FormData(form);
    
    // Validate required fields with better debugging
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
    formData.append('nonce', warehouseAjax.nonce);
    
    // Disable submit button
    const submitBtn = document.getElementById('sell-modal-submit-btn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = 'Processing...';
    submitBtn.disabled = true;
    
    console.log('Submitting sale with data:', Object.fromEntries(formData));
    
    fetch(warehouseAjax.ajax_url, {
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
        formData.append('nonce', warehouseAjax.nonce);
        formData.append('item_id', itemId);
        
        fetch(warehouseAjax.ajax_url, {
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
</style> 