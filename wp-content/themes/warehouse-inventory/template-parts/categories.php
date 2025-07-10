<?php
/**
 * Categories Management Template Part - Hierarchical View
 */

$categories_tree = get_categories_tree();
$all_categories = get_all_categories();

function render_category_tree($categories, $level = 0) {
    if (empty($categories)) return;
    
    foreach ($categories as $category) {
        $indent = str_repeat('  ', $level);
        $has_children = !empty($category->children);
        ?>
        <div class="category-row" data-level="<?php echo $level; ?>" data-category-id="<?php echo $category->id; ?>">
            <div class="category-content">
                <div class="category-indent" style="width: <?php echo $level * 20; ?>px;"></div>
                
                <?php if ($has_children): ?>
                    <button class="category-toggle" onclick="toggleCategoryChildren(<?php echo $category->id; ?>)">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                <?php else: ?>
                    <div class="category-spacer"></div>
                <?php endif; ?>
                
                <div class="category-icon" style="background-color: <?php echo esc_attr($category->color ?? '#3b82f6'); ?>">
                    <i class="fas fa-<?php echo esc_attr($category->icon ?? 'tag'); ?>"></i>
                </div>
                
                <div class="category-info">
                    <div class="category-header">
                        <h4 class="category-name-clickable" onclick="showCategoryItems(<?php echo $category->id; ?>, '<?php echo esc_js($category->name); ?>')" style="cursor: pointer; color: #3b82f6; text-decoration: underline;"><?php echo esc_html($category->name); ?></h4>
                        <span class="category-count category-count-clickable" onclick="showCategoryItems(<?php echo $category->id; ?>, '<?php echo esc_js($category->name); ?>')" style="cursor: pointer; color: #059669; text-decoration: underline;"><?php echo intval($category->item_count ?? 0); ?> items</span>
                    </div>
                    <?php if (!empty($category->description)): ?>
                        <p class="category-description"><?php echo esc_html($category->description); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="category-actions">
                    <button class="btn-icon" onclick="addSubcategory(<?php echo $category->id; ?>, '<?php echo esc_js($category->name); ?>')" title="Add Subcategory">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="btn-icon" onclick="editCategory(<?php echo $category->id; ?>)" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-danger" onclick="deleteCategory(<?php echo $category->id; ?>, '<?php echo esc_js($category->name); ?>')" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <?php if ($has_children): ?>
                <div class="category-children" id="children-<?php echo $category->id; ?>">
                    <?php render_category_tree($category->children, $level + 1); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
?>

<div class="warehouse-content">
    <div class="page-header">
        <h2>Categories Management</h2>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="toggleAllCategories()">
                <i class="fas fa-expand-arrows-alt"></i> Expand All
            </button>
            <button class="btn btn-primary" onclick="openCategoryModal()">
                <i class="fas fa-plus"></i> Add Category
            </button>
        </div>
    </div>

    <div class="categories-container">
        <?php if (!empty($categories_tree)): ?>
            <div class="categories-tree">
                <?php render_category_tree($categories_tree); ?>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-tags"></i>
                <h3>No Categories Found</h3>
                <p>Start by adding your first category to organize your inventory.</p>
                <button class="btn btn-primary" onclick="openCategoryModal()">
                    <i class="fas fa-plus"></i> Add Your First Category
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div id="categoryModal" style="display: none; position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(0, 0, 0, 0.5) !important; z-index: 999999 !important; overflow-y: auto !important;">
    <div style="position: absolute !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; background: white !important; border-radius: 8px !important; width: 90% !important; max-width: 500px !important; max-height: 80vh !important; overflow-y: auto !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;">
        <div style="padding: 20px !important; border-bottom: 1px solid #e5e7eb !important; display: flex !important; justify-content: space-between !important; align-items: center !important;">
            <h3 id="modalTitle" style="margin: 0 !important; color: #111827 !important; font-size: 1.25rem !important;">Add New Category</h3>
            <button onclick="closeCategoryModal()" style="background: none !important; border: none !important; font-size: 24px !important; cursor: pointer !important; color: #6b7280 !important; padding: 0 !important; width: 30px !important; height: 30px !important; display: flex !important; align-items: center !important; justify-content: center !important;">&times;</button>
        </div>
        
        <div style="padding: 20px !important;">
            <form id="categoryForm" onsubmit="submitCategory(event)">
                <input type="hidden" id="categoryId" value="">
                
                <table style="width: 100% !important; border-collapse: collapse !important;">
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Name *</label>
                            <input type="text" id="categoryName" name="name" required style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Parent Category</label>
                            <select id="categoryParent" name="parent_id" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important;">
                                <option value="">None (Top Level)</option>
                                <?php 
                                // Get hierarchical categories for parent selection
                                global $wpdb;
                                
                                // Check if is_active column exists
                                $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}wh_categories LIKE 'is_active'");
                                $where_clause = !empty($columns) ? "WHERE c.is_active = 1" : "";
                                
                                $parent_categories = $wpdb->get_results("
                                    SELECT c.*, 
                                           parent.name as parent_name
                                    FROM {$wpdb->prefix}wh_categories c
                                    LEFT JOIN {$wpdb->prefix}wh_categories parent ON c.parent_id = parent.id
                                    {$where_clause}
                                    ORDER BY c.parent_id IS NULL DESC, c.parent_id, 
                                             " . (!empty($columns) ? "c.sort_order," : "") . " c.name
                                ");
                                
                                foreach ($parent_categories as $cat):
                                    $display_name = $cat->name;
                                    $indent = '';
                                    
                                    if ($cat->parent_id) {
                                        $indent = '&nbsp;&nbsp;&nbsp;&nbsp;↳ ';
                                        $display_name = $cat->parent_name . ' → ' . $cat->name;
                                    }
                                ?>
                                    <option value="<?php echo $cat->id; ?>" data-level="<?php echo $cat->parent_id ? '1' : '0'; ?>" data-parent-id="<?php echo $cat->parent_id ?: ''; ?>">
                                        <?php echo $indent . esc_html($display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Description</label>
                            <textarea id="categoryDescription" name="description" rows="3" placeholder="Optional description for this category..." style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important; resize: vertical !important; font-family: inherit !important;"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <table style="width: 100% !important; border-collapse: collapse !important;">
                                <tr>
                                    <td style="width: 120px !important; padding-right: 15px !important; vertical-align: top !important;">
                                        <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Color</label>
                                        <input type="color" id="categoryColor" name="color" value="#3b82f6" style="width: 100% !important; height: 42px !important; padding: 0 !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; background: none !important; box-sizing: border-box !important; cursor: pointer !important;">
                                    </td>
                                    <td style="vertical-align: top !important;">
                                        <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Icon</label>
                                        <select id="categoryIcon" name="icon" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important;">
                                            <option value="tag">🏷️ Tag</option>
                                            <option value="laptop">💻 Electronics</option>
                                            <option value="tools">🔧 Tools</option>
                                            <option value="mobile-alt">📱 Phones</option>
                                            <option value="tv">📺 Displays</option>
                                            <option value="car">🚗 Automotive</option>
                                            <option value="home">🏠 Home & Garden</option>
                                            <option value="tshirt">👕 Clothing</option>
                                            <option value="gamepad">🎮 Gaming</option>
                                            <option value="book">📚 Books</option>
                                            <option value="music">🎵 Music</option>
                                            <option value="camera">📷 Camera</option>
                                            <option value="utensils">🍴 Kitchen</option>
                                            <option value="dumbbell">🏋️ Sports</option>
                                            <option value="briefcase">💼 Office</option>
                                            <option value="medkit">🏥 Medical</option>
                                            <option value="wrench">🔩 Hardware</option>
                                            <option value="paint-brush">🎨 Arts & Crafts</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        
        <div style="padding: 20px !important; border-top: 1px solid #e5e7eb !important; display: flex !important; justify-content: flex-end !important; gap: 10px !important;">
            <button type="button" onclick="closeCategoryModal()" style="padding: 8px 16px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; background: white !important; color: #374151 !important; cursor: pointer !important; font-size: 14px !important;">Cancel</button>
            <button type="button" onclick="submitCategory(event)" id="submitBtn" style="padding: 8px 16px !important; border: none !important; border-radius: 4px !important; background: #3b82f6 !important; color: white !important; cursor: pointer !important; font-size: 14px !important;">Add Category</button>
        </div>
    </div>
</div>

<!-- Category Items Modal -->
<div id="categoryItemsModal" style="display: none; position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(0, 0, 0, 0.5) !important; z-index: 999999 !important; overflow-y: auto !important;">
    <div style="position: absolute !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; background: white !important; border-radius: 8px !important; width: 90% !important; max-width: 700px !important; max-height: 80vh !important; overflow-y: auto !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;">
        <div style="padding: 20px !important; border-bottom: 1px solid #e5e7eb !important; display: flex !important; justify-content: space-between !important; align-items: center !important;">
            <h3 id="categoryItemsTitle" style="margin: 0 !important; color: #111827 !important; font-size: 1.25rem !important;">Items in Category</h3>
            <button onclick="closeCategoryItemsModal()" style="background: none !important; border: none !important; font-size: 24px !important; cursor: pointer !important; color: #6b7280 !important; padding: 0 !important; width: 30px !important; height: 30px !important; display: flex !important; align-items: center !important; justify-content: center !important;">&times;</button>
        </div>
        
        <div style="padding: 20px !important;">
            <div id="categoryItemsContent" style="min-height: 100px;">
                <div id="categoryItemsLoading" style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #6b7280;"></i>
                    <p style="margin-top: 10px; color: #6b7280;">Loading items...</p>
                </div>
            </div>
        </div>
        
        <div style="padding: 20px !important; border-top: 1px solid #e5e7eb !important; display: flex !important; justify-content: flex-end !important; gap: 10px !important;">
            <button type="button" onclick="closeCategoryItemsModal()" style="padding: 8px 16px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; background: white !important; color: #374151 !important; cursor: pointer !important; font-size: 14px !important;">Close</button>
            <button type="button" onclick="goToInventoryFiltered()" id="goToInventoryBtn" style="padding: 8px 16px !important; border: none !important; border-radius: 4px !important; background: #059669 !important; color: white !important; cursor: pointer !important; font-size: 14px !important;">Go to Inventory</button>
        </div>
    </div>
</div>

<!-- All styles now defined in global style.css -->

<script>
let editingCategoryId = null;

function openCategoryModal(parentId = null, parentName = '') {
    editingCategoryId = null;
    document.getElementById('modalTitle').textContent = parentId ? `Add Subcategory to "${parentName}"` : 'Add New Category';
    document.getElementById('submitBtn').textContent = 'Add Category';
    document.getElementById('categoryForm').reset();
    
    if (parentId) {
        document.getElementById('categoryParent').value = parentId;
    }
    
    document.getElementById('categoryModal').style.display = 'block';
}

function closeCategoryModal() {
    document.getElementById('categoryModal').style.display = 'none';
    editingCategoryId = null;
}

function addSubcategory(parentId, parentName) {
    openCategoryModal(parentId, parentName);
}

function editCategory(categoryId) {
    editingCategoryId = categoryId;
    
    // Fetch category data
    const formData = new FormData();
    formData.append('action', 'get_category_data');
    formData.append('nonce', warehouse_ajax.nonce);
    formData.append('category_id', categoryId);
    
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const category = data.data;
            
            document.getElementById('modalTitle').textContent = 'Edit Category';
            document.getElementById('submitBtn').textContent = 'Update Category';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryDescription').value = category.description || '';
            document.getElementById('categoryColor').value = category.color || '#3b82f6';
            document.getElementById('categoryParent').value = category.parent_id || '';
            document.getElementById('categoryIcon').value = category.icon || 'tag';
            
            document.getElementById('categoryModal').style.display = 'block';
        } else {
            alert('Error: ' + data.data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while fetching category data');
    });
}

function submitCategory(event) {
    if (event && event.preventDefault) {
        event.preventDefault();
    }
    
    const formData = new FormData();
    formData.append('action', editingCategoryId ? 'update_category' : 'add_category');
    formData.append('nonce', warehouse_ajax.nonce);
    
    if (editingCategoryId) {
        formData.append('category_id', editingCategoryId);
    }
    
    formData.append('name', document.getElementById('categoryName').value);
    formData.append('description', document.getElementById('categoryDescription').value);
    formData.append('color', document.getElementById('categoryColor').value);
    formData.append('parent_id', document.getElementById('categoryParent').value);
    formData.append('icon', document.getElementById('categoryIcon').value);
    
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data);
            closeCategoryModal();
            location.reload(); // Refresh to show updated hierarchy
        } else {
            alert('Error: ' + data.data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the category');
    });
}

