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

// Team management functions
function submitAddMember() {
    const form = jQuery('#add-member-form');
    const formData = {
        action: 'add_team_member',
        nonce: warehouse_ajax.nonce,
        username: form.find('[name="username"]').val(),
        email: form.find('[name="email"]').val(),
        first_name: form.find('[name="first_name"]').val(),
        last_name: form.find('[name="last_name"]').val(),
        role: form.find('[name="role"]').val(),
        department: form.find('[name="department"]').val(),
        position: form.find('[name="position"]').val(),
        phone: form.find('[name="phone"]').val()
    };
    
    // Basic validation
    if (!formData.username || !formData.email || !formData.first_name || !formData.last_name || !formData.role) {
        showAlert('Please fill in all required fields', 'error');
        return;
    }
    
    jQuery.post(warehouse_ajax.ajax_url, formData, function(response) {
        if (response.success) {
            showAlert('Team member added successfully!', 'success');
            closeModal('add-member-modal');
            loadTeamMembers(); // Reload the team list
        } else {
            showAlert(response.data || 'Failed to add team member', 'error');
        }
    }).fail(function() {
        showAlert('Network error. Please try again.', 'error');
    });
}

// Team member loading function is defined in team.php template

// Modal handling for new structure
jQuery(document).ready(function($) {
    // Close modal when clicking the overlay
    $(document).on('click', '.modal-overlay', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Close modal with close button
    $(document).on('click', '.modal-close, .btn-close-modal', function() {
        $(this).closest('.modal-overlay').hide();
    });
}); 