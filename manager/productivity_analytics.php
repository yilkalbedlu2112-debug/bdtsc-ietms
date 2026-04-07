<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Department Manager') {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'];

// 1. KPI Data - Task Stats
$kpi_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN priority = 'Emergency' THEN 1 ELSE 0 END) as urgent
    FROM maintenance_requests WHERE dept_id = ?
");
$kpi_stmt->execute([$dept_id]);
$kpi = $kpi_stmt->fetch();

$completion_rate = ($kpi['total'] > 0) ? round(($kpi['completed'] / $kpi['total']) * 100) : 0;

// 2. Employee Task Distribution (ለባር ቻርት)
$staff_perf = $pdo->prepare("
    SELECT u.full_name, COUNT(m.id) as task_count 
    FROM users u 
    LEFT JOIN maintenance_requests m ON u.id = m.assigned_to 
    WHERE u.dept_id = ? AND u.role != 'Department Manager'
    GROUP BY u.id LIMIT 5
");
$staff_perf->execute([$dept_id]);
$staff_data = $staff_perf->fetchAll();

// 3. Monthly Trend Data (ናሙና ለግራፍ)
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
$trend_data = [45, 52, 48, 70, 65, 80]; 

include '../includes/manager_header.php';
?>

<div class="container-fluid py-4" style="background: #f0f2f5; min-height: 100vh;">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold text-dark"><i class="bi bi-graph-up text-primary me-2"></i> Productivity & KPI Analytics</h3>
            <p class="text-muted">የዲፓርትመንቱን ውጤታማነት በግራፍ እና በቁጥር መገምገሚያ ገጽ</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="rounded-circle bg-light-primary mx-auto mb-2" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-check2-all fs-4 text-primary"></i>
                </div>
                <h4 class="fw-bold mb-0"><?php echo $completion_rate; ?>%</h4>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Completion Rate</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="rounded-circle bg-light-warning mx-auto mb-2" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-clock-history fs-4 text-warning"></i>
                </div>
                <h4 class="fw-bold mb-0"><?php echo $kpi['active']; ?></h4>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Active Tasks</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3 border-start border-danger border-4">
                <h4 class="fw-bold mb-0 text-danger"><?php echo $kpi['urgent']; ?></h4>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Urgent Issues</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3 bg-primary text-white">
                <h4 class="fw-bold mb-0"><?php echo $kpi['total']; ?></h4>
                <small class="text-white-50 text-uppercase fw-bold" style="font-size: 10px;">Total Volume</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-bold">Performance Trend (Monthly)</h6>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-bold">Task Distribution by Staff</h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <canvas id="staffChart"></canvas>
                    <div class="mt-4">
                        <small class="text-muted d-block mb-2">Top Performer: 
                            <strong class="text-dark"><?php echo $staff_data[0]['full_name'] ?? 'N/A'; ?></strong>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Staff Efficiency Metrics</h6>
                    <span class="badge bg-soft-success text-success">Real-time Data</span>
                </div>
                <div class="table-responsive p-3">
                    <table class="table table-borderless align-middle">
                        <thead class="text-muted small">
                            <tr>
                                <th>STAFF NAME</th>
                                <th>TOTAL TASKS</th>
                                <th>PROGRESS BAR</th>
                                <th>LOAD STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($staff_data as $row): 
                                $percent = ($kpi['total'] > 0) ? ($row['task_count'] / $kpi['total']) * 100 : 0;
                                $status_color = ($percent > 40) ? 'danger' : 'primary';
                            ?>
                            <tr>
                                <td><div class="fw-bold"><?php echo htmlspecialchars($row['full_name']); ?></div></td>
                                <td><span class="badge bg-light text-dark"><?php echo $row['task_count']; ?> Tasks</span></td>
                                <td style="width: 40%;">
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-<?php echo $status_color; ?>" style="width: <?php echo $percent; ?>%"></div>
                                    </div>
                                </td>
                                <td>
                                    <?php if($percent > 40): ?>
                                        <span class="text-danger small"><i class="bi bi-exclamation-triangle"></i> Overloaded</span>
                                    <?php else: ?>
                                        <span class="text-success small"><i class="bi bi-check-circle"></i> Optimal</span>
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
    // Trend Line Chart
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Completed Tasks',
                data: <?php echo json_encode($trend_data); ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.05)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 4
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
        }
    });

    // Staff Donut Chart
    const ctxStaff = document.getElementById('staffChart').getContext('2d');
    new Chart(ctxStaff, {
        type: 'doughnut',
        data: {
            labels: [<?php foreach($staff_data as $s) echo "'".$s['full_name']."',"; ?>],
            datasets: [{
                data: [<?php foreach($staff_data as $s) echo $s['task_count'].","; ?>],
                backgroundColor: ['#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#fd7e14'],
                hoverOffset: 10
            }]
        },
        options: {
            cutout: '75%',
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } }
        }
    });
</script>

<style>
    .bg-light-primary { background-color: rgba(13, 110, 253, 0.1); }
    .bg-light-warning { background-color: rgba(255, 193, 7, 0.1); }
    .bg-soft-success { background-color: #e8f5e9; }
</style>

<?php include '../includes/admin_footer.php'; ?>