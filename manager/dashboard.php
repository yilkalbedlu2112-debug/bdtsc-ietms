<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in as Department Manager
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Department Manager') {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'];
$full_name = $_SESSION['full_name'] ?? 'Manager';

// 1. Department Type and Assigned Users Logic for Task Creation
$dept_type_stmt = $pdo->prepare("SELECT dept_type, dept_name FROM departments WHERE id = ?");
$dept_type_stmt->execute([$dept_id]);
$dept_info = $dept_type_stmt->fetch();
$dept_type = $dept_info['dept_type'] ?? 'Support';
$dept_name = $dept_info['dept_name'] ?? 'Department';

if ($dept_type === 'Production') {
    $roles = "'Shift Leader', 'Supervisor'";
} else {
    $roles = "'Employee', 'Technician'";
}
$users_stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE dept_id = ? AND status = 'Active' AND role IN ($roles) ORDER BY full_name");
$users_stmt->execute([$dept_id]);
$assignable_users = $users_stmt->fetchAll();

// 2. Data Fetching for KPIs
$kpi_stmt = $pdo->prepare("SELECT 
    COUNT(*) as total, 
    SUM(CASE WHEN priority = 'Emergency' AND status != 'Completed' THEN 1 ELSE 0 END) as ongoing_repairs,
    SUM(CASE WHEN status = 'Completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as weekly_completed,
    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as weekly_total
    FROM maintenance_requests WHERE dept_id = ?");
$kpi_stmt->execute([$dept_id]);
$stats = $kpi_stmt->fetch();

$total_tasks = $stats['total'] ?: 0;
$ongoing_repairs = $stats['ongoing_repairs'] ?: 0;
$shift_perf = ($stats['weekly_total'] > 0) ? round(($stats['weekly_completed'] / $stats['weekly_total']) * 100) : 0;

// 3. Fetch Tasks for Real-time Monitor
$tasks_stmt = $pdo->prepare("SELECT m.*, u.full_name as assigned_name FROM maintenance_requests m 
    LEFT JOIN users u ON m.assigned_to = u.id 
    WHERE m.dept_id = ? ORDER BY m.created_at DESC LIMIT 30");
$tasks_stmt->execute([$dept_id]);
$monitor_tasks = $tasks_stmt->fetchAll();

// 4. For Productivity Chart (Last 7 Days)
$chart_stmt = $pdo->prepare("
    SELECT DATE(created_at) as c_date, 
           COUNT(*) as total_created, 
           SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) as total_completed 
    FROM maintenance_requests 
    WHERE dept_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
    GROUP BY c_date 
    ORDER BY c_date ASC
");
$chart_stmt->execute([$dept_id]);
$chart_data = $chart_stmt->fetchAll();

$dates = []; $created = []; $completed = [];
foreach($chart_data as $row) {
    $dates[] = date('M d', strtotime($row['c_date']));
    $created[] = $row['total_created'];
    $completed[] = $row['total_completed'];
}

include '../includes/header_glass.php';
?>

<!-- Toasts -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1055;">
    <div id="mgrToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body fw-semibold" id="toastMsg">Action complete.</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
</div>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-dark fw-bold"><i class="bi bi-briefcase-fill text-primary me-2"></i><?php echo __('dashboard') ?? 'Dashboard'; ?> - <?php echo htmlspecialchars($dept_name); ?></h2>
            <p class="text-muted mb-0">Departmental Operations Overview</p>
        </div>
        <div class="d-flex gap-2 text-end">
            <button class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                <i class="bi bi-plus-circle me-1"></i> Create Core Task
            </button>
            <button class="btn btn-danger rounded-pill px-4 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#reqMaintenanceModal">
                <i class="bi bi-tools me-1"></i> Request Engineering
            </button>
        </div>
    </div>

    <!-- 1. Top KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-primary border-4">
                <h6 class="text-muted fw-bold mb-1 text-uppercase">Total Dept Tasks</h6>
                <h3 class="fw-bold text-dark mb-0"><?php echo $total_tasks; ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-danger border-4">
                <h6 class="text-muted fw-bold mb-1 text-uppercase">Ongoing Repairs (Emergency)</h6>
                <h3 class="fw-bold text-danger mb-0"><?php echo $ongoing_repairs; ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-success border-4">
                <h6 class="text-muted fw-bold mb-1 text-uppercase">Shift Performance</h6>
                <h3 class="fw-bold text-success mb-0"><?php echo $shift_perf; ?>%</h3>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- 2. Real-time Monitor -->
        <div class="col-lg-8">
            <div class="card glass-card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0"><i class="bi bi-activity text-info me-2"></i>Real-Time Monitor (Active Tasks)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="border-0">Task / Asset</th>
                                    <th class="border-0">Assigned Actor</th>
                                    <th class="border-0">Priority</th>
                                    <th class="border-0">Live Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($monitor_tasks)): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">No active workflow found.</td></tr>
                                <?php endif; ?>
                                <?php foreach($monitor_tasks as $mt): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($mt['machine_name']); ?></div>
                                        <div class="small text-muted text-truncate" style="max-width:200px;"><?php echo htmlspecialchars($mt['issue_description']); ?></div>
                                    </td>
                                    <td>
                                        <?php if($mt['assigned_name']): ?>
                                            <span class="badge bg-light text-dark border px-2"><i class="bi bi-person me-1"></i><?php echo htmlspecialchars($mt['assigned_name']); ?></span>
                                        <?php elseif($mt['assigned_to_dept']): ?>
                                            <span class="badge bg-light text-primary border px-2"><i class="bi bi-building me-1"></i><?php echo htmlspecialchars($mt['assigned_to_dept']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small"><em>Unassigned</em></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo ($mt['priority'] == 'Emergency' || $mt['priority'] == 'High') ? 'danger' : 'info'; ?> text-white rounded-pill">
                                            <?php echo htmlspecialchars($mt['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo strpos($mt['status'], 'Completed') !== false ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                            <?php echo htmlspecialchars($mt['status']); ?>
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

        <!-- 3. Productivity Analytics -->
        <div class="col-lg-4">
            <div class="card glass-card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0"><i class="bi bi-graph-up text-primary me-2"></i>Productivity Analytics</h5>
                    <small class="text-muted">Task Completion Ratio (Last 7 Days)</small>
                </div>
                <div class="card-body">
                    <canvas id="productivityChart" style="min-height: 250px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Advanced Reporting -->
    <div class="card glass-card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 pb-2">
            <h5 class="fw-bold mb-0"><i class="bi bi-file-earmark-bar-graph text-success me-2"></i>Advanced Reporting</h5>
        </div>
        <div class="card-body">
            <form action="generate_report.php" method="POST" id="reportForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label text-muted fw-semibold">Start Date</label>
                        <input type="date" name="start_date" class="form-control bg-light border-0" required id="r_start">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fw-semibold">End Date</label>
                        <input type="date" name="end_date" class="form-control bg-light border-0" required id="r_end">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fw-semibold">Report Type</label>
                        <select name="report_type" class="form-select bg-light border-0">
                            <option value="all">All Operations</option>
                            <option value="completed">Completed Tasks Only</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" name="generate_pdf" class="btn btn-outline-danger w-50 fw-semibold rounded-pill" title="Export PDF">
                            <i class="bi bi-file-pdf"></i> PDF
                        </button>
                        <button type="submit" formaction="export_excel.php" name="export_excel" class="btn btn-outline-success w-50 fw-semibold rounded-pill" title="Export Excel">
                            <i class="bi bi-file-excel"></i> Excel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modals -->

<!-- Create Core Task Modal -->
<div class="modal fade" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-primary"><i class="bi bi-plus-circle me-2"></i>Create Core Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form onsubmit="createCoreTask(event)">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold">Task Title</label>
                        <input type="text" id="tk_title" class="form-control bg-light border-0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold">Task Type</label>
                        <select id="tk_type" class="form-select bg-light border-0" required>
                            <option value="Production">Production</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Administrative">Administrative</option>
                            <option value="General">General</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold">Description</label>
                        <textarea id="tk_desc" class="form-control bg-light border-0" rows="3" required></textarea>
                    </div>
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <label class="form-label text-muted fw-semibold">Priority</label>
                            <select id="tk_prio" class="form-select bg-light border-0">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted fw-semibold">Assign To</label>
                            <select id="tk_assign" class="form-select bg-light border-0">
                                <option value="">(Leave Pending)</option>
                                <?php foreach($assignable_users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?> (<?php echo $u['role']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Delegate Task</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Request Maintenance Modal -->
<div class="modal fade" id="reqMaintenanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card shadow border-danger border-2">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Request Engineering</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form onsubmit="requestEngineering(event)">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold">Asset / Machine Group</label>
                        <input type="text" id="rm_machine" class="form-control bg-light border-0" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted fw-semibold">Emergency Details</label>
                        <textarea id="rm_desc" class="form-control bg-light border-0" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger w-100 rounded-pill fw-bold shadow-sm">Send Alert to Engineering Dashboard</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Utilities
function tst(msg, err=false) {
    const el = document.getElementById('mgrToast');
    document.getElementById('toastMsg').textContent = msg;
    el.className = 'toast align-items-center text-white border-0 ' + (err ? 'bg-danger' : 'bg-success');
    new bootstrap.Toast(el).show();
}

// Chart.js render
document.addEventListener('DOMContentLoaded', function() {
    // Set default dates for reporting
    const today = new Date();
    const ago = new Date(); ago.setDate(today.getDate() - 30);
    document.getElementById('r_end').value = today.toISOString().split('T')[0];
    document.getElementById('r_start').value = ago.toISOString().split('T')[0];

    // Chart logic
    const ctx = document.getElementById('productivityChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [
                {
                    label: 'Created Tasks',
                    data: <?php echo json_encode($created); ?>,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Completed Tasks',
                    data: <?php echo json_encode($completed); ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
});

// AJAX Actions
function createCoreTask(e) {
    e.preventDefault();
    fetch('mgr_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'create_task',
            title: document.getElementById('tk_title').value,
            task_type: document.getElementById('tk_type').value,
            description: document.getElementById('tk_desc').value,
            priority: document.getElementById('tk_prio').value,
            assigned_to: document.getElementById('tk_assign').value
        })
    }).then(res => res.json()).then(data => {
        if(data.success){
            tst(data.message);
            bootstrap.Modal.getInstance(document.getElementById('createTaskModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else { tst(data.message, true); }
    }).catch(() => tst('Network Error', true));
}

function requestEngineering(e) {
    e.preventDefault();
    fetch('mgr_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'request_engineering',
            machine: document.getElementById('rm_machine').value,
            description: document.getElementById('rm_desc').value
        })
    }).then(res => res.json()).then(data => {
        if(data.success){
            tst(data.message);
            bootstrap.Modal.getInstance(document.getElementById('reqMaintenanceModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else { tst(data.message, true); }
    }).catch(() => tst('Network Error', true));
}
</script>

<?php include '../includes/footer_glass.php'; ?>