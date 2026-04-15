<?php
require_once '../includes/db.php';

// ማጣሪያዎቹን ከ URL (GET) መቀበል
$dept_id = isset($_GET['dept_id']) && $_GET['dept_id'] !== '' ? (int)$_GET['dept_id'] : null;
$period = isset($_GET['period']) ? $_GET['period'] : 'all';

// 1. የጊዜ ማጣሪያ (Time Filter)
$time_filter = "";
if ($period === 'today') {
    $time_filter = " AND DATE(created_at) = CURDATE()";
} elseif ($period === 'week') {
    $time_filter = " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($period === 'month') {
    $time_filter = " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
} elseif ($period === 'quarter') {
    $time_filter = " AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
} elseif ($period === 'half') {
    $time_filter = " AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
} elseif ($period === 'year') {
    $time_filter = " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
}

// 2. የዲፓርትመንት ማጣሪያ (Department Filter)
$dept_filter = $dept_id ? " AND dept_id = $dept_id" : "";
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_departments = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$total_requests = $pdo->query("SELECT COUNT(*) FROM maintenance_requests")->fetchColumn();
$requests_by_status = $pdo->query("SELECT status, COUNT(*) AS total FROM maintenance_requests GROUP BY status")->fetchAll();
$users_by_role = $pdo->query("SELECT user_role, COUNT(*) AS total FROM users GROUP BY user_role")->fetchAll();

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'bdtsc-report-' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Category', 'Label', 'Value']);
    fputcsv($output, ['Summary', 'Total Users', $total_users]);
    fputcsv($output, ['Summary', 'Total Departments', $total_departments]);
    fputcsv($output, ['Summary', 'Total Requests', $total_requests]);
    fputcsv($output, []);
    fputcsv($output, ['Requests by Status', 'Status', 'Count']);
    foreach ($requests_by_status as $row) {
        fputcsv($output, ['Requests by Status', $row['status'], $row['total']]);
    }
    fputcsv($output, []);
    fputcsv($output, ['Users by Role', 'Role', 'Count']);
    foreach ($users_by_role as $row) {
        fputcsv($output, ['Users by Role', $row['user_role'], $row['total']]);
    }
    fclose($output);
    exit;
}

session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['General Manager', 'Admin', 'Deputy General Manager'])) {
    header("Location: ../auth/login.php");
    exit();
}
include '../includes/header_glass.php';

// መስመር 66 አካባቢ የነበረውን በዚህ ተካ
$activity_log_query = "SELECT l.*, u.full_name, u.user_role FROM audit_logs l 
                       LEFT JOIN users u ON l.user_id = u.id 
                       WHERE 1=1 " . str_replace('created_at', 'l.created_at', $time_filter) . " 
                       ORDER BY l.created_at DESC LIMIT 20";
$activity_log = $pdo->query($activity_log_query)->fetchAll();
?>
<style>
    .report-action-buttons .btn {
        min-width: 160px;
        border: none;
        font-weight: 600;
        box-shadow: 0 10px 28px rgba(14, 165, 233, 0.18);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .report-action-buttons .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #22c55e);
        color: #ffffff;
    }
    .report-action-buttons .btn-success {
        background: linear-gradient(135deg, #0ea5e9, #10b981);
        color: #ffffff;
    }
    .report-action-buttons .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 30px rgba(14, 165, 233, 0.22);
    }
    @media print {
        .sidebar,
        .mobile-menu-toggle,
        .report-action-buttons,
        .btn,
        .logout-link {
            display: none !important;
        }
        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
        }
        body {
            background: #ffffff !important;
            color: #000 !important;
        }
        .card {
            box-shadow: none !important;
            border: none !important;
        }
        .table,
        .table th,
        .table td {
            color: #000 !important;
        }
    }
</style>

<div class="container-fluid mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="text-dark"><i class="bi bi-journal-text"></i> Reports</h2>
            <p class="text-muted mb-0">Export and print summary reports for users, departments, and request activity.</p>
        </div>
        <div class="report-action-buttons d-flex gap-2">
            <button type="button" class="btn btn-primary" onclick="window.print();"><i class="bi bi-printer me-2"></i>Print Report</button>
            <a href="?export=csv" class="btn btn-success"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Export to CSV</a>
        </div>
    </div>
     <div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted">DEPARTMENT</label>
                <select name="dept_id" class="form-select border-0 shadow-sm">
                    <option value="">All Departments (Global)</option>
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
                    <option value="all">Lifetime Data</option>
                    <option value="today" <?= $period == 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="week" <?= $period == 'week' ? 'selected' : '' ?>>Past 7 Days</option>
                    <option value="month" <?= $period == 'month' ? 'selected' : '' ?>>Past 30 Days</option>
                    <option value="quarter" <?= $period == 'quarter' ? 'selected' : '' ?>>Quarterly (90 Days)</option>
                    <option value="half" <?= $period == 'half' ? 'selected' : '' ?>>6 Months</option>
                    <option value="year" <?= $period == 'year' ? 'selected' : '' ?>>Annually</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100 shadow-sm"><i class="bi bi-filter"></i> Apply</button>
                <a href="reports.php" class="btn btn-outline-secondary w-100 shadow-sm"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            </div>
        </form>
    </div>
</div>
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6>Total Users</h6>
                    <h3><?php echo $total_users; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6>Total Departments</h6>
                    <h3><?php echo $total_departments; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6>Total Requests</h6>
                    <h3><?php echo $total_requests; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row gy-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    Requests by Status
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($requests_by_status as $row): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($row['status']); ?>
                                <span class="badge bg-primary rounded-pill"><?php echo $row['total']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    Users by Role
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($users_by_role as $row): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($row['user_role']); ?>
                                <span class="badge bg-success rounded-pill"><?php echo $row['total']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-dark text-white">
            Recent Activity Log
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($activity_log)): ?>
                        <?php foreach ($activity_log as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></td>
                            <td><?php echo htmlspecialchars($log['user_role'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4">No activity found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer_glass.php'; ?>