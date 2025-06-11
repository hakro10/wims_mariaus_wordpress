/**
 * Warehouse Inventory JavaScript
 */

jQuery(document).ready(function($) {
    // Initialize warehouse functionality
    console.log('Warehouse JS loaded');
    
    // Check if warehouse_ajax object exists
    if (typeof warehouse_ajax === 'undefined') {
        console.error('warehouse_ajax object not found. AJAX functionality may not work.');
        return;
    }
    
    console.log('AJAX setup successful:', warehouse_ajax);
    
    // General utility functions
    window.showAlert = function(message, type) {
        // Simple alert for now - you can enhance this with a proper notification system
        if (type === 'error') {
            alert('Error: ' + message);
        } else {
            alert(message);
        }
    };
    
    // Modal handling functions
    window.openModal = function(modalId) {
        $('#' + modalId).show();
    };
    
    window.closeModal = function(modalId) {
        $('#' + modalId).hide();
    };
    
    // Close modal when clicking outside
    $(document).on('click', '.modal', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Handle ESC key to close modals
    $(document).keyup(function(e) {
        if (e.keyCode === 27) { // ESC key
            $('.modal').hide();
        }
    });
});

// Global functions for team management (since they're called from inline onclick)
function openAddMemberModal() {
    jQuery('#add-member-form')[0].reset();
    jQuery('#add-member-modal').show();
}

function closeModal(modalId) {
    jQuery('#' + modalId).hide();
}

function copyPassword() {
    const password = jQuery('#new-password').text();
    if (navigator.clipboard) {
        navigator.clipboard.writeText(password).then(function() {
            showAlert('Password copied to clipboard', 'success');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = password;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showAlert('Password copied to clipboard', 'success');
    }
}

function showAlert(message, type) {
    // Enhanced alert function
    if (type === 'error') {
        console.error(message);
        alert('Error: ' + message);
    } else if (type === 'success') {
        console.log(message);
        alert('Success: ' + message);
    } else {
        alert(message);
    }
} 