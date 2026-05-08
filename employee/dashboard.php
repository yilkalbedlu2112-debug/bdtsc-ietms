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

// 2. ዳይናሚክ ሎጂክ (Flexible Interface Logic)
$production_group = ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'];
$technical_quality_group = ['Engineering', 'Quality Assurance'];
$finance_resource_group = ['Finance Department', 'Procurement / Property'];
$admin_strategy_group = ['General Management', 'Human Resource (HR)', 'Planning', 'Strategy & Innovation', 'System Research & Development', 'Legal Service', 'Audit & Inspection'];

$table_h = "Task / Subject";
$btn_extra_name = "Report Issue";
$btn_extra_icon = "bi-exclamation-triangle";
$theme_color = "primary";

if (in_array($dept_name, $production_group)) {
    $table_h = "Machine / Work Station";
    $btn_extra_name = "Request Maintenance";
    $btn_extra_icon = "bi-tools";
    $theme_color = (strpos($dept_name, 'Spinning') !== false) ? "info" : 
                  ((strpos($dept_name, 'Garment') !== false) ? "danger" : "success");
} 
elseif (in_array($dept_name, $technical_quality_group)) {
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

// 3. ዳታ ማምጣት (UC-09: Assigned tasks only)
$stmt = $pdo->prepare("SELECT id, title, description, deadline, priority, status, created_at, machine_name, issue_description FROM maintenance_requests WHERE assigned_to = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$my_tasks = $stmt->fetchAll();

include '../includes/header_glass.php';
?>
<style>
    :root {
        --bdtsc-color: #008080; /* የባህርዳር ጨርቃጨርቅ መለያ ከለር */
    }
    .bg-bdtsc { background-color: var(--bdtsc-color) !important; color: white; }
    .text-bdtsc { color: var(--bdtsc-color) !important; }
    .btn-bdtsc { background-color: var(--bdtsc-color); color: white; border-radius: 8px; }
    .btn-bdtsc:hover { background-color: #006666; color: white; }
    .card-task { border-left: 5px solid var(--bdtsc-color); transition: 0.3s; }
    .card-task:hover { transform: translateY(-5px); }
</style>

<div class="container-fluid py-4">
    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
                <div class="card-body p-0">
                    <!-- እዚህ ጋር bg-bdtsc ተተክቷል -->
                    <div class="bg-bdtsc p-4 text-white">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="fw-bold mb-1">Welcome <?= htmlspecialchars($_SESSION['full_name']) ?> </h2>
                                <p class="mb-0 opacity-75">
                                    <i class="bi bi-building me-1"></i> <?= htmlspecialchars($dept_name) ?> | 
                                    <i class="bi bi-person-badge me-1"></i> <?= htmlspecialchars($user_role) ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <div class="h3 fw-bold mb-0"><?= date('h:i A') ?></div>
                                <div class="small opacity-75"><?= date('D, M d, Y') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row g-4">
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0 text-dark">
                    <!-- እዚህ ጋር text-bdtsc ተተክቷል -->
                    <i class="bi bi-card-checklist text-bdtsc me-2"></i>የተመደቡልኝ ስራዎች
                </h4>
                <!-- እዚህ ጋር btn-bdtsc ተተክቷል -->
                <button class="btn btn-bdtsc shadow-sm">
                    <i class="bi <?= $btn_extra_icon ?> me-2"></i><?= $btn_extra_name ?>
                </button>
            </div>

            <?php if(empty($my_tasks)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-clipboard-x fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted">ምንም የተመደበ ስራ የለም</h5>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                <?php foreach($my_tasks as $task): ?>
                    <div class="col-md-6 col-xl-4 mb-4">
                        <!-- እዚህ ጋር card-task ክላስ ተጨምሯል -->
                        <div class="card border-0 shadow-sm h-100 card-task" style="border-radius: 12px;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="badge bg-<?= ($task['priority'] == 'High') ? 'danger' : (($task['priority'] == 'Medium') ? 'warning' : 'info') ?> bg-opacity-10 text-dark small">
                                        <?= $task['priority'] ?> Priority
                                    </span>
                                    <span class="small text-muted">#<?= $task['id'] ?></span>
                                </div>
                                
                                <h5 class="fw-bold text-dark text-truncate"><?= htmlspecialchars($task['title'] ?: 'Untitled Task') ?></h5>
                                <p class="text-muted small mb-3">
                                    <?= htmlspecialchars(substr($task['description'] ?: $task['issue_description'], 0, 90)) ?>...
                                </p>

                                <div class="mb-3 p-2 bg-light rounded shadow-sm">
                                    <div class="small mb-1"><strong><?= $table_h ?>:</strong> <?= htmlspecialchars($task['machine_name'] ?: 'General') ?></div>
                                    <div class="small"><strong>Deadline:</strong> <?= $task['deadline'] ? date('M d, Y', strtotime($task['deadline'])) : 'Open' ?></div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Status: <?= $task['status'] ?>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if($task['status'] == 'Pending'): ?>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'In Progress')">🚀 Start Task</a></li>
                                            <?php elseif($task['status'] == 'In Progress'): ?>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'Blocked')">⚠️ Blocked</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-success fw-bold" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'Under Review')">✅ Mark Completed</a></li>
                                            <?php elseif($task['status'] == 'Blocked'): ?>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'In Progress')">🔄 Resume</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                    <!-- እዚህ ጋር btn-outline-bdtsc እንዲሆን ተደርጓል -->
                                    <button class="btn btn-outline-bdtsc btn-sm" onclick="viewTaskDetails(<?= $task['id'] ?>)">
                                        <i class="bi bi-info-circle me-1"></i>Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Task Details Modal -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Task #<span id="m_task_id"></span> Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="taskDetailsContent">
                <!-- Data loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<style>
    /* ለ Details በተን ተጨማሪ ስታይል */
    .btn-outline-bdtsc { color: var(--bdtsc-color); border-color: var(--bdtsc-color); }
    .btn-outline-bdtsc:hover { background-color: var(--bdtsc-color); color: white; }
</style>
<script>
function updateTaskStatus(taskId, newStatus) {
    if(!confirm('Are you sure you want to update status to ' + newStatus + '?')) return;
    const params = new URLSearchParams({ task_id: taskId, status: newStatus });

    fetch('update_status_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            location.reload(); 
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Network error occurred.'));
}

function viewTaskDetails(taskId) {
    document.getElementById('m_task_id').textContent = taskId;
    fetch('fetch_live_data_ajax.php?task_id=' + taskId)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            const task = data.task;
            const content = `
                <h6 class="fw-bold">${task.title || 'No Title'}</h6>
                <p class="text-muted small">${task.description || task.issue_description}</p>
                <div class="row g-2 mt-2">
                    <div class="col-6 small border-end"><strong>Status:</strong> ${task.status}</div>
                    <div class="col-6 small"><strong>Priority:</strong> ${task.priority}</div>
                    <div class="col-12 small border-top pt-2"><strong>Location:</strong> ${task.machine_name || 'N/A'}</div>
                </div>
            `;
            document.getElementById('taskDetailsContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('taskDetailsModal')).show();
        }
    });
}
</script>

<?php include '../includes/footer_glass.php'; ?>