<?php
/**
 * Team Management Template Part
 */

// Team Management Template loaded

// Check if user has admin permissions
if (!current_user_can('manage_options')) {
    echo '<div class="permission-denied"><p>You do not have permission to access this page.</p></div>';
    return;
}
?>

<div class="team-management">
    <!-- Header with Add Button -->
    <div class="search-filters">
        <div class="search-row">
            <h2 style="margin: 0; color: #1f2937; font-size: 1.5rem; font-weight: 600;">
                <i class="fas fa-users"></i> Team Management
            </h2>
            <button class="btn btn-primary" onclick="openAddMemberModal()">
                <i class="fas fa-plus"></i> Add Team Member
            </button>
        </div>
    </div>

    <!-- Team Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="color: #3b82f6;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number" id="total-members">0</div>
            <div class="stat-label">Total Members</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="color: #10b981;">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-number" id="managers">0</div>
            <div class="stat-label">Managers</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="color: #3b82f6;">
                <i class="fas fa-user-friends"></i>
            </div>
            <div class="stat-number" id="employees">0</div>
            <div class="stat-label">Employees</div>
        </div>
    </div>

    <!-- Team Members Table -->
    <div class="table-container">
        <div class="search-filters" style="border-radius: 0; border-bottom: 1px solid #f3f4f6; margin-bottom: 0;">
            <div class="search-row">
                <h3 style="margin: 0; color: #1f2937; font-size: 1.125rem; font-weight: 600;">Team Members</h3>
                <div class="search-input" style="max-width: 300px;">
                    <input type="text" id="team-search" placeholder="Search members..." class="form-input" />
                    <i class="fas fa-search search-icon"></i>
                </div>
            </div>
        </div>
        
        <table class="table" id="team-table">
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
        
        <div class="loading" id="team-loading" style="display: none;">
            <i class="fas fa-spinner"></i> Loading team members...
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div id="add-member-modal" style="display: none; position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(0, 0, 0, 0.5) !important; z-index: 999999 !important; overflow-y: auto !important;">
    <div style="position: absolute !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; background: white !important; border-radius: 8px !important; width: 90% !important; max-width: 600px !important; max-height: 80vh !important; overflow-y: auto !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;">
        <div style="padding: 20px !important; border-bottom: 1px solid #e5e7eb !important; display: flex !important; justify-content: space-between !important; align-items: center !important;">
            <h3 style="margin: 0 !important; color: #111827 !important; font-size: 1.25rem !important;"><i class="fas fa-user-plus"></i> Add Team Member</h3>
            <button onclick="closeModal('add-member-modal')" style="background: none !important; border: none !important; font-size: 24px !important; cursor: pointer !important; color: #6b7280 !important; padding: 0 !important; width: 30px !important; height: 30px !important; display: flex !important; align-items: center !important; justify-content: center !important;">&times;</button>
        </div>
        
        <div style="padding: 20px !important;">
            <form id="add-member-form">
                <table style="width: 100% !important; border-collapse: collapse !important;">
                    <tr>
                        <td style="padding: 8px 0 !important;">
                            <table style="width: 100% !important; border-collapse: collapse !important;">
                                <tr>
                                    <td style="width: 48% !important; padding-right: 2% !important;">
                                        <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Username *</label>
                                        <input type="text" name="username" required style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                                    </td>
                                    <td style="width: 48% !important; padding-left: 2% !important;">
                                        <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Email *</label>
                                        <input type="email" name="email" required style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important;">
                            <table style="width: 100% !important; border-collapse: collapse !important;">
                                <tr>
                                    <td style="width: 48% !important; padding-right: 2% !important;">
                                        <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">First Name *</label>
                                        <input type="text" name="first_name" required style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                                    </td>
                                    <td style="width: 48% !important; padding-left: 2% !important;">
                                        <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Last Name *</label>
                                        <input type="text" name="last_name" required style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Role *</label>
                            <select name="role" required style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important;">
                                <option value="warehouse_employee">Warehouse Employee</option>
                                <option value="warehouse_manager">Warehouse Manager</option>
                                <option value="administrator">Administrator</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important;">
                            <table style="width: 100% !important; border-collapse: collapse !important;">
                                <tr>
                                    <td style="width: 48% !important; padding-right: 2% !important;">
                                        <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Department</label>
                                        <input type="text" name="department" placeholder="e.g., Operations, Logistics" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                                    </td>
                                    <td style="width: 48% !important; padding-left: 2% !important;">
                                        <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Position</label>
                                        <input type="text" name="position" placeholder="e.g., Team Lead, Associate" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Phone</label>
                            <input type="tel" name="phone" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        
        <div style="padding: 20px !important; border-top: 1px solid #e5e7eb !important; display: flex !important; justify-content: flex-end !important; gap: 10px !important;">
            <button type="button" onclick="closeModal('add-member-modal')" style="padding: 8px 16px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; background: white !important; color: #374151 !important; cursor: pointer !important; font-size: 14px !important;">Cancel</button>
            <button type="button" onclick="submitAddMember()" style="padding: 8px 16px !important; border: none !important; border-radius: 4px !important; background: #3b82f6 !important; color: white !important; cursor: pointer !important; font-size: 14px !important;">
                <i class="fas fa-plus"></i> Add Member
            </button>
        </div>
    </div>
