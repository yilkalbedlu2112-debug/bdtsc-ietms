<?php 
session_start();
require_once '../includes/db.php';

$dept_id = $_SESSION['dept_id'];

// 1. Fetch Maintenance Alerts (Decision Needed)
$stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE dept_id = ? AND status = 'Pending'");
$stmt->execute([$dept_id]);
$new_requests = $stmt->fetchAll();

// 2. Fetch Live Task Status
$progress_stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE dept_id = ? AND status != 'Pending'");
$progress_stmt->execute([$dept_id]);
$all_tasks = $progress_stmt->fetchAll();

// 3. Get employees for assignment
$emp_stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE dept_id = ? AND role IN ('Employee', 'Technician')");
$emp_stmt->execute([$dept_id]);
$employees = $emp_stmt->fetchAll();

include '../includes/header_glass.php';
?>
<!-- Modal and Toast placeholders -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1055;">
    <div id="liveToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body fw-semibold" id="toastMessage">
          Action completed.
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
</div>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-dark fw-bold"><i class="bi bi-person-workspace text-primary me-2"></i><?php echo __('dashboard'); ?></h2>
            <p class="text-muted mb-0"><?php echo __('tasks'); ?> & <?php echo __('maintenance'); ?></p>
        </div>
        <div class="text-end">
            <span class="badge bg-primary rounded-pill px-3 py-2 fw-semibold shadow-sm">
                <i class="bi bi-building me-1"></i> Dept ID: <?php echo htmlspecialchars($dept_id); ?>
            </span>
        </div>
    </div>

    <div class="row g-4">
        <!-- Live Task Status -->
        <div class="col-lg-8">
            <div class="card glass-card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0"><i class="bi bi-activity text-success me-2"></i><?php echo __('active_tasks'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0"><?php echo __('tasks'); ?> / Machine</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($all_tasks)): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-4">No active tasks.</td></tr>
                                <?php endif; ?>
                                <?php foreach($all_tasks as $task): ?>
                                <tr id="task-row-<?php echo $task['id']; ?>">
                                    <td>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($task['machine_name']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($task['issue_description']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill px-3 task-status-badge <?php echo ($task['status'] === 'Completed') ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                            <?php 
                                            // Handle translation dynamically if match
                                            $status_key = 'status_' . strtolower(str_replace(' ', '_', $task['status']));
                                            $translated_val = __($status_key);
                                            echo $translated_val !== $status_key ? $translated_val : htmlspecialchars($task['status']); 
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($task['status'] !== 'Completed' && $task['status'] !== 'Assigned' && $task['status'] !== 'In Progress' && $task['status'] !== 'Sent to Engineering' && $task['status'] !== 'Escalated to Manager'): ?>
                                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#assignModal<?php echo $task['id']; ?>">
                                            <i class="bi bi-person-plus me-1"></i> <?php echo __('assign_task'); ?>
                                        </button>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="bi bi-check2-all"></i> Action taken</span>
                                        <?php endif; ?>
                                        
                                        <!-- Assignment Modal -->
                                        <div class="modal fade" id="assignModal<?php echo $task['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content glass-card shadow">
                                                    <div class="modal-header border-0">
                                                        <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i><?php echo __('assign_task'); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form onsubmit="assignTask(event, <?php echo $task['id']; ?>)" id="assignForm<?php echo $task['id']; ?>">
                                                            <div class="mb-4">
                                                                <label class="form-label text-muted fw-semibold">Select Employee</label>
                                                                <select name="employee_id" class="form-select bg-light border-0" required id="empSelect<?php echo $task['id']; ?>">
                                                                    <option value="">-- Choose Employee --</option>
                                                                    <?php foreach($employees as $emp): ?>
                                                                    <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['full_name']); ?> (<?php echo $emp['role']; ?>)</option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="d-grid">
                                                                <button type="submit" class="btn btn-primary rounded-pill py-2 fw-semibold">Confirm</button>
                                                            </div>
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
        </div>

        <!-- Maintenance Alerts -->
        <div class="col-lg-4">
            <div class="card glass-card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>ማሽን ብልሽቶች (Decision Needed)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="alertsContainer">
                        <?php if(empty($new_requests)): ?>
                            <div class="list-group-item bg-transparent text-muted py-4 text-center border-0" id="noAlertsMsg">
                                <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>
                                No new maintenance alerts
                            </div>
                        <?php endif; ?>
                        <?php foreach($new_requests as $alert): ?>
                        <div class="list-group-item bg-transparent py-3 border-bottom border-light" id="alert-item-<?php echo $alert['id']; ?>">
                            <div class="d-flex justify-content-between align-items-start w-100 mb-2">
                                <h6 class="mb-0 fw-bold text-dark"><?php echo htmlspecialchars($alert['machine_name']); ?></h6>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill px-2">Alert</span>
                            </div>
                            <p class="mb-2 small text-secondary lh-sm"><?php echo htmlspecialchars($alert['issue_description']); ?></p>
                            
                            <form onsubmit="updateDecision(event, <?php echo $alert['id']; ?>)" class="d-flex gap-2">
                                <select name="severity" id="severity_<?php echo $alert['id']; ?>" class="form-select form-select-sm bg-light border-0">
                                    <option value="Low">Low</option>
                                    <option value="High">High</option>
                                </select>
                                <button type="button" onclick="submitDecision(<?php echo $alert['id']; ?>, 'engineering')" class="btn btn-sm btn-outline-primary px-3 rounded-pill" title="Send to Engineering">
                                    <i class="bi bi-send-fill"></i> Eng
                                </button>
                                <button type="button" onclick="submitDecision(<?php echo $alert['id']; ?>, 'manager')" class="btn btn-sm btn-outline-danger px-3 rounded-pill" title="Escalate to Manager">
                                    <i class="bi bi-arrow-up-circle-fill"></i> Mgr
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showToast(message, isError = false) {
    const toastEl = document.getElementById('liveToast');
    const toastBody = document.getElementById('toastMessage');
    toastBody.textContent = message;
    
    if (isError) {
        toastEl.classList.remove('bg-success');
        toastEl.classList.add('bg-danger');
    } else {
        toastEl.classList.remove('bg-danger');
        toastEl.classList.add('bg-success');
    }
    
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}

function assignTask(event, taskId) {
    event.preventDefault();
    const empId = document.getElementById('empSelect' + taskId).value;
    
    fetch('assign_task_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ task_id: taskId, employee_id: empId })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showToast(data.message);
            // Hide modal
            const modalEl = document.getElementById('assignModal' + taskId);
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
            
            // Update UI dynamically
            const row = document.getElementById('task-row-' + taskId);
            const badge = row.querySelector('.task-status-badge');
            badge.className = 'badge rounded-pill px-3 task-status-badge bg-warning text-dark';
            badge.textContent = 'Assigned'; // You could dynamically translate this too
            row.querySelector('td:nth-child(3)').innerHTML = '<span class="text-muted small"><i class="bi bi-check2-all"></i> Action taken</span>';
        } else {
            showToast(data.message, true);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error occurred', true);
    });
}

function submitDecision(reqId, action) {
    const severity = document.getElementById('severity_' + reqId).value;
    
    fetch('process_decision_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ req_id: reqId, severity: severity, action: action })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showToast(data.message);
            // Remove item from DOM smoothly
            const alertItem = document.getElementById('alert-item-' + reqId);
            alertItem.style.transition = 'opacity 0.3s';
            alertItem.style.opacity = '0';
            setTimeout(() => {
                alertItem.remove();
                if(document.getElementById('alertsContainer').children.length === 0) {
                    document.getElementById('alertsContainer').innerHTML = '<div class="list-group-item bg-transparent text-muted py-4 text-center border-0" id="noAlertsMsg"><i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>No new maintenance alerts</div>';
                }
            }, 300);
        } else {
            showToast(data.message, true);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error occurred', true);
    });
}
function updateDecision(e, reqId) {
    e.preventDefault();
}
</script>

<?php include '../includes/footer_glass.php'; ?>