<?php
/**
 * Categories Management Template Part
 */

$categories = get_all_categories();
?>

<div class="categories-content">
    <div class="page-header">
        <h1>Categories Management</h1>
        <button class="btn btn-primary" onclick="openModal('add-category-modal')">
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
                <button class="btn btn-primary" onclick="openModal('add-category-modal')">
                    <i class="fas fa-plus"></i> Add First Category
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Category Modal -->
<div id="add-category-modal" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add New Category</h3>
        </div>
        <div class="modal-body">
            <form id="add-category-form">
                <div class="form-group">
                    <label class="form-label">Category Name *</label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Color</label>
                    <input type="color" name="color" class="form-input" value="#3b82f6">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-close-modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitAddCategory()">Add Category</button>
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
</style> 