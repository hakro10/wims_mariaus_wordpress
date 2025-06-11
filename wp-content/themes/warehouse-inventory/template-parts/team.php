<?php
/**
 * Team Management Template Part
 */

// Team template loaded successfully

// Check if user has admin permissions
if (!current_user_can('manage_options')) {
    echo '<div class="permission-denied"><p>You do not have permission to access this page.</p></div>';
    return;
}
?>

<div class="team-management">
    <!-- Header with Add Button -->
    <div class="team-header">
        <h2><i class="fas fa-users"></i> Team Management</h2>
        <button class="btn btn-primary" onclick="openAddMemberModal()">
            <i class="fas fa-plus"></i> Add Team Member
        </button>
    </div>

    <!-- Team Stats -->
    <div class="team-stats">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value" id="total-members">0</div>
            <div class="stat-label">Total Members</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-value" id="active-members">0</div>
            <div class="stat-label">Active Members</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-value" id="managers">0</div>
            <div class="stat-label">Managers</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-user-friends"></i>
            </div>
            <div class="stat-value" id="employees">0</div>
            <div class="stat-label">Employees</div>
        </div>
    </div>

    <!-- Team Members Table -->
    <div class="team-table-container">
        <div class="table-header">
            <h3>Team Members</h3>
            <div class="search-box">
                <input type="text" id="team-search" placeholder="Search members..." />
                <i class="fas fa-search"></i>
            </div>
        </div>
        
        <table class="team-table" id="team-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="team-table-body">
                <!-- Dynamic content loaded here -->
            </tbody>
        </table>
        
        <div class="loading-spinner" id="team-loading" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i> Loading team members...
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div id="add-member-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Add Team Member</h3>
            <span class="close" onclick="closeModal('add-member-modal')">&times;</span>
        </div>
        
        <form id="add-member-form">
            <div class="form-grid">
                <div class="form-group">
                    <label for="add-username">Username *</label>
                    <input type="text" id="add-username" name="username" required />
                </div>
                
                <div class="form-group">
                    <label for="add-email">Email *</label>
                    <input type="email" id="add-email" name="email" required />
                </div>
                
                <div class="form-group">
                    <label for="add-first-name">First Name *</label>
                    <input type="text" id="add-first-name" name="first_name" required />
                </div>
                
                <div class="form-group">
                    <label for="add-last-name">Last Name *</label>
                    <input type="text" id="add-last-name" name="last_name" required />
                </div>
                
                <div class="form-group">
                    <label for="add-role">Role *</label>
                    <select id="add-role" name="role" required>
                        <option value="warehouse_employee">Warehouse Employee</option>
                        <option value="warehouse_manager">Warehouse Manager</option>
                        <option value="administrator">Administrator</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="add-department">Department</label>
                    <input type="text" id="add-department" name="department" placeholder="e.g., Operations, Logistics" />
                </div>
                
                <div class="form-group">
                    <label for="add-position">Position</label>
                    <input type="text" id="add-position" name="position" placeholder="e.g., Team Lead, Associate" />
                </div>
                
                <div class="form-group">
                    <label for="add-phone">Phone</label>
                    <input type="tel" id="add-phone" name="phone" />
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('add-member-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Member
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Member Modal -->
<div id="edit-member-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-edit"></i> Edit Team Member</h3>
            <span class="close" onclick="closeModal('edit-member-modal')">&times;</span>
        </div>
        
        <form id="edit-member-form">
            <input type="hidden" id="edit-member-id" name="member_id" />
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="edit-first-name">First Name *</label>
                    <input type="text" id="edit-first-name" name="first_name" required />
                </div>
                
                <div class="form-group">
                    <label for="edit-last-name">Last Name *</label>
                    <input type="text" id="edit-last-name" name="last_name" required />
                </div>
                
                <div class="form-group">
                    <label for="edit-email">Email *</label>
                    <input type="email" id="edit-email" name="email" required />
                </div>
                
                <div class="form-group">
                    <label for="edit-role">Role *</label>
                    <select id="edit-role" name="role" required>
                        <option value="warehouse_employee">Warehouse Employee</option>
                        <option value="warehouse_manager">Warehouse Manager</option>
                        <option value="administrator">Administrator</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit-department">Department</label>
                    <input type="text" id="edit-department" name="department" />
                </div>
                
                <div class="form-group">
                    <label for="edit-position">Position</label>
                    <input type="text" id="edit-position" name="position" />
                </div>
                
                <div class="form-group">
                    <label for="edit-phone">Phone</label>
                    <input type="tel" id="edit-phone" name="phone" />
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('edit-member-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Member
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Password Display Modal -->
<div id="password-modal" class="modal" style="display: none;">
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h3><i class="fas fa-key"></i> User Credentials</h3>
            <span class="close" onclick="closeModal('password-modal')">&times;</span>
        </div>
        
        <div class="password-info">
            <p><strong>Username:</strong> <span id="new-username"></span></p>
            <p><strong>Password:</strong> <span id="new-password" style="font-family: monospace; background: #f5f5f5; padding: 5px; border-radius: 3px;"></span></p>
            <p class="note">
                <i class="fas fa-info-circle"></i>
                Please save these credentials and share them securely with the user. The user can change their password after logging in.
            </p>
        </div>
        
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="copyPassword()">
                <i class="fas fa-copy"></i> Copy Password
            </button>
            <button type="button" class="btn btn-primary" onclick="closeModal('password-modal')">
                <i class="fas fa-check"></i> Got It
            </button>
        </div>
    </div>
</div>

<script>
// Team Management JavaScript
jQuery(document).ready(function($) {
    loadTeamMembers();
    
    // Add member form submission
    $('#add-member-form').on('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        return addTeamMember();
    });
    
    // Edit member form submission
    $('#edit-member-form').on('submit', function(e) {
        e.preventDefault();
        updateTeamMember();
    });
    
    // Search functionality
    $('#team-search').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterTeamMembers(searchTerm);
    });
});

