<?php
/**
 * Shift Leader dashboard: department-aware KPIs, task summary, notifications.
 * Delegation table removed — use the dedicated "Assign Tasks" page instead.
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

// ── Department grouping ─────────────────────────────────────────────
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
        break;
    case 'technical':
        $card_names = ['Maintenance requests', 'System uptime'];
        break;
    case 'finance':
        $card_names = ['Inventory status', 'Vouchers'];
        break;
    case 'admin':
        $card_names = ['KPI score', 'Case progress'];
        break;
    default:
        $card_names = ['Department focus', 'Throughput'];
        break;
}

// ── Check table / column existence ──────────────────────────────────
$hasMrTable = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_requests'"
)->fetchColumn() > 0;

$hasTasksDeptCol = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'assigned_to_dept'"
)->fetchColumn() > 0;

// ── Department insight metrics (from maintenance_requests, filtered by dept_id) ──
$metric_left = '—';
$metric_right = '—';
if ($dept_id > 0 && $hasMrTable) {
    try {
        if ($dept_ui_profile === 'production') {
            // Active machines = distinct machines with non-completed requests in this dept
            $q1 = $pdo->prepare(
                "SELECT COUNT(DISTINCT NULLIF(TRIM(machine_name), '')) FROM maintenance_requests
                 WHERE dept_id = ? AND status <> 'Completed'"
            );
            $q1->execute([$dept_id]);
            $down_machines = (int) $q1->fetchColumn();

            // Assume a baseline of 30 machines per production dept
            $total_machines = 30;
            $active_machines = $total_machines - $down_machines;
            $metric_left = $active_machines . '/' . $total_machines;

            // Production efficiency: % completed maintenance in last 30 days
            $q2 = $pdo->prepare(
                "SELECT ROUND(100 * SUM(status = 'Completed') / NULLIF(COUNT(*), 0), 1) AS pct
                 FROM maintenance_requests WHERE dept_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            $q2->execute([$dept_id]);
            $pct = $q2->fetchColumn();
            $metric_right = $pct !== null ? $pct . '%' : '—';

        } elseif ($dept_ui_profile === 'technical') {
            $q1 = $pdo->prepare(
                "SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND status NOT IN ('Completed','Cancelled')"
            );
            $q1->execute([$dept_id]);
            $metric_left = (string) (int) $q1->fetchColumn();

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

// ── Task summary stats (from maintenance_requests — the real task source) ──
$stats = [
    'pending_count'   => 0,
    'assigned_count'  => 0,
    'progress_count'  => 0,
    'review_count'    => 0,
    'completed_count' => 0,
];
if ($dept_id > 0) {
    $stats_stmt = $pdo->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN status IN ('Pending','Pending Approval','Approved') THEN 1 ELSE 0 END), 0) AS pending_count,
            COALESCE(SUM(CASE WHEN status = 'Assigned' THEN 1 ELSE 0 END), 0) AS assigned_count,
            COALESCE(SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END), 0) AS progress_count,
            COALESCE(SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END), 0) AS review_count,
            COALESCE(SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END), 0) AS completed_count
         FROM maintenance_requests
         WHERE sender_dept_id = ? OR receiver_dept_id = ? OR dept_id = ?"
    );
    $stats_stmt->execute([$dept_id, $dept_id, $dept_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC) ?: $stats;
}

// ── Pending delegation count (maintenance_requests assigned to me + dept pending) ──
$pending_delegation = 0;
if ($dept_id > 0) {
    // Tasks assigned TO this shift leader by supervisor/manager
    $pd1 = $pdo->prepare(
        "SELECT COUNT(*) FROM maintenance_requests
         WHERE assigned_to = ? AND status IN ('Assigned','Pending','Pending Approval','Approved')"
    );
    $pd1->execute([$user_id]);
    $pending_delegation += (int) $pd1->fetchColumn();

    // Unassigned department tasks
    $pd2 = $pdo->prepare(
        "SELECT COUNT(*) FROM maintenance_requests
         WHERE (sender_dept_id = ? OR receiver_dept_id = ?)
           AND status = 'Pending'
           AND (assigned_to IS NULL OR assigned_to = 0)"
    );
    $pd2->execute([$dept_id, $dept_id]);
    $pending_delegation += (int) $pd2->fetchColumn();

    // Also add tasks table pending items
    if ($hasTasksDeptCol) {
        $pd3 = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to_dept = ? AND status IN ('Pending','Redo')");
        $pd3->execute([$dept_id]);
        $pending_delegation += (int) $pd3->fetchColumn();
    }
}

// ── Recent assigned tasks from maintenance_requests (last 5) ────────
$recent_assigned = [];
if ($dept_id > 0) {
    $ra = $pdo->prepare(
        "SELECT mr.id,
                COALESCE(mr.title, mr.machine_name, 'Untitled') AS title,
                mr.status, mr.priority,
                u.full_name AS employee_name
         FROM maintenance_requests mr
         LEFT JOIN users u ON mr.assigned_to = u.id
         WHERE (mr.sender_dept_id = ? OR mr.receiver_dept_id = ?)
           AND mr.status = 'Assigned'
         ORDER BY mr.updated_at DESC
         LIMIT 5"
    );
    $ra->execute([$dept_id, $dept_id]);
    $recent_assigned = $ra->fetchAll(PDO::FETCH_ASSOC);
}

// ── Notifications ───────────────────────────────────────────────────
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

include __DIR__ . '/../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h1 class="h4 fw-bold mb-1">
                            <i class="bi bi-speedometer2 text-primary me-2"></i>Shift Leader Dashboard
                        </h1>
                        <p class="text-muted mb-0">
                            <?php echo htmlspecialchars($dept_name !== '' ? $dept_name : 'Department', ENT_QUOTES, 'UTF-8'); ?>
                            <span class="badge bg-light text-dark ms-1"><?php echo htmlspecialchars($dept_ui_profile, ENT_QUOTES, 'UTF-8'); ?> profile</span>
                        </p>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="assign_task_view.php" class="btn btn-primary shadow-sm">
                            <i class="bi bi-person-plus me-1"></i>Assign Tasks
                            <?php if ($pending_delegation > 0): ?>
                                <span class="badge bg-danger ms-1"><?php echo $pending_delegation; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Insight Metrics -->
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

    <!-- Task Status Summary Cards -->
    <h2 class="h6 text-muted text-uppercase mb-2">Task summary</h2>
    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['label' => 'Pending',     'key' => 'pending_count',   'color' => 'primary', 'icon' => 'bi-hourglass-split'],
            ['label' => 'Assigned',    'key' => 'assigned_count',  'color' => 'info',    'icon' => 'bi-person-check'],
            ['label' => 'In Progress', 'key' => 'progress_count',  'color' => 'warning',  'icon' => 'bi-arrow-repeat'],
            ['label' => 'Under Review','key' => 'review_count',    'color' => 'secondary','icon' => 'bi-eye'],
            ['label' => 'Completed',   'key' => 'completed_count', 'color' => 'success',  'icon' => 'bi-check-circle'],
        ];
        foreach ($cards as $c):
            $val = (int) ($stats[$c['key']] ?? 0);
        ?>
        <div class="col-6 col-md-4 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi <?php echo $c['icon']; ?> text-<?php echo $c['color']; ?> fs-4 mb-1 d-block"></i>
                    <div class="text-muted small"><?php echo htmlspecialchars($c['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="fs-3 fw-bold text-<?php echo htmlspecialchars($c['color'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo $val; ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Two-column: Recent Assignments + Notifications -->
    <div class="row g-4">
        <!-- Recent Assignments -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-history text-primary me-2"></i>Recently assigned tasks</span>
                    <a href="assign_task_view.php" class="btn btn-outline-primary btn-sm">
                        View all <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_assigned)): ?>
                        <p class="text-muted p-4 mb-0">No recently assigned tasks.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Assigned to</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_assigned as $ra): ?>
                                <tr>
                                    <td>#<?php echo (int) $ra['id']; ?></td>
                                    <td><?php echo htmlspecialchars((string) ($ra['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <i class="bi bi-person-fill text-primary me-1"></i>
                                        <?php echo htmlspecialchars((string) ($ra['employee_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $pColor = match($ra['priority'] ?? '') {
                                            'Urgent' => 'danger',
                                            'High'   => 'warning',
                                            'Normal' => 'info',
                                            default  => 'secondary',
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $pColor; ?>"><?php echo htmlspecialchars((string) ($ra['priority'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td><span class="badge bg-info"><?php echo htmlspecialchars((string) ($ra['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white fw-semibold py-3">
                    <i class="bi bi-bell me-2"></i>Notifications<?php echo $notifHasIsRead ? ' (unread)' : ''; ?>
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