function deleteCategory(categoryId, categoryName) {
    if (!confirm(`Are you sure you want to delete "${categoryName}"?`)) {
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
            alert(data.data);
            location.reload();
        } else {
            alert('Error: ' + data.data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the category');
    });
}

function toggleCategoryChildren(categoryId) {
    const children = document.getElementById('children-' + categoryId);
    const toggle = document.querySelector(`[data-category-id="${categoryId}"] .category-toggle`);
    
    if (children.classList.contains('collapsed')) {
        children.classList.remove('collapsed');
        toggle.classList.remove('collapsed');
    } else {
        children.classList.add('collapsed');
        toggle.classList.add('collapsed');
    }
}

function toggleAllCategories() {
    const allChildren = document.querySelectorAll('.category-children');
    const allToggles = document.querySelectorAll('.category-toggle');
    const button = event.target.closest('button');
    
    const hasCollapsed = Array.from(allChildren).some(child => child.classList.contains('collapsed'));
    
    allChildren.forEach(child => {
        if (hasCollapsed) {
            child.classList.remove('collapsed');
        } else {
            child.classList.add('collapsed');
        }
    });
    
    allToggles.forEach(toggle => {
        if (hasCollapsed) {
            toggle.classList.remove('collapsed');
        } else {
            toggle.classList.add('collapsed');
        }
    });
    
    button.innerHTML = hasCollapsed ? 
        '<i class="fas fa-compress-arrows-alt"></i> Collapse All' : 
        '<i class="fas fa-expand-arrows-alt"></i> Expand All';
}

