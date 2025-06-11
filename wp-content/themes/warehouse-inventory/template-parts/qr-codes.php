<?php
/**
 * QR Codes Management Template Part
 */
?>

<div class="qr-codes-content">
    <div class="page-header">
        <h1>QR Code Generator</h1>
        <button class="btn btn-primary" onclick="openModal('generate-qr-modal')">
            <i class="fas fa-qrcode"></i> Generate QR Code
        </button>
    </div>

    <div class="qr-options">
        <div class="option-card">
            <div class="option-icon">
                <i class="fas fa-box"></i>
            </div>
            <h3>Item QR Codes</h3>
            <p>Generate QR codes for inventory items to track stock levels and movements.</p>
            <button class="btn btn-outline" onclick="generateItemQR()">Generate for Items</button>
        </div>

        <div class="option-card">
            <div class="option-icon">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <h3>Location QR Codes</h3>
            <p>Create QR codes for warehouse locations to quickly identify storage areas.</p>
            <button class="btn btn-outline" onclick="generateLocationQR()">Generate for Locations</button>
        </div>

        <div class="option-card">
            <div class="option-icon">
                <i class="fas fa-tags"></i>
            </div>
            <h3>Category QR Codes</h3>
            <p>Generate QR codes for item categories to organize inventory efficiently.</p>
            <button class="btn btn-outline" onclick="generateCategoryQR()">Generate for Categories</button>
        </div>
    </div>

    <div class="qr-history">
        <h2>Recent QR Codes</h2>
        <div class="qr-grid">
            <div class="empty-state">
                <i class="fas fa-qrcode" style="font-size: 4rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                <h3>No QR codes generated yet</h3>
                <p>Generate QR codes to streamline your warehouse operations.</p>
                <button class="btn btn-primary" onclick="openModal('generate-qr-modal')">
                    <i class="fas fa-qrcode"></i> Generate First QR Code
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Generate QR Modal -->
<div id="generate-qr-modal" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Generate QR Code</h3>
        </div>
        <div class="modal-body">
            <form id="generate-qr-form">
                <div class="form-group">
                    <label class="form-label">QR Code Type *</label>
                    <select name="qr_type" class="form-select" required onchange="updateQROptions(this.value)">
                        <option value="">Select Type</option>
                        <option value="item">Inventory Item</option>
                        <option value="location">Warehouse Location</option>
                        <option value="category">Item Category</option>
                        <option value="custom">Custom Text/URL</option>
                    </select>
                </div>
                
                <div class="form-group" id="item-select" style="display: none;">
                    <label class="form-label">Select Item</label>
                    <select name="item_id" class="form-select">
                        <option value="">Choose Item</option>
                        <!-- Items will be loaded via AJAX -->
                    </select>
                </div>
                
                <div class="form-group" id="location-select" style="display: none;">
                    <label class="form-label">Select Location</label>
                    <select name="location_id" class="form-select">
                        <option value="">Choose Location</option>
                        <!-- Locations will be loaded via AJAX -->
                    </select>
                </div>
                
                <div class="form-group" id="category-select" style="display: none;">
                    <label class="form-label">Select Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">Choose Category</option>
                        <!-- Categories will be loaded via AJAX -->
                    </select>
                </div>
                
                <div class="form-group" id="custom-input" style="display: none;">
                    <label class="form-label">Custom Content</label>
                    <textarea name="custom_content" class="form-input" rows="3" placeholder="Enter text or URL for QR code"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">QR Code Size</label>
                    <select name="size" class="form-select">
                        <option value="200">Small (200x200)</option>
                        <option value="300" selected>Medium (300x300)</option>
                        <option value="400">Large (400x400)</option>
                    </select>
                </div>
            </form>
            
            <div id="qr-preview" style="display: none; text-align: center; margin-top: 1rem;">
                <div id="qr-image"></div>
                <div class="qr-actions" style="margin-top: 1rem;">
                    <button class="btn btn-secondary" onclick="downloadQR()">
                        <i class="fas fa-download"></i> Download
                    </button>
                    <button class="btn btn-secondary" onclick="printQR()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-close-modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="generateQRCode()">Generate QR Code</button>
        </div>
    </div>
