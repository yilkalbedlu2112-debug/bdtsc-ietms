<?php
session_start();
require_once '../includes/db.php';

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

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold text-dark"><i class="bi bi-graph-up text-primary me-2"></i> Productivity & KPI Analytics</h3>
            <p class="text-muted">የዲፓርትመንቱን ውጤታማነት በግራፍ እና በቁጥር መገምገሚያ ገጽ</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card glass-card border-0 shadow-sm text-center p-3 border-bottom border-primary border-4">
                <h4 class="fw-bold mb-0"><?php echo $completion_rate; ?>%</h4>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Completion Rate</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card glass-card border-0 shadow-sm text-center p-3 border-bottom border-warning border-4">
                <h4 class="fw-bold mb-0 text-warning"><?php echo $kpi['active']; ?></h4>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Active Tasks</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card glass-card border-0 shadow-sm text-center p-3 border-bottom border-danger border-4">
                <h4 class="fw-bold mb-0 text-danger"><?php echo $kpi['urgent']; ?></h4>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Urgent Issues</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card glass-card border-0 shadow-sm text-center p-3 bg-primary text-white">
                <h4 class="fw-bold mb-0"><?php echo $kpi['total']; ?></h4>
                <small class="text-white-50 text-uppercase fw-bold" style="font-size: 10px;">Total Volume</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card glass-card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="mb-0 fw-bold">Task Volume Trend (Last 6 Months)</h6>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="280"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card glass-card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="mb-0 fw-bold">Workload Distribution</h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <canvas id="staffChart"></canvas>
                    <div class="mt-4 text-center">
                        <small class="text-muted">Top Performer: </small>
                        <strong class="text-primary d-block"><?php echo $staff_data[0]['full_name'] ?? 'No data'; ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card glass-card border-0 shadow-sm mt-4">
                <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Staff Efficiency Metrics</h6>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success">Live Status</span>
                </div>
                <div class="table-responsive p-3">
                    <table class="table align-middle">
                        <thead class="text-muted small">
                            <tr>
                                <th>STAFF NAME</th>
                                <th>TASKS ASSIGNED</th>
                                <th>WORKLOAD PROGRESS</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($staff_data as $row): 
                                $percent = ($kpi['total'] > 0) ? ($row['task_count'] / $kpi['total']) * 100 : 0;
                                $status_color = ($percent > 30) ? 'danger' : 'primary';
                            ?>
                            <tr>
                                <td><div class="fw-bold text-dark"><?php echo htmlspecialchars($row['full_name']); ?></div></td>
                                <td><span class="badge bg-light text-dark border"><?php echo $row['task_count']; ?> Tasks</span></td>
                                <td style="width: 40%;">
                                    <div class="progress" style="height: 10px; border-radius: 10px;">
                                        <div class="progress-bar bg-<?php echo $status_color; ?>" style="width: <?php echo $percent; ?>%"></div>
                                    </div>
                                </td>
                                <td>
                                    <?php if($percent > 30): ?>
                                        <span class="text-danger small fw-bold"><i class="bi bi-exclamation-triangle"></i> Overloaded</span>
                                    <?php else: ?>
                                        <span class="text-success small fw-bold"><i class="bi bi-check-circle"></i> Optimal</span>
                                    <?php endif; ?>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Line Chart
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

    // 2. Doughnut Chart
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