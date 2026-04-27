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

<div class="container-fluid">
    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="fw-bold mb-1">
                                <i class="bi bi-person-circle text-<?= $theme_color ?> me-2"></i>ሰላም፣ <?= htmlspecialchars($_SESSION['full_name']) ?> 👋
                            </h2>
                            <p class="text-muted mb-0">
                                <span class="badge bg-<?= $theme_color ?> bg-opacity-10 text-<?= $theme_color ?> me-2">
                                    <?= htmlspecialchars($dept_name) ?>
                                </span>
                                <span class="badge bg-light text-dark">
                                    <?= htmlspecialchars($user_role) ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="h4 fw-bold text-<?= $theme_color ?> mb-0"><?= date('h:i A') ?></div>
                            <div class="small text-muted"><?= date('D, M d, Y') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row g-4">
        <!-- Tasks Section -->
        <div class="col-lg-8">
            <h4 class="fw-bold mb-3">
                <i class="bi bi-card-checklist text-<?= $theme_color ?> me-2"></i>My Tasks (የተመደቡልኝ ስራዎች)
            </h4>

            <?php if(empty($my_tasks)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-clipboard-x fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted">No tasks assigned</h5>
                        <p class="text-muted small">You don't have any tasks assigned at the moment.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach($my_tasks as $task): ?>
                <div class="card border-0 shadow-sm mb-3" id="task-card-<?= $task['id'] ?>">
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="flex-grow-1">
                                        <h5 class="fw-bold mb-2"><?= htmlspecialchars($task['title'] ?? 'No Title') ?></h5>
                                        <p class="text-muted small mb-3">
                                            <?= htmlspecialchars(substr($task['description'] ?? $task['issue_description'], 0, 120)) ?>...
                                        </p>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm ms-3" onclick="viewTaskDetails(<?= $task['id'] ?>)">
                                        <i class="bi bi-eye me-1"></i>View Details
                                    </button>
                                </div>

                                <div class="row g-2">
                                    <div class="col-auto">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            Deadline: <?= $task['deadline'] ? date('M d, Y', strtotime($task['deadline'])) : 'No deadline' ?>
                                        </small>
                                    </div>
                                    <div class="col-auto">
                                        <span class="badge bg-light text-dark">
                                            Priority: <?= htmlspecialchars($task['priority']) ?>
                                        </span>
                                    </div>
                                    <div class="col-auto">
                                        <span id="status-badge-<?= $task['id'] ?>" class="badge <?=
                                            ($task['status'] == 'Completed') ? 'bg-success' :
                                            ($task['status'] == 'In Progress' ? 'bg-primary' :
                                            ($task['status'] == 'Under Review' ? 'bg-warning text-dark' : 'bg-secondary')) ?>">
                                            <?= $task['status'] ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <?php if($task['status'] !== 'Completed' && $task['status'] !== 'Under Review'): ?>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Update Status
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <?php if($task['status'] == 'Pending'): ?>
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'In Progress')">
                                                <i class="bi bi-play-circle text-primary me-2"></i>In Progress</a></li>
                                        <?php elseif($task['status'] == 'In Progress'): ?>
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'Blocked')">
                                                <i class="bi bi-slash-circle text-warning me-2"></i>Blocked</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item fw-bold text-success" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'Under Review')">
                                                <i class="bi bi-check-all me-2"></i>Mark Completed</a></li>
                                        <?php elseif($task['status'] == 'Blocked'): ?>
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'In Progress')">
                                                <i class="bi bi-play-circle text-primary me-2"></i>Resume (In Progress)</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <?php else: ?>
                                    <span class="text-success fw-semibold small">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        <?= $task['status'] == 'Completed' ? 'Completed' : 'Under Review' ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Feedback Form Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-<?= $theme_color ?> text-white border-0">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi <?= $btn_extra_icon ?> me-2"></i><?= $btn_extra_name ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form onsubmit="submitFeedback(event)" id="feedbackForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Category</label>
                            <select class="form-select" id="issue_category" required>
                                <?php if($is_production): ?>
                                    <option value="Technical">Technical (የቴክኒክ ብልሽት)</option>
                                    <option value="Material">Material (የጥሬ እቃ እጥረት)</option>
                                    <option value="Safety">Safety (የደህንነት ስጋት)</option>
                                <?php else: ?>
                                    <option value="Administrative">Administrative (አስተዳደራዊ)</option>
                                    <option value="Resource">Resource (የግብዓት እጥረት)</option>
                                    <option value="System">System (የሲስተም ችግር)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="issue_desc" rows="4" placeholder="Describe your issue here..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-<?= $theme_color ?> w-100 fw-semibold">
                            Submit Report
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Details Modal -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Task Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="taskDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Feedback Modal for Blocked Tasks -->
<div class="modal fade" id="feedbackModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit Feedback (Blocked Task)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="blockedFeedbackForm">
                    <input type="hidden" id="blocked_task_id" name="task_id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <select class="form-select" id="blocked_category" name="category" required>
                            <option value="Technical">Technical</option>
                            <option value="Material">Material</option>
                            <option value="Administrative">Administrative</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="blocked_description" name="description" rows="4"
                                placeholder="Describe the issue blocking this task..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Submit Feedback</button>
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
            badge.className = 'badge ' + getStatusClass(newStatus);
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
        case 'Under Review': return 'bg-warning text-dark';
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