// Category Items functionality
let currentCategoryId = null;
let currentCategoryName = '';

function showCategoryItems(categoryId, categoryName) {
    currentCategoryId = categoryId;
    currentCategoryName = categoryName;
    
    // Update modal title
    document.getElementById('categoryItemsTitle').textContent = `Items in "${categoryName}"`;
    
    // Show modal
    document.getElementById('categoryItemsModal').style.display = 'block';
    
    // Show loading state
    const content = document.getElementById('categoryItemsContent');
    content.innerHTML = `
        <div id="categoryItemsLoading" style="text-align: center; padding: 40px;">
            <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #6b7280;"></i>
            <p style="margin-top: 10px; color: #6b7280;">Loading items...</p>
        </div>
    `;
    
    // Fetch items
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
            displayCategoryItems(data.data);
        } else {
            displayNoItems();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        displayError();
    });
}

function displayCategoryItems(items) {
    const content = document.getElementById('categoryItemsContent');
    
    let html = `
        <div style="max-height: 400px; overflow-y: auto;">
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <thead>
                    <tr style="background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">Item</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">ID</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">Quantity</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">Status</th>
                        <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">Action</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    items.forEach(item => {
        const statusColor = item.status === 'in-stock' ? '#059669' : 
                           item.status === 'low-stock' ? '#d97706' : '#dc2626';
        
        html += `
            <tr style="border-bottom: 1px solid #f3f4f6; hover: background-color: #f9fafb;" 
                onmouseover="this.style.backgroundColor='#f9fafb'" 
                onmouseout="this.style.backgroundColor='transparent'">
                <td style="padding: 12px; color: #111827; font-weight: 500;">${item.name}</td>
                <td style="padding: 12px; color: #6b7280; font-family: monospace;">${item.internal_id || item.sku || 'N/A'}</td>
                <td style="padding: 12px; color: #111827;">${item.quantity || 0}</td>
                <td style="padding: 12px;">
                    <span style="color: ${statusColor}; font-weight: 500; text-transform: capitalize;">
                        ${item.status || 'Unknown'}
                    </span>
                </td>
                <td style="padding: 12px; text-align: center;">
                    <button onclick="selectItemAndGoToInventory(${item.id}, '${item.name.replace(/'/g, "\\'")}', '${item.internal_id || item.sku || ''}')" 
                            style="padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                        Select
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
        <div style="margin-top: 15px; padding: 10px; background: #f0f9ff; border-radius: 6px; border: 1px solid #bae6fd;">
            <p style="margin: 0; color: #0369a1; font-size: 14px;">
                <i class="fas fa-info-circle"></i> Found ${items.length} item(s) in this category. Click "Select" to go directly to that item in inventory.
            </p>
        </div>
    `;
    
    content.innerHTML = html;
}

function displayNoItems() {
    const content = document.getElementById('categoryItemsContent');
    content.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <i class="fas fa-inbox" style="font-size: 48px; color: #d1d5db; margin-bottom: 15px;"></i>
            <h4 style="color: #6b7280; margin: 0 0 5px 0;">No Items Found</h4>
            <p style="color: #9ca3af; margin: 0;">This category doesn't contain any items yet.</p>
        </div>
    `;
}

