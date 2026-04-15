<?php
session_start();
require_once '../includes/db.php';

// Check if user is Supervisor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Supervisor') {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'];
$user_id = $_SESSION['user_id'];

// Stats queries
$stmt_created_today = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND user_id = ? AND DATE(created_at) = CURDATE()");
$stmt_created_today->execute([$dept_id, $user_id]);
$tasks_created_today = $stmt_created_today->fetchColumn();

$stmt_pending = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND status IN ('Pending', 'Pending Approval')");
$stmt_pending->execute([$dept_id]);
$pending_assignments = $stmt_pending->fetchColumn();

$stmt_issues = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND priority = 'Emergency' AND status != 'Completed'");
$stmt_issues->execute([$dept_id]);
$active_machine_issues = $stmt_issues->fetchColumn();

// Fetch Pending tasks for Task Queue
$stmt_queue = $pdo->prepare("SELECT * FROM maintenance_requests WHERE dept_id = ? AND status IN ('Pending', 'Pending Approval') ORDER BY created_at DESC");
$stmt_queue->execute([$dept_id]);
$task_queue = $stmt_queue->fetchAll();

// Fetch All tasks for Live Floor View
// Including employee name
$stmt_live = $pdo->prepare("SELECT m.*, u.full_name AS employee_name FROM maintenance_requests m LEFT JOIN users u ON m.assigned_to = u.id WHERE m.dept_id = ? ORDER BY m.created_at DESC LIMIT 50");
$stmt_live->execute([$dept_id]);
$live_tasks = $stmt_live->fetchAll();

// Fetch Employees for assignment
$stmt_emp = $pdo->prepare("SELECT id, full_name, user_role FROM users WHERE dept_id = ? AND user_role IN ('Employee', 'Technician')");
$stmt_emp->execute([$dept_id]);
$employees = $stmt_emp->fetchAll();

include '../includes/header_glass.php';
?>
<!-- Toast for Notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1055;">
    <div id="supToast" class="toast align-items-center text-white bg-success border-0" user_role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body fw-semibold" id="toastMsg">Action successful.</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
