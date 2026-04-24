<?php
session_start();
require_once '../includes/db.php';

// 1. የደህንነት ቼክ
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$dept_name = $_SESSION['dept_name']; 
$user_role = $_SESSION['user_role']; 

// 2. ዳይናሚክ ሎጂክ (በማናጀሩ ግሩፒንግ መሰረት)
$production_group = ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'];
$technical_quality_group = ['Engineering', 'Quality Assurance'];
$finance_resource_group = ['Finance Department', 'Procurement / Property'];
$admin_strategy_group = ['General Management', 'Human Resource (HR)', 'Planning', 'Strategy & Innovation', 'System Research & Development', 'Legal Service', 'Audit & Inspection'];

$is_production = false;
$table_h = "Task / Subject";
$btn_extra_name = "Report Issue";
$btn_extra_icon = "bi-exclamation-triangle";
$theme_color = "primary";

if (in_array($dept_name, $production_group)) {
    $is_production = true;
    $table_h = "Machine / Work Station";
    $btn_extra_name = "Request Maintenance";
    $btn_extra_icon = "bi-tools";
    $theme_color = (strpos($dept_name, 'Spinning') !== false) ? "info" : 
                  ((strpos($dept_name, 'Garment') !== false) ? "danger" : "success");
} 
elseif (in_array($dept_name, $technical_quality_group)) {
    $is_production = true;
    $table_h = "Asset / Equipment ID";
    $btn_extra_name = "Technical Report";
    $btn_extra_icon = "bi-shield-check";
    $theme_color = "warning";
} 
elseif (in_array($dept_name, $finance_resource_group)) {
    $table_h = "Transaction / Item Ref";
    $btn_extra_name = "New Finance Request";
    $btn_extra_icon = "bi-cash-stack";
    $theme_color = "primary";
} 
elseif (in_array($dept_name, $admin_strategy_group)) {
    $table_h = "Subject / Case Title";
    $btn_extra_name = "Case Update";
    $btn_extra_icon = "bi-journal-bookmark";
    $theme_color = "dark";
}

