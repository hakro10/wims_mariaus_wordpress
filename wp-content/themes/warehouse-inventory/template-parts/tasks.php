<?php
/**
 * Tasks Management Template Part - Kanban Board with History
 */

$tasks = get_all_tasks();
$task_history = get_task_history();

// Group tasks by status
$grouped_tasks = array(
    'pending' => array(),
    'in_progress' => array(),
    'completed' => array()
);

if ($tasks) {
    foreach ($tasks as $task) {
        if ($task->status !== 'archived') {
            $grouped_tasks[$task->status][] = $task;
        }
    }
}
?>

<div class="tasks-content">
    <div class="page-header">
        <h1>📋 Tasks Management</h1>
        <div class="header-actions">
            <button class="btn btn-outline" onclick="toggleHistoryPanel()">
                <i class="fas fa-history"></i> History
            </button>
            <button class="btn btn-outline" onclick="toggleTeamChatPanel()">
                <i class="fas fa-comments"></i> Team Chat
            </button>
            <button class="btn btn-primary" onclick="document.getElementById('add-task-modal').classList.remove('hidden'); console.log('Button clicked!');">
                <i class="fas fa-plus"></i> Add Task
            </button>
        </div>
    </div>

    <div class="tasks-main-container">
        <!-- Tasks Section -->
        <div class="tasks-section">
            <!-- Kanban Board -->
            <div class="kanban-board">
                <!-- Pending Column -->
                <div class="kanban-column" data-status="pending">
                    <div class="column-header">
                        <div class="column-title">
                            <i class="fas fa-clock"></i>
                            <span>Pending</span>
                            <span class="task-count"><?php echo count($grouped_tasks['pending']); ?></span>
                        </div>
                    </div>
                    <div class="column-content" ondrop="drop(event)" ondragover="allowDrop(event)">
                        <?php foreach ($grouped_tasks['pending'] as $task): ?>
                            <?php include get_template_directory() . '/template-parts/task-card-template.php'; ?>
                        <?php endforeach; ?>
                        
                        <?php if (empty($grouped_tasks['pending'])): ?>
                            <div class="empty-column">
                                <i class="fas fa-plus-circle"></i>
                                <p>No pending tasks</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- In Progress Column -->
                <div class="kanban-column" data-status="in_progress">
                    <div class="column-header">
                        <div class="column-title">
                            <i class="fas fa-spinner"></i>
                            <span>In Progress</span>
                            <span class="task-count"><?php echo count($grouped_tasks['in_progress']); ?></span>
                        </div>
                    </div>
                    <div class="column-content" ondrop="drop(event)" ondragover="allowDrop(event)">
                        <?php foreach ($grouped_tasks['in_progress'] as $task): ?>
                            <?php include get_template_directory() . '/template-parts/task-card-template.php'; ?>
                        <?php endforeach; ?>
                        
                        <?php if (empty($grouped_tasks['in_progress'])): ?>
                            <div class="empty-column">
                                <i class="fas fa-play-circle"></i>
                                <p>No tasks in progress</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Completed Column -->
                <div class="kanban-column" data-status="completed">
                    <div class="column-header">
                        <div class="column-title">
                            <i class="fas fa-check-circle"></i>
                            <span>Completed</span>
                            <span class="task-count"><?php echo count($grouped_tasks['completed']); ?></span>
                        </div>
                    </div>
                    <div class="column-content" ondrop="drop(event)" ondragover="allowDrop(event)">
                        <?php foreach ($grouped_tasks['completed'] as $task): ?>
                            <?php include get_template_directory() . '/template-parts/task-card-template.php'; ?>
                        <?php endforeach; ?>
                        
                        <?php if (empty($grouped_tasks['completed'])): ?>
                            <div class="empty-column">
                                <i class="fas fa-check-circle"></i>
                                <p>No completed tasks</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Section -->
        <div class="sidebar-section">

            <!-- History Panel -->
            <div id="history-panel" class="sidebar-panel hidden">
                <div class="sidebar-content">
                    <?php if ($task_history): ?>
                        <?php foreach ($task_history as $history_item): ?>
                            <div class="history-item">
                                <div class="history-date">
                                    <i class="fas fa-calendar-check"></i>
                                    <?php echo date('M j, Y - g:i A', strtotime($history_item->completed_at)); ?>
                                </div>
                                
                                <div class="history-task">
                                    <h4><?php echo esc_html($history_item->title); ?></h4>
                                    <p><?php echo esc_html($history_item->description); ?></p>
                                    
                                    <div class="history-meta">
                                        <span class="priority priority-<?php echo esc_attr($history_item->priority); ?>">
                                            <?php echo esc_html(ucfirst($history_item->priority)); ?>
                                        </span>
                                        
                                        <span class="assignee">
                                            <i class="fas fa-user"></i>
                                            <?php echo esc_html(isset($history_item->assigned_to_name) ? $history_item->assigned_to_name : 'Unassigned'); ?>
                                        </span>
                                        
                                        <?php if ($history_item->created_at && $history_item->completed_at): ?>
                                        <span class="duration">
                                            <i class="fas fa-clock"></i>
                                            <?php 
                                            $created = new DateTime($history_item->created_at);
                                            $completed = new DateTime($history_item->completed_at);
                                            $interval = $created->diff($completed);
                                            echo $interval->format('%a days %h hours');
                                            ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-history">
                            <i class="fas fa-archive"></i>
                            <h4>No completed tasks yet</h4>
                            <p>Completed tasks will appear here with timestamps.</p>
                            <p class="retention-notice"><i class="fas fa-clock"></i> Task history is kept for 6 months</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Team Chat Panel -->
            <div id="team-chat-panel" class="sidebar-panel">
                <div class="chat-messages" id="chat-messages">
                    <!-- Chat messages will be loaded here -->
                </div>
                
                <div class="chat-input-container">
                    <div class="chat-input-wrapper">
                        <input type="text" id="chat-message-input" placeholder="Type your message..." class="chat-input">
                        <button onclick="sendChatMessage()" class="btn btn-primary chat-send-btn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div id="add-task-modal" class="task-modal-overlay hidden">
    <div class="task-modal">
        <div class="task-modal-header">
            <h3>Add New Task</h3>
            <button type="button" id="modal-close-x" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <div class="task-modal-body">
            <form id="add-task-form">
                <table style="width:100%;border-spacing:0;">
                    <tr>
                        <td style="padding:10px 0;">
                            <label style="display:block;margin-bottom:5px;font-weight:bold;">Task Title *</label>
                            <input type="text" name="title" required placeholder="Enter task title" 
                                   style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <label style="display:block;margin-bottom:5px;font-weight:bold;">Description</label>
                            <textarea name="description" rows="3" placeholder="Enter task description (optional)"
                                      style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;resize:vertical;"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <label style="display:block;margin-bottom:5px;font-weight:bold;">Assign To</label>
                            <select name="assigned_to" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                                <option value="<?php echo get_current_user_id(); ?>">Assign to Me</option>
                                <?php 
                                $users = get_users(array('role__in' => array('administrator', 'warehouse_manager', 'warehouse_employee')));
                                foreach ($users as $user): 
                                    if ($user->ID != get_current_user_id()): ?>
                                        <option value="<?php echo $user->ID; ?>"><?php echo esc_html($user->display_name); ?></option>
                                    <?php endif;
                                endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <label style="display:block;margin-bottom:5px;font-weight:bold;">Priority</label>
                            <select name="priority" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;">
                            <label style="display:block;margin-bottom:5px;font-weight:bold;">Due Date (Optional)</label>
                            <input type="date" name="due_date" 
                                   style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="task-modal-footer">
            <button type="button" id="modal-cancel-btn"
                    style="padding:10px 20px;margin-right:10px;background:#f5f5f5;border:1px solid #ccc;border-radius:4px;cursor:pointer;">Cancel</button>
            <button type="button" id="modal-submit-btn"
                    style="padding:10px 20px;background:#007cba;color:white;border:none;border-radius:4px;cursor:pointer;">Add Task</button>
        </div>
    </div>
