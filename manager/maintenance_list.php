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

<div class="container-fluid">

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold mb-2">
                        <i class="bi bi-tools text-primary me-2"></i>
                        <?php echo $is_eng_manager ? 'System-Wide' : htmlspecialchars($dept_name); ?>
                        Maintenance & Task Log
                    </h2>
                    <p class="text-muted mb-0">
                        All tasks and requests
                        <?php echo $is_eng_manager ? 'across all departments' : 'sent from your department'; ?>.
                    </p>
                </div>
                <a href="create_task.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>New Request
                </a>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body">
                    <div class="h4 fw-bold text-primary mb-1"><?php echo (int)$counts['total']; ?></div>
                    <div class="small text-muted fw-semibold">Total</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body">
                    <div class="h4 fw-bold text-warning mb-1"><?php echo (int)$counts['pending']; ?></div>
                    <div class="small text-muted fw-semibold">Pending</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body">
                    <div class="h4 fw-bold text-info mb-1"><?php echo (int)$counts['in_prog']; ?></div>
                    <div class="small text-muted fw-semibold">In Progress</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body">
                    <div class="h4 fw-bold text-success mb-1"><?php echo (int)$counts['done']; ?></div>
                    <div class="small text-muted fw-semibold">Completed</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-4 pb-2">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-funnel text-secondary me-2"></i>Filter Tasks
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label text-muted small fw-bold">
                        <i class="bi bi-flag text-info me-1"></i>Status
                    </label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <?php foreach (['Pending','In Progress','Completed','Rejected'] as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo ($status_filter === $s) ? 'selected' : ''; ?>>
                                <?php echo $s; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label text-muted small fw-bold">
                        <i class="bi bi-exclamation-triangle text-warning me-1"></i>Priority
                    </label>
                    <select name="priority" class="form-select">
                        <option value="">All Priorities</option>
                        <?php foreach (['Emergency','Urgent','High','Normal'] as $p): ?>
                            <option value="<?php echo $p; ?>" <?php echo ($priority_filter === $p) ? 'selected' : ''; ?>>
                                <?php echo $p; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label text-muted small fw-bold">
                        <i class="bi bi-tag text-success me-1"></i>Request Type
                    </label>
                    <select name="request_type" class="form-select">
                        <option value="">All Types</option>
                        <?php foreach (['Repair','Manpower','Resource','Legal','Maintenance','Administrative','Other'] as $rt): ?>
                            <option value="<?php echo $rt; ?>" <?php echo ($type_filter === $rt) ? 'selected' : ''; ?>>
                                <?php echo $rt; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-search me-1"></i>Filter
                        </button>
                        <a href="maintenance_list.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tasks Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-table text-primary me-2"></i>Task Records
                        <span class="badge bg-primary ms-2"><?php echo count($tasks); ?> records</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-hash text-muted me-1"></i>ID
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-gear text-muted me-1"></i>Subject / Machine
                                    </th>
                                    <?php if ($is_eng_manager): ?>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-building text-muted me-1"></i>From Dept
                                    </th>
                                    <?php endif; ?>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-arrow-right text-muted me-1"></i>To Dept
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-tag text-muted me-1"></i>Type
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-person text-muted me-1"></i>Assigned To
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-calendar text-muted me-1"></i>Due Date
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-flag text-muted me-1"></i>Priority
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-activity text-muted me-1"></i>Status
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-eye text-muted me-1"></i>Details
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tasks)): ?>
                                <tr>
                                    <td colspan="<?php echo $is_eng_manager ? 10 : 9; ?>" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox-fill fs-1 mb-3 d-block"></i>
                                            <h6>No Tasks Found</h6>
                                            <p class="mb-0">No tasks match your current filters.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
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
                                            <td>
                                                <span class="text-muted small">#<?php echo $task['id']; ?></span>
                                            </td>
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
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                                        type="button"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#<?php echo $detail_id; ?>"
                                                        aria-expanded="false">
                                                    <i class="bi bi-chevron-down"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Expandable Details Row -->
                                        <tr class="collapse" id="<?php echo $detail_id; ?>">
                                            <td colspan="<?php echo $is_eng_manager ? 10 : 9; ?>" class="bg-light border-0 p-0">
                                                <div class="px-4 py-3 border-start border-4 border-primary">
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
                                <?php endif; ?>
                    </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 text-muted small px-4 py-3">
                    Showing <?php echo count($tasks); ?> of up to 200 most recent records.
                </div>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer_glass.php'; ?>