</div>

<style>
.qr-codes-content {
    padding: 2rem 0;
}

.qr-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.option-card {
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.option-icon {
    width: 64px;
    height: 64px;
    background: #f3f4f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.5rem;
    color: #3b82f6;
}

.option-card h3 {
    margin-bottom: 0.5rem;
}

.option-card p {
    color: #6b7280;
    margin-bottom: 1.5rem;
}

.qr-history {
    margin: 2rem 0;
}

.qr-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.qr-card {
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.qr-card img {
    max-width: 100%;
    margin-bottom: 1rem;
}

.qr-info {
    margin-bottom: 1rem;
}

.qr-type {
    background: #eff6ff;
    color: #3b82f6;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.qr-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

#qr-preview img {
    max-width: 300px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}
</style>

<script>
function updateQROptions(type) {
    // Hide all option groups
    document.getElementById('item-select').style.display = 'none';
    document.getElementById('location-select').style.display = 'none';
    document.getElementById('category-select').style.display = 'none';
    document.getElementById('custom-input').style.display = 'none';
    
    // Show relevant option group
    if (type === 'item') {
        document.getElementById('item-select').style.display = 'block';
    } else if (type === 'location') {
        document.getElementById('location-select').style.display = 'block';
    } else if (type === 'category') {
        document.getElementById('category-select').style.display = 'block';
    } else if (type === 'custom') {
        document.getElementById('custom-input').style.display = 'block';
    }
}

function generateQRCode() {
    const form = document.getElementById('generate-qr-form');
    const formData = new FormData(form);
    formData.append('action', 'wh_generate_qr');
    formData.append('nonce', warehouse_ajax.nonce);
    
    // Show loading
    document.getElementById('qr-preview').style.display = 'block';
    document.getElementById('qr-image').innerHTML = '<div style="padding: 2rem; text-align: center;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i><br>Generating QR Code...</div>';
    
    fetch(warehouse_ajax.url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('qr-image').innerHTML = `
                <img src="${data.data.qr_url}" alt="Generated QR Code" style="max-width: 300px; border: 1px solid #e5e7eb; border-radius: 8px;">
                <p style="margin-top: 1rem; font-size: 0.875rem; color: #6b7280;">Data: ${data.data.data}</p>
            `;
            window.currentQRUrl = data.data.qr_url;
        } else {
            document.getElementById('qr-image').innerHTML = `<div style="color: #ef4444; padding: 1rem;">Error: ${data.data.message}</div>`;
        }
    })
    .catch(error => {
        document.getElementById('qr-image').innerHTML = `<div style="color: #ef4444; padding: 1rem;">Error generating QR code</div>`;
        console.error('Error:', error);
    });
}

function downloadQR() {
    if (window.currentQRUrl) {
        const link = document.createElement('a');
        link.href = window.currentQRUrl;
        link.download = 'qr-code.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } else {
        alert('Please generate a QR code first');
    }
}

function printQR() {
    if (window.currentQRUrl) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head><title>Print QR Code</title></head>
                <body style="text-align: center; padding: 2rem;">
                    <img src="${window.currentQRUrl}" style="max-width: 400px;">
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    } else {
        alert('Please generate a QR code first');
    }
}

function generateItemQR() {
    openModal('generate-qr-modal');
    document.querySelector('select[name="qr_type"]').value = 'item';
    updateQROptions('item');
}

function generateLocationQR() {
    openModal('generate-qr-modal');
    document.querySelector('select[name="qr_type"]').value = 'location';
    updateQROptions('location');
}

function generateCategoryQR() {
    openModal('generate-qr-modal');
    document.querySelector('select[name="qr_type"]').value = 'category';
    updateQROptions('category');
}
</script> 