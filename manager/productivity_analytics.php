<?php
session_start();
require_once '../includes/db.php';
/** @var PDO $pdo */
// 1. Authentication Check
$allowed_mgr_roles = ['Department Manager', 'Engineering Manager'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_mgr_roles, true)) {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id      = (int)($_SESSION['dept_id'] ?? 0);
$user_role    = $_SESSION['user_role'];
$is_eng_mgr   = ($user_role === 'Engineering Manager');

// 2. KPI Data — Engineering Manager sees global stats; others see own dept
if ($is_eng_mgr) {
    $kpi_stmt = $pdo->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Completed'   THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'In Progress'  THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN priority IN ('Emergency', 'High') THEN 1 ELSE 0 END) as urgent
        FROM maintenance_requests
    ");
} else {
    $kpi_stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Completed'   THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'In Progress'  THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN priority IN ('Emergency', 'High') THEN 1 ELSE 0 END) as urgent
        FROM maintenance_requests WHERE dept_id = ?
    ");
    $kpi_stmt->execute([$dept_id]);
}
$kpi = $kpi_stmt->fetch();

$completion_rate = ($kpi['total'] > 0) ? round(($kpi['completed'] / $kpi['total']) * 100) : 0;

// 3. Employee Task Distribution (Top 5 Staff)
$staff_perf = $pdo->prepare("
    SELECT u.full_name, COUNT(m.id) as task_count 
    FROM users u 
    LEFT JOIN maintenance_requests m ON u.id = m.assigned_to 
    WHERE u.dept_id = ? AND u.user_role NOT IN ('Department Manager', 'Admin')
    GROUP BY u.id 
    ORDER BY task_count DESC LIMIT 5
");
$staff_perf->execute([$dept_id]);
$staff_data = $staff_perf->fetchAll();

// 4. Monthly Trend Data (Real Data from Database)
$trend_stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%b') as month, 
        COUNT(*) as count 
    FROM maintenance_requests 
    WHERE dept_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%m'), DATE_FORMAT(created_at, '%b')
    ORDER BY MIN(created_at) ASC
");
$trend_stmt->execute([$dept_id]);
$trend_results = $trend_stmt->fetchAll();

$months = [];
$trend_values = [];
foreach($trend_results as $tr) {
    $months[] = $tr['month'];
    $trend_values[] = $tr['count'];
}

// ዳታ ከሌለ ባዶ እንዳይሆን Default እሴት እንስጠው
if(empty($months)) { $months = ['No Data']; $trend_values = [0]; }

// ካለህ header_glass.php ጋር እናገናኘው
include '../includes/header_glass.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-2">
                <i class="bi bi-graph-up text-primary me-2"></i>Productivity & KPI Analytics
            </h2>
            <p class="text-muted mb-0">የዲፓርትመንቱን ውጤታማነት በግራፍ እና በቁጥር መገምገሚያ ገጽ</p>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body">
                    <div class="h4 fw-bold text-primary mb-1"><?php echo $completion_rate; ?>%</div>
                    <div class="small text-muted fw-semibold">Completion Rate</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body">
                    <div class="h4 fw-bold text-warning mb-1"><?php echo $kpi['active']; ?></div>
                    <div class="small text-muted fw-semibold">Active Tasks</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body">
                    <div class="h4 fw-bold text-danger mb-1"><?php echo $kpi['urgent']; ?></div>
                    <div class="small text-muted fw-semibold">Urgent Issues</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body">
                    <div class="h4 fw-bold text-success mb-1"><?php echo $kpi['total']; ?></div>
                    <div class="small text-muted fw-semibold">Total Volume</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-bar-chart-line text-primary me-2"></i>Task Volume Trend (Last 6 Months)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="280"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-pie-chart text-info me-2"></i>Workload Distribution
                    </h5>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <canvas id="staffChart"></canvas>
                    <div class="mt-4 text-center">
                        <small class="text-muted">Top Performer:</small>
                        <strong class="text-primary d-block"><?php echo $staff_data[0]['full_name'] ?? 'No data'; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff Efficiency Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-people text-success me-2"></i>Staff Efficiency Metrics
                    </h5>
                    <span class="badge bg-success">
                        <i class="bi bi-circle-fill text-success me-1"></i>Live Status
                    </span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-person text-muted me-1"></i>Staff Name
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-list-task text-muted me-1"></i>Tasks Assigned
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-bar-chart text-muted me-1"></i>Workload Progress
                                    </th>
                                    <th class="border-0 fw-bold">
                                        <i class="bi bi-activity text-muted me-1"></i>Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($staff_data as $row):
                                    $percent = ($kpi['total'] > 0) ? ($row['task_count'] / $kpi['total']) * 100 : 0;
                                    $status_color = ($percent > 30) ? 'danger' : 'success';
                                    $status_text = ($percent > 30) ? 'Overloaded' : 'Optimal';
                                    $status_icon = ($percent > 30) ? 'exclamation-triangle' : 'check-circle';
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?php echo $row['task_count']; ?> Tasks</span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-<?php echo $status_color; ?>" style="width: <?php echo $percent; ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?php echo round($percent, 1); ?>%</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_color; ?> bg-opacity-10 text-<?php echo $status_color; ?> border border-<?php echo $status_color; ?>">
                                            <i class="bi bi-<?php echo $status_icon; ?> me-1"></i><?php echo $status_text; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Line Chart for Task Volume Trend
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Task Volume',
                data: <?php echo json_encode($trend_values); ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.05)',
                fill: true,
                tension: 0.4,
                borderWidth: 3
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Doughnut Chart for Workload Distribution
    const ctxStaff = document.getElementById('staffChart').getContext('2d');
    new Chart(ctxStaff, {
        type: 'doughnut',
        data: {
            labels: [<?php foreach($staff_data as $s) echo "'".$s['full_name']."',"; ?>],
            datasets: [{
                data: [<?php foreach($staff_data as $s) echo $s['task_count'].","; ?>],
                backgroundColor: ['#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#fd7e14']
            }]
        },
        options: {
            cutout: '70%',
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }
        }
    });
</script>

<?php include '../includes/footer_glass.php'; ?>