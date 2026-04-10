<?php
session_start();
require_once '../includes/db.php';

// 1. የመግቢያ ፈቃድ ማረጋገጫ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Department Manager') {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'];
$full_name = $_SESSION['full_name'] ?? 'Manager';

// 2. የዲፓርትመንቱን መረጃ ከዳታቤዝ እናምጣ
$dept_stmt = $pdo->prepare("SELECT dept_name, dept_type FROM departments WHERE id = ?");
$dept_stmt->execute([$dept_id]);
$dept_info = $dept_stmt->fetch();
$dept_name = $dept_info['dept_name'] ?? 'Department';

// 3. የዲፓርትመንት መለያ ሎጂክ (Technical vs Admin)
// በዝርዝሩ ውስጥ ያሉ ዲፓርትመንቶች እንደ "Production/Technical" ይቆጠራሉ
$production_keywords = [
    'Spinning', 'Weaving', 'Processing', 'Garment', 
    'Engineering', 'Quality Assurance', 'Production'
];

$is_production = false;
foreach ($production_keywords as $key) {
    if (stripos($dept_name, $key) !== false) {
        $is_production = true;
        break;
    }
}

// እንደየ ዲፓርትመንቱ አይነት የሚቀያየሩ ጽሁፎች
if ($is_production) {
    $card_1 = "Total Technical Tasks";
    $card_2 = "Ongoing Repairs (Emergency)";
    $card_3 = "Machine Efficiency";
    $table_h = "Machine / Asset";
    $btn_extra_name = "Request Engineering";
    $btn_extra_icon = "bi-tools";
    $btn_extra_class = "btn-danger";
    $target_modal = "#reqMaintenanceModal";
    $roles_filter = "'Shift Leader', 'Supervisor', 'Technician'";
} else {
    $card_1 = "Total Admin Tasks";
    $card_2 = "Urgent Deadlines / Requests";
    $card_3 = "Operational Performance";
    $table_h = "Process / Subject";
    $btn_extra_name = "Internal Request";
    $btn_extra_icon = "bi-file-earmark-text";
    $btn_extra_class = "btn-secondary";
    $target_modal = "#createTaskModal";
    $roles_filter = "'Employee', 'Staff', 'Officer'";
}

// 4. ለሰራተኞች ምርጫ (Assign To) የሚሆኑ ተጠቃሚዎችን ማምጣት
$users_stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE dept_id = ? AND status = 'Active' AND role IN ($roles_filter) ORDER BY full_name");
$users_stmt->execute([$dept_id]);
$assignable_users = $users_stmt->fetchAll();

