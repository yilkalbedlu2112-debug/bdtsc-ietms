<?php
/**
 * Dedicated view: delegate Pending/Redo tasks in the leader's department (POST → assign_handler.php).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Shift Leader') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$user_id = (int) $_SESSION['user_id'];
$dept_id = isset($_SESSION['dept_id']) ? (int) $_SESSION['dept_id'] : 0;
$dept_name = trim((string) ($_SESSION['dept_name'] ?? ''));

if ($dept_name === '' && $dept_id > 0) {
    $dn = $pdo->prepare('SELECT dept_name FROM departments WHERE id = ?');
    $dn->execute([$dept_id]);
    $dept_name = (string) ($dn->fetchColumn() ?: '');
}

$hasTasksDeptCol = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'assigned_to_dept'"
)->fetchColumn() > 0;

$titleCol = ((int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'title'"
)->fetchColumn() > 0) ? 'title' : 'task_title';

$delegation_tasks = [];
if ($hasTasksDeptCol && $dept_id > 0) {
    $delegation_stmt = $pdo->prepare(
        "SELECT id, `{$titleCol}` AS title, priority, status FROM tasks
         WHERE assigned_to_dept = ? AND status IN ('Pending','Redo')
         ORDER BY id DESC"
    );
    $delegation_stmt->execute([$dept_id]);
    $delegation_tasks = $delegation_stmt->fetchAll();
}

$employee_options = [];
if ($dept_id > 0) {
    $emp_stmt = $pdo->prepare(
        "SELECT id, full_name FROM users
         WHERE status = 'Active' AND user_role = 'Employee' AND dept_id = ?
         ORDER BY full_name"
    );
    $emp_stmt->execute([$dept_id]);
    $employee_options = $emp_stmt->fetchAll();
}

$flash_ok = isset($_GET['flash']) && $_GET['flash'] === 'success';
$flash_err = isset($_GET['flash']) && $_GET['flash'] === 'error';
$flash_msg = isset($_GET['msg']) ? (string) $_GET['msg'] : '';

include __DIR__ . '/../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="mb-3">
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-person-plus text-primary me-2"></i>Assign tasks</h1>
            <p class="text-muted small mb-0">
                Pending and redo tasks scoped to your department:
                <strong><?php echo htmlspecialchars($dept_name !== '' ? $dept_name : ('Department #' . ($dept_id ?: '?')), ENT_QUOTES, 'UTF-8'); ?></strong>.
            </p>
        </div>
    </div>

    <?php if ($flash_ok): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            Task assigned successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($flash_err && $flash_msg !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <?php echo htmlspecialchars($flash_msg, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold py-3">Delegation queue</div>
        <div class="card-body p-0">
            <?php if (!$hasTasksDeptCol): ?>
                <p class="text-warning p-4 mb-0">The <code>tasks.assigned_to_dept</code> column is required for this page.</p>
            <?php elseif ($dept_id <= 0): ?>
                <p class="text-warning p-4 mb-0">Your profile must include a department (<code>dept_id</code>).</p>
            <?php elseif (empty($delegation_tasks)): ?>
                <p class="text-muted p-4 mb-0">No tasks in Pending or Redo for your department.</p>
            <?php elseif (empty($employee_options)): ?>
                <p class="text-warning p-4 mb-0">No active Employees found in your department.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th style="min-width: 320px;">Assign</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($delegation_tasks as $task): ?>
                        <tr>
                            <td><?php echo (int) $task['id']; ?></td>
                            <td><?php echo htmlspecialchars((string) ($task['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars((string) ($task['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo htmlspecialchars((string) ($task['priority'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <form method="post" action="assign_handler.php" class="d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-end">
                                    <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
                                    <select name="employee_id" class="form-select form-select-sm flex-grow-1" required>
                                        <option value="">— Select employee —</option>
                                        <?php foreach ($employee_options as $emp): ?>
                                            <option value="<?php echo (int) $emp['id']; ?>">
                                                <?php echo htmlspecialchars((string) $emp['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm text-nowrap">Assign Task</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_glass.php'; ?>
