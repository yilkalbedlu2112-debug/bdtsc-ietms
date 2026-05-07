<?php
/**
 * Shift Leader — Assign Tasks View
 *
 * Shows tasks from TWO sources:
 *  1. maintenance_requests assigned TO this Shift Leader (by Supervisor/Manager) → re-delegate to Employee
 *  2. maintenance_requests sent to this department (sender_dept_id) still Pending → assign to Employee
 *  3. tasks table — Pending/Redo for this department (legacy support)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Shift Leader') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$user_id   = (int) $_SESSION['user_id'];
$dept_id   = isset($_SESSION['dept_id']) ? (int) $_SESSION['dept_id'] : 0;
$dept_name = trim((string) ($_SESSION['dept_name'] ?? ''));

if ($dept_name === '' && $dept_id > 0) {
    $dn = $pdo->prepare('SELECT dept_name FROM departments WHERE id = ?');
    $dn->execute([$dept_id]);
    $dept_name = (string) ($dn->fetchColumn() ?: '');
}

// ── 1. Tasks from maintenance_requests assigned TO this Shift Leader ────
//    (Supervisor/Manager assigned these to the Shift Leader to delegate down)
$mr_assigned_to_me = [];
if ($dept_id > 0) {
    $stmt1 = $pdo->prepare(
        "SELECT mr.id, 
                COALESCE(mr.title, mr.machine_name, 'Untitled') AS title,
                mr.issue_description AS description,
                mr.priority, mr.status, mr.task_type, mr.created_at,
                u.full_name AS created_by_name,
                u.user_role AS created_by_role
         FROM maintenance_requests mr
         LEFT JOIN users u ON mr.user_id = u.id
         WHERE mr.assigned_to = ? 
           AND mr.status IN ('Assigned','Pending','Pending Approval','Approved')
         ORDER BY mr.created_at DESC"
    );
    $stmt1->execute([$user_id]);
    $mr_assigned_to_me = $stmt1->fetchAll(PDO::FETCH_ASSOC);
}

// ── 2. Tasks from maintenance_requests sent to this department (unassigned) ─
$mr_dept_pending = [];
if ($dept_id > 0) {
    $stmt2 = $pdo->prepare(
        "SELECT mr.id,
                COALESCE(mr.title, mr.machine_name, 'Untitled') AS title,
                mr.issue_description AS description,
                mr.priority, mr.status, mr.task_type, mr.created_at,
                u.full_name AS created_by_name,
                u.user_role AS created_by_role
         FROM maintenance_requests mr
         LEFT JOIN users u ON mr.user_id = u.id
         WHERE (mr.sender_dept_id = ? OR mr.receiver_dept_id = ?)
           AND mr.status = 'Pending'
           AND (mr.assigned_to IS NULL OR mr.assigned_to = 0)
         ORDER BY mr.created_at DESC"
    );
    $stmt2->execute([$dept_id, $dept_id]);
    $mr_dept_pending = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}

// ── 3. Tasks from tasks table (legacy — Pending/Redo for this department) ──
$hasTasksDeptCol = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'assigned_to_dept'"
)->fetchColumn() > 0;

$titleCol = ((int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'title'"
)->fetchColumn() > 0) ? 'title' : 'task_title';

$tasks_table_items = [];
if ($hasTasksDeptCol && $dept_id > 0) {
    $stmt3 = $pdo->prepare(
        "SELECT id, `{$titleCol}` AS title, priority, status, 'tasks' AS source_table
         FROM tasks
         WHERE assigned_to_dept = ? AND status IN ('Pending','Redo')
         ORDER BY id DESC"
    );
    $stmt3->execute([$dept_id]);
    $tasks_table_items = $stmt3->fetchAll(PDO::FETCH_ASSOC);
}

// ── Employee options (same department) ──────────────────────────────
$employee_options = [];
if ($dept_id > 0) {
    $emp_stmt = $pdo->prepare(
        "SELECT id, full_name FROM users
         WHERE status = 'Active' AND user_role = 'Employee' AND dept_id = ?
         ORDER BY full_name"
    );
    $emp_stmt->execute([$dept_id]);
    $employee_options = $emp_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Flash messages ──────────────────────────────────────────────────
$flash_ok  = isset($_GET['flash']) && $_GET['flash'] === 'success';
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
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-person-plus text-primary me-2"></i>Assign Tasks to Employees</h1>
            <p class="text-muted small mb-0">
                Tasks from your Supervisor/Manager and department queue for:
                <strong><?php echo htmlspecialchars($dept_name !== '' ? $dept_name : ('Department #' . ($dept_id ?: '?')), ENT_QUOTES, 'UTF-8'); ?></strong>
            </p>
        </div>
    </div>

    <?php if ($flash_ok): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <?php echo $flash_msg !== '' ? htmlspecialchars($flash_msg, ENT_QUOTES, 'UTF-8') : 'Task assigned successfully.'; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($flash_err && $flash_msg !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <?php echo htmlspecialchars($flash_msg, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- SECTION 1: Tasks assigned TO this Shift Leader by superiors   -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white fw-semibold py-3">
            <i class="bi bi-inbox me-2"></i>Tasks Assigned to You
            <?php if (count($mr_assigned_to_me) > 0): ?>
                <span class="badge bg-light text-primary ms-2"><?php echo count($mr_assigned_to_me); ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($mr_assigned_to_me)): ?>
                <p class="text-muted p-4 mb-0"><i class="bi bi-check-circle me-1"></i>No tasks assigned to you by Supervisor/Manager at this time.</p>
            <?php elseif (empty($employee_options)): ?>
                <p class="text-warning p-4 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>No active Employees found in your department to assign tasks to.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Title / Subject</th>
                            <th>From</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th style="min-width: 320px;">Assign to Employee</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mr_assigned_to_me as $task): ?>
                        <tr>
                            <td>#<?php echo (int) $task['id']; ?></td>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars((string) $task['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars(mb_substr((string) ($task['description'] ?? ''), 0, 60), ENT_QUOTES, 'UTF-8'); ?>…</small>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars((string) ($task['created_by_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    <br><span class="badge bg-light text-dark"><?php echo htmlspecialchars((string) ($task['created_by_role'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                </small>
                            </td>
                            <td>
                                <?php
                                $pColor = match($task['priority'] ?? '') {
                                    'Emergency' => 'danger',
                                    'Urgent'    => 'danger',
                                    'High'      => 'warning',
                                    default     => 'info',
                                };
                                ?>
                                <span class="badge bg-<?php echo $pColor; ?>"><?php echo htmlspecialchars((string) ($task['priority'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars((string) ($task['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td>
                                <form method="post" action="assign_handler.php" class="d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-end">
                                    <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
                                    <input type="hidden" name="source" value="maintenance_requests">
                                    <select name="employee_id" class="form-select form-select-sm flex-grow-1" required>
                                        <option value="">— Select employee —</option>
                                        <?php foreach ($employee_options as $emp): ?>
                                            <option value="<?php echo (int) $emp['id']; ?>">
                                                <?php echo htmlspecialchars((string) $emp['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm text-nowrap">
                                        <i class="bi bi-person-plus me-1"></i>Assign
                                    </button>
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

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- SECTION 2: Unassigned department tasks (maintenance_requests) -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <?php if (!empty($mr_dept_pending)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold py-3">
            <i class="bi bi-collection text-warning me-2"></i>Unassigned Department Tasks
            <span class="badge bg-warning text-dark ms-2"><?php echo count($mr_dept_pending); ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($employee_options)): ?>
                <p class="text-warning p-4 mb-0">No active Employees found in your department.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Title / Subject</th>
                            <th>Type</th>
                            <th>Priority</th>
                            <th style="min-width: 320px;">Assign to Employee</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mr_dept_pending as $task): ?>
                        <tr>
                            <td>#<?php echo (int) $task['id']; ?></td>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars((string) $task['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars(mb_substr((string) ($task['description'] ?? ''), 0, 60), ENT_QUOTES, 'UTF-8'); ?>…</small>
                            </td>
                            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars((string) ($task['task_type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td>
                                <?php
                                $pColor = match($task['priority'] ?? '') {
                                    'Emergency' => 'danger',
                                    'Urgent'    => 'danger',
                                    'High'      => 'warning',
                                    default     => 'info',
                                };
                                ?>
                                <span class="badge bg-<?php echo $pColor; ?>"><?php echo htmlspecialchars((string) ($task['priority'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <form method="post" action="assign_handler.php" class="d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-end">
                                    <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
                                    <input type="hidden" name="source" value="maintenance_requests">
                                    <select name="employee_id" class="form-select form-select-sm flex-grow-1" required>
                                        <option value="">— Select employee —</option>
                                        <?php foreach ($employee_options as $emp): ?>
                                            <option value="<?php echo (int) $emp['id']; ?>">
                                                <?php echo htmlspecialchars((string) $emp['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-outline-primary btn-sm text-nowrap">
                                        <i class="bi bi-person-plus me-1"></i>Assign
                                    </button>
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
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- SECTION 3: Tasks table items (legacy — Pending/Redo)          -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <?php if (!empty($tasks_table_items)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold py-3">
            <i class="bi bi-list-task text-secondary me-2"></i>Other Pending Tasks
            <span class="badge bg-secondary ms-2"><?php echo count($tasks_table_items); ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($employee_options)): ?>
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
                        <?php foreach ($tasks_table_items as $task): ?>
                        <tr>
                            <td><?php echo (int) $task['id']; ?></td>
                            <td><?php echo htmlspecialchars((string) ($task['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars((string) ($task['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo htmlspecialchars((string) ($task['priority'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <form method="post" action="assign_handler.php" class="d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-end">
                                    <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
                                    <input type="hidden" name="source" value="tasks">
                                    <select name="employee_id" class="form-select form-select-sm flex-grow-1" required>
                                        <option value="">— Select employee —</option>
                                        <?php foreach ($employee_options as $emp): ?>
                                            <option value="<?php echo (int) $emp['id']; ?>">
                                                <?php echo htmlspecialchars((string) $emp['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm text-nowrap">Assign</button>
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
    <?php endif; ?>

    <!-- Empty state when nothing to show -->
    <?php if (empty($mr_assigned_to_me) && empty($mr_dept_pending) && empty($tasks_table_items)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-clipboard-check fs-1 text-muted mb-3 d-block"></i>
            <h5 class="text-muted">No pending tasks</h5>
            <p class="text-muted small">There are no tasks awaiting assignment in your department.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_glass.php'; ?>
