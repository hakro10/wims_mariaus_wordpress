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
                            <div class="location-path"><?php echo esc_html($location->full_path); ?></div>
                        </div>
                        <div class="location-actions">
                            <button class="btn-icon" onclick="editLocation(<?php echo $location->id; ?>)" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-icon" onclick="deleteLocation(<?php echo $location->id; ?>)" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                            <button class="btn-icon" onclick="generateQR(<?php echo $location->id; ?>)" title="Generate QR">
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
            <h3 id="modalTitle" style="margin: 0 !important; color: #111827 !important; font-size: 1.25rem !important;">Add New Location</h3>
            <button onclick="closeLocationModal()" style="background: none !important; border: none !important; font-size: 24px !important; cursor: pointer !important; color: #6b7280 !important; padding: 0 !important; width: 30px !important; height: 30px !important; display: flex !important; align-items: center !important; justify-content: center !important;">&times;</button>
        </div>
        <div style="padding: 20px !important;">
            <form id="add-location-form">
                <table style="width: 100% !important; border-collapse: collapse !important;">
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Location Name *</label>
                            <input type="text" id="locationName" name="name" required style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Location Code</label>
                            <input type="text" id="locationCode" name="code" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Parent Location</label>
                            <select name="parent_id" id="parentLocation" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important;">
                                <option value="">-- Root Level --</option>
                                <?php 
                                $all_locations = get_all_locations();
                                foreach ($all_locations as $loc): ?>
                                    <option value="<?php echo $loc->id; ?>" data-level="<?php echo $loc->level; ?>">
                                        <?php echo str_repeat('└─ ', $loc->level - 1) . esc_html($loc->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Type</label>
                            <select name="type" id="locationType" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important;">
                                <option value="town">Town</option>
                                <option value="warehouse">Warehouse</option>
                                <option value="section">Section</option>
                                <option value="aisle">Aisle</option>
                                <option value="rack">Rack</option>
                                <option value="shelf">Shelf</option>
                                <option value="bin">Bin</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Description</label>
                            <textarea name="description" id="locationDescription" rows="3" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important; resize: vertical !important; font-family: inherit !important;"></textarea>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div style="padding: 20px !important; border-top: 1px solid #e5e7eb !important; display: flex !important; justify-content: flex-end !important; gap: 10px !important;">
            <button type="button" onclick="closeLocationModal()" style="padding: 8px 16px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; background: white !important; color: #374151 !important; cursor: pointer !important; font-size: 14px !important;">Cancel</button>
            <button type="button" id="submitLocationBtn" onclick="submitAddLocation()" style="padding: 8px 16px !important; border: none !important; border-radius: 4px !important; background: #3b82f6 !important; color: white !important; cursor: pointer !important; font-size: 14px !important;">Add Location</button>
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

.location-path {
    color: #3b82f6;
    font-size: 0.8125rem;
    margin-top: 0.25rem;
    font-style: italic;
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
    resetLocationModal();
    document.getElementById('add-location-modal').style.display = 'block';
}

function closeLocationModal() {
    document.getElementById('add-location-modal').style.display = 'none';
    document.getElementById('add-location-form').reset();
    resetLocationModal();
}

function resetLocationModal() {
    const submitBtn = document.getElementById('submitLocationBtn');
    const modalTitle = document.getElementById('modalTitle');
    
    // Reset to add mode
    modalTitle.textContent = 'Add New Location';
    submitBtn.textContent = 'Add Location';
    submitBtn.removeAttribute('data-edit-id');
}

function submitAddLocation() {
    const form = document.getElementById('add-location-form');
    const formData = new FormData(form);
    const submitBtn = document.getElementById('submitLocationBtn');
    const editId = submitBtn.getAttribute('data-edit-id');
    
    // Validate required fields
    const name = formData.get('name');
    if (!name || !name.trim()) {
        alert('Please enter a location name');
        return;
    }
    
    // Prepare data for submission
    const submitData = new URLSearchParams({
        action: editId ? 'update_location' : 'add_location',
        nonce: warehouse_ajax.nonce,
        name: name.trim(),
        code: formData.get('code') || '',
        type: formData.get('type') || 'warehouse',
        parent_id: formData.get('parent_id') || '',
        description: formData.get('description') || ''
    });
    
    if (editId) {
        submitData.append('location_id', editId);
    }
    
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
            alert(editId ? 'Location updated successfully!' : 'Location added successfully!');
            closeLocationModal();
            location.reload(); // Refresh to show changes
        } else {
            alert('Error ' + (editId ? 'updating' : 'adding') + ' location: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error ' + (editId ? 'updating' : 'adding') + ' location. Please try again.');
    });
}

function editLocation(locationId) {
    // Get location data via AJAX
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_location_data&location_id=${locationId}&nonce=${warehouse_ajax.nonce}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            openEditLocationModal(data.data);
        } else {
            alert('Error fetching location data: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error fetching location data');
    });
}

function openEditLocationModal(location) {
    const modal = document.getElementById('add-location-modal');
    const submitBtn = document.getElementById('submitLocationBtn');
    const modalTitle = document.getElementById('modalTitle');
    
    // Change modal title and button text
    modalTitle.textContent = 'Edit Location';
    submitBtn.textContent = 'Update Location';
    submitBtn.setAttribute('data-edit-id', location.id);
    
    // Populate form fields
    document.getElementById('locationName').value = location.name || '';
    document.getElementById('locationCode').value = location.code || '';
    document.getElementById('locationDescription').value = location.description || '';
    document.getElementById('locationType').value = location.type || '';
    
    // Set parent location if exists
    const parentSelect = document.getElementById('parentLocation');
    if (location.parent_id) {
        parentSelect.value = location.parent_id;
    } else {
        parentSelect.value = '';
    }
    
    modal.style.display = 'block';
}

function deleteLocation(locationId) {
    if (!confirm('Are you sure you want to delete this location? This action cannot be undone.')) {
        return;
    }

    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete_location&location_id=${locationId}&nonce=${warehouse_ajax.nonce}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Location deleted successfully');
            location.reload();
        } else {
            alert('Error deleting location: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting location');
    });
}

function generateQR(locationId) {
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=generate_qr_code&type=location&id=${locationId}&nonce=${warehouse_ajax.nonce}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Open print modal with QR code
            fetch(warehouse_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_qr_print_data&type=location&id=${locationId}&nonce=${warehouse_ajax.nonce}`
            })
            .then(response => response.json())
            .then(printData => {
                if (printData.success) {
                    openPrintModal(printData.data);
                } else {
                    alert('Error getting QR print data: ' + (printData.data || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error getting QR print data');
            });
        } else {
            alert('Error generating QR code: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error generating QR code');
    });
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.id === 'add-location-modal') {
        closeLocationModal();
    }
});
</script> 