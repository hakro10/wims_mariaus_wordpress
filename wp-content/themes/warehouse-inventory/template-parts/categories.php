<?php
/**
 * Categories Management Template Part
 */

$categories = get_all_categories();
?>

<div class="warehouse-content">
    <div class="page-header">
        <h2>Categories Management</h2>
        <button class="btn btn-primary" onclick="openCategoryModal()">
            <i class="fas fa-plus"></i> Add Category
        </button>
    </div>

    <div class="categories-grid">
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $category): ?>
                <div class="category-card" onclick="viewCategoryItems(<?php echo $category->id; ?>, '<?php echo esc_js($category->name); ?>')">
                    <div class="category-icon" style="background-color: <?php echo esc_attr($category->color); ?>">
                        <i class="fas fa-tag"></i>
                    </div>
                    <div class="category-info">
                        <h3><?php echo esc_html($category->name); ?></h3>
                        <p><?php echo esc_html($category->description); ?></p>
                    </div>
                    <div class="category-stats">
                        <span class="item-count"><?php echo intval($category->item_count); ?> items</span>
                    </div>
                    <div class="category-actions" onclick="event.stopPropagation();">
                        <button class="btn-icon" onclick="editCategory(<?php echo $category->id; ?>)" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-danger" onclick="deleteCategory(<?php echo $category->id; ?>, '<?php echo esc_js($category->name); ?>')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-tags"></i>
                <h3>No Categories Found</h3>
                <p>Start by adding your first category.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Category Modal -->
<div id="categoryModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; width: 500px; max-width: 90vw;">
        <h3 style="margin: 0 0 20px 0; color: #333;">Add New Category</h3>
        
        <form id="addCategoryForm" onsubmit="submitAddCategory(event)">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 10px 0; width: 30%; vertical-align: top;">
                        <label style="font-weight: bold; color: #555;">Name *</label>
                    </td>
                    <td style="padding: 10px 0;">
                        <input type="text" id="categoryName" name="name" required 
                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; vertical-align: top;">
                        <label style="font-weight: bold; color: #555;">Description</label>
                    </td>
                    <td style="padding: 10px 0;">
                        <textarea id="categoryDescription" name="description" rows="3"
                                  style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"></textarea>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; vertical-align: top;">
                        <label style="font-weight: bold; color: #555;">Color</label>
                    </td>
                    <td style="padding: 10px 0;">
                        <input type="color" id="categoryColor" name="color" value="#3498db"
                               style="width: 60px; height: 40px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
                    </td>
                </tr>
            </table>
            
            <div style="margin-top: 20px; text-align: right;">
                <button type="button" onclick="closeCategoryModal()" 
                        style="padding: 10px 20px; margin-right: 10px; background: #95a5a6; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Cancel
                </button>
                <button type="submit" 
                        style="padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Add Category
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Category Items Modal -->
<div id="categoryItemsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; width: 80%; max-width: 1000px; max-height: 80vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 id="categoryItemsTitle" style="margin: 0; color: #333;">Category Items</h3>
            <button onclick="closeCategoryItemsModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">&times;</button>
        </div>
        
        <div id="categoryItemsList">
            <!-- Items will be loaded here -->
        </div>
    </div>
</div>

<style>
.warehouse-content {
    padding: 2rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.category-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    cursor: pointer;
}

.category-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-bottom: 1rem;
}

.category-info {
    margin-bottom: 1rem;
}

.category-info h3 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.category-info p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.category-stats {
    margin-bottom: 1rem;
}

.item-count {
    color: #666;
    font-size: 0.9rem;
}

.category-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    background: none;
    border: none;
    padding: 0.5rem;
    border-radius: 4px;
    cursor: pointer;
    color: #666;
}

.btn-icon:hover {
    background: #f3f4f6;
}

.btn-danger:hover {
    background: #fee2e2;
    color: #dc2626;
}