</div>

<style>
.tasks-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    margin: 0;
    color: #1f2937;
    font-size: 2rem;
    font-weight: 600;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

/* Main Layout Styles */
.tasks-main-container {
    display: flex;
    height: calc(100vh - 120px);
    background: #f8fafc;
    gap: 0;
}

.tasks-section {
    flex: 1;
    padding: 1.5rem;
    overflow: hidden;
    min-width: 0;
}

.sidebar-section {
    width: 400px;
    background: white;
    border-left: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    height: 520px; /* Adjusted height without header */
    max-height: 520px;
    overflow: hidden;
}



/* Sidebar Panels */
.sidebar-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.sidebar-panel.hidden {
    display: none;
}

.sidebar-content {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
}

.kanban-board {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    height: 100%;
}

.kanban-column {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1rem;
    min-height: 600px;
    display: flex;
    flex-direction: column;
}

.column-header {
    margin-bottom: 1rem;
}

.column-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    font-size: 1.1rem;
    color: #374151;
}

.task-count {
    background: #e5e7eb;
    color: #6b7280;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-left: auto;
}

.column-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    min-height: 400px;
    padding: 0.5rem;
    border-radius: 8px;
    transition: background-color 0.2s;
}

.column-content.drag-over {
    background: #e0f2fe;
    border: 2px dashed #0284c7;
}

