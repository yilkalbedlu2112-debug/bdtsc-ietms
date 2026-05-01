<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Supervisor') {
    header('Location: ../auth/login.php');
    exit();
}

$dept_name = $_SESSION['dept_name'] ?? 'Supervisor';
$dept_id = $_SESSION['dept_id'] ?? 0;
$production_group = ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'];
if (!in_array($dept_name, $production_group, true)) {
    die("<div class='alert alert-danger'>Access Denied: This page is only for Production Supervisors.</div>");
}

$departmentStmt = $pdo->query("SELECT id, dept_name FROM departments ORDER BY dept_name");
$departments = $departmentStmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border-start border-primary border-4">
                <div>
                    <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-send-check text-primary me-2"></i>Create New Task</h3>
                    <p class="text-muted mb-0 small">Create a task with priority, deadline and technical instructions.</p>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div id="taskAlert"></div>
                    <form id="supervisorCreateTaskForm">
                        <input type="hidden" name="action" value="create_task">
                        <div class="mb-3">
                            <label class="form-label">Task Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Enter task title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Send To Department</label>
                            <select name="receiver_dept_id" class="form-select" required>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($dept['id'] == $dept_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['dept_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Choose the department that should receive this task.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="5" placeholder="Enter technical instructions" required></textarea>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select" required>
                                    <option value="Emergency">Emergency</option>
                                    <option value="High">High</option>
                                    <option value="Normal" selected>Normal</option>
                                    <option value="Medium">Medium</option>
                                    <option value="Low">Low</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Task Type</label>
                                <select name="task_type" class="form-select" required>
                                    <option value="Daily Production">Daily Production</option>
                                    <option value="Quality Check">Quality Check</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Breakdown">Breakdown</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deadline</label>
                            <input type="datetime-local" name="deadline" class="form-control" required>
                            <div class="form-text">Deadline cannot be in the past.</div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Create Task</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    const deadlineInput = document.querySelector('input[name="deadline"]');
    if (deadlineInput) {
        const now = new Date();
        const localNow = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
        deadlineInput.min = localNow;
    }

    $('#supervisorCreateTaskForm').on('submit', function(e) {
        e.preventDefault();
        $.post('supervisor_controller.php', $(this).serialize(), function(res) {
            var alertClass = res.status === 'success' ? 'alert-success' : 'alert-danger';
            $('#taskAlert').html('<div class="alert ' + alertClass + '">' + res.message + '</div>');
            if (res.status === 'success') {
                $('#supervisorCreateTaskForm')[0].reset();
            }
        }, 'json').fail(function() {
            $('#taskAlert').html('<div class="alert alert-danger">Server error. Please try again.</div>');
        });
    });
});
</script>

<?php include '../includes/footer_glass.php'; ?>