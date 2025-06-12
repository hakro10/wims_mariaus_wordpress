<?php
/**
 * Categories Management Template Part
 */

$categories = get_all_categories();
?>

<div class="categories-content">
    <div class="page-header">
        <h1>Categories Management</h1>
        <button class="btn btn-primary" onclick="openCategoryModal()">
            <i class="fas fa-plus"></i> Add Category
        </button>
    </div>

    <div class="categories-grid">
        <?php if ($categories): ?>
            <?php foreach ($categories as $category): ?>
                <div class="category-card">
                    <div class="category-header">
                        <div class="category-icon" style="background-color: <?php echo esc_attr($category->color); ?>">
                            <i class="fas fa-tag"></i>
                        </div>
                        <div class="category-actions">
                            <button class="btn-icon" onclick="editCategory(<?php echo $category->id; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-icon" onclick="deleteCategory(<?php echo $category->id; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <h3><?php echo esc_html($category->name); ?></h3>
                    <p><?php echo esc_html($category->description); ?></p>
                    
                    <div class="category-stats">
                        <span class="item-count"><?php echo $category->item_count; ?> items</span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-tags" style="font-size: 4rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                <h3>No categories yet</h3>
                <p>Create categories to organize your inventory items.</p>
                <button class="btn btn-primary" onclick="openCategoryModal()">
                    <i class="fas fa-plus"></i> Add First Category
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Category Modal -->
<div id="add-category-modal" style="display: none; position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(0, 0, 0, 0.5) !important; z-index: 999999 !important; overflow-y: auto !important;">
    <div style="position: absolute !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; background: white !important; border-radius: 8px !important; width: 90% !important; max-width: 500px !important; max-height: 80vh !important; overflow-y: auto !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;">
        <div style="padding: 20px !important; border-bottom: 1px solid #e5e7eb !important; display: flex !important; justify-content: space-between !important; align-items: center !important;">
            <h3 style="margin: 0 !important; color: #111827 !important; font-size: 1.25rem !important;">Add New Category</h3>
            <button onclick="closeCategoryModal()" style="background: none !important; border: none !important; font-size: 24px !important; cursor: pointer !important; color: #6b7280 !important; padding: 0 !important; width: 30px !important; height: 30px !important; display: flex !important; align-items: center !important; justify-content: center !important;">&times;</button>
        </div>
        <div style="padding: 20px !important;">
            <form id="add-category-form">
                <table style="width: 100% !important; border-collapse: collapse !important;">
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Category Name *</label>
                            <input type="text" name="name" required style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Description</label>
                            <textarea name="description" rows="3" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important; resize: vertical !important; font-family: inherit !important;"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Color</label>
                            <input type="color" name="color" value="#3b82f6" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important; height: 40px !important;">
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div style="padding: 20px !important; border-top: 1px solid #e5e7eb !important; display: flex !important; justify-content: flex-end !important; gap: 10px !important;">
            <button type="button" onclick="closeCategoryModal()" style="padding: 8px 16px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; background: white !important; color: #374151 !important; cursor: pointer !important; font-size: 14px !important;">Cancel</button>
            <button type="button" onclick="submitAddCategory()" style="padding: 8px 16px !important; border: none !important; border-radius: 4px !important; background: #3b82f6 !important; color: white !important; cursor: pointer !important; font-size: 14px !important;">Add Category</button>
        </div>
    </div>
</div>

<style>
.categories-content {
    padding: 2rem 0;
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
    border: 1px solid #e5e7eb;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
}

.category-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.category-actions {
    display: flex;
    gap: 0.5rem;
}

.category-stats {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
}

.item-count {
    color: #6b7280;
    font-size: 0.875rem;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    grid-column: 1 / -1;
}

.empty-state h3 {
    color: #374151;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 20px;
}
</style>

<script>
function openCategoryModal() {
    document.getElementById('add-category-modal').style.display = 'block';
}

function closeCategoryModal() {
    document.getElementById('add-category-modal').style.display = 'none';
    document.getElementById('add-category-form').reset();
}

function submitAddCategory() {
    const form = document.getElementById('add-category-form');
    const formData = new FormData(form);
    
    // Validate required fields
    const name = formData.get('name');
    if (!name || !name.trim()) {
        alert('Please enter a category name');
        return;
    }
    
    // Prepare data for submission
    const submitData = new URLSearchParams({
        action: 'add_category',
        nonce: warehouse_ajax.nonce,
        name: name.trim(),
        description: formData.get('description') || '',
        color: formData.get('color') || '#3b82f6'
    });
    
    // Submit the category
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: submitData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Category added successfully!');
            closeCategoryModal();
            location.reload(); // Refresh to show new category
        } else {
            alert('Error adding category: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding category. Please try again.');
    });
}

function editCategory(categoryId) {
    alert('Edit functionality coming soon! Category ID: ' + categoryId);
}

function deleteCategory(categoryId) {
    if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
        const submitData = new URLSearchParams({
            action: 'delete_category',
            nonce: warehouse_ajax.nonce,
            category_id: categoryId
        });
        
        fetch(warehouse_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: submitData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Category deleted successfully!');
                location.reload();
            } else {
                alert('Error deleting category: ' + (data.data || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting category. Please try again.');
        });
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.id === 'add-category-modal') {
        closeCategoryModal();
    }
});
</script> 