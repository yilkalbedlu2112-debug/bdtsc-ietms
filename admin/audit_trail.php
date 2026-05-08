<?php 
require_once '../includes/db.php';
session_start();

// ሴኩሪቲ ቼክ
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['General Manager', 'Admin', 'Deputy General Manager'])) {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/header_glass.php';

// 1. የጊዜ ገደብ መፈለጊያ (Date Filter) Logic
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$query_str = "SELECT l.*, u.full_name, u.user_role 
              FROM audit_logs l 
              LEFT JOIN users u ON l.user_id = u.id";

if (!empty($start_date) && !empty($end_date)) {
    $query_str .= " WHERE DATE(l.created_at) BETWEEN :start AND :end";
}

$query_str .= " ORDER BY l.created_at DESC";

$stmt = $pdo->prepare($query_str);
if (!empty($start_date) && !empty($end_date)) {
    $stmt->execute(['start' => $start_date, 'end' => $end_date]);
} else {
    $stmt->execute();
}
$logs = $stmt->fetchAll();

// የአክሽን ከለር ፈንክሽን
function getActionBadge($action) {
    $action = strtolower($action);
    if (strpos($action, 'delete') !== false || strpos($action, 'failed') !== false || strpos($action, 'force') !== false) {
        return 'bg-danger';
    } elseif (strpos($action, 'login') !== false || strpos($action, 'create') !== false || strpos($action, 'add') !== false) {
        return 'bg-success';
    } elseif (strpos($action, 'update') !== false || strpos($action, 'assign') !== false || strpos($action, 'verify') !== false) {
        return 'bg-warning text-dark';
    }
    return 'bg-primary';
}


?>


<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-dark fw-bold"><i class="bi bi-shield-lock-fill text-danger me-2"></i>Audit Trail</h2>
            <p class="text-muted mb-0">Security monitoring and system activity history</p>
        </div>
        <div class="btn-group shadow-sm">
            <a href="generate_pdf.php?type=audit&start=<?= $start_date ?>&end=<?= $end_date ?>" class="btn btn-danger rounded-start-pill">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </a>
            <a href="export_excel.php?type=audit" class="btn btn-success rounded-end-pill">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </a>
        </div>
    </div>

    <div class="card mb-4 border-0 shadow-sm rounded-4 p-3 bg-white">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">From Date</label>
                <input type="date" name="start_date" class="form-control border-light rounded-3" value="<?= $start_date ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">To Date</label>
                <input type="date" name="end_date" class="form-control border-light rounded-3" value="<?= $end_date ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Quick Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control border-light" placeholder="Search logs...">
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-dark rounded-3">Filter Logs</button>
            </div>
        </form>
    </div>

    <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="auditTable">
                <thead class="bg-dark text-white">
                    <tr>
                        <th class="py-3 px-4">Timestamp</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <?php if ($logs): ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="px-4">
                                <div class="fw-bold text-dark"><?= date('H:i:s', strtotime($log['created_at'])) ?></div>
                                <div class="small text-muted"><?= date('M d, Y', strtotime($log['created_at'])) ?></div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-light rounded-circle p-2 me-2 text-center" style="width:35px">
                                        <i class="bi bi-person text-secondary"></i>
                                    </div>
                                    <span class="fw-semibold"><?= htmlspecialchars($log['full_name'] ?? 'System') ?></span>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= $log['user_role'] ?? 'System' ?></span></td>
                            <td>
                                <span class="badge <?= getActionBadge($log['action']) ?> rounded-pill px-3">
                                    <?= htmlspecialchars($log['action']) ?>
                                </span>
                            </td>
                            <td class="small text-muted" style="max-width: 250px;"><?= htmlspecialchars($log['details']) ?></td>
                            <td><code class="text-danger small"><?= $log['ip_address'] ?></code></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No records found for the selected period.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Real-time Search
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toUpperCase();
    let rows = document.querySelector("#auditTable tbody").rows;
    for (let i = 0; i < rows.length; i++) {
        let text = rows[i].innerText.toUpperCase();
        rows[i].style.display = text.includes(filter) ? "" : "none";
    }
});
</script>

<?php include '../includes/footer_glass.php'; ?>