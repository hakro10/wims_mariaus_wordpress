<?php
/**
 * Locations Management Template Part - Hierarchical View
 */

$locations_tree = get_locations_tree();
$all_locations = get_all_locations();

function render_location_tree($locations, $level = 0) {
    if (empty($locations)) return;
    
    foreach ($locations as $location) {
        $indent = str_repeat('  ', $level);
        $has_children = !empty($location->children);
        
        // Map location type to icon
        $type_icons = [
            'warehouse' => 'warehouse',
            'town' => 'city',
            'section' => 'th-large',
            'aisle' => 'arrows-alt-h',
            'rack' => 'layer-group',
            'shelf' => 'bars',
            'bin' => 'box'
        ];
        $icon = $type_icons[$location->type] ?? 'map-marker-alt';
        
        // Map location type to color
        $type_colors = [
            'warehouse' => '#8b5cf6',
            'town' => '#06b6d4', 
            'section' => '#10b981',
            'aisle' => '#f59e0b',
            'rack' => '#ef4444',
            'shelf' => '#6366f1',
            'bin' => '#84cc16'
        ];
        $color = $type_colors[$location->type] ?? '#6b7280';
        ?>
        <div class="location-row" data-level="<?php echo $level; ?>" data-location-id="<?php echo $location->id; ?>">
            <div class="location-content">
                <div class="location-indent" style="width: <?php echo $level * 20; ?>px;"></div>
                
                <?php if ($has_children): ?>
                    <button class="location-toggle" onclick="toggleLocationChildren(<?php echo $location->id; ?>)">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                <?php else: ?>
                    <div class="location-spacer"></div>
                <?php endif; ?>
                
                <div class="location-icon" style="background-color: <?php echo esc_attr($color); ?>">
                    <i class="fas fa-<?php echo esc_attr($icon); ?>"></i>
                </div>
                
                <div class="location-info">
                    <div class="location-header">
                        <span class="location-name"><?php echo esc_html($location->name); ?></span>
                        <?php if (!empty($location->code)): ?>
                            <span class="location-code"><?php echo esc_html($location->code); ?></span>
                        <?php endif; ?>
                        <span class="location-type"><?php echo ucfirst($location->type); ?></span>
                        <?php if (isset($location->item_count) && $location->item_count > 0): ?>
                            <span class="item-count"><?php echo $location->item_count; ?> items</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($location->description)): ?>
                        <div class="location-description"><?php echo esc_html($location->description); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="location-actions">
                    <button class="action-btn add-sublocation" onclick="openLocationModal(<?php echo $location->id; ?>)" title="Add Sub-location">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="action-btn edit-location" onclick="editLocation(<?php echo $location->id; ?>)" title="Edit Location">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn generate-qr" onclick="generateQR(<?php echo $location->id; ?>)" title="Generate QR Code">
                        <i class="fas fa-qrcode"></i>
                    </button>
                    <button class="action-btn delete-location" onclick="deleteLocation(<?php echo $location->id; ?>)" title="Delete Location">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <?php if ($has_children): ?>
                <div class="location-children" id="children-<?php echo $location->id; ?>">
                    <?php render_location_tree($location->children, $level + 1); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
?>

<div class="warehouse-content">
    <div class="page-header">
        <h2>Locations Management</h2>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="toggleAllLocations()">
                <i class="fas fa-expand-arrows-alt"></i> Expand All
            </button>
            <button class="btn btn-primary" onclick="openLocationModal()">
                <i class="fas fa-plus"></i> Add Location
            </button>
        </div>
    </div>

    <div class="locations-container">
        <?php if (!empty($locations_tree)): ?>
            <div class="locations-tree">
                <?php render_location_tree($locations_tree); ?>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-map-marker-alt"></i>
                <h3>No Locations Found</h3>
                <p>Start by adding your first location to organize your warehouse layout.</p>
                <button class="btn btn-primary" onclick="openLocationModal()">
                    <i class="fas fa-plus"></i> Add Your First Location
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Location Modal -->
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
                                <option value="">None (Top Level)</option>
                                <?php 
                                // Get hierarchical locations for parent selection
                                foreach ($all_locations as $loc):
                                    $display_name = $loc->name;
                                    $indent = '';
                                    
                                    if ($loc->level > 1) {
                                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $loc->level - 1) . '↳ ';
                                    }
                                ?>
                                    <option value="<?php echo $loc->id; ?>" data-level="<?php echo $loc->level; ?>" data-parent-id="<?php echo $loc->parent_id ?: ''; ?>">
                                        <?php echo $indent . esc_html($display_name); ?>
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
                            <textarea name="description" id="locationDescription" rows="3" placeholder="Optional description for this location..." style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important; resize: vertical !important; font-family: inherit !important;"></textarea>
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
.warehouse-content {
    padding: 2rem 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

.locations-container {
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.locations-tree {
    max-height: 70vh;
    overflow-y: auto;
}

.location-row {
    border-bottom: 1px solid #f3f4f6;
    transition: background-color 0.2s;
}

.location-row:hover {
    background-color: #f9fafb;
}

.location-content {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    gap: 8px;
}

.location-indent {
    flex-shrink: 0;
}

.location-toggle {
    width: 24px;
    height: 24px;
    border: none;
    background: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    color: #6b7280;
    transition: all 0.2s;
}

.location-toggle:hover {
    background-color: #f3f4f6;
    color: #374151;
}

.location-toggle.expanded i {
    transform: rotate(180deg);
}

.location-spacer {
    width: 24px;
    height: 24px;
}

.location-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    flex-shrink: 0;
}

.location-info {
    flex: 1;
    min-width: 0;
}

.location-header {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.location-name {
    font-weight: 600;
    color: #111827;
    font-size: 14px;
}

.location-code {
    background: #f3f4f6;
    color: #6b7280;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    font-family: monospace;
}

.location-type {
    background: #dbeafe;
    color: #1e40af;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.item-count {
    color: #059669;
    font-size: 12px;
    font-weight: 500;
}

.location-description {
    color: #6b7280;
    font-size: 13px;
    margin-top: 4px;
    line-height: 1.4;
}

.location-actions {
    display: flex;
    gap: 4px;
    margin-left: auto;
}

.action-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: none;
    cursor: pointer;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    transition: all 0.2s;
    font-size: 14px;
}

.action-btn:hover {
    background-color: #f3f4f6;
    color: #374151;
}

.action-btn.delete-location:hover {
    background-color: #fee2e2;
    color: #dc2626;
}

.location-children {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
}

.location-children.expanded {
    max-height: 1000px;
}

.no-data {
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.no-data i {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.no-data h3 {
    color: #374151;
    margin-bottom: 0.5rem;
}

.btn {
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    border: none;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn-secondary:hover {
    background: #e5e7eb;
}
</style>

<script>
let currentEditingLocationId = null;

function toggleLocationChildren(locationId) {
    const children = document.getElementById('children-' + locationId);
    const toggle = document.querySelector(`[data-location-id="${locationId}"] .location-toggle`);
    
    if (children) {
        if (children.classList.contains('expanded')) {
            children.classList.remove('expanded');
            toggle.classList.remove('expanded');
        } else {
            children.classList.add('expanded');
            toggle.classList.add('expanded');
        }
    }
}

function toggleAllLocations() {
    const allChildren = document.querySelectorAll('.location-children');
    const allToggles = document.querySelectorAll('.location-toggle');
    const button = event.target.closest('button');
    
    const isExpanding = button.textContent.includes('Expand');
    
    allChildren.forEach(child => {
        if (isExpanding) {
            child.classList.add('expanded');
        } else {
            child.classList.remove('expanded');
        }
    });
    
    allToggles.forEach(toggle => {
        if (isExpanding) {
            toggle.classList.add('expanded');
        } else {
            toggle.classList.remove('expanded');
        }
    });
    
    button.innerHTML = isExpanding ? 
        '<i class="fas fa-compress-arrows-alt"></i> Collapse All' : 
        '<i class="fas fa-expand-arrows-alt"></i> Expand All';
}

function openLocationModal(parentId = null) {
    const modal = document.getElementById('add-location-modal');
    const title = document.getElementById('modalTitle');
    const form = document.getElementById('add-location-form');
    const submitBtn = document.getElementById('submitLocationBtn');
    
    // Reset form
    form.reset();
    currentEditingLocationId = null;
    
    if (parentId) {
        title.textContent = 'Add Sub-location';
        document.getElementById('parentLocation').value = parentId;
        submitBtn.textContent = 'Add Sub-location';
    } else {
        title.textContent = 'Add New Location';
        submitBtn.textContent = 'Add Location';
    }
    
    modal.style.display = 'block';
}

function closeLocationModal() {
    const modal = document.getElementById('add-location-modal');
    modal.style.display = 'none';
    currentEditingLocationId = null;
}

function editLocation(locationId) {
    // Get location data via AJAX
    jQuery.ajax({
        url: warehouse_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'get_location_data',
            location_id: locationId,
            nonce: warehouse_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                const location = response.data;
                currentEditingLocationId = locationId;
                
                // Populate form
                document.getElementById('locationName').value = location.name || '';
                document.getElementById('locationCode').value = location.code || '';
                document.getElementById('parentLocation').value = location.parent_id || '';
                document.getElementById('locationType').value = location.type || 'warehouse';
                document.getElementById('locationDescription').value = location.description || '';
                
                // Update modal
                document.getElementById('modalTitle').textContent = 'Edit Location';
                document.getElementById('submitLocationBtn').textContent = 'Update Location';
                document.getElementById('add-location-modal').style.display = 'block';
            } else {
                alert('Error loading location data: ' + (response.data || 'Unknown error'));
            }
        },
        error: function() {
            alert('Error communicating with server');
        }
    });
}

function submitAddLocation() {
    const form = document.getElementById('add-location-form');
    const formData = new FormData(form);
    
    const action = currentEditingLocationId ? 'update_location' : 'add_location';
    formData.append('action', action);
    formData.append('nonce', warehouse_ajax.nonce);
    
    if (currentEditingLocationId) {
        formData.append('location_id', currentEditingLocationId);
    }
    
    jQuery.ajax({
        url: warehouse_ajax.ajax_url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                closeLocationModal();
                location.reload(); // Refresh page to show changes
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        },
        error: function() {
            alert('Error communicating with server');
        }
    });
}

function deleteLocation(locationId) {
    if (!confirm('Are you sure you want to delete this location? This action cannot be undone.')) {
        return;
    }
    
    jQuery.ajax({
        url: warehouse_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'delete_location',
            location_id: locationId,
            nonce: warehouse_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                location.reload(); // Refresh page to show changes
            } else {
                alert('Error deleting location: ' + (response.data || 'Unknown error'));
            }
        },
        error: function() {
            alert('Error communicating with server');
        }
    });
}

function generateQR(locationId) {
    // Implementation for QR code generation
    jQuery.ajax({
        url: warehouse_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'generate_qr_code',
            location_id: locationId,
            nonce: warehouse_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                // Show QR code in modal or download
                window.open(response.data.qr_url, '_blank');
            } else {
                alert('Error generating QR code: ' + (response.data || 'Unknown error'));
            }
        },
        error: function() {
            alert('Error communicating with server');
        }
    });
}

// Close modal when clicking outside
document.getElementById('add-location-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLocationModal();
    }
});
</script> 