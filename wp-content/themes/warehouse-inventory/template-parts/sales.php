<?php
/**
 * Sales Template Part
 */

// Get search parameters
$search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$search_filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'all';

// Build search SQL
global $wpdb;
$sql = "SELECT s.*, i.name as item_name, i.internal_id, u.display_name as sold_by_name
        FROM {$wpdb->prefix}wh_sales s
        LEFT JOIN {$wpdb->prefix}wh_inventory_items i ON s.item_id = i.id
        LEFT JOIN {$wpdb->prefix}users u ON s.sold_by = u.ID
        WHERE 1=1";

$params = array();

if (!empty($search_query)) {
    switch ($search_filter) {
        case 'item':
            $sql .= " AND (i.name LIKE %s OR i.internal_id LIKE %s)";
            $search_term = '%' . $search_query . '%';
            $params = array($search_term, $search_term);
            break;
        case 'customer':
            $sql .= " AND (s.customer_name LIKE %s OR s.customer_phone LIKE %s OR s.customer_email LIKE %s)";
            $search_term = '%' . $search_query . '%';
            $params = array($search_term, $search_term, $search_term);
            break;
        case 'seller':
            $sql .= " AND u.display_name LIKE %s";
            $params = array('%' . $search_query . '%');
            break;
        case 'sale_number':
            $sql .= " AND s.sale_number LIKE %s";
            $params = array('%' . $search_query . '%');
            break;
        default: // 'all'
            $sql .= " AND (i.name LIKE %s OR i.internal_id LIKE %s OR s.customer_name LIKE %s OR s.customer_phone LIKE %s OR s.customer_email LIKE %s OR u.display_name LIKE %s OR s.sale_number LIKE %s)";
            $search_term = '%' . $search_query . '%';
            $params = array($search_term, $search_term, $search_term, $search_term, $search_term, $search_term, $search_term);
            break;
    }
}

$sql .= " ORDER BY s.sale_date DESC LIMIT 100";

if (!empty($params)) {
    $sales_data = $wpdb->get_results($wpdb->prepare($sql, $params));
} else {
    $sales_data = $wpdb->get_results($sql);
}

// Get today's sales
$today_sales = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(total_amount) FROM {$wpdb->prefix}wh_sales WHERE DATE(sale_date) = %s",
    current_time('Y-m-d')
));

// Get this month's sales
$month_sales = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(total_amount) FROM {$wpdb->prefix}wh_sales WHERE YEAR(sale_date) = %d AND MONTH(sale_date) = %d",
    current_time('Y'), current_time('n')
));

// Get total sales count
$total_sales = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wh_sales");
?>