.task-card {
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    cursor: grab;
    transition: all 0.2s;
    position: relative;
}

.task-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.task-card:active {
    cursor: grabbing;
}

.task-card.dragging {
    opacity: 0.5;
    transform: rotate(5deg);
}

.task-priority {
    position: absolute;
    left: 0;
    top: 0;
    width: 4px;
    height: 100%;
    border-radius: 4px 0 0 4px;
}

.priority-low { background: #10b981; }
.priority-medium { background: #f59e0b; }
.priority-high { background: #ef4444; }
.priority-urgent { background: #dc2626; }

.task-content {
    margin-left: 1rem;
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.task-header h4 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: #111827;
    line-height: 1.3;
}

.task-actions {
    opacity: 0;
    transition: opacity 0.2s;
}

.task-card:hover .task-actions {
    opacity: 1;
}

.task-description {
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 1rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.task-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.task-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    color: #9ca3af;
    border-top: 1px solid #f3f4f6;
    padding-top: 0.5rem;
}

.task-id {
    font-weight: 600;
    color: #6b7280;
}

.empty-column {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #9ca3af;
    text-align: center;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    margin-top: 2rem;
}

.empty-column i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}



.history-item {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.2s;
}

.history-item:hover {
    background: #f8fafc;
}

.history-item:last-child {
    border-bottom: none;
}

.history-date {
    color: #059669;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.history-task h4 {
    margin: 0 0 0.25rem 0;
    font-size: 0.95rem;
    color: #111827;
}

.history-task p {
    color: #6b7280;
    font-size: 0.875rem;
    margin: 0 0 0.75rem 0;
    line-height: 1.4;
}

.history-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    font-size: 0.75rem;
}

.history-meta .priority {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.history-meta .priority-low { background: #d1fae5; color: #065f46; }
.history-meta .priority-medium { background: #fef3c7; color: #92400e; }
.history-meta .priority-high { background: #fee2e2; color: #991b1b; }
.history-meta .priority-urgent { background: #fecaca; color: #7f1d1d; }

.history-meta span {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    margin-right: 1rem;
}

.priority {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-weight: 600;
    text-transform: uppercase;
}

.priority-low {
    background: #d1fae5;
    color: #065f46;
}

.priority-medium {
    background: #fef3c7;
    color: #92400e;
}

.priority-high {
    background: #fee2e2;
    color: #991b1b;
}

.empty-history {
    text-align: center;
    padding: 3rem 2rem;
    color: #6b7280;
}

.empty-history i {
    font-size: 3rem;
    color: #d1d5db;
    margin-bottom: 1rem;
    display: block;
}

.empty-history h4 {
    margin: 0 0 0.5rem 0;
    color: #374151;
}

.empty-history p {
    margin: 0;
    font-size: 0.9rem;
}

/* Modal Styles */
.modal-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(0, 0, 0, 0.5) !important;
    z-index: 999999 !important;
}

.modal-overlay.hidden {
    display: none !important;
}

.modal {
    position: absolute !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    background: white !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
    width: 500px !important;
    max-width: 90vw !important;
    max-height: 90vh !important;
    overflow: hidden !important;
}

.modal-header {
    padding: 20px !important;
    border-bottom: 1px solid #eee !important;
    background: #f9f9f9 !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
}

.modal-title {
    margin: 0 !important;
    font-size: 18px !important;
    font-weight: 600 !important;
    color: #333 !important;
}

.modal-close {
    background: none !important;
    border: none !important;
    font-size: 24px !important;
    color: #666 !important;
    cursor: pointer !important;
    padding: 5px !important;
    line-height: 1 !important;
}

.modal-close:hover {
    color: #333 !important;
}

.modal-body {
    padding: 20px !important;
    max-height: 60vh !important;
    overflow-y: auto !important;
}

.modal-footer {
    padding: 20px !important;
    border-top: 1px solid #eee !important;
    background: #f9f9f9 !important;
    display: flex !important;
    gap: 10px !important;
    justify-content: flex-end !important;
}

#add-task-form {
    width: 100% !important;
    display: block !important;
}

.form-group {
    margin-bottom: 20px !important;
    display: block !important;
    width: 100% !important;
    clear: both !important;
}

.form-group:last-child {
    margin-bottom: 0 !important;
}

.form-label {
    display: block !important;
    width: 100% !important;
    margin-bottom: 8px !important;
    font-weight: 500 !important;
    color: #333 !important;
    font-size: 14px !important;
    line-height: 1.4 !important;
}

.form-input, .form-select {
    display: block !important;
    width: 100% !important;
    padding: 12px !important;
    border: 1px solid #ddd !important;
    border-radius: 6px !important;
    font-size: 14px !important;
    background: white !important;
    box-sizing: border-box !important;
    font-family: inherit !important;
    line-height: 1.4 !important;
    margin: 0 !important;
}

.form-input:focus, .form-select:focus {
    outline: none !important;
    border-color: #007cba !important;
    box-shadow: 0 0 5px rgba(0, 124, 186, 0.3) !important;
}

.form-input::placeholder {
    color: #999 !important;
}

.form-select {
    cursor: pointer !important;
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
}

textarea.form-input {
    min-height: 80px !important;
    resize: vertical !important;
}

.btn {
    padding: 12px 20px !important;
    border-radius: 6px !important;
    font-weight: 500 !important;
    font-size: 14px !important;
    cursor: pointer !important;
    border: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    text-decoration: none !important;
}

.btn-primary {
    background: #007cba !important;
    color: white !important;
}

.btn-primary:hover {
    background: #005a8b !important;
}

.btn-secondary {
    background: #f1f1f1 !important;
    color: #333 !important;
    border: 1px solid #ddd !important;
}

.btn-secondary:hover {
    background: #e6e6e6 !important;
}

.btn:disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
}

/* Responsive */
@media (max-width: 1024px) {
    .tasks-main-container {
        flex-direction: column;
        height: auto;
    }
    
    .tasks-section {
        height: 60vh;
        padding: 1rem;
    }
    
    .sidebar-section {
        width: 100%;
        height: 300px; /* Reduced height without sidebar header */
        border-left: none;
        border-top: 1px solid #e5e7eb;
        max-height: 300px;
    }
    
    .chat-messages {
        height: 220px; /* More space for chat on mobile */
        max-height: 220px;
    }
    
    .kanban-board {
        grid-template-columns: 1fr;
        gap: 1rem;
        height: auto;
    }
    
    .kanban-column {
        min-height: 300px;
    }
    
    .modal-overlay {
        padding: 10px;
    }
    
    .modal {
        width: 100%;
        max-width: 95vw;
        margin: 0;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-header,
    .modal-body,
    .modal-footer {
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }
}

/* Team Chat Panel Styles */
.chat-messages {
    overflow-y: auto;
    padding: 1rem;
    height: 440px; /* Same height as before */
    max-height: 440px;
}

.chat-message {
    margin-bottom: 1rem;
    padding: 0.75rem;
    border-radius: 8px;
    position: relative;
}

.chat-message.own {
    background: #e0f2fe;
    margin-left: 2rem;
    text-align: right;
}

.chat-message.other {
    background: #f8fafc;
    margin-right: 2rem;
}

.chat-message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    font-size: 0.75rem;
    color: #6b7280;
}

.chat-message.own .chat-message-header {
    flex-direction: row-reverse;
}

.chat-message-author {
    font-weight: 600;
    color: #374151;
}

.chat-message-time {
    font-size: 0.7rem;
}

.chat-message-content {
    color: #111827;
    line-height: 1.4;
}

.chat-input-container {
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
    height: 80px; /* Fixed height for input area */
    flex-shrink: 0;
    display: flex;
    align-items: center;
}

.chat-input-wrapper {
    display: flex;
    gap: 0.5rem;
}

.chat-input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
}

.chat-input:focus {
    outline: none;
    border-color: #007cba;
    box-shadow: 0 0 0 3px rgba(0, 124, 186, 0.1);
}

.chat-send-btn {
    padding: 0.75rem 1rem !important;
    border-radius: 6px !important;
}

.empty-chat {
    text-align: center;
    padding: 3rem 2rem;
    color: #6b7280;
}

.empty-chat i {
    font-size: 3rem;
    color: #d1d5db;
    margin-bottom: 1rem;
    display: block;
}

.empty-chat h4 {
    margin: 0 0 0.5rem 0;
    color: #374151;
}

.empty-chat p {
    margin: 0;
    font-size: 0.9rem;
}

.retention-notice {
    color: #9ca3af !important;
    font-size: 0.8rem !important;
    margin-top: 1rem !important;
    padding-top: 1rem !important;
    border-top: 1px solid #e5e7eb !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.5rem !important;
}
</style>

<script>
// Drag and Drop Functions
let draggedElement = null;

function allowDrop(ev) {
    ev.preventDefault();
    ev.currentTarget.classList.add('drag-over');
}

function drag(ev) {
    draggedElement = ev.target;
    ev.target.classList.add('dragging');
    ev.dataTransfer.setData("text", ev.target.dataset.taskId);
}

function drop(ev) {
    ev.preventDefault();
    ev.currentTarget.classList.remove('drag-over');
    
    const taskId = ev.dataTransfer.getData("text");
    const newStatus = ev.currentTarget.closest('.kanban-column').dataset.status;
    const oldStatus = draggedElement.dataset.status;
    
    if (draggedElement) {
        draggedElement.classList.remove('dragging');
    }
    
    if (newStatus !== oldStatus) {
        updateTaskStatus(taskId, newStatus);
        
        // Move the task card to new column
        ev.currentTarget.appendChild(draggedElement);
        draggedElement.dataset.status = newStatus;
        
        // Update task counts
        updateTaskCounts();
        
        // If completed, move to history after delay
        if (newStatus === 'completed') {
            showNotification('Task will be archived in 3 seconds...', 'info');
            setTimeout(() => {
                console.log('Auto-archiving completed task:', taskId);
                moveTaskToHistory(taskId);
            }, 3000); // 3 second delay
        }
    }
}

function updateTaskStatus(taskId, newStatus) {
    console.log('Updating task', taskId, 'to status', newStatus);
    console.log('Nonce available:', warehouse_ajax.nonce);
    
    const formData = new FormData();
    formData.append('action', 'update_task_status');
    formData.append('task_id', taskId);
    formData.append('status', newStatus);
    formData.append('nonce', warehouse_ajax.nonce);
    
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Response:', data);
        if (data.success) {
            showNotification('Task status updated successfully', 'success');
        } else {
            showNotification(data.data || 'Failed to update task status', 'error');
            console.error('Error updating task:', data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating task status', 'error');
    });
}

function moveTaskToHistory(taskId) {
    console.log('Moving task to history:', taskId);
    
    const formData = new FormData();
    formData.append('action', 'move_task_to_history');
    formData.append('task_id', taskId);
    formData.append('nonce', warehouse_ajax.nonce);
    
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Move to history response:', data);
        if (data.success) {
            // Remove task from board
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            if (taskCard) {
                taskCard.style.transition = 'all 0.5s ease';
                taskCard.style.opacity = '0';
                taskCard.style.transform = 'translateX(100%)';
                
                setTimeout(() => {
                    taskCard.remove();
                    updateTaskCounts();
                    showNotification('Task moved to history', 'success');
                    
                    // Always refresh history panel (even if hidden)
                    refreshHistoryPanel();
                }, 500);
            }
        } else {
            console.error('Failed to move task to history:', data);
            showNotification('Failed to move task to history', 'error');
        }
    })
    .catch(error => {
        console.error('Error moving task to history:', error);
        showNotification('Error moving task to history', 'error');
    });
}

function updateTaskCounts() {
    document.querySelectorAll('.kanban-column').forEach(column => {
        const taskCards = column.querySelectorAll('.task-card');
        const count = taskCards.length;
        const countElement = column.querySelector('.task-count');
        const emptyState = column.querySelector('.empty-column');
        
        // Update count
        if (countElement) {
            countElement.textContent = count;
        }
        
        // Show/hide empty state
        if (emptyState) {
            emptyState.style.display = count > 0 ? 'none' : 'flex';
        }
    });
}

function showHistoryPanel() {
    const historyPanel = document.getElementById('history-panel');
    const chatPanel = document.getElementById('team-chat-panel');
    
    // Switch panels
    historyPanel.classList.remove('hidden');
    chatPanel.classList.add('hidden');
    
    // Refresh history when showing
    refreshHistoryPanel();
}

function showChatPanel() {
    const historyPanel = document.getElementById('history-panel');
    const chatPanel = document.getElementById('team-chat-panel');
    
    // Switch panels
    chatPanel.classList.remove('hidden');
    historyPanel.classList.add('hidden');
    
    // Load chat messages when showing
    loadChatMessages();
}

// Keep backward compatibility for header buttons
function toggleHistoryPanel() {
    showHistoryPanel();
}

function toggleTeamChatPanel() {
    showChatPanel();
}

function loadChatMessages() {
    const chatMessages = document.getElementById('chat-messages');
    if (!chatMessages) return;
    
    chatMessages.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    
    const formData = new FormData();
    formData.append('action', 'get_chat_messages');
    formData.append('nonce', warehouse_ajax.nonce);
    
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            displayChatMessages(data.data);
        } else {
            chatMessages.innerHTML = `
                <div class="empty-chat">
                    <i class="fas fa-comments"></i>
                    <h4>No messages yet</h4>
                    <p>Start a conversation with your team!</p>
                    <p class="retention-notice"><i class="fas fa-clock"></i> Chat messages are kept for 6 months</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading chat messages:', error);
        chatMessages.innerHTML = `
            <div class="empty-chat">
                <i class="fas fa-exclamation-triangle"></i>
                <h4>Failed to load messages</h4>
                <p>Please try again later.</p>
            </div>
        `;
    });
}

function displayChatMessages(messages) {
    const chatMessages = document.getElementById('chat-messages');
    const currentUserId = warehouse_ajax.current_user_id;
    
    if (!messages || messages.length === 0) {
        chatMessages.innerHTML = `
            <div class="empty-chat">
                <i class="fas fa-comments"></i>
                <h4>No messages yet</h4>
                <p>Start a conversation with your team!</p>
                <p class="retention-notice"><i class="fas fa-clock"></i> Chat messages are kept for 6 months</p>
            </div>
        `;
        return;
    }
    
    let messagesHtml = '';
    messages.forEach(message => {
        const isOwn = message.user_id == currentUserId;
        const messageClass = isOwn ? 'own' : 'other';
        
        messagesHtml += `
            <div class="chat-message ${messageClass}">
                <div class="chat-message-header">
                    <span class="chat-message-author">${message.user_name || 'Unknown User'}</span>
                    <span class="chat-message-time">${formatMessageTime(message.created_at)}</span>
                </div>
                <div class="chat-message-content">${escapeHtml(message.message)}</div>
            </div>
        `;
    });
    
    chatMessages.innerHTML = messagesHtml;
    
    // Scroll to bottom
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function sendChatMessage() {
    const messageInput = document.getElementById('chat-message-input');
    const message = messageInput.value.trim();
    
    if (!message) return;
    
    const formData = new FormData();
    formData.append('action', 'send_chat_message');
    formData.append('message', message);
    formData.append('nonce', warehouse_ajax.nonce);
    
    // Disable input while sending
    messageInput.disabled = true;
    
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageInput.value = '';
            loadChatMessages(); // Reload messages
        } else {
            showNotification('Failed to send message', 'error');
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        showNotification('Error sending message', 'error');
    })
    .finally(() => {
        messageInput.disabled = false;
        messageInput.focus();
    });
}

function formatMessageTime(timestamp) {
    try {
        const date = new Date(timestamp);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        
        return date.toLocaleDateString();
    } catch (e) {
        return timestamp;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function refreshHistoryPanel() {
    const formData = new FormData();
    formData.append('action', 'get_task_history');
    formData.append('nonce', warehouse_ajax.nonce);
    
    // Find the history content within the history panel
    const historyPanel = document.getElementById('history-panel');
    const historyContent = historyPanel ? historyPanel.querySelector('.sidebar-content') : null;
    
    if (historyContent) {
        historyContent.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    }
    
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('History response:', data);
        
        if (data.success && historyContent) {
            let historyHtml = '';
            
            if (data.data && data.data.length > 0) {
                data.data.forEach(item => {
                    try {
                        // Handle date formatting safely
                        let completedDateStr = 'Unknown date';
                        let daysDiff = 0;
                        
                        if (item.completed_at) {
                            const completedDate = new Date(item.completed_at);
                            if (!isNaN(completedDate.getTime())) {
                                completedDateStr = completedDate.toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });
                                
                                if (item.created_at) {
                                    const createdDate = new Date(item.created_at);
                                    if (!isNaN(createdDate.getTime())) {
                                        const timeDiff = Math.abs(completedDate - createdDate);
                                        daysDiff = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
                                    }
                                }
                            }
                        }
                        
                        historyHtml += `
                            <div class="history-item">
                                <div class="history-date">
                                    <i class="fas fa-calendar-check"></i>
                                    ${completedDateStr}
                                </div>
                                <div class="history-task">
                                    <h4>${item.title || 'Unknown Task'}</h4>
                                    <p>${item.description || ''}</p>
                                    <div class="history-meta">
                                        <span class="priority priority-${item.priority || 'medium'}">
                                            ${(item.priority || 'medium').toUpperCase()}
                                        </span>
                                        <span class="assignee">
                                            <i class="fas fa-user"></i>
                                            ${item.assigned_to_name || 'System'}
                                        </span>
                                        <span class="duration">
                                            <i class="fas fa-clock"></i>
                                            ${daysDiff > 0 ? daysDiff + ' days' : 'Same day'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        `;
                    } catch (itemError) {
                        console.error('Error processing history item:', itemError, item);
                    }
                });
            } else {
                historyHtml = `
                    <div class="empty-history" style="text-align: center; padding: 2rem; color: #6b7280;">
                        <i class="fas fa-archive" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                        <h4>No completed tasks yet</h4>
                        <p>Completed tasks will appear here with timestamps.</p>
                        <p class="retention-notice"><i class="fas fa-clock"></i> Task history is kept for 6 months</p>
                    </div>
                `;
            }
            
            historyContent.innerHTML = historyHtml;
        } else {
            console.error('History request failed:', data);
            if (historyContent) {
                historyContent.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: #ef4444;">
                        <i class="fas fa-exclamation-triangle"></i><br>
                        Failed to load history: ${data.data || 'Unknown error'}
                    </div>
                `;
            }
        }
    })
    .catch(error => {
        console.error('Error refreshing history:', error);
        if (historyContent) {
            historyContent.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: #ef4444;">
                    <i class="fas fa-exclamation-triangle"></i><br>
                    Failed to load history
                </div>
            `;
        }
    });
}

window.submitAddTask = function() {
    console.log('submitAddTask called');
    const form = document.getElementById('add-task-form');
    if (!form) {
        console.error('Form not found');
        return;
    }
    
    const title = form.querySelector('[name="title"]').value.trim();
    
    if (!title) {
        showNotification('Please enter a task title', 'error');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'add_task');
    formData.append('nonce', warehouse_ajax.nonce);
    
    // Disable button to prevent double submission
    const submitBtn = document.querySelector('#add-task-modal button[onclick*="submitAddTask"]');
    if (submitBtn) {
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = 'Adding...';
        submitBtn.disabled = true;
        
        fetch(warehouse_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Task added successfully', 'success');
                closeModal('add-task-modal');
                // Refresh the page to show new task
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification(data.data?.message || 'Failed to add task', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error adding task', 'error');
        })
        .finally(() => {
            // Re-enable button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }
}

function editTask(taskId) {
    showNotification('Edit task functionality coming soon', 'info');
}

function deleteTask(taskId) {
    if (!confirm('Are you sure you want to delete this task?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_task');
    formData.append('task_id', taskId);
    formData.append('nonce', warehouse_ajax.nonce);
    
    fetch(warehouse_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove task from board
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            if (taskCard) {
                taskCard.style.transition = 'all 0.3s ease';
                taskCard.style.opacity = '0';
                taskCard.style.transform = 'scale(0.8)';
                
                setTimeout(() => {
                    taskCard.remove();
                    updateTaskCounts();
                    showNotification('Task deleted successfully', 'success');
                }, 300);
            }
        } else {
            showNotification('Failed to delete task', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error deleting task', 'error');
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;font-size:1.2rem;cursor:pointer;margin-left:1rem;">&times;</button>
    `;
    
    // Add notification styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 1rem;
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 3000);
}

// Modal functions - Make them explicitly global
window.openModal = function(modalId) {
    console.log('Opening modal:', modalId);
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        console.log('Modal opened successfully');
    } else {
        console.error('Modal not found:', modalId);
    }
}