function loadTeamMembers() {
    console.log('Loading team members...');
    console.log('warehouse_ajax object:', warehouse_ajax);
    
    jQuery('#team-loading').show();
    
    jQuery.ajax({
        url: warehouse_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'get_team_members',
            nonce: warehouse_ajax.nonce
        },
        success: function(response) {
            console.log('AJAX Response:', response);
            if (response.success) {
                displayTeamMembers(response.data);
                updateTeamStats(response.data);
            } else {
                console.error('Error response:', response.data);
                showAlert('Error loading team members: ' + response.data, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', xhr, status, error);
            showAlert('Failed to load team members: ' + error, 'error');
        },
        complete: function() {
            jQuery('#team-loading').hide();
        }
    });
}

function displayTeamMembers(members) {
    const tbody = jQuery('#team-table-body');
    tbody.empty();
    
    if (members.length === 0) {
        tbody.append('<tr><td colspan="9" class="no-data">No team members found</td></tr>');
        return;
    }
    
    members.forEach(function(member) {
        const lastLogin = member.last_login ? formatDate(member.last_login) : 'Never';
        const roleLabel = getRoleLabel(member.role);
        const statusBadge = `<span class="status-badge ${member.status}">${member.status}</span>`;
        
        const row = `
            <tr data-member-id="${member.id}">
                <td>
                    <div class="member-info">
                        <div class="member-name">${member.first_name} ${member.last_name}</div>
                        <div class="member-meta">${member.display_name || ''}</div>
                    </div>
                </td>
                <td>${member.username}</td>
                <td>${member.email}</td>
                <td><span class="role-badge ${member.role}">${roleLabel}</span></td>
                <td>${member.department || '-'}</td>
                <td>${member.position || '-'}</td>
                <td>${statusBadge}</td>
                <td>${lastLogin}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon" onclick="editMember(${member.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon" onclick="resetPassword(${member.id})" title="Reset Password">
                            <i class="fas fa-key"></i>
                        </button>
                        <button class="btn-icon danger" onclick="deleteMember(${member.id})" title="Remove">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function updateTeamStats(members) {
    const totalMembers = members.length;
    const activeMembers = members.filter(m => m.status === 'active').length;
    const managers = members.filter(m => m.role === 'warehouse_manager' || m.role === 'administrator').length;
    const employees = members.filter(m => m.role === 'warehouse_employee').length;
    
    jQuery('#total-members').text(totalMembers);
    jQuery('#active-members').text(activeMembers);
    jQuery('#managers').text(managers);
    jQuery('#employees').text(employees);
}

function openAddMemberModal() {
    jQuery('#add-member-form')[0].reset();
    jQuery('#add-member-modal').show();
}

function addTeamMember() {
    const submitButton = jQuery('#add-member-form button[type="submit"]');
    
    // Check if already submitting
    if (submitButton.prop('disabled')) {
        return false;
    }
    
    const formData = jQuery('#add-member-form').serialize();
    
    // Disable submit button and show loading
    submitButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');
    
    jQuery.ajax({
        url: warehouse_ajax.ajax_url,
        type: 'POST',
        data: formData + '&action=add_team_member&nonce=' + warehouse_ajax.nonce,
        success: function(response) {
            console.log('Add member response:', response);
            if (response.success) {
                showAlert(response.data.message || 'Team member added successfully', 'success');
                closeModal('add-member-modal');
                
                // Reset form
                jQuery('#add-member-form')[0].reset();
                
                // Show password modal with credentials
                jQuery('#new-username').text(jQuery('#add-username').val());
                jQuery('#new-password').text(response.data.password);
                jQuery('#password-modal').show();
                
                // Reload team members after short delay
                setTimeout(loadTeamMembers, 500);
            } else {
                showAlert('Error: ' + (response.data || 'Failed to add team member'), 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Add member error:', xhr, status, error);
            showAlert('Failed to add team member: ' + error, 'error');
        },
        complete: function() {
            // Re-enable submit button after a delay to prevent double submission
            setTimeout(function() {
                submitButton.prop('disabled', false).html('<i class="fas fa-plus"></i> Add Member');
            }, 1000);
        }
    });
    
    return false; // Prevent default form submission
}

function editMember(memberId) {
    // Find member data from the table
    const row = jQuery(`tr[data-member-id="${memberId}"]`);
    if (!row.length) return;
    
    // Get member data from the current display (you might want to store full data)
    jQuery('#edit-member-id').val(memberId);
    
    // You'll need to implement getting full member data
    // For now, this is a simplified version
    jQuery('#edit-member-modal').show();
}

function updateTeamMember() {
    const formData = jQuery('#edit-member-form').serialize();
    
    jQuery.ajax({
        url: warehouse_ajax.ajax_url,
        type: 'POST',
        data: formData + '&action=update_team_member&nonce=' + warehouse_ajax.nonce,
        success: function(response) {
            if (response.success) {
                showAlert(response.data, 'success');
                closeModal('edit-member-modal');
                loadTeamMembers();
            } else {
                showAlert('Error: ' + response.data, 'error');
            }
        },
        error: function() {
            showAlert('Failed to update team member', 'error');
        }
    });
}

function deleteMember(memberId) {
    if (!confirm('Are you sure you want to remove this team member? This will permanently delete their WordPress account and cannot be undone.')) {
        return;
    }
    
    jQuery.ajax({
        url: warehouse_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'delete_team_member',
            member_id: memberId,
            nonce: warehouse_ajax.nonce
        },
        success: function(response) {
            console.log('Delete member response:', response);
            if (response.success) {
                showAlert(response.data || 'Team member removed successfully', 'success');
                
                // Reload team members after short delay
                setTimeout(loadTeamMembers, 500);
            } else {
                showAlert('Error: ' + response.data, 'error');
            }
        },
        error: function() {
            showAlert('Failed to remove team member', 'error');
        }
    });
}

function resetPassword(memberId) {
    if (!confirm('Are you sure you want to reset this user\'s password? They will need to use the new password to login.')) {
        return;
    }
    
    jQuery.ajax({
        url: warehouse_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'reset_user_password',
            member_id: memberId,
            nonce: warehouse_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                showAlert(response.data.message, 'success');
                
                // Show new password
                jQuery('#new-username').text('Password Reset');
                jQuery('#new-password').text(response.data.password);
                jQuery('#password-modal').show();
            } else {
                showAlert('Error: ' + response.data, 'error');
            }
        },
        error: function() {
            showAlert('Failed to reset password', 'error');
        }
    });
}

function filterTeamMembers(searchTerm) {
    jQuery('#team-table-body tr').each(function() {
        const row = jQuery(this);
        const text = row.text().toLowerCase();
        
        if (text.includes(searchTerm)) {
            row.show();
        } else {
            row.hide();
        }
    });
}

function copyPassword() {
    const password = jQuery('#new-password').text();
    navigator.clipboard.writeText(password).then(function() {
        showAlert('Password copied to clipboard', 'success');
    });
}

function closeModal(modalId) {
    jQuery('#' + modalId).hide();
}

function getRoleLabel(role) {
    const roles = {
        'warehouse_employee': 'Employee',
        'warehouse_manager': 'Manager',
        'administrator': 'Admin'
    };
    return roles[role] || role;
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString();
}

function showAlert(message, type) {
    // Simple alert for now, you can enhance this
    alert(message);
}
</script>