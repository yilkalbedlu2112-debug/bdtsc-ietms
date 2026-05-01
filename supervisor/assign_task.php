<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Supervisor') {
    header('Location: ../auth/login.php');
    exit();
}

$dept_id = $_SESSION['dept_id'] ?? 0;
$dept_name = $_SESSION['dept_name'] ?? 'Supervisor';
$production_group = ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'];
if (!in_array($dept_name, $production_group, true)) {
    die("<div class='alert alert-danger'>Access Denied: This page is only for Production Supervisors.</div>");
}

$pendingTasksStmt = $pdo->prepare(
    "SELECT id, title, machine_name, issue_description, priority, created_at
     FROM maintenance_requests
     WHERE (dept_id = ? OR sender_dept_id = ?) AND status = 'Pending'
     ORDER BY created_at DESC"
);
$pendingTasksStmt->execute([$dept_id, $dept_id]);
$pendingTasks = $pendingTasksStmt->fetchAll(PDO::FETCH_ASSOC);

$shiftLeadersStmt = $pdo->prepare("SELECT id, full_name FROM users WHERE dept_id = ? AND user_role = 'Shift Leader' AND status = 'Active' ORDER BY full_name");
$shiftLeadersStmt->execute([$dept_id]);
$shiftLeaders = $shiftLeadersStmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border-start border-primary border-4">
                <div>
                    <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-person-plus text-primary me-2"></i>Assign Pending Task</h3>
                    <p class="text-muted mb-0 small">Select a pending task and assign it to a shift leader.</p>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div id="assignAlert"></div>
                    <?php if (empty($pendingTasks)): ?>
                        <div class="alert alert-info">No pending tasks are currently available for assignment.</div>
                    <?php else: ?>
                        <form id="assignTaskForm">
                            <input type="hidden" name="action" value="assign_task">
                            <input type="hidden" name="request_id" id="selected_task_id" value="">
                            <div class="mb-3">
                                <label class="form-label">Select Shift Leader</label>
                                <select name="shift_leader_id" class="form-select" required>
                                    <option value="">-- Choose Shift Leader --</option>
                                    <?php foreach ($shiftLeaders as $leader): ?>
                                        <option value="<?php echo $leader['id']; ?>"><?php echo htmlspecialchars($leader['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Priority</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingTasks as $task): ?>
                                            <tr>
                                                <td>
                                                    <input type="radio" name="task_selector" value="<?php echo $task['id']; ?>" class="form-check-input task-radio">
                                                </td>
                                                <td>#<?php echo $task['id']; ?></td>
                                                <td><?php echo htmlspecialchars($task['title'] ?: $task['machine_name']); ?></td>
                                                <td><span class="badge bg-<?php echo ($task['priority'] === 'Emergency' || $task['priority'] === 'High') ? 'danger' : 'secondary'; ?>"><?php echo htmlspecialchars($task['priority']); ?></span></td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($task['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary">Assign Task</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="mb-3">Assign Task Notes</h5>
                <p class="text-muted">Assignments apply only to tasks with status <strong>Pending</strong>. Once assigned, the task status becomes <strong>Assigned</strong> and the selected shift leader receives a notification.</p>
                <p class="text-muted">If there are no shift leaders listed, make sure at least one active Shift Leader exists in your department.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const selectedTask = urlParams.get('selected_task');
    if (selectedTask) {
        const radio = $('input[name="task_selector"][value="' + selectedTask + '"]');
        if (radio.length) {
            radio.prop('checked', true);
            $('html, body').scrollTop(radio.closest('tr').offset().top - 100);
        }
    }

    $('#assignTaskForm').on('submit', function(e) {
        e.preventDefault();
        var selectedTaskId = $('input[name="task_selector"]:checked').val();
        if (!selectedTaskId) {
            $('#assignAlert').html('<div class="alert alert-warning">Please select a pending task to assign.</div>');
            return;
        }
        $('#selected_task_id').val(selectedTaskId);
        $.post('supervisor_controller.php', $(this).serialize(), function(res) {
            var alertClass = res.status === 'success' ? 'alert-success' : 'alert-danger';
            $('#assignAlert').html('<div class="alert ' + alertClass + '">' + res.message + '</div>');
            if (res.status === 'success') {
                location.reload();
            }
        }, 'json').fail(function() {
            $('#assignAlert').html('<div class="alert alert-danger">Server error. Please try again.</div>');
        });
    });
});
</script>

<?php include '../includes/footer_glass.php'; ?>