// 3. ዳታ ማምጣት (የራሱን ስራ ብቻ እንዲያይ - UC-09)
$stmt = $pdo->prepare("SELECT id, title, description, deadline, priority, status, created_at, machine_name, issue_description FROM maintenance_requests WHERE assigned_to = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$my_tasks = $stmt->fetchAll();

include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm border-start border-5 border-<?= $theme_color ?>">
            <div>
                <h4 class="fw-bold mb-0">ሰላም፣ <?= htmlspecialchars($_SESSION['full_name']) ?> 👋</h4>
                <small class="text-muted fw-bold text-uppercase"><?= htmlspecialchars($dept_name) ?> | <?= htmlspecialchars($user_role) ?></small>
            </div>
            <div class="text-end">
                <div class="fw-bold fs-5 text-uppercase"><?= date('h:i A') ?></div>
                <div class="small text-muted"><?= date('D, M d, Y') ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <h5 class="fw-bold mb-3"><i class="bi bi-card-checklist me-2 text-<?= $theme_color ?>"></i><?= __('my_tasks') ?> (የተመደቡልኝ ስራዎች)</h5>
            
            <?php foreach($my_tasks as $task): ?>
            <div class="glass-card p-4 mb-3 border-0 shadow-sm position-relative overflow-hidden bg-white rounded-4" id="task-card-<?= $task['id'] ?>">
                <div class="position-absolute top-0 start-0 h-100 bg-<?= ($task['priority'] == 'High' || $task['priority'] == 'Urgent') ? 'danger' : ($task['priority'] == 'Medium' ? 'warning' : 'success') ?>" style="width: 5px;"></div>
                
                <div class="d-flex justify-content-between align-items-start">
                    <div class="ms-2 flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="fw-bold text-dark fs-5 mb-1"><?= htmlspecialchars($task['title'] ?? 'No Title') ?></h6>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewTaskDetails(<?= $task['id'] ?>)">
                                <i class="bi bi-eye"></i> View Details
                            </button>
                        </div>
                        <p class="text-muted mb-2 small"><?= htmlspecialchars(substr($task['description'] ?? $task['issue_description'], 0, 100)) ?>...</p>
                        <div class="d-flex gap-3 mt-3 flex-wrap">
                            <span class="small text-muted"><i class="bi bi-calendar-event me-1"></i> 
                                <?= __('deadline') ?>: <?= $task['deadline'] ? date('M d, Y', strtotime($task['deadline'])) : __('no_deadline') ?>
                            </span>
                            <span class="badge bg-light text-dark border mb-2 small text-uppercase">
                                <?= __('priority') ?>: <?= htmlspecialchars($task['priority']) ?>
                            </span>
                            <span id="status-badge-<?= $task['id'] ?>" class="badge rounded-pill px-3 py-2 <?= 
                                ($task['status'] == 'Completed') ? 'bg-success' : 
                                ($task['status'] == 'In Progress' ? 'bg-primary' : 
                                ($task['status'] == 'Under Review' ? 'bg-warning' : 'bg-secondary')) ?> text-white">
                                <?= $task['status'] ?>
                            </span>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2 ms-3">
                        <?php if($task['status'] !== 'Completed' && $task['status'] !== 'Under Review'): ?>
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm shadow-sm px-3 rounded-pill dropdown-toggle" data-bs-toggle="dropdown">
                                <?= __('update_status') ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                <?php if($task['status'] == 'Pending'): ?>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'In Progress')">
                                        <i class="bi bi-play-circle text-primary me-2"></i><?= __('status_in_progress') ?></a></li>
                                <?php elseif($task['status'] == 'In Progress'): ?>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'Blocked')">
                                        <i class="bi bi-slash-circle text-warning me-2"></i><?= __('status_blocked') ?></a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item fw-bold text-success" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'Under Review')">
                                        <i class="bi bi-check-all me-2"></i><?= __('mark_completed') ?></a></li>
                                <?php elseif($task['status'] == 'Blocked'): ?>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'In Progress')">
                                        <i class="bi bi-play-circle text-primary me-2"></i><?= __('resume') ?> (<?= __('status_in_progress') ?>)</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <?php else: ?>
                            <span class="text-success fw-bold small"><i class="bi bi-check-circle-fill me-1"></i> 
                                <?= $task['status'] == 'Completed' ? __('status_completed') : __('status_under_review') ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(empty($my_tasks)): ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                    <i class="bi bi-clipboard-x fs-1 text-muted mb-2"></i>
                    <p class="text-muted"><?= __('no_tasks') ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-<?= $theme_color ?> text-white p-3 border-0 rounded-top-4">
                    <h5 class="mb-0 fw-bold"><i class="bi <?= $btn_extra_icon ?> me-2"></i> <?= $btn_extra_name ?></h5>
                </div>
                <div class="card-body p-4">
                    <form onsubmit="submitFeedback(event)" id="feedbackForm">
                        <div class="mb-3">
                            <label class="form-label small fw-bold"><?= __('category') ?></label>
                            <select class="form-select bg-light border-0" id="issue_category" required>
                                <?php if($is_production): ?>
                                    <option value="Technical"><?= __('technical') ?> (የቴክኒክ ብልሽት)</option>
                                    <option value="Material"><?= __('material') ?> (የጥሬ እቃ እጥረት)</option>
                                    <option value="Safety">Safety (የደህንነት ስጋት)</option>
                                <?php else: ?>
                                    <option value="Administrative"><?= __('administrative') ?> (አስተዳደራዊ)</option>
                                    <option value="Resource">Resource (የግብዓት እጥረት)</option>
                                    <option value="System">System (የሲስተም ችግር)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold"><?= __('description') ?></label>
                            <textarea class="form-control bg-light border-0" id="issue_desc" rows="4" placeholder="<?= __('submit_feedback') ?>ን እዚህ ይግለጹ..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-<?= $theme_color ?> w-100 py-2 fw-bold rounded-pill text-white">
                            ሪፖርት ላክ (<?= __('submit_feedback') ?>)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Details Modal -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="taskDetailsModalLabel"><?= __('task_details') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="taskDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Feedback Modal for Blocked Tasks -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel"><?= __('submit_feedback') ?> (<?= __('status_blocked') ?> Task)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="blockedFeedbackForm">
                    <input type="hidden" id="blocked_task_id" name="task_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= __('category') ?></label>
                        <select class="form-select" id="blocked_category" name="category" required>
                            <option value="Technical"><?= __('technical') ?></option>
                            <option value="Material"><?= __('material') ?></option>
                            <option value="Administrative"><?= __('administrative') ?></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= __('description') ?></label>
                        <textarea class="form-control" id="blocked_description" name="description" rows="4" 
                                placeholder="Describe the issue blocking this task..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><?= __('submit_feedback') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function updateTaskStatus(taskId, newStatus) {
    if(!confirm('Are you sure you want to change the status to ' + newStatus + '?')) return;

    fetch('update_status_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ task_id: taskId, status: newStatus })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            // Update status badge
            const badge = document.getElementById('status-badge-' + taskId);
            badge.className = 'badge rounded-pill px-3 py-2 ' + getStatusClass(newStatus) + ' text-white';
            badge.textContent = newStatus;
            
            // If status changed to Blocked, show feedback modal
            if(newStatus === 'Blocked') {
                document.getElementById('blocked_task_id').value = taskId;
                new bootstrap.Modal(document.getElementById('feedbackModal')).show();
            } else {
                location.reload(); // Reload to update UI
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the task status.');
    });
}

function getStatusClass(status) {
    switch(status) {
        case 'Completed': return 'bg-success';
        case 'In Progress': return 'bg-primary';
        case 'Under Review': return 'bg-warning';
        case 'Blocked': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

function viewTaskDetails(taskId) {
    fetch('fetch_live_data_ajax.php?task_id=' + taskId)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            const task = data.task;
            const content = `
                <div class="row">
                    <div class="col-md-8">
                        <h6 class="fw-bold">${task.title || 'No Title'}</h6>
                        <p class="text-muted">${task.description || task.issue_description}</p>
                        <hr>
                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Priority:</strong> ${task.priority}<br>
                                <strong>Status:</strong> ${task.status}<br>
                                <strong>Created:</strong> ${new Date(task.created_at).toLocaleDateString()}
                            </div>
                            <div class="col-sm-6">
                                <strong>Deadline:</strong> ${task.deadline ? new Date(task.deadline).toLocaleDateString() : 'No deadline'}<br>
                                <strong>Machine/Workstation:</strong> ${task.machine_name || 'N/A'}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('taskDetailsContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('taskDetailsModal')).show();
        } else {
            alert('Error loading task details');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while loading task details.');
    });
}

function submitFeedback(e) {
    e.preventDefault();
    const category = document.getElementById('issue_category').value;
    const desc = document.getElementById('issue_desc').value;

    fetch('submit_feedback_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ category: category, description: desc })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert('Feedback submitted successfully!');
            document.getElementById('feedbackForm').reset();
        } else {
            alert('Error submitting feedback.');
        }
    });
}

// Handle blocked feedback form
document.getElementById('blockedFeedbackForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('submit_feedback_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert('Feedback submitted successfully! Task status updated to Blocked.');
            this.reset();
            bootstrap.Modal.getInstance(document.getElementById('feedbackModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting feedback.');
    });
});
</script>

<?php include '../includes/footer_glass.php'; ?>