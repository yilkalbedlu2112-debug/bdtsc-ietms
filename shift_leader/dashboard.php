<?php
/**
 * Shift Leader dashboard: department-aware KPIs, task summary, delegation, notifications.
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

$production_group = ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'];
$technical_quality_group = ['Engineering', 'Quality Assurance'];
$finance_resource_group = ['Finance Department', 'Procurement / Property'];
$admin_strategy_group = ['General Management', 'Human Resource (HR)', 'Planning', 'Strategy & Innovation', 'System Research & Development', 'Legal Service', 'Audit & Inspection'];

$dept_ui_profile = 'default';
if (in_array($dept_name, $production_group, true)) {
    $dept_ui_profile = 'production';
} elseif (in_array($dept_name, $technical_quality_group, true)) {
    $dept_ui_profile = 'technical';
} elseif (in_array($dept_name, $finance_resource_group, true)) {
    $dept_ui_profile = 'finance';
} elseif (in_array($dept_name, $admin_strategy_group, true)) {
    $dept_ui_profile = 'admin';
}

switch ($dept_ui_profile) {
    case 'production':
        $card_names = ['Active machines', 'Production efficiency'];
        $table_headers = ['ID', 'Work item', 'Status', 'Priority', 'Assign to operator'];
        break;
    case 'technical':
        $card_names = ['Maintenance requests', 'System uptime'];
        $table_headers = ['ID', 'Request / task', 'Status', 'Priority', 'Assign to technician'];
        break;
    case 'finance':
        $card_names = ['Inventory status', 'Vouchers'];
        $table_headers = ['ID', 'Reference', 'Status', 'Priority', 'Assign to staff'];
        break;
    case 'admin':
        $card_names = ['KPI score', 'Case progress'];
        $table_headers = ['ID', 'Subject', 'Status', 'Priority', 'Assign to owner'];
        break;
    default:
        $card_names = ['Department focus', 'Throughput'];
        $table_headers = ['ID', 'Title', 'Status', 'Priority', 'Assign to employee'];
        break;
}

$hasMrTable = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_requests'"
)->fetchColumn() > 0;

$hasTasksDeptCol = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'assigned_to_dept'"
)->fetchColumn() > 0;

$metric_left = '—';
$metric_right = '—';
if ($dept_id > 0 && $hasMrTable) {
    try {
        if ($dept_ui_profile === 'production') {
            $q1 = $pdo->prepare(
                "SELECT COUNT(DISTINCT NULLIF(TRIM(machine_name), '')) FROM maintenance_requests
                 WHERE dept_id = ? AND status <> 'Completed'"
            );
            $q1->execute([$dept_id]);
            $metric_left = (string) (int) $q1->fetchColumn();

            $q2 = $pdo->prepare(
                "SELECT ROUND(100 * SUM(status = 'Completed') / NULLIF(COUNT(*), 0), 1) AS pct
                 FROM maintenance_requests WHERE dept_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            $q2->execute([$dept_id]);
            $pct = $q2->fetchColumn();
            $metric_right = $pct !== null ? $pct . '% (30d)' : '—';
        } elseif ($dept_ui_profile === 'technical') {
            $q1 = $pdo->prepare(
                "SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND status NOT IN ('Completed','Cancelled')"
            );
            $q1->execute([$dept_id]);
            $open = (int) $q1->fetchColumn();
            $metric_left = (string) $open;

            $q2 = $pdo->prepare(
                "SELECT ROUND(100 * SUM(status = 'Completed') / NULLIF(COUNT(*), 0), 1) FROM maintenance_requests
                 WHERE dept_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)"
            );
            $q2->execute([$dept_id]);
            $up = $q2->fetchColumn();
            $metric_right = $up !== null ? (string) (float) $up . '% resolved (14d)' : '—';
        } elseif ($dept_ui_profile === 'finance') {
            $q1 = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND status NOT IN ('Completed')");
            $q1->execute([$dept_id]);
            $metric_left = (string) (int) $q1->fetchColumn() . ' open items';

            $q2 = $pdo->prepare(
                "SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND status = 'Completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            $q2->execute([$dept_id]);
            $metric_right = (string) (int) $q2->fetchColumn() . ' cleared (30d)';
        } elseif ($dept_ui_profile === 'admin') {
            if ($hasTasksDeptCol) {
                $qa = $pdo->prepare(
                    "SELECT COUNT(*) FROM tasks WHERE assigned_to_dept = ? AND status IN ('Under Review','Grievance','Pending')"
                );
                $qa->execute([$dept_id]);
                $metric_left = (string) (int) $qa->fetchColumn() . ' open cases';

                $qb = $pdo->prepare(
                    "SELECT ROUND(100 * SUM(status = 'Completed') / NULLIF(COUNT(*), 0), 1) FROM tasks
                     WHERE assigned_to_dept = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
                );
                $qb->execute([$dept_id]);
                $cp = $qb->fetchColumn();
                $metric_right = $cp !== null ? (string) (float) $cp . '% closed (30d)' : '—';
            } else {
                $metric_left = $dept_name !== '' ? $dept_name : '—';
                $metric_right = 'Configure tasks.assigned_to_dept';
            }
        } else {
            $q1 = $pdo->prepare(
                "SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND status NOT IN ('Completed')"
            );
            $q1->execute([$dept_id]);
            $metric_left = (string) (int) $q1->fetchColumn() . ' open requests';

            $q2 = $pdo->prepare(
                "SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND status = 'Completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            $q2->execute([$dept_id]);
            $metric_right = (string) (int) $q2->fetchColumn() . ' completed (30d)';
        }
    } catch (Throwable $e) {
        $metric_left = '—';
        $metric_right = '—';
    }
}

$titleCol = ((int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'title'"
)->fetchColumn() > 0) ? 'title' : 'task_title';

$taskStatsSql = "SELECT
    COALESCE(SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END), 0) AS pending_count,
    COALESCE(SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END), 0) AS progress_count,
    COALESCE(SUM(CASE WHEN status = 'Under Review' THEN 1 ELSE 0 END), 0) AS review_count,
    COALESCE(SUM(CASE WHEN status = 'Grievance' THEN 1 ELSE 0 END), 0) AS grievance_count,
    COALESCE(SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END), 0) AS completed_count
    FROM tasks";
$taskStatsParams = [];
if ($hasTasksDeptCol && $dept_id > 0) {
    $taskStatsSql .= ' WHERE assigned_to_dept = ?';
    $taskStatsParams[] = $dept_id;
}
if ($taskStatsParams) {
    $stats_stmt = $pdo->prepare($taskStatsSql);
    $stats_stmt->execute($taskStatsParams);
} else {
    $stats_stmt = $pdo->query($taskStatsSql);
}
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

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

$notifHasIsRead = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'is_read'"
)->fetchColumn() > 0;

if ($notifHasIsRead) {
    $notif_stmt = $pdo->prepare(
        'SELECT id, message, created_at FROM notifications
         WHERE user_id = ? AND is_read = 0
         ORDER BY created_at DESC'
    );
} else {
    $notif_stmt = $pdo->prepare(
        'SELECT id, message, created_at FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT 50'
    );
}
$notif_stmt->execute([$user_id]);
$notifications = $notif_stmt->fetchAll();

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
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h1 class="h4 fw-bold mb-1"><i class="bi bi-speedometer2 text-primary me-2"></i>Shift Leader dashboard</h1>
                    <p class="text-muted mb-0">
                        <?php echo htmlspecialchars($dept_name !== '' ? $dept_name : 'Department', ENT_QUOTES, 'UTF-8'); ?>
                        <span class="badge bg-light text-dark ms-1"><?php echo htmlspecialchars($dept_ui_profile, ENT_QUOTES, 'UTF-8'); ?> profile</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($flash_ok): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Task assigned successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($flash_err && $flash_msg !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash_msg, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <h2 class="h6 text-muted text-uppercase mb-2">Department insights</h2>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small"><?php echo htmlspecialchars($card_names[0], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="fs-3 fw-bold text-primary"><?php echo htmlspecialchars($metric_left, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small"><?php echo htmlspecialchars($card_names[1], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="fs-3 fw-bold text-success"><?php echo htmlspecialchars($metric_right, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h6 text-muted text-uppercase mb-2">Task summary</h2>
    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['label' => 'Pending', 'key' => 'pending_count', 'color' => 'primary'],
            ['label' => 'In Progress', 'key' => 'progress_count', 'color' => 'warning'],
            ['label' => 'Under Review', 'key' => 'review_count', 'color' => 'info'],
            ['label' => 'Grievance', 'key' => 'grievance_count', 'color' => 'danger'],
            ['label' => 'Completed', 'key' => 'completed_count', 'color' => 'success'],
        ];
        foreach ($cards as $c):
            $val = (int) ($stats[$c['key']] ?? 0);
        ?>
        <div class="col-6 col-md-4 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small"><?php echo htmlspecialchars($c['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="fs-3 fw-bold text-<?php echo htmlspecialchars($c['color'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo $val; ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold py-3">Delegation — Pending &amp; redo</div>
                <div class="card-body p-0">
                    <?php if (!$hasTasksDeptCol): ?>
                        <p class="text-warning p-4 mb-0">Delegation requires the <code>tasks.assigned_to_dept</code> column so tasks can be matched to your department.</p>
                    <?php elseif ($dept_id <= 0): ?>
                        <p class="text-warning p-4 mb-0">Your profile must have a department (<code>dept_id</code>) to list and assign delegation tasks.</p>
                    <?php elseif (empty($delegation_tasks)): ?>
                        <p class="text-muted p-4 mb-0">No tasks in Pending or Redo for your department.</p>
                    <?php elseif (empty($employee_options)): ?>
                        <p class="text-warning p-4 mb-0">No employees found in your department. Add users with role Employee.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo htmlspecialchars($table_headers[0], ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars($table_headers[1], ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars($table_headers[2], ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars($table_headers[3], ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th style="min-width: 260px;"><?php echo htmlspecialchars($table_headers[4], ENT_QUOTES, 'UTF-8'); ?></th>
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
                                        <form method="post" action="assign_task.php" class="d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-end">
                                            <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
                                            <select name="employee_id" class="form-select form-select-sm" required>
                                                <option value="">— Employee —</option>
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
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white fw-semibold py-3">
                    Notifications<?php echo $notifHasIsRead ? ' (unread)' : ''; ?>
                </div>
                <div class="card-body p-0" style="max-height: 440px; overflow-y: auto;">
                    <?php if (empty($notifications)): ?>
                        <p class="text-muted p-3 mb-0 small">No notifications.</p>
                    <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                        <div class="border-bottom px-3 py-2">
                            <small class="text-muted"><?php echo htmlspecialchars((string) ($n['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                            <p class="mb-0 small"><?php echo htmlspecialchars((string) ($n['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_glass.php'; ?>
