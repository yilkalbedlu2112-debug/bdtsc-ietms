<?php
session_start();
require_once '../includes/db.php';

// Security: Check if user is logged in as Department Manager
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Department Manager') {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'];
$full_name = $_SESSION['full_name'] ?? 'Manager';

// --- DATA FETCHING (Dynamic Counters) ---
// 1. Task Stats
$stats_stmt = $pdo->prepare("SELECT 
    COUNT(*) as total, 
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN priority = 'Emergency' AND status != 'Completed' THEN 1 ELSE 0 END) as urgent
    FROM maintenance_requests WHERE dept_id = ?");
$stats_stmt->execute([$dept_id]);
$stats = $stats_stmt->fetch();

// 2. Fetch Tasks for Progress Monitor
$tasks_stmt = $pdo->prepare("SELECT m.*, u.full_name as technician FROM maintenance_requests m 
    LEFT JOIN users u ON m.assigned_to = u.id 
    WHERE m.dept_id = ? ORDER BY m.created_at DESC");
$tasks_stmt->execute([$dept_id]);
$tasks = $tasks_stmt->fetchAll();

include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Welcome back, <?php echo $full_name; ?>!</h3>
        <div class="text-muted fw-medium"><?php echo date('D, M d, Y'); ?></div>
    </div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Welcome back, <?php echo $full_name; ?>!</h3>
                    <div class="text-muted fw-medium"><?php echo date('D, M d, Y'); ?></div>
                </div>
                
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="card card-kpi shadow-sm p-4 bg-white border-start border-primary border-5">
                            <small class="text-muted text-uppercase fw-bold">Total Department Tasks</small>
                            <h2 class="fw-bold mt-2"><?php echo $stats['total']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-kpi shadow-sm p-4 bg-white border-start border-success border-5">
                            <small class="text-muted text-uppercase fw-bold">Completed Tasks</small>
                            <h2 class="fw-bold mt-2 text-success"><?php echo $stats['completed']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-kpi shadow-sm p-4 bg-white border-start border-danger border-5">
                            <small class="text-muted text-uppercase fw-bold">Emergency Issues</small>
                            <h2 class="fw-bold mt-2 text-danger"><?php echo $stats['urgent']; ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm p-4">
                    <h5>Quick Start</h5>
                    <p class="text-muted">ከግራ በኩል ያለውን ሜኑ በመጠቀም አዳዲስ ስራዎችን መመደብ፣ አፈጻጸሞችን ማየት እና ሪፖርቶችን ማውጣት ይችላሉ።</p>
                </div>
                
                <div class="mb-5">
                    <div class="card border-0 shadow-sm p-4 glass-card">
                    <h4 class="fw-bold mb-4">Real-Time Task Status</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Asset</th><th>Technician</th><th>Priority</th><th>Status</th><th>Timeline</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($tasks as $t): ?>
                                <tr>
                                    <td><strong><?php echo $t['machine_name']; ?></strong></td>
                                    <td><?php echo $t['technician'] ?? 'Pending Assignment'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($t['priority'] == 'Emergency') ? 'danger' : 'info'; ?>">
                                            <?php echo $t['priority']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge border text-dark">
                                            <i class="bi bi-circle-fill small me-1 text-<?php echo ($t['status'] == 'Completed') ? 'success' : 'warning'; ?>"></i>
                                            <?php echo $t['status']; ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted"><?php echo date('M d, H:i', strtotime($t['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                
                <div class="mb-5">
                <div class="row g-4">
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm p-4">
                            <h5 class="fw-bold mb-4">Task Completion Analytics</h5>
                            <canvas id="completionChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm p-4 h-100 bg-primary text-white">
                            <h5>Success Rate</h5>
                            <h1 class="display-4 fw-bold"><?php echo ($stats['total'] > 0) ? round(($stats['completed']/$stats['total'])*100) : 0; ?>%</h1>
                            <p class="small opacity-75">Average department efficiency based on historical task data.</p>
                        </div>
                    </div>
    </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Productivity Chart (Graph)
    new Chart(document.getElementById('completionChart'), {
        type: 'bar',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            datasets: [{
                label: 'Tasks Completed',
                data: [5, 8, 4, 10, 7, 9],
                backgroundColor: '#2563eb'
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>

<?php include '../includes/footer_glass.php'; ?>