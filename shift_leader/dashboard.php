<?php 
session_start();
require_once '../includes/db.php';

// 1. ሴሽን እና የShift Leader ሚና ማረጋገጥ
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Shift Leader') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$dept_id = $_SESSION['dept_id'];
$full_name = $_SESSION['full_name'];
$dept_name = $_SESSION['dept_name'];

// 2. የገጽታ ቀለም (Theme) - ለፕሮዳክሽን ብቻ
$theme_color = "primary";
if (strpos($dept_name, 'Spinning') !== false) $theme_color = "info";
elseif (strpos($dept_name, 'Weaving') !== false) $theme_color = "success";
elseif (strpos($dept_name, 'Processing') !== false) $theme_color = "warning";
elseif (strpos($dept_name, 'Garment') !== false) $theme_color = "danger";

// --- የዳታ አሰባሰብ (Queries) ---

// Pending tasks routed to this Shift Leader department
$pending_alerts_stmt = $pdo->prepare("SELECT mr.*, u.full_name as creator_name 
                                   FROM maintenance_requests mr 
                                   LEFT JOIN users u ON mr.user_id = u.id 
                                   WHERE mr.receiver_dept_id = ? AND mr.status IN ('Pending', 'Pending Approval') 
                                   ORDER BY mr.created_at DESC");
$pending_alerts_stmt->execute([$dept_id]);
$pending_alerts = $pending_alerts_stmt->fetchAll();

// Department-level monitoring tasks
$department_tasks_stmt = $pdo->prepare("SELECT mr.*, u.full_name as assigned_employee 
                                      FROM maintenance_requests mr 
                                      LEFT JOIN users u ON mr.assigned_to = u.id 
                                      WHERE mr.receiver_dept_id = ? 
                                      ORDER BY FIELD(mr.status, 'Blocked', 'In Progress', 'Pending Approval', 'Pending', 'Assigned', 'Completed'), mr.updated_at DESC");
$department_tasks_stmt->execute([$dept_id]);
$department_tasks = $department_tasks_stmt->fetchAll();

// Verification pending tasks for this department
$verification_stmt = $pdo->prepare("SELECT mr.*, u.full_name as emp_name 
                                   FROM maintenance_requests mr 
                                   JOIN users u ON mr.assigned_to = u.id 
                                   WHERE mr.receiver_dept_id = ? AND mr.status = 'In Progress' AND mr.is_verified = 0 
                                   ORDER BY mr.updated_at DESC");
$verification_stmt->execute([$dept_id]);
$verification_tasks = $verification_stmt->fetchAll();

// Employee list with active task counts
$emp_stmt = $pdo->prepare("SELECT u.id, u.full_name, COUNT(mr.id) as active_task_count 
                          FROM users u 
                          LEFT JOIN maintenance_requests mr ON mr.assigned_to = u.id AND mr.status != 'Completed' 
                          WHERE u.dept_id = ? AND u.user_role = 'Employee' 
                          GROUP BY u.id, u.full_name");
$emp_stmt->execute([$dept_id]);
$my_employees = $emp_stmt->fetchAll();

// Active tasks currently assigned to department employees
$employee_tasks_stmt = $pdo->prepare("SELECT mr.*, u.full_name as assigned_employee 
                                     FROM maintenance_requests mr 
                                     JOIN users u ON mr.assigned_to = u.id 
                                     WHERE mr.receiver_dept_id = ? 
                                       AND mr.assigned_to IS NOT NULL 
                                       AND mr.status != 'Completed' 
                                     ORDER BY u.full_name, FIELD(mr.status,'Pending Approval','Pending','Assigned','In Progress','Blocked'), mr.updated_at DESC");
$employee_tasks_stmt->execute([$dept_id]);
$employee_tasks = $employee_tasks_stmt->fetchAll();

// Recent department reports
$report_stmt = $pdo->prepare("SELECT pr.*, u.full_name as employee_name 
                             FROM production_reports pr 
                             JOIN users u ON pr.user_id = u.id 
                             WHERE pr.dept_id = ? AND pr.reported_to LIKE '%Shift Leader%' 
                             ORDER BY pr.report_date DESC LIMIT 10");
$report_stmt->execute([$dept_id]);
$employee_reports = $report_stmt->fetchAll();

// New pending maintenance requests routed to this department
$new_req_stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE receiver_dept_id = ? AND status = 'Pending' ORDER BY created_at DESC");
$new_req_stmt->execute([$dept_id]);
$new_requests = $new_req_stmt->fetchAll();

// New Alerts: Pending Approval routed to this Shift Leader department
$alerts_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM maintenance_requests WHERE receiver_dept_id = ? AND status = 'Pending Approval' AND is_read_by_receiver = 0");
$alerts_count_stmt->execute([$dept_id]);
$new_alerts_count = $alerts_count_stmt->fetch()['count'];

// Verification Pending: In Progress, not verified, in department
$verification_pending_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM maintenance_requests WHERE receiver_dept_id = ? AND status = 'In Progress' AND is_verified = 0");
$verification_pending_stmt->execute([$dept_id]);
$verification_pending_count = $verification_pending_stmt->fetch()['count'];
// ---------------------------------

include '../includes/header_glass.php';
?>

<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1055;">
    <div id="liveToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body fw-semibold" id="toastMessage">ተሳክቷል።</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
</div>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 glass-card shadow-sm border-start border-5 border-<?php echo $theme_color; ?>">
        <div>
            <h2 class="text-dark fw-bold mb-0">
                <i class="bi bi-person-badge-fill text-<?php echo $theme_color; ?> me-2"></i>
                <?php echo htmlspecialchars($dept_name); ?> - Dashboard
            </h2>
            <div class="mt-2">
                <?php if ($new_alerts_count > 0): ?>
                    <span class="badge bg-warning text-dark me-2">
                        <i class="bi bi-exclamation-triangle"></i> New Alerts: <?php echo $new_alerts_count; ?>
                    </span>
                <?php endif; ?>
                <?php if ($verification_pending_count > 0): ?>
                    <span class="badge bg-info text-white">
                        <i class="bi bi-check-circle"></i> Verification Pending: <?php echo $verification_pending_count; ?>
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-muted mb-0">መስመር፦ <strong><?php echo $full_name; ?></strong> | ሪፖርት ለ፦ <strong>Supervisor/Manager</strong></p>
        </div>
        <div class="text-end">
            <span class="badge bg-<?php echo $theme_color; ?> rounded-pill px-3 py-2 shadow-sm mb-1">
                <i class="bi bi-clock-history me-1"></i> Active Shift
            </span>
            <div class="small text-muted fw-bold"><?php echo date('l, M d, Y'); ?></div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card glass-card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                    <div>
                        <h5 class="fw-bold mb-0"><i class="bi bi-list-check text-primary me-2"></i>Assign Tasks</h5>
                        <small class="text-muted">Assign pending tasks to employees in your department.</small>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive mb-4">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Machine</th>
                                    <th>Issue</th>
                                    <th>Priority</th>
                                    <th>Assign To</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($pending_alerts)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-3">No assignable tasks available.</td></tr>
                                <?php endif; ?>
                                <?php foreach($pending_alerts as $alert): ?>
                                <tr id="assign-row-<?php echo $alert['id']; ?>">
                                    <td><?php echo htmlspecialchars($alert['machine_name']); ?></td>
                                    <td><?php echo htmlspecialchars($alert['issue_description']); ?></td>
                                    <td><span class="badge bg-<?php echo ($alert['priority']=='Emergency') ? 'danger' : 'warning'; ?>"><?php echo htmlspecialchars($alert['priority']); ?></span></td>
                                    <td>
                                        <select id="assignEmployeeSelect_<?php echo $alert['id']; ?>" class="form-select form-select-sm" onchange="updateRowAssigneeWarning(<?php echo $alert['id']; ?>)">
                                            <option value="">Select employee</option>
                                            <?php foreach($my_employees as $emp): ?>
                                                <option value="<?php echo $emp['id']; ?>" data-active-count="<?php echo $emp['active_task_count']; ?>">
                                                    <?php echo htmlspecialchars($emp['full_name']); ?> (<?php echo $emp['active_task_count']; ?> active)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div id="assignWarning_<?php echo $alert['id']; ?>" class="form-text text-danger d-none">Warning: selected employee already has more than 3 active tasks.</div>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-primary me-2" onclick="assignTask(<?php echo $alert['id']; ?>)">Assign</button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="processAlert(<?php echo $alert['id']; ?>, 'escalate')">Escalate</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card glass-card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                    <div>
                        <h5 class="fw-bold mb-0"><i class="bi bi-bar-chart-line text-primary me-2"></i>Department Task Monitor</h5>
                        <small class="text-muted">Only tasks where receiver_dept_id matches your department.</small>
                    </div>
                    <div class="btn-group" role="group" aria-label="Task filters">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="filterTasks('blocked')">Blocked Tasks</button>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="filterTasks('pending_verification')">Pending Verification</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="filterTasks('all')">All Tasks</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive mb-4">
                        <table class="table table-hover align-middle" id="departmentTaskTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Task</th>
                                    <th>Assigned</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($department_tasks)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-3">No department tasks found.</td></tr>
                                <?php endif; ?>
                                <?php foreach($department_tasks as $task): 
                                    $displayStatus = $task['status'];
                                    $statusClass = 'secondary';
                                    $filterTag = 'all';

                                    if ($task['status'] === 'Blocked') {
                                        $displayStatus = 'Blocked';
                                        $statusClass = 'danger';
                                        $filterTag = 'blocked';
                                    } elseif ($task['status'] === 'In Progress' && $task['is_verified'] == 0) {
                                        $displayStatus = 'Under Review';
                                        $statusClass = 'warning';
                                        $filterTag = 'pending_verification';
                                    } elseif ($task['status'] === 'In Progress') {
                                        $displayStatus = 'In Progress';
                                        $statusClass = 'primary';
                                    } elseif ($task['status'] === 'Pending Approval') {
                                        $displayStatus = 'Pending Approval';
                                        $statusClass = 'info';
                                    } elseif ($task['status'] === 'Pending') {
                                        $displayStatus = 'Pending';
                                        $statusClass = 'secondary';
                                    } elseif ($task['status'] === 'Assigned') {
                                        $displayStatus = 'Assigned';
                                        $statusClass = 'dark';
                                    } elseif ($task['status'] === 'Completed') {
                                        $displayStatus = 'Completed';
                                        $statusClass = 'success';
                                    }
                                ?>
                                <tr id="task-row-<?php echo $task['id']; ?>" data-filter="<?php echo $filterTag; ?>" data-status="<?php echo htmlspecialchars($task['status']); ?>" data-verified="<?php echo htmlspecialchars($task['is_verified']); ?>">
                                    <td>
                                        <div class="fw-semibold">#<?php echo $task['id']; ?> - <?php echo htmlspecialchars($task['machine_name'] ?: $task['title'] ?: 'No subject'); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars(substr($task['issue_description'] ?? $task['description'], 0, 80)); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($task['assigned_employee'] ?: 'Unassigned'); ?></td>
                                    <td><span class="badge bg-<?php echo $statusClass; ?> text-white"><?php echo $displayStatus; ?></span></td>
                                    <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($task['priority']); ?></span></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary me-2" onclick="showTaskDetails(<?php echo $task['id']; ?>)">Details</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card glass-card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                    <div>
                        <h5 class="fw-bold mb-0"><i class="bi bi-person-lines-fill text-success me-2"></i>Assigned Tasks per Employee</h5>
                        <small class="text-muted">Show tasks currently assigned to the department’s employees.</small>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Task</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($employee_tasks)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-3">No active assigned tasks found.</td></tr>
                                <?php endif; ?>
                                <?php foreach($employee_tasks as $task): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($task['assigned_employee']); ?></td>
                                    <td><?php echo htmlspecialchars($task['machine_name'] ?: $task['title']); ?></td>
                                    <td><span class="badge bg-<?php echo ($task['status'] === 'In Progress' ? 'primary' : ($task['status'] === 'Blocked' ? 'danger' : 'secondary')); ?> text-white"><?php echo htmlspecialchars($task['status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($task['priority']); ?></td>
                                    <td><?php echo date('M d', strtotime($task['updated_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card glass-card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0"><i class="bi bi-journal-text text-success me-2"></i>ከሰራተኞች የመጡ ሪፖርቶች</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ሰራተኛ</th>
                                    <th>ማሽን</th>
                                    <th>መጠን</th>
                                    <th>ድርጊት</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($employee_reports as $report): ?>
                                <tr>
                                    <td><strong><?php echo $report['employee_name']; ?></strong></td>
                                    <td><?php echo $report['machine_name']; ?></td>
                                    <td><span class="badge bg-light text-dark"><?php echo $report['quantity_produced']; ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary rounded-circle" title="ወደ ሱፐርቫይዘር ላክ"><i class="bi bi-arrow-up-right"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card glass-card border-0 shadow-sm h-100 border-top border-4 border-danger">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-tools me-2"></i>የጥገና ጥያቄዎች</h5>
                </div>
                <div class="card-body p-0" id="alertsContainer">
                    <div class="list-group list-group-flush">
                        <?php if(empty($new_requests)): ?>
                            <div class="text-center py-5 text-muted" id="noAlertsMsg">አዲስ የጥገና ጥያቄ የለም</div>
                        <?php endif; ?>
                        <?php foreach($new_requests as $alert): ?>
                        <div class="list-group-item bg-transparent py-3 border-bottom" id="alert-item-<?php echo $alert['id']; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($alert['machine_name']); ?></h6>
                                <select id="severity_<?php echo $alert['id']; ?>" class="form-select form-select-sm w-auto border-0 bg-light text-danger py-0 px-2" style="font-size: 0.7rem;">
                                    <option value="Low">Low</option>
                                    <option value="Normal" selected>Normal</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                            <p class="small text-muted mb-2"><?php echo htmlspecialchars($alert['issue_description']); ?></p>
                            
                            <div class="d-flex gap-2">
                                <button onclick="submitDecision(<?php echo $alert['id']; ?>, 'engineering')" class="btn btn-sm btn-outline-info w-50 rounded-pill" style="font-size: 0.75rem;">
                                    <i class="bi bi-gear"></i> ኢንጂነሪንግ
                                </button>
                                <button onclick="submitDecision(<?php echo $alert['id']; ?>, 'manager')" class="btn btn-sm btn-outline-danger w-50 rounded-pill" style="font-size: 0.75rem;">
                                    <i class="bi bi-shield-exclamation"></i> ማናጀር
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 text-center pb-4 mt-3">
                    <button class="btn btn-sm btn-light w-100 rounded-pill border" data-bs-toggle="modal" data-bs-target="#newMaintModal">
                        <i class="bi bi-plus-circle me-1"></i> አዲስ የጥገና ጥያቄ ፍጠር
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals for Alert Processing -->
<div class="modal fade" id="escalateAlertModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Escalate Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="escalateAlertForm">
                <div class="modal-body">
                    <input type="hidden" name="req_id" id="escalate_req_id">
                    <p>Are you sure you want to escalate this alert to Engineering/Manager?</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Escalate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function processAlert(reqId, action) {
    if (action === 'escalate') {
        document.getElementById('escalate_req_id').value = reqId;
        var modal = new bootstrap.Modal(document.getElementById('escalateAlertModal'));
        modal.show();
    }
}

function updateRowAssigneeWarning(taskId) {
    const select = document.getElementById('assignEmployeeSelect_' + taskId);
    const warning = document.getElementById('assignWarning_' + taskId);
    if (!select || !warning) return;
    const activeCount = parseInt(select.selectedOptions[0].dataset.activeCount || '0', 10);
    warning.classList.toggle('d-none', activeCount <= 3);
}

function assignTask(taskId) {
    const select = document.getElementById('assignEmployeeSelect_' + taskId);
    if (!select) return;
    const employeeId = select.value;
    if (!employeeId) {
        alert('Please select an employee to assign.');
        return;
    }

    const activeCount = parseInt(select.selectedOptions[0].dataset.activeCount || '0', 10);
    const warning = document.getElementById('assignWarning_' + taskId);
    if (activeCount > 3) {
        warning.classList.remove('d-none');
        if (!confirm('Selected employee already has more than 3 active tasks. Continue?')) {
            return;
        }
    }

    fetch('assign_task_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `task_id=${taskId}&employee_id=${employeeId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('assign-row-' + taskId)?.remove();
            const toast = new bootstrap.Toast(document.getElementById('liveToast'));
            document.getElementById('toastMessage').innerText = data.message || 'Assigned successfully.';
            toast.show();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Unable to assign task.'));
}

function filterTasks(filter) {
    const rows = document.querySelectorAll('#departmentTaskTable tbody tr');
    rows.forEach(row => {
        const rowFilter = row.dataset.filter || 'all';
        row.style.display = (filter === 'all' || rowFilter === filter) ? '' : 'none';
    });
}

function showTaskDetails(taskId) {
    window.location.href = 'verify_task.php?id=' + taskId;
}

function updateRowAssigneeWarning(taskId) {
    const select = document.getElementById('assignEmployeeSelect_' + taskId);
    const warning = document.getElementById('assignWarning_' + taskId);
    if (!select || !warning) return;
    const activeCount = parseInt(select.selectedOptions[0].dataset.activeCount || '0', 10);
    warning.classList.toggle('d-none', activeCount <= 3);
}

function assignTask(taskId) {
    const select = document.getElementById('assignEmployeeSelect_' + taskId);
    if (!select) return;
    const employeeId = select.value;
    if (!employeeId) {
        alert('Please select an employee to assign.');
        return;
    }

    const activeCount = parseInt(select.selectedOptions[0].dataset.activeCount || '0', 10);
    const warning = document.getElementById('assignWarning_' + taskId);
    if (activeCount > 3) {
        warning.classList.remove('d-none');
        if (!confirm('Selected employee already has more than 3 active tasks. Continue?')) {
            return;
        }
    }

    fetch('assign_task_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `task_id=${taskId}&employee_id=${employeeId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('assign-row-' + taskId)?.remove();
            const toast = new bootstrap.Toast(document.getElementById('liveToast'));
            document.getElementById('toastMessage').innerText = data.message || 'Assigned successfully.';
            toast.show();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Unable to assign task.'));
}

// AJAX for alert processing
$('#escalateAlertForm').on('submit', function(e){
    e.preventDefault();
    $.post('process_decision.php', $(this).serialize() + '&escalate=1', function(res){
        alert('Alert escalated successfully!');
        location.reload();
    });
});
</script>

<?php include '../includes/footer_glass.php'; ?>