// 5. KPI Data ማምጣት
$kpi_stmt = $pdo->prepare("SELECT 
    COUNT(*) as total, 
    SUM(CASE WHEN priority IN ('Emergency', 'High') AND status != 'Completed' THEN 1 ELSE 0 END) as ongoing_repairs,
    SUM(CASE WHEN status = 'Completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as weekly_completed,
    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as weekly_total
    FROM maintenance_requests WHERE dept_id = ?");
$kpi_stmt->execute([$dept_id]);
$stats = $kpi_stmt->fetch();

$total_tasks = $stats['total'] ?: 0;
$ongoing_repairs = $stats['ongoing_repairs'] ?: 0;
$shift_perf = ($stats['weekly_total'] > 0) ? round(($stats['weekly_completed'] / $stats['weekly_total']) * 100) : 0;

// 6. የቅርብ ጊዜ ስራዎች (Monitor)
$tasks_stmt = $pdo->prepare("SELECT m.*, u.full_name as assigned_name FROM maintenance_requests m 
    LEFT JOIN users u ON m.assigned_to = u.id 
    WHERE m.dept_id = ? ORDER BY m.created_at DESC LIMIT 15");
$tasks_stmt->execute([$dept_id]);
$monitor_tasks = $tasks_stmt->fetchAll();

include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-dark fw-bold">
                <i class="bi bi-briefcase-fill text-primary me-2"></i>
                Dashboard - <?php echo htmlspecialchars($dept_name); ?>
            </h2>
            <p class="text-muted mb-0">Departmental Operations Overview</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                <i class="bi bi-plus-circle me-1"></i> Create Core Task
            </button>
            <a href="audit_logs.php" class="btn btn-outline-dark rounded-pill px-4 fw-semibold shadow-sm">
               <i class="bi bi-shield-lock me-1"></i> View Audit Logs
           </a>
            <button class="btn <?php echo $btn_extra_class; ?> rounded-pill px-4 fw-semibold shadow-sm" 
                    data-bs-toggle="modal" data-bs-target="<?php echo $target_modal; ?>">
                <i class="bi <?php echo $btn_extra_icon; ?> me-1"></i> 
                <?php echo $btn_extra_name; ?>
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-primary border-4">
                <h6 class="text-muted fw-bold mb-1 text-uppercase"><?php echo $card_1; ?></h6>
                <h3 class="fw-bold text-dark mb-0"><?php echo $total_tasks; ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-danger border-4">
                <h6 class="text-muted fw-bold mb-1 text-uppercase"><?php echo $card_2; ?></h6>
                <h3 class="fw-bold text-danger mb-0"><?php echo $ongoing_repairs; ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-success border-4">
                <h6 class="text-muted fw-bold mb-1 text-uppercase"><?php echo $card_3; ?></h6>
                <h3 class="fw-bold text-success mb-0"><?php echo $shift_perf; ?>%</h3>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-12">
            <div class="card glass-card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0"><i class="bi bi-activity text-info me-2"></i>Real-Time Monitor (Active Tasks)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0"><?php echo $table_h; ?></th>
                                    <th class="border-0">Assigned Actor</th>
                                    <th class="border-0">Priority</th>
                                    <th class="border-0">Live Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($monitor_tasks)): ?>
                                   <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                      <i class="bi bi-exclamation-circle me-2"></i>Insufficient data to generate analytics.
                                   </td>
                                  </tr>
                                <?php endif; ?>
                                <?php foreach($monitor_tasks as $mt): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($mt['machine_name']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($mt['issue_description']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($mt['assigned_name'] ?? 'Unassigned'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo ($mt['priority'] == 'Emergency' || $mt['priority'] == 'High') ? 'danger' : 'info'; ?> rounded-pill">
                                            <?php echo $mt['priority']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo ($mt['status'] == 'Completed') ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                            <?php echo $mt['status']; ?>
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
<div class="card glass-card border-0 shadow-sm mt-4">
    <div class="card-header bg-transparent border-0 pt-4 pb-2">
        <h5 class="fw-bold mb-0"><i class="bi bi-file-earmark-bar-graph text-success me-2"></i>Advanced Reporting</h5>
    </div>
    <div class="card-body">
        <form action="generate_report.php" method="POST">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-muted small">Start Date</label>
                    <input type="date" name="start_date" class="form-control bg-light border-0" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small">End Date</label>
                    <input type="date" name="end_date" class="form-control bg-light border-0" required>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" name="generate_pdf" class="btn btn-outline-danger w-50 fw-semibold rounded-pill">
                        <i class="bi bi-file-pdf"></i> PDF Report
                    </button>
                    <button type="submit" formaction="export_excel.php" name="export_excel" class="btn btn-outline-success w-50 fw-semibold rounded-pill">
                        <i class="bi bi-file-excel"></i> Excel Export
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-primary">
                    <?php echo $is_production ? 'Assign Technical Task' : 'Delegate Admin Task'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form onsubmit="createCoreTask(event)">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold">
                            <?php echo $is_production ? 'Machine / Task Name' : 'Subject / Case Name'; ?>
                        </label>
                        <input type="text" id="tk_title" class="form-control bg-light border-0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold">Task Type</label>
                        <select id="tk_type" class="form-select bg-light border-0" required>
                            <?php if($is_production): ?>
                                <option value="Production">Production</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Quality">Quality Control</option>
                            <?php else: ?>
                                <option value="Administrative">Administrative</option>
                                <option value="Reporting">Reporting</option>
                                <option value="Finance">Finance/Budget</option>
                            <?php endif; ?>
                            <option value="General">General</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold">Description</label>
                        <textarea id="tk_desc" class="form-control bg-light border-0" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold">Assign To</label>
                        <select id="tk_assign" class="form-select bg-light border-0">
                            <option value="">(Leave Pending)</option>
                            <?php foreach($assignable_users as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?> (<?php echo $u['role']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">
                        <?php echo $is_production ? 'Assign Task' : 'Delegate Task'; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

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
                        <label class="form-label text-muted fw-semibold">Machine / Asset Name</label>
                        <input type="text" id="rm_machine" class="form-control bg-light border-0" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted fw-semibold">Emergency Details</label>
                        <textarea id="rm_desc" class="form-control bg-light border-0" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger w-100 rounded-pill fw-bold">Send Alert to Engineering</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// AJAX Actions (ለማሳያ ያህል የተቀመጡ ናቸው)
function createCoreTask(e) {
    e.preventDefault();
    alert("Task Created Successfully!");
    location.reload();
}

function requestEngineering(e) {
    e.preventDefault();
    alert("Engineering Alert Sent!");
    location.reload();
}
</script>

<?php include '../includes/footer_glass.php'; ?>