.no-data {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.no-data i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.no-data h3 {
    color: #374151;
    margin-bottom: 10px;
}

.no-data p {
    color: #6b7280;
    margin-bottom: 20px;
}
</style>

<script>
function openCategoryModal() {
    document.getElementById('categoryModal').style.display = 'block';
}

function closeCategoryModal() {
    document.getElementById('categoryModal').style.display = 'none';
    document.getElementById('addCategoryForm').reset();
}

function submitAddCategory(event) {
    event.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'add_category');
    formData.append('nonce', warehouse_ajax.nonce);
    formData.append('name', document.getElementById('categoryName').value);
    formData.append('description', document.getElementById('categoryDescription').value);
    formData.append('color', document.getElementById('categoryColor').value);
    
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Category added successfully!');
            closeCategoryModal();
            location.reload();
        } else {
            alert('Error: ' + data.data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the category.');
    });
}

function deleteCategory(categoryId, categoryName) {
    if (!confirm(`Are you sure you want to delete the category "${categoryName}"? This action cannot be undone.`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_category');
    formData.append('nonce', warehouse_ajax.nonce);
    formData.append('category_id', categoryId);
    
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Category deleted successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the category.');
    });
}

function viewCategoryItems(categoryId, categoryName) {
    document.getElementById('categoryItemsTitle').textContent = `Items in "${categoryName}"`;
    document.getElementById('categoryItemsModal').style.display = 'block';
    document.getElementById('categoryItemsList').innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading items...</div>';
    
    const formData = new FormData();
    formData.append('action', 'get_category_items');
    formData.append('nonce', warehouse_ajax.nonce);
    formData.append('category_id', categoryId);
    
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.length > 0) {
            let itemsHtml = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">';
            
            data.data.forEach(item => {
                const stockStatus = item.quantity <= 0 ? 'out-of-stock' : (item.quantity <= item.reorder_level ? 'low-stock' : 'in-stock');
                const stockColor = stockStatus === 'out-of-stock' ? '#e74c3c' : (stockStatus === 'low-stock' ? '#f39c12' : '#27ae60');
                
                itemsHtml += `
                    <div style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; background: white;">
                        <h4 style="margin: 0 0 10px 0; color: #333;">${item.name}</h4>
                        <p style="margin: 5px 0; color: #666;"><strong>SKU:</strong> ${item.sku || 'N/A'}</p>
                        <p style="margin: 5px 0; color: #666;"><strong>Description:</strong> ${item.description || 'No description'}</p>
                        <p style="margin: 5px 0; color: #666;"><strong>Quantity:</strong> <span style="color: ${stockColor}; font-weight: bold;">${item.quantity}</span></p>
                        <p style="margin: 5px 0; color: #666;"><strong>Unit Price:</strong> $${parseFloat(item.unit_price).toFixed(2)}</p>
                        <p style="margin: 5px 0; color: #666;"><strong>Location:</strong> ${item.location || 'Not specified'}</p>
                        <div style="margin-top: 10px;">
                            <span style="background: ${stockColor}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; text-transform: uppercase;">
                                ${stockStatus.replace('-', ' ')}
                            </span>
                        </div>
                    </div>
                `;
            });
            
            itemsHtml += '</div>';
            document.getElementById('categoryItemsList').innerHTML = itemsHtml;
        } else {
            document.getElementById('categoryItemsList').innerHTML = `
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                    <h3>No Items Found</h3>
                    <p>This category doesn't have any items yet.</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('categoryItemsList').innerHTML = `
            <div style="text-align: center; padding: 40px; color: #e74c3c;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 15px;"></i>
                <h3>Error Loading Items</h3>
                <p>An error occurred while loading category items.</p>
            </div>
        `;
    });
}

function closeCategoryItemsModal() {
    document.getElementById('categoryItemsModal').style.display = 'none';
}

function editCategory(categoryId) {
    // TODO: Implement edit functionality
    alert('Edit functionality will be implemented soon!');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const categoryModal = document.getElementById('categoryModal');
    const itemsModal = document.getElementById('categoryItemsModal');
    
    if (event.target === categoryModal) {
        closeCategoryModal();
    }
    if (event.target === itemsModal) {
        closeCategoryItemsModal();
    }
}
</script> 