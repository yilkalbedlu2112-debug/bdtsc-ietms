<?php 
require_once '../includes/db.php';
include '../includes/admin_header.php';

// 1. መረጃውን ከዳታቤዝ ማምጣት (ከነ ሰራተኛው ስም ጋር)
$query = "SELECT l.*, u.full_name, u.role 
          FROM audit_logs l 
          LEFT JOIN users u ON l.user_id = u.id 
          ORDER BY l.created_at DESC";
$logs = $pdo->query($query)->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-dark"><i class="bi bi-shield-check text-danger me-2"></i>Audit Trail</h2>
            <p class="text-muted mb-0">System activity monitoring and security logs</p>
        </div>
        <button onclick="window.print()" class="btn btn-outline-danger rounded-pill shadow-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i>Generate Report
        </button>
    </div>

    <div class="card mb-4 border-0 shadow-sm rounded-4 p-3">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0 rounded-start-4">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control border-0 rounded-end-4" placeholder="Search by user name or action...">
                </div>
            </div>
            <div class="col-md-6 text-end">
                <small class="text-muted">Total Records: <strong><?php echo count($logs); ?></strong></small>
            </div>
        </div>
    </div>

    <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-dark text-white py-3">
            <h6 class="mb-0"><i class="bi bi-activity me-2"></i>System Activity Logs</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="auditTable">
                <thead class="table-dark">
                    <tr>
                        <th class="border-0 fw-semibold"><i class="bi bi-clock me-1"></i>Timestamp</th>
                        <th class="border-0 fw-semibold"><i class="bi bi-person me-1"></i>User</th>
                        <th class="border-0 fw-semibold"><i class="bi bi-shield me-1"></i>Role</th>
                        <th class="border-0 fw-semibold"><i class="bi bi-gear me-1"></i>Action</th>
                        <th class="border-0 fw-semibold"><i class="bi bi-info-circle me-1"></i>Details</th>
                        <th class="border-0 fw-semibold"><i class="bi bi-globe me-1"></i>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($logs) > 0): ?>
                        <?php foreach ($logs as $log): ?>
                        <tr class="border-bottom border-light">
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-info bg-opacity-10 rounded-circle p-2 me-3">
                                        <i class="bi bi-calendar-event text-info"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted"><?php echo date('M d, Y', strtotime($log['created_at'])); ?></div>
                                        <div class="fw-semibold"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                        <i class="bi bi-person-fill text-primary"></i>
                                    </div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                                    <?php echo $log['role'] ?? 'N/A'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary rounded-pill px-3 py-2">
                                    <i class="bi bi-activity me-1"></i><?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td class="text-truncate" style="max-width: 300px;" title="<?php echo htmlspecialchars($log['details']); ?>">
                                <?php echo htmlspecialchars($log['details']); ?>
                            </td>
                            <td>
                                <code class="bg-light px-2 py-1 rounded small"><?php echo $log['ip_address']; ?></code>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-info-circle fs-1 mb-3 d-block"></i>
                                    <h5>No Activity Found</h5>
                                    <p>System activity logs will appear here.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toUpperCase();
    let rows = document.querySelector("#auditTable tbody").rows;
    for (let i = 0; i < rows.length; i++) {
        let text = rows[i].textContent.toUpperCase();
        rows[i].style.display = text.includes(filter) ? "" : "none";
    }
});
</script>

<style>
@media print {
    .btn, #searchInput, .admin-sidebar { display: none !important; }
    .container-fluid { width: 100%; margin: 0; padding: 0; }
}
</style>

<?php include '../includes/admin_footer.php'; ?>