<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Department Manager') {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'];
$dept_name = $_SESSION['dept_name'] ?? 'Department';
$full_name = $_SESSION['full_name'] ?? 'Manager';

// Stats
$total_tasks_stmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ?");
$total_tasks_stmt->execute([$dept_id]);
$total_tasks = $total_tasks_stmt->fetchColumn();

$pending_requests_stmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND status = 'Pending'");
$pending_requests_stmt->execute([$dept_id]);
$pending_requests = $pending_requests_stmt->fetchColumn();

$technician_count = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'Technician' AND dept_id = ?");
$technician_count->execute([$dept_id]);
$technician_count = $technician_count->fetchColumn();

$requests_stmt = $pdo->prepare("SELECT m.*, u.full_name AS requested_by FROM maintenance_requests m LEFT JOIN users u ON m.user_id = u.id WHERE m.dept_id = ? ORDER BY m.id DESC");
$requests_stmt->execute([$dept_id]);
$requests = $requests_stmt->fetchAll();

$production_stmt = $pdo->prepare("SELECT p.*, u.full_name AS reported_by FROM production_reports p LEFT JOIN users u ON p.user_id = u.id WHERE p.dept_id = ? ORDER BY p.id DESC");
$production_stmt->execute([$dept_id]);
$production_reports = $production_stmt->fetchAll();

$showMaintenanceMenu = preg_match('/engineering|maintenance|ጥገና|repair|tech/i', $dept_name);
$showProductionMenu = preg_match('/garment|production|product|ምርት|textile/i', $dept_name);
if (! $showMaintenanceMenu && ! $showProductionMenu) {
    $showMaintenanceMenu = true;
    $showProductionMenu = true;
}

include '../includes/manager_header.php';
?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h1 class="page-title mb-2">Welcome, <?php echo htmlspecialchars($full_name); ?> - <?php echo htmlspecialchars($dept_name); ?> Manager Dashboard</h1>
                <p class="text-muted mb-0 amharic-font">ይህ ዳሽቦርድ የክፍላችሁን መረጃ ብቻ ይከፍላል።</p>
            </div>
            <div class="text-end">
                <a href="../auth/logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm card-border-blue">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="card-icon bg-blue"><i class="bi bi-list-check"></i></div>
                            <div>
                                <h6 class="text-uppercase text-muted mb-1">Total Tasks</h6>
                                <h2 class="mb-0"><?php echo (int)$total_tasks; ?></h2>
                            </div>
                        </div>
                        <p class="mb-0 text-muted">የዛሬው ዲፓርትመንት አጠቃላይ ተግባራት</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm card-border-yellow">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="card-icon bg-warning"><i class="bi bi-clock-history"></i></div>
                            <div>
                                <h6 class="text-uppercase text-muted mb-1">Pending Requests</h6>
                                <h2 class="mb-0"><?php echo (int)$pending_requests; ?></h2>
                            </div>
                        </div>
                        <p class="mb-0 text-muted">እየተጠበቀ ያለ የጥገና ጥያቄዎች</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm card-border-green">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="card-icon bg-success"><i class="bi bi-people-fill"></i></div>
                            <div>
                                <h6 class="text-uppercase text-muted mb-1">Technicians</h6>
                                <h2 class="mb-0"><?php echo (int)$technician_count; ?></h2>
                            </div>
                        </div>
                        <p class="mb-0 text-muted">የዲፓርትመንቱ ቴክኒሻኖች</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div id="maintenance" class="card shadow-sm">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Maintenance Requests</h5>
                        <span class="badge bg-white text-dark"><?php echo count($requests); ?> requests</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Machine</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Requested By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($requests)): ?>
                                        <tr><td colspan="4" class="text-center py-4">No maintenance requests found for this department.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($requests as $req): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($req['machine_name']); ?></td>
                                                <td><?php echo htmlspecialchars($req['issue_description']); ?></td>
                                                <td><span class="badge <?php echo ($req['status'] === 'Pending') ? 'bg-warning text-dark' : (($req['status'] === 'Completed') ? 'bg-success' : 'bg-info text-dark'); ?>"><?php echo htmlspecialchars($req['status']); ?></span></td>
                                                <td><?php echo htmlspecialchars($req['requested_by'] ?? 'Unknown'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div id="production" class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Production Reports</h5>
                        <span class="badge bg-white text-primary"><?php echo count($production_reports); ?> reports</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Machine</th>
                                        <th>Quantity</th>
                                        <th>Shift</th>
                                        <th>Reported By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($production_reports)): ?>
                                        <tr><td colspan="4" class="text-center py-4">No production reports found for this department.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($production_reports as $report): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($report['machine_name']); ?></td>
                                                <td><?php echo htmlspecialchars($report['quantity_produced']); ?></td>
                                                <td><?php echo htmlspecialchars($report['shift']); ?></td>
                                                <td><?php echo htmlspecialchars($report['reported_by'] ?? 'Unknown'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
