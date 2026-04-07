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

// 1. የስታቲስቲክስ መረጃዎች
$total_tasks_stmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ?");
$total_tasks_stmt->execute([$dept_id]);
$total_tasks = $total_tasks_stmt->fetchColumn();

$pending_requests_stmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND status IN ('Pending', 'Pending Approval')");
$pending_requests_stmt->execute([$dept_id]);
$pending_requests = $pending_requests_stmt->fetchColumn();

$staff_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE dept_id = ? AND status = 'Active' AND role != 'Department Manager'");
$staff_count_stmt->execute([$dept_id]);
$staff_count = $staff_count_stmt->fetchColumn();

// 2. የተግባራት ዝርዝር (Maintenance Detail)
$requests_stmt = $pdo->prepare("
    SELECT m.*, u.full_name AS requested_by, a.full_name AS assigned_to_name 
    FROM maintenance_requests m 
    LEFT JOIN users u ON m.user_id = u.id 
    LEFT JOIN users a ON m.assigned_to = a.id 
    WHERE m.dept_id = ? 
    ORDER BY CASE WHEN m.priority = 'Emergency' THEN 1 WHEN m.priority = 'High' THEN 2 ELSE 3 END, m.id DESC
");
$requests_stmt->execute([$dept_id]);
$requests = $requests_stmt->fetchAll();

// 3. የምርት ሪፖርት (Production Detail)
$production_stmt = $pdo->prepare("SELECT p.*, u.full_name AS reported_by FROM production_reports p LEFT JOIN users u ON p.user_id = u.id WHERE p.dept_id = ? ORDER BY p.id DESC");
$production_stmt->execute([$dept_id]);
$production_reports = $production_stmt->fetchAll();

// 4. ለAnalytics/Chart የሚሆን ዳታ
$completed_tasks_stmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND status = 'Completed'");
$completed_tasks_stmt->execute([$dept_id]);
$completed_tasks = $completed_tasks_stmt->fetchColumn();

$in_progress_tasks = $total_tasks - $completed_tasks;
$success_rate = ($total_tasks > 0) ? round(($completed_tasks / $total_tasks) * 100) : 0;

$isProductionDept = preg_match('/production|spinning|weaving|garment/i', $dept_name);

include '../includes/manager_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold text-dark"><?php echo htmlspecialchars($dept_name); ?> Dashboard</h1>
            <p class="text-muted small mb-0">Manager: <strong><?php echo htmlspecialchars($full_name); ?></strong></p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="location.reload()" class="btn btn-white shadow-sm border"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            <a href="create_task.php" class="btn btn-primary shadow-sm"><i class="bi bi-plus-lg"></i> New Task</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="small text-uppercase opacity-75">Total Tasks</h6><h2 class="fw-bold mb-0"><?php echo $total_tasks; ?></h2></div>
                    <div class="fs-1 opacity-50"><i class="bi bi-list-task"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-warning text-dark p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="small text-uppercase opacity-75">Pending Tasks</h6><h2 class="fw-bold mb-0"><?php echo $in_progress_tasks; ?></h2></div>
                    <div class="fs-1 opacity-50"><i class="bi bi-hourglass-split"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-info text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="small text-uppercase opacity-75">Active Staff</h6><h2 class="fw-bold mb-0"><?php echo $staff_count; ?></h2></div>
                    <div class="fs-1 opacity-50"><i class="bi bi-people"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Recent Task Overview</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="bg-light">
                                <tr><th>Machine</th><th>Priority</th><th>Status</th><th>Deadline</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($requests, 0, 5) as $req): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($req['machine_name']); ?></strong></td>
                                    <td><span class="badge bg-<?php echo ($req['priority'] == 'Emergency') ? 'danger' : 'warning'; ?>"><?php echo $req['priority']; ?></span></td>
                                    <td><span class="badge border text-dark"><?php echo $req['status']; ?></span></td>
                                    <td><?php echo $req['due_date'] ? date('M d', strtotime($req['due_date'])) : 'N/A'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100 text-center p-4">
                <h6 class="fw-bold mb-3">Task Completion Rate</h6>
                <canvas id="completionChart"></canvas>
                <h4 class="mt-3 fw-bold text-primary"><?php echo $success_rate; ?>%</h4>
            </div>
        </div>
    </div>

    <div id="maintenance-section" class="card border-0 shadow-sm mb-5">
        <div class="card-header bg-dark text-white py-3">
            <h5 class="mb-0 fs-6 fw-bold"><i class="bi bi-tools me-2"></i> ዝርዝር የጥገና መዝገብ (Detailed Maintenance Logs)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Task ID</th>
                            <th>Machine/Asset</th>
                            <th>Description</th>
                            <th>Assigned Technician</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $task): ?>
                        <tr>
                            <td>#<?php echo $task['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($task['machine_name']); ?></strong></td>
                            <td><small><?php echo htmlspecialchars($task['issue_description']); ?></small></td>
                            <td><?php echo htmlspecialchars($task['assigned_to_name'] ?? 'Not Assigned'); ?></td>
                            <td><span class="badge bg-<?php echo ($task['status'] == 'Completed') ? 'success' : 'info'; ?>"><?php echo $task['status']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($isProductionDept): ?>
    <div id="production-section" class="card border-0 shadow-sm mb-5 border-start border-success border-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fs-6 fw-bold text-success"><i class="bi bi-graph-up-arrow me-2"></i> የምርት ዝርዝር ሪፖርት (Production Report Detail)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Machine Name</th><th>Quantity</th><th>Shift</th><th>Reported By</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($production_reports as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['machine_name']); ?></td>
                            <td class="fw-bold"><?php echo number_format($p['quantity_produced']); ?></td>
                            <td><?php echo $p['shift']; ?></td>
                            <td><?php echo htmlspecialchars($p['reported_by']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($p['report_date'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div id="analytics-section" class="row g-4 mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h5 class="fw-bold mb-4 text-secondary"><i class="bi bi-pie-chart me-2"></i> የዲፓርትመንት አፈጻጸም ትንታኔ (Performance Analytics)</h5>
                    <div class="row text-center">
                        <div class="col-md-3 border-end">
                            <h3 class="fw-bold text-primary"><?php echo $total_tasks; ?></h3>
                            <small class="text-muted">Total Requests</small>
                        </div>
                        <div class="col-md-3 border-end">
                            <h3 class="fw-bold text-success"><?php echo $completed_tasks; ?></h3>
                            <small class="text-muted">Solved Issues</small>
                        </div>
                        <div class="col-md-3 border-end">
                            <h3 class="fw-bold text-danger"><?php echo $in_progress_tasks; ?></h3>
                            <small class="text-muted">Pending Issues</small>
                        </div>
                        <div class="col-md-3">
                            <h3 class="fw-bold text-dark"><?php echo $success_rate; ?>%</h3>
                            <small class="text-muted">Efficiency Score</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    new Chart(document.getElementById('completionChart'), {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Pending'],
            datasets: [{
                data: [<?php echo $completed_tasks; ?>, <?php echo $in_progress_tasks; ?>],
                backgroundColor: ['#0d6efd', '#ffc107'],
                borderWidth: 0
            }]
        },
        options: { cutout: '80%', plugins: { legend: { display: false } } }
    });
</script>

<?php include '../includes/admin_footer.php'; ?>