<div class="sales-content">
    <div class="page-header">
        <h1>Sales Management</h1>
        <button class="btn btn-primary" onclick="openModal('record-sale-modal')">
            <i class="fas fa-plus"></i> Record Sale
        </button>
    </div>

    <div class="sales-stats">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-value">$<?php echo number_format($today_sales ?: 0, 2); ?></div>
            <div class="stat-label">Today's Sales</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-value">$<?php echo number_format($month_sales ?: 0, 2); ?></div>
            <div class="stat-label">This Month</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-value"><?php echo number_format($total_sales); ?></div>
            <div class="stat-label">Total Sales</div>
        </div>
    </div>

    <!-- Search and Actions -->
    <div class="sales-controls">
        <div class="search-section">
            <form method="GET" class="search-form">
                <input type="hidden" name="tab" value="sales">
                <div class="search-input-group">
                    <select name="filter" class="search-filter">
                        <option value="all" <?php selected($search_filter, 'all'); ?>>All Fields</option>
                        <option value="item" <?php selected($search_filter, 'item'); ?>>Item Name/ID</option>
                        <option value="customer" <?php selected($search_filter, 'customer'); ?>>Customer Info</option>
                        <option value="seller" <?php selected($search_filter, 'seller'); ?>>Seller Name</option>
                        <option value="sale_number" <?php selected($search_filter, 'sale_number'); ?>>Sale Number</option>
                    </select>
                    <input type="text" 
                           name="search" 
                           value="<?php echo esc_attr($search_query); ?>" 
                           placeholder="Search sales..." 
                           class="search-input">
                    <button type="submit" class="btn btn-primary search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search_query)): ?>
                        <a href="?tab=sales" class="btn btn-secondary clear-btn">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="openRecordSaleModal()">
                <i class="fas fa-plus"></i> Record Sale
            </button>
            <button class="btn btn-secondary" onclick="exportSales()">
                <i class="fas fa-download"></i> Export Sales
            </button>
            <button class="btn btn-secondary" onclick="refreshSales()">
                <i class="fas fa-refresh"></i> Refresh
            </button>
        </div>
    </div>

    <div class="sales-list">
        <div class="sales-header">
            <h2>
                <?php if (!empty($search_query)): ?>
                    Search Results for "<?php echo esc_html($search_query); ?>"
                    <span class="result-count">(<?php echo count($sales_data); ?> found)</span>
                <?php else: ?>
                    Recent Sales
                <?php endif; ?>
            </h2>
        </div>
        
        <?php if ($sales_data && count($sales_data) > 0): ?>
            <div class="sales-table-container">
                <table class="sales-table">
                    <thead>
                        <tr>
                            <th>Sale #</th>
                            <th>Item</th>
                            <th>Customer</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Sold By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales_data as $sale): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($sale->sale_number); ?></strong>
                                </td>
                                <td>
                                    <div class="item-info">
                                        <div class="item-name"><?php echo esc_html($sale->item_name); ?></div>
                                        <div class="item-id">ID: <?php echo esc_html($sale->internal_id); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-name"><?php echo esc_html($sale->customer_name ?: 'N/A'); ?></div>
                                        <?php if ($sale->customer_phone): ?>
                                            <div class="customer-phone"><?php echo esc_html($sale->customer_phone); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo number_format($sale->quantity_sold); ?></td>
                                <td>$<?php echo number_format($sale->unit_price, 2); ?></td>
                                <td>
                                    <strong>$<?php echo number_format($sale->total_amount, 2); ?></strong>
                                </td>
                                <td>
                                    <span class="payment-method">
                                        <?php echo esc_html($sale->payment_method ?: 'Cash'); ?>
                                    </span>
                                    <br>
                                    <span class="payment-status status-<?php echo esc_attr($sale->payment_status ?: 'completed'); ?>">
                                        <?php echo esc_html(ucwords($sale->payment_status ?: 'completed')); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="sale-date">
                                        <?php echo date('M j, Y', strtotime($sale->sale_date)); ?>
                                    </div>
                                    <div class="sale-time">
                                        <?php echo date('g:i A', strtotime($sale->sale_date)); ?>
                                    </div>
                                </td>
                                <td><?php echo esc_html($sale->sold_by_name ?: 'System'); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon" onclick="viewSaleDetails(<?php echo $sale->id; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-icon" onclick="printReceipt(<?php echo $sale->id; ?>)" title="Print Receipt">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <?php if ($sale->warranty_period): ?>
                                            <button class="btn-icon" onclick="viewWarranty(<?php echo $sale->id; ?>)" title="Warranty Info">
                                                <i class="fas fa-shield-alt"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart" style="font-size: 4rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                <h3>No sales recorded yet</h3>
                <p>Start recording sales to track your revenue.</p>
                <button class="btn btn-primary" onclick="window.location.href='?tab=inventory'">
                    <i class="fas fa-plus"></i> Record First Sale
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Record Sale Modal -->
<div id="record-sale-modal" style="display: none; position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(0, 0, 0, 0.5) !important; z-index: 999999 !important; overflow-y: auto !important;">
    <div style="position: absolute !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; background: white !important; border-radius: 8px !important; width: 90% !important; max-width: 500px !important; max-height: 80vh !important; overflow-y: auto !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;">
        <div style="padding: 20px !important; border-bottom: 1px solid #e5e7eb !important; display: flex !important; justify-content: space-between !important; align-items: center !important;">
            <h3 style="margin: 0 !important; color: #111827 !important; font-size: 1.25rem !important;">Record Sale</h3>
            <button onclick="closeRecordSaleModal()" style="background: none !important; border: none !important; font-size: 24px !important; cursor: pointer !important; color: #6b7280 !important; padding: 0 !important; width: 30px !important; height: 30px !important; display: flex !important; align-items: center !important; justify-content: center !important;">&times;</button>
        </div>
        <div style="padding: 20px !important;">
            <form id="record-sale-form">
                <table style="width: 100% !important; border-collapse: collapse !important;">
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Item *</label>
                            <select name="item_id" required style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important;">
                                <option value="">Select Item</option>
                                <!-- Items will be loaded via AJAX -->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important;">
                            <table style="width: 100% !important; border-collapse: collapse !important;">
                                <tr>
                                    <td style="width: 48% !important; padding-right: 2% !important;">
                                        <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Quantity *</label>
                                        <input type="number" name="quantity" min="1" required style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                                    </td>
                                    <td style="width: 48% !important; padding-left: 2% !important;">
                                        <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Unit Price *</label>
                                        <input type="number" name="unit_price" step="0.01" min="0" required style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important;">
                            <table style="width: 100% !important; border-collapse: collapse !important;">
                                <tr>
                                    <td style="width: 48% !important; padding-right: 2% !important;">
                                        <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Customer Name</label>
                                        <input type="text" name="customer_name" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                                    </td>
                                    <td style="width: 48% !important; padding-left: 2% !important;">
                                        <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Customer Email</label>
                                        <input type="email" name="customer_email" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Payment Method</label>
                            <select name="payment_method" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important;">
                                <option value="cash">Cash</option>
                                <option value="card">Credit/Debit Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="check">Check</option>
                                <option value="other">Other</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Notes</label>
                            <textarea name="notes" rows="3" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important; resize: vertical !important; font-family: inherit !important;"></textarea>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div style="padding: 20px !important; border-top: 1px solid #e5e7eb !important; display: flex !important; justify-content: flex-end !important; gap: 10px !important;">
            <button type="button" onclick="closeRecordSaleModal()" style="padding: 8px 16px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; background: white !important; color: #374151 !important; cursor: pointer !important; font-size: 14px !important;">Cancel</button>
            <button type="button" onclick="submitRecordSale()" style="padding: 8px 16px !important; border: none !important; border-radius: 4px !important; background: #3b82f6 !important; color: white !important; cursor: pointer !important; font-size: 14px !important;">Record Sale</button>
        </div>
    </div>
</div>

<!-- Sale Details Modal -->
<div id="sale-details-modal" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3>Sale Details</h3>
            <button class="modal-close" onclick="closeSaleDetailsModal()">&times;</button>
        </div>
        <div class="modal-body" id="sale-details-content">
            <!-- Sale details will be loaded here -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeSaleDetailsModal()">Close</button>
            <button class="btn btn-primary" onclick="printCurrentSale()">Print Receipt</button>
        </div>
    </div>
</div>

<style>
.sales-content {
    padding: 2rem 0;
}

.sales-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.sales-controls {
    margin-bottom: 30px;
}

.search-section {
    margin-bottom: 20px;
}

.search-form {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.search-input-group {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.search-filter {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    background: white;
    font-size: 14px;
    min-width: 140px;
}

.search-input {
    flex: 1;
    min-width: 200px;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 14px;
}

.search-btn, .clear-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.search-btn {
    background: #3b82f6;
    color: white;
}

.search-btn:hover {
    background: #2563eb;
}

.clear-btn {
    background: #6b7280;
    color: white;
}

.clear-btn:hover {
    background: #4b5563;
    text-decoration: none;
    color: white;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.sales-header {
    margin-bottom: 20px;
}

.sales-header h2 {
    color: #111827;
    margin: 0;
}

.result-count {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: normal;
}

.sales-table-container {
    overflow-x: auto;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.sales-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1200px;
}

.sales-table th,
.sales-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.sales-table th {
    background-color: #f9fafb;
    font-weight: 600;
    color: #374151;
}

.sales-table tr:hover {
    background-color: #f9fafb;
}

.item-info .item-name {
    font-weight: 600;
    color: #111827;
}

.item-info .item-id {
    font-size: 0.875rem;
    color: #6b7280;
}

.customer-info .customer-name {
    font-weight: 500;
}

.customer-info .customer-phone {
    font-size: 0.875rem;
    color: #6b7280;
}

.payment-method {
    background: #e5e7eb;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.875rem;
}

.payment-status {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-completed {
    background: #d1fae5;
    color: #065f46;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-failed {
    background: #fee2e2;
    color: #991b1b;
}

.sale-date {
    font-weight: 500;
}

.sale-time {
    font-size: 0.875rem;
    color: #6b7280;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-icon {
    background: none;
    border: 1px solid #d1d5db;
    padding: 6px 8px;
    border-radius: 4px;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.2s;
}

.btn-icon:hover {
    background: #f3f4f6;
    color: #374151;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.empty-state h3 {
    color: #374151;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 20px;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal {
    background: white;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #111827;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6b7280;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

@media (max-width: 768px) {
    .sales-stats {
        grid-template-columns: 1fr;
    }
    
    .search-input-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-filter,
    .search-input {
        min-width: auto;
        width: 100%;
    }
    
    .search-btn,
    .clear-btn {
        width: 100%;
        justify-content: center;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .sales-table-container {
        font-size: 0.875rem;
    }
    
    .sales-table th,
    .sales-table td {
        padding: 8px;
    }
    
    .sales-header h2 {
        font-size: 1.25rem;
    }
    
    .result-count {
        display: block;
        margin-top: 5px;
    }
}
</style>

<script>
function refreshSales() {
    location.reload();
}

function exportSales() {
    // Placeholder for export functionality
    alert('Export functionality coming soon!');
}

function viewSaleDetails(saleId) {
    // Show loading
    document.getElementById('sale-details-content').innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    document.getElementById('sale-details-modal').style.display = 'flex';
    
    // Fetch sale details via AJAX
    fetch(warehouseAjax.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'get_sale_details',
            nonce: warehouseAjax.nonce,
            sale_id: saleId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displaySaleDetails(data.data);
        } else {
            document.getElementById('sale-details-content').innerHTML = '<div style="color: red;">Error loading sale details</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('sale-details-content').innerHTML = '<div style="color: red;">Error loading sale details</div>';
    });
}

function displaySaleDetails(sale) {
    const content = `
        <div class="sale-detail-grid">
            <div class="detail-section">
                <h4>Sale Information</h4>
                <div class="detail-row">
                    <span class="label">Sale Number:</span>
                    <span class="value">${sale.sale_number}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Date:</span>
                    <span class="value">${new Date(sale.sale_date).toLocaleString()}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Total Amount:</span>
                    <span class="value">$${parseFloat(sale.total_amount).toFixed(2)}</span>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Item Details</h4>
                <div class="detail-row">
                    <span class="label">Item:</span>
                    <span class="value">${sale.item_name}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Quantity:</span>
                    <span class="value">${sale.quantity_sold}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Unit Price:</span>
                    <span class="value">$${parseFloat(sale.unit_price).toFixed(2)}</span>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Customer Information</h4>
                <div class="detail-row">
                    <span class="label">Name:</span>
                    <span class="value">${sale.customer_name || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Phone:</span>
                    <span class="value">${sale.customer_phone || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Email:</span>
                    <span class="value">${sale.customer_email || 'N/A'}</span>
                </div>
            </div>
            
            ${sale.warranty_period ? `
            <div class="detail-section">
                <h4>Warranty Information</h4>
                <div class="detail-row">
                    <span class="label">Period:</span>
                    <span class="value">${sale.warranty_period}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Expires:</span>
                    <span class="value">${new Date(sale.warranty_expiry_date).toLocaleDateString()}</span>
                </div>
            </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('sale-details-content').innerHTML = content;
}

function closeSaleDetailsModal() {
    document.getElementById('sale-details-modal').style.display = 'none';
}

function printReceipt(saleId) {
    // Placeholder for print functionality
    alert('Print receipt functionality coming soon!');
}

function printCurrentSale() {
    // Placeholder for print current sale
    alert('Print functionality coming soon!');
}

function viewWarranty(saleId) {
    // Placeholder for warranty view
    alert('Warranty details functionality coming soon!');
}

// Record Sale Modal Functions
function openRecordSaleModal() {
    document.getElementById('record-sale-modal').style.display = 'block';
    loadInventoryItems();
}

function closeRecordSaleModal() {
    document.getElementById('record-sale-modal').style.display = 'none';
    document.getElementById('record-sale-form').reset();
}

function loadInventoryItems() {
    // Load inventory items for the dropdown
    fetch(warehouseAjax.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'get_inventory_items',
            nonce: warehouseAjax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const select = document.querySelector('#record-sale-form select[name="item_id"]');
            select.innerHTML = '<option value="">Select Item</option>';
            data.data.forEach(item => {
                if (item.quantity > 0) {
                    select.innerHTML += `<option value="${item.id}" data-price="${item.selling_price}">${item.name} (Stock: ${item.quantity})</option>`;
                }
            });
        }
    })
    .catch(error => {
        console.error('Error loading items:', error);
    });
}

function submitRecordSale() {
    const form = document.getElementById('record-sale-form');
    const formData = new FormData(form);
    
    // Validate required fields
    const itemId = formData.get('item_id');
    const quantity = formData.get('quantity');
    const unitPrice = formData.get('unit_price');
    
    if (!itemId || !quantity || !unitPrice) {
        alert('Please fill in all required fields (Item, Quantity, Unit Price)');
        return;
    }
    
    // Prepare data for submission
    const submitData = new URLSearchParams({
        action: 'record_sale',
        nonce: warehouseAjax.nonce,
        item_id: itemId,
        quantity: quantity,
        unit_price: unitPrice,
        customer_name: formData.get('customer_name') || '',
        customer_email: formData.get('customer_email') || '',
        payment_method: formData.get('payment_method') || 'cash',
        notes: formData.get('notes') || ''
    });
    
    // Submit the sale
    fetch(warehouseAjax.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: submitData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Sale recorded successfully!');
            closeRecordSaleModal();
            location.reload(); // Refresh to show new sale
        } else {
            alert('Error recording sale: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error recording sale. Please try again.');
    });
}

// Auto-fill unit price when item is selected
document.addEventListener('change', function(e) {
    if (e.target.name === 'item_id') {
        const selectedOption = e.target.options[e.target.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        if (price) {
            document.querySelector('#record-sale-form input[name="unit_price"]').value = price;
        }
    }
});

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.id === 'record-sale-modal') {
        closeRecordSaleModal();
    }
    if (e.target.classList.contains('modal-overlay')) {
        closeSaleDetailsModal();
    }
});
</script> 