<?php
/**
 * QR Codes Template Part
 */

// Get all items and locations for QR code generation
global $wpdb;
$items = $wpdb->get_results("
    SELECT i.*, c.name as category_name, l.name as location_name 
    FROM {$wpdb->prefix}wh_inventory_items i
    LEFT JOIN {$wpdb->prefix}wh_categories c ON i.category_id = c.id
    LEFT JOIN {$wpdb->prefix}wh_locations l ON i.location_id = l.id
    ORDER BY i.name
");

$locations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wh_locations ORDER BY name");
?>

<div class="qr-codes-content">
    <!-- QR Code Scanner Section -->
    <div class="scanner-section">
        <div class="section-header">
            <h2>📱 QR Code Scanner</h2>
            <p>Scan QR codes to quickly access items and locations</p>
        </div>
        
        <div class="scanner-container">
            <div class="scanner-controls">
                <button id="startScanner" class="btn btn-primary">
                    <i class="fas fa-camera"></i> Start Scanner
                </button>
                <button id="stopScanner" class="btn btn-secondary" style="display: none;">
                    <i class="fas fa-stop"></i> Stop Scanner
                </button>
                <button id="switchCamera" class="btn btn-secondary" style="display: none;">
                    <i class="fas fa-sync"></i> Switch Camera
                </button>
            </div>
            
            <div id="scannerVideo" class="scanner-video" style="display: none;">
                <video id="qrVideo" width="100%" height="300" autoplay></video>
                <div class="scanner-overlay">
                    <div class="scanner-frame"></div>
                </div>
            </div>
            
            <div id="scanResult" class="scan-result" style="display: none;">
                <div class="result-content">
                    <h3>Scan Result</h3>
                    <div id="resultData"></div>
                    <button id="closeScanResult" class="btn btn-secondary">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Generation Section -->
    <div class="generation-section">
        <div class="section-header">
            <h2>🏷️ Generate QR Codes</h2>
            <div class="section-actions">
                <button id="generateAllItems" class="btn btn-primary">
                    Generate All Item QR Codes
                </button>
                <button id="generateAllLocations" class="btn btn-secondary">
                    Generate All Location QR Codes
                </button>
            </div>
        </div>

        <!-- Items QR Codes -->
        <div class="qr-items-section">
            <h3>Inventory Items</h3>
            <div class="qr-grid">
                <?php foreach ($items as $item): ?>
                    <div class="qr-card" data-type="item" data-id="<?php echo $item->id; ?>">
                        <div class="qr-card-header">
                            <h4><?php echo esc_html($item->name); ?></h4>
                            <span class="item-id">ID: <?php echo esc_html($item->internal_id); ?></span>
                        </div>
                        
                        <div class="qr-code-container">
                            <?php if ($item->qr_code_image): ?>
                                <img src="<?php echo esc_url($item->qr_code_image); ?>" alt="QR Code" class="qr-code-image">
                            <?php else: ?>
                                <div class="qr-placeholder">
                                    <i class="fas fa-qrcode"></i>
                                    <span>No QR Code</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="qr-card-actions">
                            <button class="btn btn-sm btn-primary generate-qr" data-type="item" data-id="<?php echo $item->id; ?>">
                                <?php echo $item->qr_code_image ? 'Regenerate' : 'Generate'; ?>
                            </button>
                            <?php if ($item->qr_code_image): ?>
                                <button class="btn btn-sm btn-secondary download-qr" data-url="<?php echo esc_url($item->qr_code_image); ?>">
                                    Download
                                </button>
                                <button class="btn btn-sm btn-secondary print-qr" data-id="<?php echo $item->id; ?>" data-type="item">
                                    Print
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="item-details">
                            <small>
                                Category: <?php echo esc_html($item->category_name ?: 'Uncategorized'); ?><br>
                                Location: <?php echo esc_html($item->location_name ?: 'No location'); ?><br>
                                Quantity: <?php echo number_format($item->quantity); ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Locations QR Codes -->
        <div class="qr-locations-section">
            <h3>Warehouse Locations</h3>
            <div class="qr-grid">
                <?php foreach ($locations as $location): ?>
                    <div class="qr-card" data-type="location" data-id="<?php echo $location->id; ?>">
                        <div class="qr-card-header">
                            <h4><?php echo esc_html($location->name); ?></h4>
                            <span class="location-type"><?php echo esc_html(ucwords($location->type)); ?></span>
                        </div>
                        
                        <div class="qr-code-container">
                            <?php if ($location->qr_code_image): ?>
                                <img src="<?php echo esc_url($location->qr_code_image); ?>" alt="QR Code" class="qr-code-image">
                            <?php else: ?>
                                <div class="qr-placeholder">
                                    <i class="fas fa-qrcode"></i>
                                    <span>No QR Code</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="qr-card-actions">
                            <button class="btn btn-sm btn-primary generate-qr" data-type="location" data-id="<?php echo $location->id; ?>">
                                <?php echo $location->qr_code_image ? 'Regenerate' : 'Generate'; ?>
                            </button>
                            <?php if ($location->qr_code_image): ?>
                                <button class="btn btn-sm btn-secondary download-qr" data-url="<?php echo esc_url($location->qr_code_image); ?>">
                                    Download
                                </button>
                                <button class="btn btn-sm btn-secondary print-qr" data-id="<?php echo $location->id; ?>" data-type="location">
                                    Print
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="location-details">
                            <small>
                                <?php echo esc_html($location->description ?: 'No description'); ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Print Modal -->
<div id="printModal" class="print-modal-overlay" style="display: none;">
    <div class="print-modal-content">
        <div class="print-modal-header">
            <h3>Print QR Code</h3>
            <button class="print-modal-close" type="button">&times;</button>
        </div>
        <div class="print-modal-body">
            <div id="printContent"></div>
        </div>
        <div class="print-modal-footer">
            <button id="printQRCode" class="btn btn-primary" type="button">Print</button>
            <button class="btn btn-secondary print-modal-cancel" type="button">Cancel</button>
        </div>
    </div>
</div>

<style>
.qr-codes-content {
    padding: 1rem;
}

.scanner-section, .generation-section {
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    margin-bottom: 2rem;
    padding: 1.5rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.section-header h2 {
    margin: 0;
    color: #1f2937;
}

.section-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.scanner-container {
    max-width: 600px;
    margin: 0 auto;
}

.scanner-controls {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.scanner-video {
    position: relative;
    background: #000;
    border-radius: 8px;
    overflow: hidden;
}

.scanner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.scanner-frame {
    width: 200px;
    height: 200px;
    border: 2px solid #3b82f6;
    border-radius: 8px;
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
}

.scan-result {
    background: #f3f4f6;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}

.qr-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.qr-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    transition: all 0.2s;
}

.qr-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.qr-card-header {
    margin-bottom: 1rem;
}

.qr-card-header h4 {
    margin: 0 0 0.25rem 0;
    color: #1f2937;
    font-size: 1rem;
}

.item-id, .location-type {
    font-size: 0.875rem;
    color: #6b7280;
}

.qr-code-container {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
    min-height: 120px;
    align-items: center;
}

.qr-code-image {
    max-width: 120px;
    max-height: 120px;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
}

.qr-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #9ca3af;
    font-size: 0.875rem;
}

.qr-placeholder i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.qr-card-actions {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.item-details, .location-details {
    color: #6b7280;
    font-size: 0.8125rem;
    line-height: 1.4;
}

/* Print Modal Specific Styles */
.print-modal-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(0, 0, 0, 0.6) !important;
    z-index: 99999 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.print-modal-content {
    background: white !important;
    border-radius: 8px !important;
    max-width: 500px !important;
    width: 90% !important;
    max-height: 90vh !important;
    overflow-y: auto !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3) !important;
    position: relative !important;
}

.print-modal-header {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding: 1.5rem !important;
    border-bottom: 1px solid #e5e7eb !important;
}

.print-modal-header h3 {
    margin: 0 !important;
    color: #1f2937 !important;
    font-size: 1.25rem !important;
    font-weight: 600 !important;
}

.print-modal-body {
    padding: 1.5rem !important;
}

.print-modal-footer {
    display: flex !important;
    gap: 0.75rem !important;
    justify-content: flex-end !important;
    padding: 1.5rem !important;
    border-top: 1px solid #e5e7eb !important;
}

.print-modal-close {
    background: none !important;
    border: none !important;
    font-size: 1.5rem !important;
    cursor: pointer !important;
    color: #6b7280 !important;
    padding: 0 !important;
    width: 32px !important;
    height: 32px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 4px !important;
    transition: all 0.2s !important;
}

.print-modal-close:hover {
    background-color: #f3f4f6 !important;
    color: #374151 !important;
}

/* Print QR Label */
.print-qr-label {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

.qr-image img {
    width: 120px;
    height: 120px;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
}

.qr-info h3 {
    margin: 0 0 0.5rem 0;
    color: #1f2937;
    font-size: 1.25rem;
}

.qr-info p {
    margin: 0.25rem 0;
    color: #6b7280;
    font-size: 0.875rem;
}

@media print {
    body * {
        visibility: hidden;
    }
    
    .print-qr-label, .print-qr-label * {
        visibility: visible;
    }
    
    .print-qr-label {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        display: flex;
        align-items: center;
        gap: 1rem;
        page-break-inside: avoid;
        border: none;
        padding: 0;
    }
    
    .qr-image img {
        width: 100px;
        height: 100px;
        border: 1px solid #000;
    }
    
    .qr-info h3 {
        margin: 0;
        font-size: 1.2rem;
        color: #000;
    }
    
    .qr-info p {
        margin: 0.25rem 0;
        color: #000;
        font-size: 0.9rem;
    }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .qr-codes-content {
        padding: 0.5rem;
    }
    
    .section-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .section-actions {
        justify-content: center;
    }
    
    .qr-grid {
        grid-template-columns: 1fr;
    }
    
    .scanner-controls {
        flex-direction: column;
    }
    
    .qr-card-actions {
        justify-content: center;
    }
    
    .print-modal-content {
        width: 95%;
        margin: 1rem;
    }
}

@media (max-width: 480px) {
    .scanner-frame {
        width: 150px;
        height: 150px;
    }
    
    .qr-code-image {
        max-width: 100px;
        max-height: 100px;
    }
}
</style>

<script>
let qrScanner = null;
let currentStream = null;
let facingMode = 'environment'; // Start with back camera

document.addEventListener('DOMContentLoaded', function() {
    // Setup print modal event handlers first
    setupPrintModalEvents();
    
    // QR Code Scanner functionality
    const startScannerBtn = document.getElementById('startScanner');
    const stopScannerBtn = document.getElementById('stopScanner');
    const switchCameraBtn = document.getElementById('switchCamera');
    const scannerVideo = document.getElementById('scannerVideo');
    const qrVideo = document.getElementById('qrVideo');
    const scanResult = document.getElementById('scanResult');
    const resultData = document.getElementById('resultData');
    const closeScanResult = document.getElementById('closeScanResult');

    // Check if camera is available
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        startScannerBtn.textContent = 'Camera not supported';
        startScannerBtn.disabled = true;
    }

    startScannerBtn.addEventListener('click', startScanner);
    stopScannerBtn.addEventListener('click', stopScanner);
    switchCameraBtn.addEventListener('click', switchCamera);
    closeScanResult.addEventListener('click', closeScanResult);

    // Bulk generation (these buttons are static, so direct listeners are fine)
    document.getElementById('generateAllItems').addEventListener('click', function() {
        generateBulkQRCodes('item');
    });

    document.getElementById('generateAllLocations').addEventListener('click', function() {
        generateBulkQRCodes('location');
    });

    // Use event delegation for dynamic QR buttons
    document.addEventListener('click', function(e) {
        // Handle QR generation buttons (for dynamic buttons)
        if (e.target.classList.contains('generate-qr')) {
            const type = e.target.dataset.type;
            const id = e.target.dataset.id;
            generateQRCode(type, id, e.target);
            return;
        }
        
        // Handle download buttons (for dynamic buttons)
        if (e.target.classList.contains('download-qr')) {
            const url = e.target.dataset.url;
            downloadQRCode(url);
            return;
        }
        
        // Handle print buttons (for dynamic buttons)
        if (e.target.classList.contains('print-qr')) {
            const id = e.target.dataset.id;
            const type = e.target.dataset.type;
            printQRCode(id, type);
            return;
        }
    });
});

async function startScanner() {
    try {
        const constraints = {
            video: {
                facingMode: facingMode,
                width: { ideal: 640 },
                height: { ideal: 480 }
            }
        };

        currentStream = await navigator.mediaDevices.getUserMedia(constraints);
        qrVideo.srcObject = currentStream;
        
        scannerVideo.style.display = 'block';
        startScannerBtn.style.display = 'none';
        stopScannerBtn.style.display = 'inline-block';
        switchCameraBtn.style.display = 'inline-block';

        // Start QR code detection
        startQRDetection();
        
    } catch (error) {
        console.error('Error starting scanner:', error);
        alert('Could not start camera. Please check permissions.');
    }
}

function stopScanner() {
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
        currentStream = null;
    }
    
    scannerVideo.style.display = 'none';
    startScannerBtn.style.display = 'inline-block';
    stopScannerBtn.style.display = 'none';
    switchCameraBtn.style.display = 'none';
    scanResult.style.display = 'none';
}

async function switchCamera() {
    facingMode = facingMode === 'environment' ? 'user' : 'environment';
    stopScanner();
    setTimeout(startScanner, 100);
}

function startQRDetection() {
    // Simple QR detection using canvas (basic implementation)
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    
    function detectQR() {
        if (qrVideo.readyState === qrVideo.HAVE_ENOUGH_DATA) {
            canvas.width = qrVideo.videoWidth;
            canvas.height = qrVideo.videoHeight;
            context.drawImage(qrVideo, 0, 0, canvas.width, canvas.height);
            
            // Here you would integrate with a QR code library like jsQR
            // For now, we'll simulate detection
            
            // In a real implementation, you'd use:
            // const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            // const code = jsQR(imageData.data, imageData.width, imageData.height);
        }
        
        if (currentStream) {
            requestAnimationFrame(detectQR);
        }
    }
    
    detectQR();
}

function handleQRDetection(qrData) {
    try {
        const data = JSON.parse(qrData);
        displayScanResult(data);
    } catch (error) {
        displayScanResult({ type: 'unknown', data: qrData });
    }
}

function displayScanResult(data) {
    let resultHTML = '';
    
    if (data.type === 'item') {
        resultHTML = `
            <div class="scan-result-item">
                <h4>📦 Inventory Item</h4>
                <p><strong>Name:</strong> ${data.name}</p>
                <p><strong>ID:</strong> ${data.id}</p>
                <p><strong>Quantity:</strong> ${data.quantity}</p>
                <div class="scan-actions">
                    <button onclick="viewItem(${data.id})" class="btn btn-primary">View Details</button>
                    <button onclick="sellItem(${data.id})" class="btn btn-secondary">Sell Item</button>
                </div>
            </div>
        `;
    } else if (data.type === 'location') {
        resultHTML = `
            <div class="scan-result-location">
                <h4>📍 Location</h4>
                <p><strong>Name:</strong> ${data.name}</p>
                <p><strong>Type:</strong> ${data.type}</p>
                <div class="scan-actions">
                    <button onclick="viewLocation(${data.id})" class="btn btn-primary">View Location</button>
                    <button onclick="viewLocationItems(${data.id})" class="btn btn-secondary">View Items</button>
                </div>
            </div>
        `;
    } else {
        resultHTML = `
            <div class="scan-result-unknown">
                <h4>❓ Unknown QR Code</h4>
                <p>Data: ${data.data || 'No data'}</p>
            </div>
        `;
    }
    
    resultData.innerHTML = resultHTML;
    scanResult.style.display = 'block';
}

function generateQRCode(type, id, button) {
    const originalText = button.textContent;
    button.textContent = 'Generating...';
    button.disabled = true;
    
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'generate_qr_code',
            nonce: warehouse_ajax.nonce,
            type: type,
            id: id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the QR code display
            const card = button.closest('.qr-card');
            const container = card.querySelector('.qr-code-container');
            container.innerHTML = `<img src="${data.data.qr_url}" alt="QR Code" class="qr-code-image">`;
            
            // Update button text
            button.textContent = 'Regenerate';
            
            // Add download and print buttons if they don't exist
            if (!card.querySelector('.download-qr')) {
                const actionsDiv = card.querySelector('.qr-card-actions');
                actionsDiv.innerHTML += `
                    <button class="btn btn-sm btn-secondary download-qr" data-url="${data.data.qr_url}">Download</button>
                    <button class="btn btn-sm btn-secondary print-qr" data-id="${id}" data-type="${type}">Print</button>
                `;
                // Event listeners are handled by event delegation, no need to manually attach
            }
            
            alert('QR Code generated successfully!');
        } else {
            alert('Error generating QR code: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error generating QR code. Please try again.');
    })
    .finally(() => {
        button.textContent = originalText;
        button.disabled = false;
    });
}