</div>

<!-- Edit Member Modal -->
<div id="edit-member-modal" style="display: none; position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(0, 0, 0, 0.5) !important; z-index: 999999 !important; overflow-y: auto !important;">
    <div style="position: absolute !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; background: white !important; border-radius: 8px !important; width: 90% !important; max-width: 600px !important; max-height: 80vh !important; overflow-y: auto !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;">
        <div style="padding: 20px !important; border-bottom: 1px solid #e5e7eb !important; display: flex !important; justify-content: space-between !important; align-items: center !important;">
            <h3 style="margin: 0 !important; color: #111827 !important; font-size: 1.25rem !important;"><i class="fas fa-user-edit"></i> Edit Team Member</h3>
            <button onclick="closeModal('edit-member-modal')" style="background: none !important; border: none !important; font-size: 24px !important; cursor: pointer !important; color: #6b7280 !important; padding: 0 !important; width: 30px !important; height: 30px !important; display: flex !important; align-items: center !important; justify-content: center !important;">&times;</button>
        </div>
        
        <div style="padding: 20px !important;">
            <form id="edit-member-form">
                <input type="hidden" id="edit-member-id" name="member_id" />
                
                <table style="width: 100% !important; border-collapse: collapse !important;">
                    <tr>
                        <td style="padding: 8px 0 !important;">
                            <table style="width: 100% !important; border-collapse: collapse !important;">
                                <tr>
                                    <td style="width: 48% !important; padding-right: 2% !important;">
                                        <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">First Name *</label>
                                        <input type="text" id="edit-first-name" name="first_name" required style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                                    </td>
                                    <td style="width: 48% !important; padding-left: 2% !important;">
                                        <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Last Name *</label>
                                        <input type="text" id="edit-last-name" name="last_name" required style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Email *</label>
                            <input type="email" id="edit-email" name="email" required style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Role *</label>
                            <select id="edit-role" name="role" required style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; background: white !important; box-sizing: border-box !important; display: block !important;">
                                <option value="warehouse_employee">Warehouse Employee</option>
                                <option value="warehouse_manager">Warehouse Manager</option>
                                <option value="administrator">Administrator</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important;">
                            <table style="width: 100% !important; border-collapse: collapse !important;">
                                <tr>
                                    <td style="width: 48% !important; padding-right: 2% !important;">
                                        <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Department</label>
                                        <input type="text" id="edit-department" name="department" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                                    </td>
                                    <td style="width: 48% !important; padding-left: 2% !important;">
                                        <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Position</label>
                                        <input type="text" id="edit-position" name="position" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0 !important; vertical-align: top !important;">
                            <label style="display: block !important; font-weight: 600 !important; color: #374151 !important; margin-bottom: 5px !important;">Phone</label>
                            <input type="tel" id="edit-phone" name="phone" style="width: 100% !important; padding: 8px 12px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; font-size: 14px !important; box-sizing: border-box !important; display: block !important;">
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        
        <div style="padding: 20px !important; border-top: 1px solid #e5e7eb !important; display: flex !important; justify-content: flex-end !important; gap: 10px !important;">
            <button type="button" onclick="closeModal('edit-member-modal')" style="padding: 8px 16px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; background: white !important; color: #374151 !important; cursor: pointer !important; font-size: 14px !important;">Cancel</button>
            <button type="button" onclick="updateTeamMember()" style="padding: 8px 16px !important; border: none !important; border-radius: 4px !important; background: #3b82f6 !important; color: white !important; cursor: pointer !important; font-size: 14px !important;">
                <i class="fas fa-save"></i> Update Member
            </button>
        </div>
    </div>
</div>

