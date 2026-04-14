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
$dept_name = trim($_SESSION['dept_name'] ?? $dept_info['dept_name'] ?? 'Department');

// ነባሪ እሴቶች
$is_production = false; 

$card_1 = "Total Assigned Tasks";
$card_2 = "Pending Approvals";
$card_3 = "Dept. Performance";
$table_h = "Task / Project Name";
$btn_extra_name = "New Task Request";
$btn_extra_icon = "bi-plus-circle";
$btn_extra_class = "btn-outline-primary";
$target_modal = "#createTaskModal";

// Use exact user-provided department labels for workflow grouping
$dept_aliases = [
    'Human Resource (HR)' => 'HR',
    'Strategy & Innovation' => 'Strategy',
    'System Research & Development' => 'R&D',
    'Audit & Inspection' => 'Audit',
    'Procurement & Property' => 'Procurement',
    'Legal Service' => 'Legal',
    'Quality Assurance' => 'QA'
];
$dept_key = $dept_aliases[$dept_name] ?? $dept_name;

$production_group = ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'];
$technical_quality_group = ['Engineering', 'Quality Assurance'];
$finance_resource_group = ['Finance Department', 'Procurement & Property'];
$admin_strategy_group = ['Human Resource (HR)', 'Planning', 'Strategy / Innovation', 'System Research & Development', 'Legal Service', 'Audit & Inspection'];

if (in_array($dept_key, $production_group, true)) {
    $is_production = true;
    $card_1 = "Production Efficiency";
    $card_2 = "Active Machines";
    $card_3 = "Output Efficiency";
    $table_h = "Machine / Work Station";
    $btn_extra_name = "Request Maintenance";
    $btn_extra_icon = "bi-tools";
    $btn_extra_class = "btn-outline-danger";
    $target_modal = "#reqMaintenanceModal";
    $roles = ['Shift Leader', 'Supervisor', 'Employee'];
} elseif (in_array($dept_key, $technical_quality_group, true)) {
    $is_production = true;
    $card_1 = "Maintenance Requests";
    $card_2 = "Repair Progress";
    $card_3 = "System Uptime";
    $table_h = "Asset / Equipment ID";
    $btn_extra_name = "Request Maintenance";
    $btn_extra_icon = "bi-tools";
    $btn_extra_class = "btn-outline-danger";
    $target_modal = "#reqMaintenanceModal";
    $roles = ['Technician', 'Electrician', 'Lab Analyst', 'Employee'];
} elseif (in_array($dept_key, $finance_resource_group, true)) {
    $is_production = false;
    $card_1 = "Pending Vouchers/Requests";
    $card_2 = "Inventory Status";
    $card_3 = "Financial Accuracy";
    $table_h = "Transaction / Item Ref";
    $btn_extra_name = "New Finance Task";
    $btn_extra_icon = "bi-plus-circle";
    $btn_extra_class = "btn-outline-primary";
    $target_modal = "#createTaskModal";
    $roles = ['Accountant', 'Purchaser', 'Store Keeper', 'Officer'];
} elseif (in_array($dept_key, $admin_strategy_group, true)) {
    $is_production = false;
    $card_1 = "Project/Case Progress";
    $card_2 = "Departmental KPI";
    $card_3 = "Performance Score";
    $table_h = "Subject / Case Title";
    $btn_extra_name = "New Task Request";
    $btn_extra_icon = "bi-plus-circle";
    $btn_extra_class = "btn-outline-primary";
    $target_modal = "#createTaskModal";
    $roles = ['Officer', 'Clerk', 'Secretary', 'Auditor', 'Employee'];
} else {
    $roles = ['Employee', 'Staff', 'Officer'];
}

// 🔴 FIX: Safe prepared IN clause
$placeholders = implode(',', array_fill(0, count($roles), '?'));

$sql = "SELECT id, full_name, role 
        FROM users 
        WHERE dept_id = ? 
        AND status = 'Active' 
        AND role IN ($placeholders)
        ORDER BY full_name";

$params = array_merge([$dept_id], $roles);

$users_stmt = $pdo->prepare($sql);
$users_stmt->execute($params);
$assignable_users = $users_stmt->fetchAll();

// KPI
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

