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

<div class="container-fluid py-4">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-inbox-fill text-primary me-2"></i>
                Cross-Departmental Requests
            </h3>
            <p class="text-muted mb-0">
                <i class="bi bi-building me-1"></i>
                <strong><?php echo htmlspecialchars($dept_name); ?></strong> Department
            </p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-primary border-4">
                <div class="h4 fw-bold text-primary mb-0"><?php echo (int)$counts['total']; ?></div>
                <div class="small text-muted fw-semibold">Total Received</div>
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
                <div class="h4 fw-bold text-info mb-0"><?php echo (int)$counts['in_progress']; ?></div>
                <div class="small text-muted fw-semibold">In Progress</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-success border-4">
                <div class="h4 fw-bold text-success mb-0"><?php echo (int)$counts['completed']; ?></div>
                <div class="small text-muted fw-semibold">Completed</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card glass-card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-bold mb-1">DIRECTION</label>
                    <select name="direction" class="form-select form-select-sm bg-light border-0">
                        <option value="incoming" <?php if ($filter_dir === 'incoming') echo 'selected'; ?>>Incoming (Received)</option>
                        <option value="outgoing" <?php if ($filter_dir === 'outgoing') echo 'selected'; ?>>Outgoing (Sent)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-bold mb-1">REQUEST TYPE</label>
                    <select name="request_type" class="form-select form-select-sm bg-light border-0">
                        <option value="">All Types</option>
                        <?php foreach (['Repair','Manpower','Resource','Legal','Maintenance','Administrative','Other'] as $rt): ?>
                        <option value="<?php echo $rt; ?>" <?php if ($filter_type === $rt) echo 'selected'; ?>>
                            <?php echo $rt; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-bold mb-1">STATUS</label>
                    <select name="status" class="form-select form-select-sm bg-light border-0">
                        <option value="">All Statuses</option>
                        <?php foreach (['Pending','In Progress','Completed','Rejected'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php if ($filter_status === $s) echo 'selected'; ?>>
                            <?php echo $s; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 w-50">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="view_requests.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 w-50">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="card glass-card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 pb-2 px-4">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-table me-2 text-primary"></i>
                    <?php echo $filter_dir === 'outgoing' ? 'Outgoing Requests' : 'Incoming Requests'; ?>
                    <span class="badge bg-primary-subtle text-primary ms-2 px-2 py-1 rounded-pill">
                        <?php echo count($requests); ?> records
                    </span>
                </h6>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-uppercase text-muted" style="font-size: 0.72rem; letter-spacing: .5px;">
                            <th class="ps-4">Requested By</th>
                            <th><?php echo $filter_dir === 'outgoing' ? 'To (Receiver)' : 'From (Sender)'; ?></th>
                            <th>Request Type</th>
                            <th>Subject / Asset</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Date</th>
                            <?php if ($filter_dir === 'incoming'): ?>
                            <th class="pe-4">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No <?php echo $filter_dir; ?> requests found.
                            </td>
                        </tr>
                        <?php endif; ?>

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
                            <td class="ps-4">
                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($r['requester_name'] ?? 'Unknown'); ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border fw-normal">
                                    <?php echo htmlspecialchars(
                                        $filter_dir === 'outgoing'
                                        ? ($r['receiver_dept_name'] ?? '—')
                                        : ($r['sender_dept_name']   ?? '—')
                                    ); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-light text-dark border px-3 py-2" style="font-size:0.8rem;">
                                    <?php echo $rt_icon . ' ' . htmlspecialchars($r['request_type'] ?? 'Other'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold" style="max-width:220px;">
                                    <?php echo htmlspecialchars($r['machine_name'] ?? '—'); ?>
                                </div>
                                <?php if (!empty($r['issue_description'])): ?>
                                <div class="text-muted small" style="max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
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
                                <span class="badge <?php echo $s_class; ?> rounded-pill px-3">
                                    <?php echo htmlspecialchars($r['status']); ?>
                                </span>
                            </td>
                            <td class="text-muted small">
                                <?php echo date('M d, Y', strtotime($r['created_at'])); ?><br>
                                <span style="font-size:0.72rem;"><?php echo date('h:i A', strtotime($r['created_at'])); ?></span>
                            </td>
                            <?php if ($filter_dir === 'incoming'): ?>
                            <td class="pe-4">
                                <form method="POST" class="d-flex gap-1">
                                    <input type="hidden" name="req_id" value="<?php echo (int)$r['id']; ?>">
                                    <?php if ($r['status'] === 'Pending'): ?>
                                        <input type="hidden" name="status_value" value="In Progress">
                                        <button type="submit" name="update_status"
                                                class="btn btn-sm btn-outline-primary rounded-pill px-2">
                                            <i class="bi bi-play-fill"></i> Start
                                        </button>
                                    <?php elseif ($r['status'] === 'In Progress'): ?>
                                        <input type="hidden" name="status_value" value="Completed">
                                        <button type="submit" name="update_status"
                                                class="btn btn-sm btn-success rounded-pill px-2">
                                            <i class="bi bi-check-lg"></i> Done
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                            <?php echo htmlspecialchars($r['status']); ?>
                                        </span>
                                    <?php endif; ?>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-transparent border-0 text-muted small px-4 py-3">
            Showing <?php echo count($requests); ?> request(s).
        </div>
    </div>

</div>

<?php include '../includes/footer_glass.php'; ?>