function displayError() {
    const content = document.getElementById('categoryItemsContent');
    content.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f59e0b; margin-bottom: 15px;"></i>
            <h4 style="color: #d97706; margin: 0 0 5px 0;">Error Loading Items</h4>
            <p style="color: #92400e; margin: 0;">Unable to load items for this category.</p>
        </div>
    `;
}

function closeCategoryItemsModal() {
    document.getElementById('categoryItemsModal').style.display = 'none';
    currentCategoryId = null;
    currentCategoryName = '';
}

function selectItemAndGoToInventory(itemId, itemName, itemInternalId) {
    // Store the selected item info for highlighting in inventory
    localStorage.setItem('selectedItemId', itemId);
    localStorage.setItem('selectedItemName', itemName);
    localStorage.setItem('selectedItemInternalId', itemInternalId);
    
    // Navigate to inventory page
    window.location.href = '/?tab=inventory&item=' + itemId + '&highlight=true';
}

function goToInventoryFiltered() {
    if (currentCategoryId) {
        // Navigate to inventory with category filter
        window.location.href = '/?tab=inventory&category=' + currentCategoryId + '&category_name=' + encodeURIComponent(currentCategoryName);
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    const categoryModal = document.getElementById('categoryModal');
    const categoryItemsModal = document.getElementById('categoryItemsModal');
    
    if (event.target === categoryModal) {
        closeCategoryModal();
    }
    
    if (event.target === categoryItemsModal) {
        closeCategoryItemsModal();
    }
}
</script> 