// Monitor
$tasks_stmt = $pdo->prepare("SELECT m.*, u.full_name as assigned_name FROM maintenance_requests m 
    LEFT JOIN users u ON m.assigned_to = u.id 
    WHERE m.dept_id = ? ORDER BY m.created_at DESC LIMIT 15");
$tasks_stmt->execute([$dept_id]);
$monitor_tasks = $tasks_stmt->fetchAll();

include '../includes/header_glass.php';
?>

<div class="p-4 mb-4 rounded-4 shadow" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white;">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="bi bi-person-circle me-2"></i>
                Welcome, <?php echo htmlspecialchars($full_name); ?>
            </h2>
            <p class="mb-0" style="opacity: 0.9;">
                <span class="badge rounded-pill bg-white text-primary px-3 py-2 fw-bold me-2">
                    <i class="bi bi-shield-check"></i> Manager
                </span>
                <i class="bi bi-building me-1"></i> 
                <strong><?php echo htmlspecialchars($dept_name); ?></strong> Department Operations
                <span class="ms-3 opacity-75 border-start ps-3">
                    <i class="bi bi-calendar3 me-1"></i> <?php echo date('M d, Y'); ?>
                </span>
            </p>
        </div>
        
        <div class="d-flex gap-2">
            <button class="btn btn-light rounded-pill px-4 fw-bold shadow-sm border-0" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                <i class="bi bi-plus-circle text-primary me-1"></i> Create Core Task
            </button>
            
            <button class="btn btn-outline-light rounded-pill px-4 fw-semibold shadow-sm" 
                    data-bs-toggle="modal" data-bs-target="<?php echo $target_modal; ?>">
                <i class="bi <?php echo $btn_extra_icon; ?> me-1"></i> 
                <?php echo $btn_extra_name; ?>
            </button>
        </div>
    </div>
</div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-primary border-4">
                <h6 class="text-muted fw-bold mb-1 text-uppercase small"><?php echo $card_1; ?></h6>
                <h3 class="fw-bold text-dark mb-0"><?php echo $total_tasks; ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-danger border-4">
                <h6 class="text-muted fw-bold mb-1 text-uppercase small"><?php echo $card_2; ?></h6>
                <h3 class="fw-bold text-danger mb-0"><?php echo $ongoing_repairs; ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card glass-card border-0 shadow-sm text-center py-3 border-bottom border-success border-4">
                <h6 class="text-muted fw-bold mb-1 text-uppercase small"><?php echo $card_3; ?></h6>
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
                                      <i class="bi bi-exclamation-circle me-2"></i>No active tasks found in this department.
                                    </td>
                                  </tr>
                                <?php endif; ?>
                                <?php foreach($monitor_tasks as $mt): ?>
                                <tr>
                                    <td>
    <div class="fw-semibold text-dark">
        <?php 
            if ($is_production) {
                // ለምርት ክፍል ማሽን ስም (machine_name ወይም title ሊሆን ይችላል)
                echo htmlspecialchars($mt['machine_name'] ?? $mt['title'] ?? 'Unknown Machine');
            } else {
                // ለአስተዳደር ክፍል (task_title በሚለው ፋንታ title ተጠቀም)
                echo htmlspecialchars($mt['title'] ?? $mt['task_title'] ?? 'No Subject');
            }
        ?>
    </div>
    <div class="small text-muted">
        <?php echo htmlspecialchars($mt['issue_description'] ?? $mt['description'] ?? 'No description'); ?>
    </div>
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

    <div class="card glass-card border-0 shadow-sm mt-4">
        <div class="card-header bg-transparent border-0 pt-4 pb-2">
            <h5 class="fw-bold mb-0"><i class="bi bi-file-earmark-bar-graph text-success me-2"></i>Advanced Reporting</h5>
        </div>
        <div class="card-body">
            <form action="generate_report.php" method="POST">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label text-muted small fw-bold">START DATE</label>
                        <input type="date" name="start_date" class="form-control bg-light border-0" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small fw-bold">END DATE</label>
                        <input type="date" name="end_date" class="form-control bg-light border-0" required>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" name="generate_pdf" class="btn btn-outline-danger w-50 fw-semibold rounded-pill shadow-sm">
                            <i class="bi bi-file-pdf"></i> PDF Report
                        </button>
                        <button type="submit" formaction="export_excel.php" name="export_excel" class="btn btn-outline-success w-50 fw-semibold rounded-pill shadow-sm">
                            <i class="bi bi-file-excel"></i> Excel Export
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card shadow border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary">
                    <i class="bi bi-plus-circle-fill me-2"></i>Assign New Task
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form onsubmit="createCoreTask(event)">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">
                            <?php echo $is_production ? 'MACHINE / STATION NAME' : 'SUBJECT / CASE TITLE'; ?>
                        </label>
                        <input type="text" id="tk_title" name="title" class="form-control bg-light border-0 py-2" 
                               placeholder="Enter details here..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">
                            <i class="bi bi-tag-fill me-1"></i> TASK TYPE / CATEGORY
                        </label>
                        <select id="tk_type" name="task_type" class="form-select bg-light border-0 py-2" required>
                            <?php if ($is_production): ?>
                                <option value="">-- Select Production Task --</option>
                                <option value="Production">Daily Production</option>
                                <option value="Quality">Quality Check / Lab Analysis</option>
                                <option value="Maintenance">Machine Maintenance</option>
                                <option value="Breakdown">Breakdown Repair</option>
                                <option value="Safety">Safety Inspection</option>
                            <?php else: ?>
                                <option value="">-- Select Admin Task --</option>
                                <option value="Administrative">Administrative Task</option>
                                <option value="Finance">Budgeting / Payment</option>
                                <option value="Reporting">Report Preparation</option>
                                <option value="Planning">Strategic Planning</option>
                                <option value="Legal">Legal / Contract Review</option>
                                <option value="HR">Human Resource / Staffing</option>
                                <option value="Procurement">Procurement / Store</option>
                                <option value="Audit">Audit / Compliance</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">ASSIGN TO STAFF</label>
                        <select id="tk_assign" name="assigned_to" class="form-select bg-light border-0 py-2">
                            <option value="">(Leave Pending)</option>
                            <?php if (!empty($assignable_users)): ?>
                                <?php foreach($assignable_users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>">
                                        <?php echo htmlspecialchars($u['full_name']); ?> (<?php echo $u['role']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">DESCRIPTION / INSTRUCTIONS</label>
                        <textarea id="tk_desc" name="description" class="form-control bg-light border-0" rows="3" 
                                  placeholder="Write detailed instructions..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm mt-2">
                        <i class="bi bi-check2-circle me-1"></i>
                        <?php echo $is_production ? 'Assign Production Task' : 'Delegate Admin Task'; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="reqMaintenanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card shadow border-danger border-top border-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-tools me-2"></i>Engineering Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form onsubmit="requestEngineering(event)">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">MACHINE / ASSET NAME</label>
                        <input type="text" id="rm_machine" class="form-control bg-light border-0 py-2" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">FAULT DETAILS / EMERGENCY</label>
                        <textarea id="rm_desc" class="form-control bg-light border-0" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger w-100 rounded-pill fw-bold py-2 shadow-sm">Send Alert to Engineering</button>
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