<!-- Password Display Modal -->
<div id="password-modal" style="display: none; position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(0, 0, 0, 0.5) !important; z-index: 999999 !important; overflow-y: auto !important;">
    <div style="position: absolute !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; background: white !important; border-radius: 8px !important; width: 90% !important; max-width: 500px !important; max-height: 80vh !important; overflow-y: auto !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;">
        <div style="padding: 20px !important; border-bottom: 1px solid #e5e7eb !important; display: flex !important; justify-content: space-between !important; align-items: center !important;">
            <h3 style="margin: 0 !important; color: #111827 !important; font-size: 1.25rem !important;"><i class="fas fa-key"></i> User Credentials</h3>
            <button onclick="closeModal('password-modal')" style="background: none !important; border: none !important; font-size: 24px !important; cursor: pointer !important; color: #6b7280 !important; padding: 0 !important; width: 30px !important; height: 30px !important; display: flex !important; align-items: center !important; justify-content: center !important;">&times;</button>
        </div>
        
        <div style="padding: 20px !important;">
            <div style="padding: 16px !important; background: #f8fafc !important; border-radius: 8px !important; margin-bottom: 16px !important; border: 1px solid #e2e8f0 !important;">
                <p style="margin: 0 0 8px 0 !important; font-weight: 600 !important; color: #374151 !important;"><strong>Username:</strong> <span id="new-username"></span></p>
                <p style="margin: 0 0 16px 0 !important; font-weight: 600 !important; color: #374151 !important;"><strong>Password:</strong></p>
                <div style="font-family: monospace !important; background: #e2e8f0 !important; padding: 8px 12px !important; border-radius: 4px !important; display: block !important; margin: 8px 0 16px 0 !important; word-break: break-all !important; border: 1px solid #cbd5e1 !important;">
                    <span id="new-password" style="color: #111827 !important; font-size: 14px !important;"></span>
                </div>
                <p style="margin: 0 !important; color: #64748b !important; font-size: 14px !important;">
                    <i class="fas fa-info-circle"></i>
                    Please save these credentials and share them securely with the user. The user can change their password after logging in.
                </p>
            </div>
        </div>
        
        <div style="padding: 20px !important; border-top: 1px solid #e5e7eb !important; display: flex !important; justify-content: flex-end !important; gap: 10px !important;">
            <button type="button" onclick="copyPassword()" style="padding: 8px 16px !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; background: white !important; color: #374151 !important; cursor: pointer !important; font-size: 14px !important;">
                <i class="fas fa-copy"></i> Copy Password
            </button>
            <button type="button" onclick="closeModal('password-modal')" style="padding: 8px 16px !important; border: none !important; border-radius: 4px !important; background: #3b82f6 !important; color: white !important; cursor: pointer !important; font-size: 14px !important;">
                <i class="fas fa-check"></i> Got It
            </button>
        </div>
    </div>
</div>