function generateBulkQRCodes(type) {
    const typeText = type === 'item' ? 'items' : 'locations';
    if (!confirm(`Generate QR codes for all ${typeText}? This will update all existing QR codes.`)) {
        return;
    }
    
    const button = document.getElementById(`generateAll${type === 'item' ? 'Items' : 'Locations'}`);
    const originalText = button.textContent;
    button.textContent = 'Generating...';
    button.disabled = true;
    
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'generate_all_qr_codes',
            nonce: warehouse_ajax.nonce,
            type: typeText
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message);
            // Refresh the page to show updated QR codes
            window.location.reload();
        } else {
            alert('Error generating QR codes: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error generating QR codes. Please try again.');
    })
    .finally(() => {
        button.textContent = originalText;
        button.disabled = false;
    });
}

function downloadQRCode(url) {
    if (!url || url.includes('placeholder')) {
        alert('QR code not available for download. Please generate a QR code first.');
        return;
    }
    
    // Create a temporary link element
    const link = document.createElement('a');
    link.href = url;
    link.download = 'qr-code-' + Date.now() + '.png';
    link.target = '_blank';
    
    // Temporarily add to DOM and click
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function printQRCode(id, type) {
    console.log('printQRCode called with:', { id, type });
    
    // Get the QR code directly from the card
    const card = document.querySelector(`[data-type="${type}"][data-id="${id}"]`);
    
    if (!card) {
        console.error('Item card not found:', { type, id });
        alert('Item not found');
        return;
    }
    
    console.log('Card found:', card);
    
    const qrImage = card.querySelector('.qr-code-image');
    const name = card.querySelector('.qr-card-header h4')?.textContent;
    
    console.log('QR Image:', qrImage);
    console.log('Name:', name);
    
    let additionalInfo = '';
    if (type === 'item') {
        const itemId = card.querySelector('.item-id')?.textContent;
        const itemDetails = card.querySelector('.item-details');
        if (itemDetails) {
            additionalInfo = `<p>${itemId}</p><p>${itemDetails.innerHTML.replace(/<br>/g, '</p><p>')}</p>`;
        }
    } else if (type === 'location') {
        const locationType = card.querySelector('.location-type')?.textContent;
        const locationDetails = card.querySelector('.location-details');
        if (locationDetails) {
            additionalInfo = `<p>Type: ${locationType}</p><p>${locationDetails.textContent}</p>`;
        }
    }
    
    if (qrImage && qrImage.src && !qrImage.src.includes('placeholder')) {
        console.log('QR image found, displaying modal');
        
        const printContent = document.getElementById('printContent');
        printContent.innerHTML = `
            <div class="print-qr-label">
                <div class="qr-image">
                    <img src="${qrImage.src}" alt="QR Code" style="width: 120px; height: 120px;">
                </div>
                <div class="qr-info">
                    <h3>${name}</h3>
                    <p><strong>ID:</strong> ${id}</p>
                    ${additionalInfo}
                </div>
            </div>
        `;
        
        const modal = document.getElementById('printModal');
        console.log('Modal element:', modal);
        
        // Force display the modal
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        
        console.log('Modal should now be visible');
    } else {
        console.error('QR code not found or invalid:', {
            qrImage: !!qrImage,
            src: qrImage?.src,
            hasPlaceholder: qrImage?.src?.includes('placeholder')
        });
        alert('QR code not found or not generated yet. Please generate a QR code first by clicking the "Generate" or "Regenerate" button.');
    }
}

// Global function to close the print modal
window.closePrintModal = function() {
    console.log('closePrintModal called');
    const modal = document.getElementById('printModal');
    if (modal) {
        console.log('Hiding modal');
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
        modal.style.opacity = '0';
    } else {
        console.error('Modal not found in closePrintModal');
    }
};

// Setup print modal event handlers
function setupPrintModalEvents() {
    const modal = document.getElementById('printModal');
    const closeBtn = modal.querySelector('.print-modal-close');
    const cancelBtn = modal.querySelector('.print-modal-cancel');
    const printBtn = document.getElementById('printQRCode');
    
    console.log('Setting up print modal events...', {
        modal: !!modal,
        closeBtn: !!closeBtn,
        cancelBtn: !!cancelBtn,
        printBtn: !!printBtn
    });
    
    if (!modal || !closeBtn || !cancelBtn || !printBtn) {
        console.error('Print modal elements not found!', {
            modal: !!modal,
            closeBtn: !!closeBtn,
            cancelBtn: !!cancelBtn,
            printBtn: !!printBtn
        });
        return;
    }
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        console.log('Modal click:', e.target === modal);
        if (e.target === modal) {
            closePrintModal();
        }
    });
    
    // Close modal with close button
    closeBtn.addEventListener('click', function(e) {
        console.log('Close button clicked');
        e.preventDefault();
        e.stopPropagation();
        closePrintModal();
    });
    
    // Close modal with cancel button
    cancelBtn.addEventListener('click', function(e) {
        console.log('Cancel button clicked');
        e.preventDefault();
        e.stopPropagation();
        closePrintModal();
    });
    
    // Print button
    printBtn.addEventListener('click', function(e) {
        console.log('Print button clicked');
        e.preventDefault();
        e.stopPropagation();
        window.print();
    });
    
    // Close modal with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display !== 'none') {
            console.log('ESC key pressed, closing modal');
            closePrintModal();
        }
    });
}

function viewItem(id) {
    window.location.href = `?tab=inventory&action=view&item_id=${id}`;
}

function sellItem(id) {
    window.location.href = `?tab=inventory&action=sell&item_id=${id}`;
}

function viewLocation(id) {
    window.location.href = `?tab=locations&action=view&location_id=${id}`;
}

function viewLocationItems(id) {
    window.location.href = `?tab=inventory&location=${id}`;
}
</script> 