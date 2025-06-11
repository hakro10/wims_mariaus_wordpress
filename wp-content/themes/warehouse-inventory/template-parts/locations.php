<?php
/**
 * Locations Management Template Part
 */

$locations = get_all_locations();
?>

<div class="locations-content">
    <div class="page-header">
        <h1>Locations Management</h1>
        <button class="btn btn-primary" onclick="openModal('add-location-modal')">
            <i class="fas fa-plus"></i> Add Location
        </button>
    </div>

    <div class="locations-grid">
        <?php if ($locations): ?>
            <?php foreach ($locations as $location): ?>
                <div class="location-card">
                    <div class="location-header">
                        <div class="location-info">
                            <h3><?php echo esc_html($location->name); ?></h3>
                            <span class="location-code"><?php echo esc_html($location->code ?: 'No code'); ?></span>
                        </div>
                        <div class="location-actions">
                            <button class="btn-icon" onclick="editLocation(<?php echo $location->id; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-icon" onclick="generateQR(<?php echo $location->id; ?>)">
                                <i class="fas fa-qrcode"></i>
                            </button>
                        </div>
                    </div>
                    
                    <p><?php echo esc_html($location->description); ?></p>
                    
                    <div class="location-details">
                        <div class="detail-item">
                            <span class="detail-label">Type:</span>
                            <span class="detail-value"><?php echo esc_html(ucfirst($location->type)); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Level:</span>
                            <span class="detail-value"><?php echo $location->level; ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-map-marker-alt" style="font-size: 4rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                <h3>No locations yet</h3>
                <p>Create locations to organize your warehouse layout.</p>
                <button class="btn btn-primary" onclick="openModal('add-location-modal')">
                    <i class="fas fa-plus"></i> Add First Location
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Location Modal -->
<div id="add-location-modal" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add New Location</h3>
        </div>
        <div class="modal-body">
            <form id="add-location-form">
                <div class="form-group">
                    <label class="form-label">Location Name *</label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Location Code</label>
                    <input type="text" name="code" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="warehouse">Warehouse</option>
                        <option value="section">Section</option>
                        <option value="aisle">Aisle</option>
                        <option value="rack">Rack</option>
                        <option value="shelf">Shelf</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-close-modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitAddLocation()">Add Location</button>
        </div>
    </div>
</div>

<style>
.locations-content {
    padding: 2rem 0;
}

.locations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.location-card {
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.location-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
}

.location-code {
    color: #6b7280;
    font-size: 0.875rem;
}

.location-actions {
    display: flex;
    gap: 0.5rem;
}

.location-details {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.detail-label {
    color: #6b7280;
    font-size: 0.875rem;
}

.detail-value {
    font-weight: 500;
}
</style> 