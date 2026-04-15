<?php
session_start();
require_once '../includes/db.php';

// ጥበቃ (Security Check)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Department Manager') {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'];
$dept_name = $_SESSION['dept_name'] ?? 'Production';

// --- የዳታ መሰብሰቢያ (Data Queries) ---

// 1. የዛሬ ምርት በዩኒት (Today's Total Production)
$today_prod = $pdo->prepare("SELECT SUM(quantity_produced) FROM production_reports WHERE dept_id = ? AND DATE(created_at) = CURDATE()");
$today_prod->execute([$dept_id]);
$today_total = $today_prod->fetchColumn() ?? 0;

// 2. በስራ ላይ ያሉ ማሽኖች (Active Machines)
$active_machines = $pdo->prepare("SELECT COUNT(DISTINCT machine_name) FROM production_reports WHERE dept_id = ? AND DATE(created_at) = CURDATE()");
$active_machines->execute([$dept_id]);
$machine_count = $active_machines->fetchColumn() ?? 0;

// 3. ዝርዝር የምርት ሪፖርት (Production Logs)
$logs_stmt = $pdo->prepare("
    SELECT p.*, u.full_name as supervisor 
    FROM production_reports p 
    LEFT JOIN users u ON p.user_id = u.id 
    WHERE p.dept_id = ? 
    ORDER BY p.id DESC LIMIT 50
");
$logs_stmt->execute([$dept_id]);
$production_logs = $logs_stmt->fetchAll();

// 4. የፈረቃ ስታቲስቲክስ (Shift-wise Distribution)
$shift_stmt = $pdo->prepare("SELECT shift, SUM(quantity_produced) as total FROM production_reports WHERE dept_id = ? GROUP BY shift");
$shift_stmt->execute([$dept_id]);
$shift_data = $shift_stmt->fetchAll();

include '../includes/manager_header.php';
?>

<style>
    :root { --prod-green: #2ecc71; --sidebar-bg: #1e2b37; }
    .wrapper { display: flex; min-height: 100vh; background: #f4f7f6; }
    
    /* Sidebar Customization */
    .sidebar { width: 280px; background: var(--sidebar-bg); color: white; position: fixed; height: 100vh; }
    .sidebar .nav-link { color: #aeb9c2; padding: 15px 25px; border-left: 5px solid transparent; transition: 0.3s; }
    .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #2c3e50; color: white; border-left-color: var(--prod-green); }
    
    .main-content { flex: 1; margin-left: 280px; padding: 40px; }
    .card-stat { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .status-indicator { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    
    .tab-pane { display: none; }
    .tab-pane.active { display: block; animation: slideUp 0.4s ease-out; }
    @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="wrapper">
    <nav class="sidebar">
        <div class="p-4 text-center border-bottom border-secondary">
            <h4 class="fw-bold text-white mb-0">PROD-TRACK</h4>
            <small class="text-success">Production Management</small>
        </div>
        <div class="nav flex-column mt-4">
            <a class="nav-link active" data-bs-toggle="pill" href="#overview"><i class="bi bi-grid-1x2-fill me-2"></i> Production Overview</a>
            <a class="nav-link" data-bs-toggle="pill" href="#daily-logs"><i class="bi bi-journal-text me-2"></i> Daily Output Logs</a>
            <a class="nav-link" data-bs-toggle="pill" href="#efficiency"><i class="bi bi-lightning-charge me-2"></i> Machine Efficiency</a>
            <a class="nav-link" data-bs-toggle="pill" href="#shifts"><i class="bi bi-clock-history me-2"></i> Shift Analytics</a>
            <hr class="mx-4 opacity-25">
            <a href="add_production.php" class="nav-link text-success"><i class="bi bi-plus-circle-fill me-2"></i> Report New Output</a>
        </div>
    </nav>

    <main class="main-content">
        <div class="tab-content">

            <div class="tab-pane active" id="overview">
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card card-stat p-4 bg-white">
                            <h6 class="text-muted small text-uppercase">Today's Production</h6>
                            <h2 class="fw-bold text-success mt-2"><?php echo number_format($today_total); ?> <small class="fs-6 text-muted">Units</small></h2>
                            <div class="progress mt-3" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: 75%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stat p-4 bg-white">
                            <h6 class="text-muted small text-uppercase">Active Machines</h6>
                            <h2 class="fw-bold text-primary mt-2"><?php echo $machine_count; ?></h2>
                            <p class="small text-muted mt-2"><span class="status-indicator bg-success"></span> All systems operational</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stat p-4 bg-dark text-white">
                            <h6 class="opacity-75 small text-uppercase">Monthly Target</h6>
                            <h2 class="fw-bold mt-2">82%</h2>
                            <small class="text-info">18% remaining to goal</small>
                        </div>
                    </div>
                </div>

                <div class="card card-stat p-4 mb-4">
                    <h5 class="fw-bold mb-4">Production Trend (Weekly)</h5>
                    <canvas id="prodTrendChart" style="max-height: 350px;"></canvas>
                </div>
            </div>

            <div class="tab-pane" id="daily-logs">
                <div class="card card-stat p-4">
                    <div class="d-flex justify-content-between mb-4">
                        <h5 class="fw-bold">Detailed Production Output</h5>
                        <button class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i> Export CSV</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Ref ID</th><th>Machine/Line</th><th>Quantity Produced</th><th>Shift</th><th>Supervisor</th><th>Time Reported</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($production_logs as $log): ?>
                                <tr>
                                    <td>#PROD-<?php echo $log['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($log['machine_name']); ?></strong></td>
                                    <td class="text-success fw-bold"><?php echo number_format($log['quantity_produced']); ?></td>
                                    <td><span class="badge rounded-pill bg-light text-dark border"><?php echo $log['shift']; ?></span></td>
                                    <td><?php echo htmlspecialchars($log['supervisor']); ?></td>
                                    <td><?php echo date('h:i A', strtotime($log['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane" id="shifts">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card card-stat p-4">
                            <h5 class="fw-bold mb-4">Output by Shift</h5>
                            <canvas id="shiftPieChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-stat p-4 bg-light">
                            <h5>Shift Summary</h5>
                            <ul class="list-group list-group-flush bg-transparent">
                                <?php foreach($shift_data as $sd): ?>
                                <li class="list-group-item bg-transparent d-flex justify-content-between">
                                    <strong><?php echo $sd['shift']; ?> Shift</strong>
                                    <span><?php echo number_format($sd['total']); ?> Units</span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Sidebar Tab Switching
    document.querySelectorAll('.nav-link[data-bs-toggle="pill"]').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            document.querySelector(this.getAttribute('href')).classList.add('active');
        });
    });

    // 2. Production Trend Chart
    new Chart(document.getElementById('prodTrendChart'), {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            datasets: [{
                label: 'Production Output',
                data: [4500, 5200, 4800, 6100, 5900, 6300],
                borderColor: '#2ecc71',
                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                fill: true,
                tension: 0.4
            }]
        }
    });

    // 3. Shift Pie Chart
    new Chart(document.getElementById('shiftPieChart'), {
        type: 'doughnut',
        data: {
            labels: [<?php foreach($shift_data as $s) echo "'".$s['shift']."',"; ?>],
            datasets: [{
                data: [<?php foreach($shift_data as $s) echo $s['total'].","; ?>],
                backgroundColor: ['#1abc9c', '#3498db', '#f1c40f']
            }]
        }
    });
</script>

<?php include '../includes/footer_glass.php'; ?>