window.closeModal = function(modalId) {
    console.log('closeModal function called with modalId:', modalId);
    const modal = document.getElementById(modalId);
    console.log('Modal element found:', modal);
    
    if (modal) {
        console.log('Current modal classes before:', modal.className);
        modal.classList.add('hidden');
        console.log('Current modal classes after:', modal.className);
        
        // Also try setting display none as backup
        modal.style.display = 'none';
        
        if (modalId === 'add-task-modal') {
            const form = document.getElementById('add-task-form');
            if (form) {
                form.reset();
                console.log('Form reset');
            }
        }
        console.log('Modal closed successfully');
    } else {
        console.error('Modal not found:', modalId);
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('task-modal-overlay') && !e.target.classList.contains('hidden')) {
        e.target.classList.add('hidden');
    }
});

// Initialize drag and drop event listeners
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.column-content').forEach(column => {
        column.addEventListener('dragleave', function(e) {
            if (!this.contains(e.relatedTarget)) {
                this.classList.remove('drag-over');
            }
        });
    });
    
    // Add backup event listener for Add Task button
    const addTaskBtn = document.querySelector('button[onclick*="add-task-modal"]');
    if (addTaskBtn) {
        addTaskBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Add Task button clicked via event listener');
            openModal('add-task-modal');
        });
    }
    
    // Add event listeners for modal buttons
    const xButton = document.getElementById('modal-close-x');
    if (xButton) {
        xButton.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('X button clicked');
            // Close modal directly
            const modal = document.getElementById('add-task-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                const form = document.getElementById('add-task-form');
                if (form) form.reset();
                console.log('Modal closed via X button');
            }
        });
    }
    
    const cancelButton = document.getElementById('modal-cancel-btn');
    if (cancelButton) {
        cancelButton.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Cancel button clicked');
            // Close modal directly
            const modal = document.getElementById('add-task-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                const form = document.getElementById('add-task-form');
                if (form) form.reset();
                console.log('Modal closed via Cancel button');
            }
        });
    }
    
    const submitButton = document.getElementById('modal-submit-btn');
    if (submitButton) {
        submitButton.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Submit button clicked');
            // Call submit function directly
            const form = document.getElementById('add-task-form');
            if (!form) {
                console.error('Form not found');
                return;
            }
            
            const title = form.querySelector('[name="title"]').value.trim();
            
            if (!title) {
                alert('Please enter a task title');
                return;
            }
            
            const formData = new FormData(form);
            formData.append('action', 'add_task');
            formData.append('nonce', warehouse_ajax.nonce);
            
            // Disable button
            submitButton.innerHTML = 'Adding...';
            submitButton.disabled = true;
            
            fetch(warehouse_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Task added successfully');
                    // Close modal
                    const modal = document.getElementById('add-task-modal');
                    if (modal) {
                        modal.classList.add('hidden');
                        modal.style.display = 'none';
                        form.reset();
                    }
                    // Refresh page
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    alert('Failed to add task: ' + (data.data?.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding task');
            })
            .finally(() => {
                // Re-enable button
                submitButton.innerHTML = 'Add Task';
                submitButton.disabled = false;
            });
        });
    }
    
    updateTaskCounts();
    
    // Initialize sidebar with chat as default
    showChatPanel();
    
    // Add Enter key listener for chat input
    const chatInput = document.getElementById('chat-message-input');
    if (chatInput) {
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendChatMessage();
            }
        });
    }
});

// Add CSS for notification animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    /* Modal Styles */
    .task-modal-overlay {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        background: rgba(0,0,0,0.5) !important;
        z-index: 999999 !important;
        display: block !important;
    }

    .task-modal-overlay.hidden {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }

    .task-modal {
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        background: white !important;
        border-radius: 8px !important;
        width: 500px !important;
        max-width: 90% !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3) !important;
    }

    .task-modal-header {
        padding: 20px !important;
        border-bottom: 1px solid #eee !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
    }

    .task-modal-header h3 {
        margin: 0 !important;
        font-size: 18px !important;
        color: #333 !important;
    }

    .task-modal-body {
        padding: 20px !important;
        max-height: 60vh !important;
        overflow-y: auto !important;
    }

    .task-modal-footer {
        padding: 20px !important;
        border-top: 1px solid #eee !important;
        text-align: right !important;
    }
`;
document.head.appendChild(style);
</script> 