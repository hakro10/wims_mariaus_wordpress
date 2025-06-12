<?php
/**
 * Locations Management Template Part
 */

$locations = get_all_locations();
?>

<div class="locations-content">
    <div class="page-header">
        <h1>Locations Management</h1>
        <button class="btn btn-primary" onclick="openLocationModal()">
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
                <button class="btn btn-primary" onclick="openLocationModal()">
                    <i class="fas fa-plus"></i> Add First Location
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Location Modal -->
<div id="add-location-modal" style="display: none; position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(0, 0, 0, 0.5) !important; z-index: 999999 !important; overflow-y: auto !important;">
    <div style="position: absolute !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; background: white !important; border-radius: 8px !important; width: 90% !important; max-width: 500px !important; max-height: 80vh !important; overflow-y: auto !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;">
        <div style="padding: 20px !important; border-bottom: 1px solid #e5e7eb !important; display: flex !important; justify-content: space-between !important; align-items: center !important;">
            <h3 style="margin: 0 !important; color: #111827 !important; font-size: 1.25rem !important;">Add New Location</h3>
            <button onclick="closeLocationModal()" style="background: none !important; border: none !important; font-size: 24px !important; cursor: pointer !important; color: #6b7280 !important; padding: 0 !important; width: 30px !important; height: 30px !important; display: flex !important; align-items: center !important; justify-content: center !important;">&times;</button>
        </div>
        <div style="padding: 20px !important;">
            <form id="add-location-form">
                <table style="width: 100% !important; border-collapse: collapse !important;">
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Location Name *</label>
                            <input type="text" name="name" required style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Location Code</label>
                            <input type="text" name="code" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Type</label>
                            <select name="type" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important;">
                                <option value="warehouse">Warehouse</option>
                                <option value="section">Section</option>
                                <option value="aisle">Aisle</option>
                                <option value="rack">Rack</option>
                                <option value="shelf">Shelf</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Level</label>
                            <input type="number" name="level" min="1" value="1" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Description</label>
                            <textarea name="description" rows="3" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important; resize: vertical !important; font-family: inherit !important;"></textarea>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div style="padding: 20px !important; border-top: 1px solid #e5e7eb !important; display: flex !important; justify-content: flex-end !important; gap: 10px !important;">
            <button type="button" onclick="closeLocationModal()" style="padding: 8px 16px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; background: white !important; color: #374151 !important; cursor: pointer !important; font-size: 14px !important;">Cancel</button>
            <button type="button" onclick="submitAddLocation()" style="padding: 8px 16px !important; border: none !important; border-radius: 4px !important; background: #3b82f6 !important; color: white !important; cursor: pointer !important; font-size: 14px !important;">Add Location</button>
        </div>
    </div>
</div>

<style>
.locations-content {
    padding: 2rem 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
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
function openLocationModal() {
    document.getElementById('add-location-modal').style.display = 'block';
}

function closeLocationModal() {
    document.getElementById('add-location-modal').style.display = 'none';
    document.getElementById('add-location-form').reset();
}

function submitAddLocation() {
    const form = document.getElementById('add-location-form');
    const formData = new FormData(form);
    
    // Validate required fields
    const name = formData.get('name');
    if (!name || !name.trim()) {
        alert('Please enter a location name');
        return;
    }
    
    // Prepare data for submission
    const submitData = new URLSearchParams({
        action: 'add_location',
        nonce: warehouse_ajax.nonce,
        name: name.trim(),
        code: formData.get('code') || '',
        type: formData.get('type') || 'warehouse',
        level: formData.get('level') || '1',
        description: formData.get('description') || ''
    });
    
    // Submit the location
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
            alert('Location added successfully!');
            closeLocationModal();
            location.reload(); // Refresh to show new location
        } else {
            alert('Error adding location: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding location. Please try again.');
    });
}

function editLocation(locationId) {
    alert('Edit functionality coming soon! Location ID: ' + locationId);
}

function generateQR(locationId) {
    alert('QR code generation coming soon! Location ID: ' + locationId);
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.id === 'add-location-modal') {
        closeLocationModal();
    }
});
</script> 