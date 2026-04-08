<?php
require_once '../includes/db.php';

$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_departments = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$total_requests = $pdo->query("SELECT COUNT(*) FROM maintenance_requests")->fetchColumn();
$requests_by_status = $pdo->query("SELECT status, COUNT(*) AS total FROM maintenance_requests GROUP BY status")->fetchAll();
$users_by_role = $pdo->query("SELECT role, COUNT(*) AS total FROM users GROUP BY role")->fetchAll();

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
        fputcsv($output, ['Users by Role', $row['role'], $row['total']]);
    }
    fclose($output);
    exit;
}

session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['General Manager', 'Admin', 'Deputy General Manager'])) {
    header("Location: ../auth/login.php");
    exit();
}
include '../includes/header_glass.php';

$activity_log = $pdo->query("SELECT l.*, u.full_name, u.role FROM audit_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 20")->fetchAll();
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
                                <?php echo htmlspecialchars($row['role']); ?>
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
                            <td><?php echo htmlspecialchars($log['role'] ?? 'N/A'); ?></td>
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