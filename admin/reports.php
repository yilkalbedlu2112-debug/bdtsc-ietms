<?php
session_start();
require_once '../includes/db.php';

// Auth Check - መጀመሪያ ፍቃድ ማረጋገጥ
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['General Manager', 'Admin', 'Deputy General Manager'])) {
    header("Location: ../auth/login.php");
    exit();
}

// 1. የማጣሪያ ሎጂክ (Filters)
$dept_id = isset($_GET['dept_id']) && $_GET['dept_id'] !== '' ? (int)$_GET['dept_id'] : null;
$period = isset($_GET['period']) ? $_GET['period'] : 'all';

$time_filter = "";
if ($period === 'today') {
    $time_filter = " AND created_at >= CURDATE()";
} elseif ($period === 'week') {
    $time_filter = " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($period === 'month') {
    $time_filter = " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
}

// 2. ዳታዎችን ከዳታቤዝ ማምጣት (Summary Stats)
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_departments = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$total_requests = $pdo->query("SELECT COUNT(*) FROM maintenance_requests")->fetchColumn();

// Requests by Status
$requests_by_status = $pdo->query("SELECT status, COUNT(*) AS total FROM maintenance_requests GROUP BY status")->fetchAll();

// Users by Role
$users_by_role = $pdo->query("SELECT user_role, COUNT(*) AS total FROM users GROUP BY user_role")->fetchAll();

// 3. Export Logic (CSV)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'bdtsc-report-' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Category', 'Label', 'Value']);
    fputcsv($output, ['Summary', 'Total Users', $total_users]);
    fputcsv($output, ['Summary', 'Total Departments', $total_departments]);
    fputcsv($output, ['Summary', 'Total Requests', $total_requests]);
    fclose($output);
    exit;
}

// Activity log query
$activity_log_query = "SELECT l.*, u.full_name, u.user_role FROM audit_logs l 
                       LEFT JOIN users u ON l.user_id = u.id 
                       WHERE 1=1 " . str_replace('created_at', 'l.created_at', $time_filter) . " 
                       ORDER BY l.created_at DESC LIMIT 20";
$activity_log = $pdo->query($activity_log_query)->fetchAll();

include '../includes/header_glass.php';
?>

<style>
    /* UI Styles */
    .report-action-buttons .btn {
        min-width: 150px;
        font-weight: 600;
        border: none;
    }
    .report-action-buttons .btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .report-action-buttons .btn-success { background: linear-gradient(135deg, #10b981, #059669); }

    @media print {
        .sidebar, .mobile-menu-toggle, .report-action-buttons, .no-print, .btn, .bi {
            display: none !important;
        }
        .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; }
        body { background: white !important; }
    }
</style>

<div class="container-fluid mt-4">
    <div class="d-none d-print-block text-center mb-4">
        <h2 class="fw-bold">BAHIR DAR TEXTILE SHARE COMPANY</h2>
        <h5>Industrial Machine Maintenance Performance Report</h5>
        <hr>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h2 class="text-dark"><i class="bi bi-journal-text me-2 text-primary"></i> Reports</h2>
            <p class="text-muted mb-0">Summary of system activity and performance analytics.</p>
        </div>
        <div class="report-action-buttons d-flex gap-2">
            <button type="button" class="btn btn-primary text-white" onclick="window.print();">
                <i class="bi bi-printer me-1"></i> Print Report
            </button>
            <a href="?export=csv" class="btn btn-success text-white">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4 bg-light no-print">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted">DEPARTMENT</label>
                    <select name="dept_id" class="form-select border-0 shadow-sm">
                        <option value="">All Departments</option>
                        <?php 
                        $depts = $pdo->query("SELECT * FROM departments ORDER BY dept_name ASC")->fetchAll();
                        foreach ($depts as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $dept_id == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['dept_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted">TIME PERIOD</label>
                    <select name="period" class="form-select border-0 shadow-sm">
                        <option value="all">Lifetime</option>
                        <option value="today" <?= $period == 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= $period == 'week' ? 'selected' : '' ?>>Past 7 Days</option>
                        <option value="month" <?= $period == 'month' ? 'selected' : '' ?>>Past 30 Days</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100 shadow-sm">Apply</button>
                    <a href="reports.php" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-4 text-center">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 p-3">
                <div class="small text-muted fw-bold">TOTAL USERS</div>
                <h2 class="fw-bold text-primary"><?php echo $total_users; ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 p-3">
                <div class="small text-muted fw-bold">DEPARTMENTS</div>
                <h2 class="fw-bold text-success"><?php echo $total_departments; ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 p-3">
                <div class="small text-muted fw-bold">MAINTENANCE REQUESTS</div>
                <h2 class="fw-bold text-danger"><?php echo $total_requests; ?></h2>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">Requests by Status</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>Status</th><th class="text-end">Count</th></tr></thead>
                        <tbody>
                            <?php foreach ($requests_by_status as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td class="text-end fw-bold"><?php echo $row['total']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">Users by Role</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>Role</th><th class="text-end">Count</th></tr></thead>
                        <tbody>
                            <?php foreach ($users_by_role as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['user_role']); ?></td>
                                <td class="text-end fw-bold"><?php echo $row['total']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-4 mb-5">
        <div class="card-header bg-dark text-white">Recent Activity Trail</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Date</th><th>User</th><th>Action</th><th>Details</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($activity_log as $log): ?>
                    <tr>
                        <td><small><?php echo $log['created_at']; ?></small></td>
                        <td><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></td>
                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($log['action']); ?></span></td>
                        <td><small><?php echo htmlspecialchars($log['details']); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer_glass.php'; ?>