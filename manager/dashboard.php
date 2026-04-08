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

include '../includes/manager_header.php';
?>

<style>
    :root { --primary-blue: #2563eb; --sidebar-dark: #1e293b; --bg-gray: #f8fafc; }
    body { background-color: var(--bg-gray); font-family: 'Inter', sans-serif; }
    .wrapper { display: flex; min-height: 100vh; }
    
    /* Sidebar Layout */
    .sidebar { width: 280px; background: var(--sidebar-dark); color: white; position: fixed; height: 100vh; z-index: 1000; }
    .main-content { flex: 1; margin-left: 280px; padding: 30px; transition: 0.3s; }
    
    .nav-link { color: #94a3b8; padding: 14px 24px; border-radius: 8px; margin: 4px 15px; display: flex; align-items: center; transition: 0.2s; }
    .nav-link:hover, .nav-link.active { background: #334155; color: white; }
    .nav-link.active { border-left: 4px solid var(--primary-blue); background: rgba(37, 99, 235, 0.1); }
    .nav-link i { margin-right: 12px; font-size: 1.2rem; }

    /* UI Components */
    .tab-pane { display: none; animation: fadeIn 0.3s ease-in-out; }
    .tab-pane.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    .card-kpi { border: none; border-radius: 12px; transition: transform 0.2s; }
    .card-kpi:hover { transform: translateY(-3px); }
    /* ዘመናዊ የካርድ ስታይል */
.card {
    border: none;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px); /* Glass effect */
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

/* Sidebar ማሳመሪያ */
.nav-link {
    border-radius: 12px !important;
    margin: 5px 15px;
    font-weight: 500;
}

/* Status Badges */
.badge {
    padding: 8px 12px;
    border-radius: 8px;
    font-weight: 600;
    letter-spacing: 0.5px;
}
</style>

<div class="wrapper">
    <nav class="sidebar">
        <div class="p-4 border-bottom border-secondary mb-3">
            <h5 class="fw-bold text-white mb-0">BDTSC-IETMS</h5>
            <small class="text-info">Dept Manager Portal</small>
        </div>
        <div class="nav flex-column" id="v-pills-tab" role="tablist">
            <a class="nav-link active" data-bs-toggle="pill" href="#overview"><i class="bi bi-grid-1x2"></i> Dashboard Overview</a>
            <a class="nav-link" data-bs-toggle="pill" href="#create-task"><i class="bi bi-plus-square"></i> Create New Task</a>
            <a class="nav-link" data-bs-toggle="pill" href="#monitor"><i class="bi bi-activity"></i> Task Progress</a>
            <a class="nav-link" data-bs-toggle="pill" href="#analytics"><i class="bi bi-bar-chart-line"></i> Productivity (KPI)</a>
            <a class="nav-link" data-bs-toggle="pill" href="#reports"><i class="bi bi-file-earmark-pdf"></i> Generate Reports</a>
            <hr class="mx-3 opacity-25">
            <a href="../auth/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
        </div>
    </nav>

    <main class="main-content">
        <div class="tab-content">
            
            <div class="tab-pane active" id="overview">
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
            </div>

            <div class="tab-pane" id="create-task">
                <div class="card border-0 shadow-sm p-4">
                    <h4 class="fw-bold mb-4">Assign New Maintenance Task</h4>
                    <form action="save_task.php" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Machine Name/Line</label>
                                <input type="text" name="machine_name" class="form-control" required placeholder="Ex: Spinning Machine #4">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Task Priority</label>
                                <select name="priority" class="form-select" required>
                                    <option value="Low">Low (Routine)</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                    <option value="Emergency">🚨 Emergency (Immediate Focus)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Detailed Instructions</label>
                                <textarea name="instructions" class="form-control" rows="4" placeholder="Describe the issue and required action..."></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary px-5 py-2">Assign Task Now</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="tab-pane" id="monitor">
                <div class="card border-0 shadow-sm p-4">
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
                </div>
            </div>

            <div class="tab-pane" id="analytics">
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

            <div class="tab-pane" id="reports">
                <div class="card border-0 shadow-sm p-5 text-center">
                    <div class="mb-4 text-primary"><i class="bi bi-file-earmark-bar-graph display-1"></i></div>
                    <h3>Export Performance Data</h3>
                    <p class="text-muted mx-auto" style="max-width: 500px;">የዲፓርትመንቱን ታሪካዊ መረጃዎች ለበላይ ማናጀሮች ለማቅረብ በ PDF ወይም በ Excel አውርደው መጠቀም ይችላሉ።</p>
                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <a href="generate_report.php?type=pdf" class="btn btn-outline-danger btn-lg px-4"><i class="bi bi-file-pdf"></i> Export PDF</a>
                        <a href="export_excel.php" class="btn btn-outline-success btn-lg px-4"><i class="bi bi-file-excel"></i> Export Excel</a>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Sidebar Tab Swapping
    document.querySelectorAll('.nav-link[data-bs-toggle="pill"]').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            document.querySelector(this.getAttribute('href')).classList.add('active');
        });
    });

    // 2. Productivity Chart (Graph)
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

<?php include '../includes/admin_footer.php'; ?>