<script>
// Team Management JavaScript
jQuery(document).ready(function($) {
    console.log('Team page loaded, initializing team management...');
    console.log('warehouse_ajax available:', typeof warehouse_ajax !== 'undefined');
    
    if (typeof warehouse_ajax === 'undefined') {
        console.error('warehouse_ajax object not found!');
        alert('Error: Team functionality not available. Check console for details.');
        return;
    }
    
    // Load team members
    loadTeamMembers();
    
    // Add member form submission
    $('#add-member-form').on('submit', function(e) {
        e.preventDefault();
        addTeamMember();
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
    console.log('=== LOADING TEAM MEMBERS ===');
    console.log('warehouse_ajax object:', typeof warehouse_ajax !== 'undefined' ? warehouse_ajax : 'UNDEFINED');
    console.log('jQuery loaded:', typeof jQuery !== 'undefined');
    console.log('Current time:', new Date().toISOString());
    
    if (typeof warehouse_ajax === 'undefined') {
        alert('Error: warehouse_ajax object not loaded! Check console for details.');
        jQuery('#team-table-body').html('<tr><td colspan="9" class="no-data">warehouse_ajax not loaded</td></tr>');
        return;
    }
    
    console.log('Making AJAX request to:', warehouse_ajax.ajax_url);
    console.log('Using nonce:', warehouse_ajax.nonce);
    
    jQuery('#team-loading').show();
    
    jQuery.ajax({
        url: warehouse_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'get_team_members',
            nonce: warehouse_ajax.nonce
        },
        success: function(response) {
            console.log('AJAX Response received:', response);
            console.log('Response type:', typeof response);
            console.log('Response success:', response ? response.success : 'undefined');
            
            if (response && response.success) {
                console.log('Team members data:', response.data);
                console.log('Number of members:', response.data ? response.data.length : 'no data');
                displayTeamMembers(response.data);
                updateTeamStats(response.data);
            } else {
                console.error('Error response:', response ? response.data : 'No response');
                alert('Error loading team members: ' + (response ? response.data : 'No response'));
                
                // Show empty state
                jQuery('#team-table-body').html('<tr><td colspan="9" class="no-data">Failed to load team members</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', xhr, status, error);
            alert('Failed to load team members: ' + error);
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
        
        // Handle empty names - fallback to display_name or username
        let displayName = '';
        if (member.first_name && member.last_name) {
            displayName = `${member.first_name} ${member.last_name}`;
        } else if (member.first_name || member.last_name) {
            displayName = `${member.first_name || ''} ${member.last_name || ''}`.trim();
        } else if (member.display_name) {
            displayName = member.display_name;
        } else {
            displayName = member.username;
        }
        
        const row = `
            <tr data-member-id="${member.id}">
                <td>
                    <div class="member-info">
                        <div class="member-name">${displayName}</div>
                        <div class="member-meta">${member.user_login || member.username}</div>
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
    console.log('Updating team stats for', members.length, 'members');
    const totalMembers = members.length;
    const activeMembers = members.filter(m => m.status === 'active').length;
    const managers = members.filter(m => m.role === 'warehouse_manager' || m.role === 'administrator').length;
    const employees = members.filter(m => m.role === 'warehouse_employee' || m.role === 'subscriber').length;
    
    jQuery('#total-members').text(totalMembers);
    jQuery('#managers').text(managers);
    jQuery('#employees').text(employees);
    
    console.log('Stats updated:', { totalMembers, managers, employees });
}

function openAddMemberModal() {
    document.getElementById('add-member-form').reset();
    document.getElementById('add-member-modal').style.display = 'block';
}

function submitAddMember() {
    const form = document.getElementById('add-member-form');
    const formData = new FormData(form);
    
    // Validate required fields
    const username = formData.get('username');
    const email = formData.get('email');
    const firstName = formData.get('first_name');
    const lastName = formData.get('last_name');
    const role = formData.get('role');
    
    if (!username || !email || !firstName || !lastName || !role) {
        alert('Please fill in all required fields (Username, Email, First Name, Last Name, Role)');
        return;
    }
    
    // Prepare data for submission
    const submitData = new URLSearchParams({
        action: 'add_team_member',
        nonce: warehouse_ajax.nonce,
        username: username,
        email: email,
        first_name: firstName,
        last_name: lastName,
        role: role,
        department: formData.get('department') || '',
        position: formData.get('position') || '',
        phone: formData.get('phone') || ''
    });
    
    // Submit the team member
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
            alert('Team member added successfully!');
            closeModal('add-member-modal');
            loadTeamMembers(); // Refresh team list
            
            // Show password modal if password is provided
            if (data.data && data.data.password) {
                document.getElementById('new-username').textContent = username;
                document.getElementById('new-password').textContent = data.data.password;
                document.getElementById('password-modal').style.display = 'block';
            }
        } else {
            alert('Error adding team member: ' + (data.data || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding team member. Please try again.');
    });
}

function editMember(memberId) {
    // Find member data from the table
    const row = jQuery(`tr[data-member-id="${memberId}"]`);
    if (!row.length) return;
    
    // Get member data from the current display (you might want to store full data)
    jQuery('#edit-member-id').val(memberId);
    
    // You'll need to implement getting full member data
    // For now, this is a simplified version
    jQuery('#edit-member-modal').css('display', 'flex');
}

function updateTeamMember() {
    const formData = jQuery('#edit-member-form').serialize();
    
    jQuery.ajax({
        url: warehouse_ajax.ajax_url,
        type: 'POST',
        data: formData + '&action=update_team_member&nonce=' + warehouse_ajax.nonce,
        success: function(response) {
            if (response.success) {
                alert('Team member updated successfully!');
                closeModal('edit-member-modal');
                loadTeamMembers();
            } else {
                alert('Error: ' + response.data);
            }
        },
        error: function() {
            alert('Failed to update team member');
        }
    });
}

function deleteMember(memberId) {
    if (!confirm('Are you sure you want to remove this team member? This action cannot be undone.')) {
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
            if (response.success) {
                alert('Team member removed successfully!');
                loadTeamMembers();
            } else {
                alert('Error: ' + response.data);
            }
        },
        error: function() {
            alert('Failed to remove team member');
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
                alert('Password reset successfully!');
                
                // Show new password
                jQuery('#new-username').text('Password Reset');
                jQuery('#new-password').text(response.data.password);
                jQuery('#password-modal').css('display', 'flex');
            } else {
                alert('Error: ' + response.data);
            }
        },
        error: function() {
            alert('Failed to reset password');
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
    const password = document.getElementById('new-password').textContent;
    navigator.clipboard.writeText(password).then(function() {
        alert('Password copied to clipboard!');
    }).catch(function() {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = password;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('Password copied to clipboard!');
    });
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.id === 'add-member-modal') {
        closeModal('add-member-modal');
    }
    if (e.target.id === 'edit-member-modal') {
        closeModal('edit-member-modal');
    }
    if (e.target.id === 'password-modal') {
        closeModal('password-modal');
    }
});

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