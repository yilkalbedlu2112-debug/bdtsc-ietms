<?php
// manager/view_requests.php
// Shows all cross-departmental requests where THIS department is the receiver.
session_start();
require_once '../includes/db.php';

// ── Auth guard ────────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_role']) ||
    !in_array($_SESSION['user_role'], ['Department Manager', 'Engineering Manager'], true)) {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id   = (int)($_SESSION['dept_id']  ?? 0);
$user_id   = (int)($_SESSION['user_id']  ?? 0);
$user_role = $_SESSION['user_role'] ?? '';
$full_name = $_SESSION['full_name'] ?? 'Manager';

// ── Fetch this department's name ──────────────────────────────────────────────
$stmt_dept = $pdo->prepare("SELECT dept_name FROM departments WHERE id = ?");
$stmt_dept->execute([$dept_id]);
$current_dept = $stmt_dept->fetch();
$dept_name = $current_dept['dept_name'] ?? 'Department';

// ── Handle status update action ───────────────────────────────────────────────
if (isset($_POST['update_status'])) {
    $req_id    = (int)$_POST['req_id'];
    $new_status = trim($_POST['status_value']);
    $allowed_statuses = ['Pending', 'In Progress', 'Completed', 'Rejected'];

    if (in_array($new_status, $allowed_statuses, true)) {
        // Only allow updates on requests received by this dept
        $upd = $pdo->prepare(
            "UPDATE maintenance_requests SET status = ?, is_read_by_receiver = 1
             WHERE id = ? AND receiver_dept_id = ?");
        $upd->execute([$new_status, $req_id, $dept_id]);

        log_action($pdo, $user_id, 'Request Status Updated',
            "$user_role ($full_name) updated request #$req_id to '$new_status'.");
    }
}

// ── Mark incoming requests as read ───────────────────────────────────────────
$pdo->prepare("UPDATE maintenance_requests SET is_read_by_receiver = 1 WHERE receiver_dept_id = ? AND is_read_by_receiver = 0")
    ->execute([$dept_id]);

// ── Fetch filter values ───────────────────────────────────────────────────────
$filter_status = $_GET['status']       ?? '';
$filter_type   = $_GET['request_type'] ?? '';
$filter_dir    = $_GET['direction']    ?? 'incoming'; // incoming | outgoing

// ── Build query based on direction ───────────────────────────────────────────
$conditions = [];
$params      = [];

if ($filter_dir === 'outgoing') {
    $conditions[] = "mr.sender_dept_id = ?";
    $params[]     = $dept_id;
} else {
    // Default: incoming
    $conditions[] = "mr.receiver_dept_id = ?";
    $params[]     = $dept_id;
}

if ($filter_status !== '') {
    $conditions[] = "mr.status = ?";
    $params[]     = $filter_status;
}
if ($filter_type !== '') {
    $conditions[] = "mr.request_type = ?";
    $params[]     = $filter_type;
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$sql = "SELECT mr.*,
               usr.full_name      AS requester_name,
               sd.dept_name       AS sender_dept_name,
               rd.dept_name       AS receiver_dept_name
        FROM   maintenance_requests mr
        JOIN   users u_dummy ON mr.user_id = u_dummy.id   -- keep join valid
        LEFT JOIN users usr ON mr.user_id = usr.id
        LEFT JOIN departments sd ON mr.sender_dept_id   = sd.id
        LEFT JOIN departments rd ON mr.receiver_dept_id = rd.id
        $where
        ORDER BY mr.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// ── Summary counts ────────────────────────────────────────────────────────────
$cnt_stmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status='Pending'     THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status='In Progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN status='Completed'   THEN 1 ELSE 0 END) AS completed
     FROM maintenance_requests WHERE receiver_dept_id = ?");
$cnt_stmt->execute([$dept_id]);
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
                        <i class="bi bi-inbox-fill text-primary me-2"></i>
                        Cross-Departmental Requests
                    </h2>
                    <p class="text-muted mb-0">
                        <i class="bi bi-building me-1"></i>
                        <strong><?php echo htmlspecialchars($dept_name); ?></strong> Department
                    </p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body">
                    <div class="h4 fw-bold text-primary mb-1"><?php echo (int)$counts['total']; ?></div>
                    <div class="small text-muted fw-semibold">Total Received</div>
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
                    <div class="h4 fw-bold text-info mb-1"><?php echo (int)$counts['in_progress']; ?></div>
                    <div class="small text-muted fw-semibold">In Progress</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body">
                    <div class="h4 fw-bold text-success mb-1"><?php echo (int)$counts['completed']; ?></div>
                    <div class="small text-muted fw-semibold">Completed</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-funnel text-secondary me-2"></i>Filter Requests
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label text-muted small fw-bold">
                                <i class="bi bi-arrow-left-right text-info me-1"></i>Direction
                            </label>
                            <select name="direction" class="form-select">
                                <option value="incoming" <?php echo $filter_dir === 'incoming' ? 'selected' : ''; ?>>Incoming (Received)</option>
                                <option value="outgoing" <?php echo $filter_dir === 'outgoing' ? 'selected' : ''; ?>>Outgoing (Sent)</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label text-muted small fw-bold">
                                <i class="bi bi-tag text-success me-1"></i>Request Type
                            </label>
                            <select name="request_type" class="form-select">
                                <option value="">All Types</option>
                                <?php foreach (['Repair','Manpower','Resource','Legal','Maintenance','Administrative','Other'] as $rt): ?>
                                    <option value="<?php echo $rt; ?>" <?php echo $filter_type === $rt ? 'selected' : ''; ?>>
                                        <?php echo $rt; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label text-muted small fw-bold">
                                <i class="bi bi-flag text-warning me-1"></i>Status
                            </label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <?php foreach (['Pending','In Progress','Completed','Rejected'] as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo $filter_status === $s ? 'selected' : ''; ?>>
                                        <?php echo $s; ?>
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
                                <a href="view_requests.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-1"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-table text-primary me-2"></i>
                        <?php echo $filter_dir === 'outgoing' ? 'Outgoing Requests' : 'Incoming Requests'; ?>
                        <span class="badge bg-primary ms-2"><?php echo count($requests); ?> records</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-person text-muted me-1"></i>Requested By
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-building text-muted me-1"></i><?php echo $filter_dir === 'outgoing' ? 'To (Receiver)' : 'From (Sender)'; ?>
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-tag text-muted me-1"></i>Type
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-gear text-muted me-1"></i>Subject / Asset
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-flag text-muted me-1"></i>Priority
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-activity text-muted me-1"></i>Status
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-calendar text-muted me-1"></i>Date
                                    </th>
                                    <?php if ($filter_dir === 'incoming'): ?>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-gear text-muted me-1"></i>Action
                                    </th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                                    <tbody>
                                <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="<?php echo $filter_dir === 'incoming' ? 8 : 7; ?>" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox-fill fs-1 mb-3 d-block"></i>
                                            <h6>No <?php echo $filter_dir; ?> Requests Found</h6>
                                            <p class="mb-0">No requests match your current filters.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($requests as $r): ?>
                                        <?php
                                            $p_class = match($r['priority']) {
                                                'Emergency', 'Urgent' => 'bg-danger',
                                                'High'                => 'bg-warning text-dark',
                                                default               => 'bg-info text-dark'
                                            };
                                            $s_class = match($r['status']) {
                                                'Completed'   => 'bg-success',
                                                'In Progress' => 'bg-primary',
                                                'Rejected'    => 'bg-danger',
                                                default       => 'bg-secondary'
                                            };
                                            $rt_icons = [
                                                'Repair'         => '🔧',
                                                'Manpower'       => '👥',
                                                'Resource'       => '📦',
                                                'Legal'          => '⚖️',
                                                'Maintenance'    => '🛠️',
                                                'Administrative' => '📋',
                                                'Other'          => '📌',
                                            ];
                                            $rt_icon = $rt_icons[$r['request_type']] ?? '📌';
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">
                                                    <?php echo htmlspecialchars($r['requester_name'] ?? 'Unknown'); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?php echo htmlspecialchars(
                                                        $filter_dir === 'outgoing'
                                                        ? ($r['receiver_dept_name'] ?? '—')
                                                        : ($r['sender_dept_name']   ?? '—')
                                                    ); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?php echo $rt_icon . ' ' . htmlspecialchars($r['request_type'] ?? 'Other'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold" style="max-width: 220px;">
                                                    <?php echo htmlspecialchars($r['machine_name'] ?? '—'); ?>
                                                </div>
                                                <?php if (!empty($r['issue_description'])): ?>
                                                <div class="text-muted small text-truncate" style="max-width: 220px;">
                                                    <?php echo htmlspecialchars($r['issue_description']); ?>
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $p_class; ?> rounded-pill">
                                                    <?php echo htmlspecialchars($r['priority']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $s_class; ?> rounded-pill">
                                                    <?php echo htmlspecialchars($r['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small">
                                                <div><?php echo date('M d, Y', strtotime($r['created_at'])); ?></div>
                                                <div class="small"><?php echo date('h:i A', strtotime($r['created_at'])); ?></div>
                                            </td>
                                            <?php if ($filter_dir === 'incoming'): ?>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="req_id" value="<?php echo (int)$r['id']; ?>">
                                                    <?php if ($r['status'] === 'Pending'): ?>
                                                        <input type="hidden" name="status_value" value="In Progress">
                                                        <button type="submit" name="update_status"
                                                                class="btn btn-sm btn-outline-primary rounded-pill">
                                                            <i class="bi bi-play-fill me-1"></i>Start
                                                        </button>
                                                    <?php elseif ($r['status'] === 'In Progress'): ?>
                                                        <input type="hidden" name="status_value" value="Completed">
                                                        <button type="submit" name="update_status"
                                                                class="btn btn-sm btn-success rounded-pill">
                                                            <i class="bi bi-check-lg me-1"></i>Done
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted small">
                                                            <i class="bi bi-check-circle-fill text-success me-1"></i>
                                                            <?php echo htmlspecialchars($r['status']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </form>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 text-muted small px-4 py-3">
                    Showing <?php echo count($requests); ?> request(s).
                </div>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer_glass.php'; ?>