</div>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-dark fw-bold"><i class="bi bi-eye text-primary me-2"></i><?php echo __('dashboard'); ?> - Supervisor</h2>
            <p class="text-muted mb-0">Production Floor Oversight & Task Breakdown</p>
        </div>
        <div class="text-end">
            <!-- Report Machine Failure Button -->
            <button class="btn btn-danger rounded-pill shadow-sm px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#reportIssueModal">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo __('Report Machine Failure') ?? 'Report Machine Failure'; ?>
            </button>
            <div class="mt-2 text-muted small">Dept ID: <?php echo htmlspecialchars($dept_id); ?></div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-primary border-4">
                <h6 class="text-muted fw-bold mb-1">Tasks Created Today</h6>
                <h3 class="fw-bold text-dark mb-0"><?php echo $tasks_created_today; ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-warning border-4">
                <h6 class="text-muted fw-bold mb-1">Pending Assignments</h6>
                <h3 class="fw-bold text-dark mb-0"><?php echo $pending_assignments; ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-danger border-4">
                <h6 class="text-muted fw-bold mb-1">Active Machine Issues</h6>
                <h3 class="fw-bold text-danger mb-0"><?php echo $active_machine_issues; ?></h3>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row g-4">
        <!-- Detailed Task Creation -->
        <div class="col-lg-4">
            <div class="card glass-card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-pencil-square me-2"></i>Create Detailed Task</h5>
                    <p class="small text-muted mb-0">ዝርዝር እና ጥቃቅን ስራዎች</p>
                </div>
                <div class="card-body">
                    <form onsubmit="createDetailedTask(event)" id="createTaskForm">
                        <div class="mb-3">
                            <label class="form-label text-muted fw-semibold">Task Title / Machine</label>
                            <input type="text" id="ct_title" class="form-control bg-light border-0" placeholder="e.g. Clean Filter on Loom 4" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-semibold">Detailed Instructions</label>
                            <textarea id="ct_desc" class="form-control bg-light border-0" rows="5" placeholder="Enter detailed steps..." required></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted fw-semibold">Priority</label>
                            <select id="ct_priority" class="form-select bg-light border-0">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-semibold shadow-sm">
                            <i class="bi bi-plus-circle me-1"></i> Add Task
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Task Queue & Live Floor View -->
        <div class="col-lg-8">
            <!-- Task Queue (Assignment Support) -->
            <div class="card glass-card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0"><i class="bi bi-list-check text-warning me-2"></i>Task Queue</h5>
                    <p class="small text-muted mb-0">Assign to Employees or Notify Shift Leader (መቅረት የሌለባቸው ስራዎች)</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="border-0">Task</th>
                                    <th class="border-0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($task_queue)): ?>
                                    <tr><td colspan="2" class="text-center text-muted py-4">Queue is empty.</td></tr>
                                <?php endif; ?>
                                <?php foreach($task_queue as $tq): ?>
                                <tr id="queue-row-<?php echo $tq['id']; ?>">
                                    <td>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($tq['machine_name']); ?></div>
                                        <div class="small text-muted text-truncate" style="max-width:300px;"><?php echo htmlspecialchars($tq['issue_description']); ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#assignEmpModal<?php echo $tq['id']; ?>">
                                                <i class="bi bi-person-check"></i> Assign
                                            </button>
                                            <button onclick="notifyShiftLeader(<?php echo $tq['id']; ?>)" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Notify Shift Leader for crucial tasks">
                                                <i class="bi bi-envelope-fill"></i> S.L Alert
                                            </button>
                                        </div>

                                        <!-- Assign Employee Modal -->
                                        <div class="modal fade" id="assignEmpModal<?php echo $tq['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content glass-card shadow">
                                                    <div class="modal-header border-0">
                                                        <h5 class="modal-title fw-bold">Assign Employee</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form onsubmit="assignDirectly(event, <?php echo $tq['id']; ?>)">
                                                            <div class="mb-4">
                                                                <label class="form-label text-muted fw-semibold">Select Employee</label>
                                                                <select id="empSelectQ<?php echo $tq['id']; ?>" class="form-select bg-light border-0" required>
                                                                    <option value="">-- Choose --</option>
                                                                    <?php foreach($employees as $emp): ?>
                                                                    <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['full_name']); ?> (<?php echo $emp['user_role']; ?>)</option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <button type="submit" class="btn btn-primary w-100 rounded-pill fw-semibold">Confirm Assignment</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Live Floor View -->
            <div class="card glass-card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0"><i class="bi bi-webcam text-info me-2"></i>Live Floor View</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="border-0">Machine / Task</th>
                                    <th class="border-0">Assigned</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($live_tasks)): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">No tasks found on the floor.</td></tr>
                                <?php endif; ?>
                                <?php foreach($live_tasks as $lt): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($lt['machine_name']); ?></div>
                                        <div class="small text-muted text-truncate" style="max-width:200px;"><?php echo htmlspecialchars($lt['issue_description']); ?></div>
                                    </td>
                                    <td>
                                        <?php if($lt['employee_name']): ?>
                                            <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-person me-1"></i><?php echo htmlspecialchars($lt['employee_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small"><em>Unassigned</em></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo ($lt['status'] === 'Completed') ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo htmlspecialchars($lt['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($lt['status'] !== 'Completed'): ?>
                                        <button onclick="escalateTask(<?php echo $lt['id']; ?>)" class="btn btn-sm btn-outline-warning rounded-pill" title="Escalate to Manager">
                                            <i class="bi bi-arrow-up-circle"></i> Escalate
                                        </button>
                                        <?php else: ?>
                                            <i class="bi bi-check-all text-success fs-5"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Report Issue Modal -->
<div class="modal fade" id="reportIssueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card shadow border-danger border-2">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Report Machine Failure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form onsubmit="reportFailure(event)">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold">Machine Name / Number</label>
                        <input type="text" id="rf_machine" class="form-control bg-light border-0" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted fw-semibold">Failure Details</label>
                        <textarea id="rf_desc" class="form-control bg-light border-0" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger w-100 rounded-pill fw-bold shadow-sm">Send Alert to Engineering</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function shTst(msg, err=false) {
    const el = document.getElementById('supToast');
    document.getElementById('toastMsg').textContent = msg;
    el.className = 'toast align-items-center text-white border-0 ' + (err ? 'bg-danger' : 'bg-success');
    new bootstrap.Toast(el).show();
}

function createDetailedTask(e) {
    e.preventDefault();
    const title = document.getElementById('ct_title').value;
    const desc = document.getElementById('ct_desc').value;
    const priority = document.getElementById('ct_priority').value;

    fetch('sup_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'create_task', title, desc, priority })
    }).then(res => res.json()).then(data => {
        if(data.success) {
            shTst(data.message);
            document.getElementById('createTaskForm').reset();
            setTimeout(() => location.reload(), 1000);
        } else shTst(data.message, true);
    }).catch(() => shTst('Network error', true));
}

function assignDirectly(e, taskId) {
    e.preventDefault();
    const empId = document.getElementById('empSelectQ' + taskId).value;
    
    fetch('sup_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'assign_task', task_id: taskId, employee_id: empId })
    }).then(res => res.json()).then(data => {
        if(data.success) {
            shTst('Task assigned successfully!');
            bootstrap.Modal.getInstance(document.getElementById('assignEmpModal' + taskId)).hide();
            document.getElementById('queue-row-' + taskId).style.display = 'none';
        } else shTst(data.message, true);
    }).catch(() => shTst('Network error', true));
}

function notifyShiftLeader(taskId) {
    fetch('sup_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'notify_shift_leader', task_id: taskId })
    }).then(res => res.json()).then(data => {
        if(data.success) {
            shTst('Shift Leader Notified!');
            document.getElementById('queue-row-' + taskId).style.display = 'none';
        } else shTst(data.message, true);
    }).catch(() => shTst('Network error', true));
}

function reportFailure(e) {
    e.preventDefault();
    const machine = document.getElementById('rf_machine').value;
    const desc = document.getElementById('rf_desc').value;

    fetch('sup_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'report_failure', machine, desc })
    }).then(res => res.json()).then(data => {
        if(data.success) {
            shTst('Engineering Altered Successfully!');
            bootstrap.Modal.getInstance(document.getElementById('reportIssueModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else shTst(data.message, true);
    }).catch(() => shTst('Network error', true));
}

function escalateTask(taskId) {
    if(!confirm('Are you sure you want to escalate this to management?')) return;
    fetch('sup_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'escalate_task', task_id: taskId })
    }).then(res => res.json()).then(data => {
        if(data.success) {
            shTst('Task escalated successfully.');
            setTimeout(() => location.reload(), 1000);
        } else shTst(data.message, true);
    }).catch(() => shTst('Network error', true));
}
</script>

<?php include '../includes/footer_glass.php'; ?>