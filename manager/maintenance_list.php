<?php
// manager/maintenance_list.php
// Full history log of tasks/requests originating FROM this department.
session_start();
require_once '../includes/db.php';

// ── Fix 1: Role guard now includes Engineering Manager ─────────────────────
if (!isset($_SESSION['user_role']) ||
    !in_array($_SESSION['user_role'], ['Department Manager', 'Engineering Manager'], true)) {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id   = (int)($_SESSION['dept_id']   ?? 0);
$user_role = $_SESSION['user_role'];
$dept_name = trim($_SESSION['dept_name']  ?? 'Department');
$is_eng_manager = ($user_role === 'Engineering Manager');

// ── Filters ────────────────────────────────────────────────────────────────
$status_filter   = $_GET['status']       ?? '';
$priority_filter = $_GET['priority']     ?? '';
$type_filter     = $_GET['request_type'] ?? '';

// ── Fix 2: Query now uses sender_dept_id (cross-dept schema) ───────────────
// Engineering Manager sees ALL requests system-wide; others see their own.
if ($is_eng_manager) {
    $base_condition = "1=1"; // see everything
    $params = [];
} else {
    $base_condition = "m.sender_dept_id = ?";
    $params = [$dept_id];
}

if ($status_filter !== '') {
    $base_condition .= " AND m.status = ?";
    $params[] = $status_filter;
}
if ($priority_filter !== '') {
    $base_condition .= " AND m.priority = ?";
    $params[] = $priority_filter;
}
if ($type_filter !== '') {
    $base_condition .= " AND m.request_type = ?";
    $params[] = $type_filter;
}

$query = "SELECT m.*,
                 u.full_name      AS requester,
                 a.full_name      AS technician,
                 sd.dept_name     AS sender_dept_name,
                 rd.dept_name     AS receiver_dept_name
          FROM   maintenance_requests m
          LEFT JOIN users u         ON m.user_id          = u.id
          LEFT JOIN users a         ON m.assigned_to       = a.id
          LEFT JOIN departments sd  ON m.sender_dept_id    = sd.id
          LEFT JOIN departments rd  ON m.receiver_dept_id  = rd.id
          WHERE  $base_condition
          ORDER BY m.created_at DESC
          LIMIT 200";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// ── Summary counts ─────────────────────────────────────────────────────────
$cnt_col   = $is_eng_manager ? "1=1" : "sender_dept_id = $dept_id";
$cnt_stmt  = $pdo->query(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status='Pending'      THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status='In Progress'  THEN 1 ELSE 0 END) AS in_prog,
        SUM(CASE WHEN status='Completed'    THEN 1 ELSE 0 END) AS done
     FROM maintenance_requests WHERE $cnt_col");
$counts = $cnt_stmt->fetch();

include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <h2 class="h4 fw-bold mb-1">
                <i class="bi bi-tools text-primary me-2"></i>
                <?php echo $is_eng_manager ? 'System-Wide' : htmlspecialchars($dept_name); ?>
                Maintenance &amp; Task Log
            </h2>
            <p class="text-muted small mb-0">
                All tasks and requests
                <?php echo $is_eng_manager ? 'across all departments' : 'sent from your department'; ?>.
            </p>
        </div>
        <a href="create_task.php" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> New Request
        </a>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-primary border-4">
                <div class="h4 fw-bold text-primary mb-0"><?php echo (int)$counts['total']; ?></div>
                <div class="small text-muted fw-semibold">Total</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-warning border-4">
                <div class="h4 fw-bold text-warning mb-0"><?php echo (int)$counts['pending']; ?></div>
                <div class="small text-muted fw-semibold">Pending</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-info border-4">
                <div class="h4 fw-bold text-info mb-0"><?php echo (int)$counts['in_prog']; ?></div>
                <div class="small text-muted fw-semibold">In Progress</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-success border-4">
                <div class="h4 fw-bold text-success mb-0"><?php echo (int)$counts['done']; ?></div>
                <div class="small text-muted fw-semibold">Completed</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card glass-card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-bold mb-1">STATUS</label>
                    <select name="status" class="form-select form-select-sm bg-light border-0" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <?php foreach (['Pending','In Progress','Completed','Rejected'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo ($status_filter === $s) ? 'selected' : ''; ?>>
                            <?php echo $s; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-bold mb-1">PRIORITY</label>
                    <select name="priority" class="form-select form-select-sm bg-light border-0" onchange="this.form.submit()">
                        <option value="">All Priorities</option>
                        <?php foreach (['Emergency','Urgent','High','Normal'] as $p): ?>
                        <option value="<?php echo $p; ?>" <?php echo ($priority_filter === $p) ? 'selected' : ''; ?>>
                            <?php echo $p; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-bold mb-1">REQUEST TYPE</label>
                    <select name="request_type" class="form-select form-select-sm bg-light border-0" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <?php foreach (['Repair','Manpower','Resource','Legal','Maintenance','Administrative','Other'] as $rt): ?>
                        <option value="<?php echo $rt; ?>" <?php echo ($type_filter === $rt) ? 'selected' : ''; ?>>
                            <?php echo $rt; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <a href="maintenance_list.php" class="btn btn-outline-secondary btn-sm rounded-pill w-100">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tasks Table -->
    <div class="card glass-card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 pb-2 px-4">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-table me-2 text-primary"></i>Task Records
                <span class="badge bg-primary-subtle text-primary ms-2 px-2 py-1 rounded-pill">
                    <?php echo count($tasks); ?> records
                </span>
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="maintenanceTable">
                    <thead class="table-light">
                        <tr class="text-uppercase text-muted" style="font-size:0.72rem; letter-spacing:.5px;">
                            <th class="ps-4">#</th>
                            <th>Subject / Machine</th>
                            <?php if ($is_eng_manager): ?>
                            <th>From Dept</th>
                            <?php endif; ?>
                            <th>To Dept</th>
                            <th>Type</th>
                            <th>Assigned To</th>
                            <th>Due Date</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th class="pe-4">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No tasks found matching the current filters.
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php foreach ($tasks as $task): ?>
                        <?php
                            $p_class = match($task['priority']) {
                                'Emergency', 'Urgent' => 'bg-danger',
                                'High'                => 'bg-warning text-dark',
                                default               => 'bg-info text-dark'
                            };
                            $s_class = match($task['status']) {
                                'Completed'   => 'bg-success',
                                'In Progress' => 'bg-primary',
                                'Rejected'    => 'bg-danger',
                                default       => 'bg-secondary'
                            };
                            $isOverdue = (!empty($task['due_date'])
                                          && strtotime($task['due_date']) < time()
                                          && $task['status'] !== 'Completed');
                            $rt_icons = [
                                'Repair'         => '🔧',
                                'Manpower'       => '👥',
                                'Resource'       => '📦',
                                'Legal'          => '⚖️',
                                'Maintenance'    => '🛠️',
                                'Administrative' => '📋',
                                'Other'          => '📌',
                            ];
                            $rt_icon = $rt_icons[$task['request_type'] ?? ''] ?? '📌';
                            $detail_id = 'detail_' . $task['id'];
                        ?>
                        <!-- Main row -->
                        <tr>
                            <td class="ps-4 text-muted small">#<?php echo $task['id']; ?></td>
                            <td>
                                <div class="fw-semibold text-dark">
                                    <?php echo htmlspecialchars($task['machine_name'] ?? '—'); ?>
                                </div>
                                <div class="text-muted small" style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?php echo htmlspecialchars(substr($task['issue_description'] ?? '', 0, 50)); ?>
                                </div>
                            </td>
                            <?php if ($is_eng_manager): ?>
                            <td>
                                <span class="badge bg-light text-dark border fw-normal">
                                    <?php echo htmlspecialchars($task['sender_dept_name'] ?? '—'); ?>
                                </span>
                            </td>
                            <?php endif; ?>
                            <td>
                                <span class="badge bg-light text-dark border fw-normal">
                                    <?php echo htmlspecialchars($task['receiver_dept_name'] ?? '—'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1" style="font-size:0.78rem;">
                                    <?php echo $rt_icon . ' ' . htmlspecialchars($task['request_type'] ?? $task['task_type'] ?? 'Other'); ?>
                                </span>
                            </td>
                            <td>
                                <i class="bi bi-person-badge me-1 text-muted"></i>
                                <?php echo htmlspecialchars($task['technician'] ?? 'Not Assigned'); ?>
                            </td>
                            <td class="<?php echo $isOverdue ? 'text-danger fw-bold' : 'text-muted'; ?> small">
                                <?php if (!empty($task['due_date'])): ?>
                                    <?php if ($isOverdue): ?><i class="bi bi-exclamation-circle me-1"></i><?php endif; ?>
                                    <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">No deadline</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $p_class; ?> rounded-pill">
                                    <?php echo htmlspecialchars($task['priority'] ?? '—'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $s_class; ?> rounded-pill px-3">
                                    <?php echo htmlspecialchars($task['status'] ?? '—'); ?>
                                </span>
                            </td>
                            <!-- Fix 3: Replace broken view_task/edit_task links with inline expand -->
                            <td class="pe-4">
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#<?php echo $detail_id; ?>"
                                        aria-expanded="false">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Fix 3: Inline expandable detail row (replaces missing view_task.php) -->
                        <tr class="collapse" id="<?php echo $detail_id; ?>">
                            <td colspan="<?php echo $is_eng_manager ? 10 : 9; ?>" class="bg-light border-0 p-0">
                                <div class="px-4 py-3 border-start border-4 border-primary" style="font-size:0.87rem;">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="text-muted small fw-bold mb-1">FULL DESCRIPTION</div>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($task['issue_description'] ?? 'No description provided.')); ?></p>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-muted small fw-bold mb-1">SUBMITTED BY</div>
                                            <p class="mb-0"><?php echo htmlspecialchars($task['requester'] ?? 'Unknown'); ?></p>
                                            <div class="text-muted small mt-1">
                                                <?php echo date('M d, Y — h:i A', strtotime($task['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-muted small fw-bold mb-1">ROUTING</div>
                                            <p class="mb-1">
                                                <span class="text-muted">From:</span>
                                                <?php echo htmlspecialchars($task['sender_dept_name'] ?? '—'); ?>
                                            </p>
                                            <p class="mb-0">
                                                <span class="text-muted">To:</span>
                                                <?php echo htmlspecialchars($task['receiver_dept_name'] ?? '—'); ?>
                                            </p>
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
        <div class="card-footer bg-transparent border-0 text-muted small px-4 py-3">
            Showing <?php echo count($tasks); ?> of up to 200 most recent records.
        </div>
    </div>

</div>

<?php include '../includes/footer_glass.php'; ?>