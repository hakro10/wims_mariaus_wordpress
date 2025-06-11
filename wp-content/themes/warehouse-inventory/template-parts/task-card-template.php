<?php
/**
 * Task Card Template for Kanban Board
 */

// Ensure $task variable is available
if (!isset($task)) {
    return;
}

$priority_class = 'priority-' . esc_attr($task->priority);
$due_date = $task->due_date ? date('M j', strtotime($task->due_date)) : '';
$is_overdue = $task->due_date && strtotime($task->due_date) < time();
?>

<div class="task-card" 
     draggable="true" 
     ondragstart="drag(event)" 
     data-task-id="<?php echo esc_attr($task->id); ?>" 
     data-status="<?php echo esc_attr($task->status); ?>">
     
    <div class="task-priority <?php echo $priority_class; ?>"></div>
    
    <div class="task-content">
        <div class="task-header">
            <h4><?php echo esc_html($task->title); ?></h4>
            <div class="task-actions">
                <button onclick="editTask(<?php echo $task->id; ?>)" class="btn-icon" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteTask(<?php echo $task->id; ?>)" class="btn-icon" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        
        <?php if ($task->description): ?>
            <div class="task-description">
                <?php echo esc_html($task->description); ?>
            </div>
        <?php endif; ?>
        
        <div class="task-meta">
            <?php if ($task->assigned_to_name): ?>
                <span class="assignee">
                    <i class="fas fa-user"></i>
                    <?php echo esc_html($task->assigned_to_name); ?>
                </span>
            <?php endif; ?>
            
            <?php if ($due_date): ?>
                <span class="due-date <?php echo $is_overdue ? 'overdue' : ''; ?>">
                    <i class="fas fa-calendar"></i>
                    <?php echo $due_date; ?>
                    <?php if ($is_overdue): ?>
                        <i class="fas fa-exclamation-triangle" title="Overdue"></i>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
        
        <div class="task-footer">
            <span class="task-id">#<?php echo $task->id; ?></span>
            <span class="task-created">
                <?php echo date('M j', strtotime($task->created_at)); ?>
            </span>
        </div>
    </div>
</div>

<style>
.btn-icon {
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    transition: all 0.2s;
    font-size: 0.875rem;
}

.btn-icon:hover {
    background: #f3f4f6;
    color: #374151;
}

.assignee {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.due-date {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.due-date.overdue {
    color: #ef4444;
    font-weight: 600;
}

.task-created {
    font-size: 0.75rem;
    color: #9ca3af;
}
</style>