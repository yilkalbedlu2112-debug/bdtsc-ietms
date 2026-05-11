<?php
session_start();

require_once '../includes/db.php';
/** @var PDO $pdo */
// 1. የመግቢያ ፈቃድ ማረጋገጫ
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Department Manager', 'Engineering Manager'], true)) {
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

$is_eng_manager = ($dept_id == 16 && $_SESSION['user_role'] === 'Engineering Manager');

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

// dept_key = dept_name directly  (aliases removed — they broke in_array matching)
// All group arrays use EXACT names from the `departments` table
$dept_key = $dept_name;

$production_group = [
    'Spinning Department',
    'Weaving Department',
    'Processing Department',
    'Garment Department',
];

$technical_quality_group = [
    'Engineering',
    'Quality Assurance',   // DB id 13
];

$finance_resource_group = [
    'Finance Department',  // DB id 7
    'Procurement / Property', // DB id 14 — exact name
];

$admin_strategy_group = [
    'General Management',          // DB id 1
    'Human Resource (HR)',          // DB id 12 — exact name with parentheses
    'Planning',                     // DB id 5
    'Strategy & Innovation',        // DB id 4 — exact name with &
    'System Research & Development',// DB id 6
    'Legal Service',                // DB id 15
    'Audit & Inspection',           // DB id 11
];

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
    // Fallback for any unlisted department
    $roles = ['Employee', 'Staff', 'Officer'];
}

// 🔴 FIX: Safe prepared IN clause
$placeholders = implode(',', array_fill(0, count($roles), '?'));

$sql = "SELECT id, full_name, user_role 
        FROM users 
        WHERE dept_id = ? 
        AND status = 'Active' 
        AND user_role IN ($placeholders)
        ORDER BY full_name";

$params = array_merge([$dept_id], $roles);

$users_stmt = $pdo->prepare($sql);
$users_stmt->execute($params);
$assignable_users = $users_stmt->fetchAll();

// KPI
if ($is_eng_manager) {
    $kpi_stmt = $pdo->prepare("SELECT 
        COUNT(*) as total, 
        SUM(CASE WHEN priority IN ('Emergency', 'High') AND status != 'Completed' THEN 1 ELSE 0 END) as ongoing_repairs,
        SUM(CASE WHEN status = 'Completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as weekly_completed,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as weekly_total
        FROM maintenance_requests");
    $kpi_stmt->execute();
} else {
    $kpi_stmt = $pdo->prepare("SELECT 
        COUNT(*) as total, 
        SUM(CASE WHEN priority IN ('Emergency', 'High') AND status != 'Completed' THEN 1 ELSE 0 END) as ongoing_repairs,
        SUM(CASE WHEN status = 'Completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as weekly_completed,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as weekly_total
        FROM maintenance_requests WHERE dept_id = ?");
    $kpi_stmt->execute([$dept_id]);
}
$stats = $kpi_stmt->fetch();

$total_tasks = $stats['total'] ?: 0;
$ongoing_repairs = $stats['ongoing_repairs'] ?: 0;
$shift_perf = ($stats['weekly_total'] > 0) ? round(($stats['weekly_completed'] / $stats['weekly_total']) * 100) : 0;

// Monitor
if ($is_eng_manager) {
    $tasks_stmt = $pdo->prepare("SELECT m.*, u.full_name as assigned_name, d.dept_name as request_dept FROM maintenance_requests m 
        LEFT JOIN users u ON m.assigned_to = u.id 
        LEFT JOIN departments d ON m.dept_id = d.id 
        WHERE m.status != 'Completed' ORDER BY m.created_at DESC LIMIT 15");
    $tasks_stmt->execute();
} else {
    $tasks_stmt = $pdo->prepare("SELECT m.*, u.full_name as assigned_name, d.dept_name as request_dept FROM maintenance_requests m 
        LEFT JOIN users u ON m.assigned_to = u.id 
        LEFT JOIN departments d ON m.dept_id = d.id 
        WHERE m.dept_id = ? ORDER BY m.created_at DESC LIMIT 15");
    $tasks_stmt->execute([$dept_id]);
}
$monitor_tasks = $tasks_stmt->fetchAll();

$dispatch_tasks = [];
if ($is_eng_manager) {
    $dispatch_stmt = $pdo->prepare("SELECT m.*, u.full_name as assigned_name, d.dept_name as request_dept FROM maintenance_requests m 
        LEFT JOIN users u ON m.assigned_to = u.id 
        LEFT JOIN departments d ON m.dept_id = d.id 
        WHERE m.status = 'Pending' ORDER BY m.created_at DESC LIMIT 20");
    $dispatch_stmt->execute();
    $dispatch_tasks = $dispatch_stmt->fetchAll();
}

// ── Notification count: unread incoming cross-dept requests for this dept ────
$notif_stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM maintenance_requests
     WHERE receiver_dept_id = ? AND is_read_by_receiver = 0");
$notif_stmt->execute([$dept_id]);
$incoming_count = (int)$notif_stmt->fetchColumn();

// ── All departments for the cross-dept request modal dropdown ────────────────
$depts_stmt = $pdo->prepare("SELECT id, dept_name FROM departments ORDER BY dept_name");
$depts_stmt->execute();
$all_departments = $depts_stmt->fetchAll();

include '../includes/header_glass.php';

$message = '';
if (isset($_GET['success']) && $_GET['success'] === 'sent') {
    $message = 'Maintenance request sent successfully!';
}
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
        
        <div class="d-flex gap-2 align-items-center">
            <!-- Notification Bell -->
            <a href="view_requests.php" class="btn btn-outline-light rounded-pill px-3 position-relative" title="Incoming Cross-Dept Requests">
                <i class="bi bi-bell-fill"></i>
                <?php if ($incoming_count > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.7rem;">
                    <?php echo $incoming_count > 9 ? '9+' : $incoming_count; ?>
                    <span class="visually-hidden">unread requests</span>
                </span>
                <?php endif; ?>
            </a>

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
        <?php if ($message): ?>
            <div class="col-12">
                <div class="alert alert-success glass-card border-0 shadow-sm">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
        <?php endif; ?>

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

    <?php if ($is_eng_manager): ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-12">
            <div class="card glass-card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0"><i class="bi bi-send-check text-primary me-2"></i>Maintenance Dispatch Center</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0">Department</th>
                                    <th class="border-0">Request</th>
                                    <th class="border-0">Priority</th>
                                    <th class="border-0">Requested On</th>
                                    <th class="border-0">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($dispatch_tasks)): ?>
                                   <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                      <i class="bi bi-exclamation-circle me-2"></i>No pending dispatch requests currently.
                                    </td>
                                  </tr>
                                <?php endif; ?>
                                <?php foreach($dispatch_tasks as $task): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($task['request_dept'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            <?php echo htmlspecialchars($task['machine_name'] ?? $task['title'] ?? 'No subject'); ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?php echo htmlspecialchars($task['issue_description'] ?? 'No description'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo ($task['priority'] == 'Emergency' || $task['priority'] == 'High') ? 'danger' : 'info'; ?> rounded-pill">
                                            <?php echo htmlspecialchars($task['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted small">
                                            <?php echo date('M d, Y', strtotime($task['created_at'] ?? 'now')); ?>
                                        </span>
                                    </td>
                                    <td>
                                      <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" onclick="dispatchRequest(<?php echo (int)$task['id']; ?>)">
                                          <i class="bi bi-box-arrow-in-right me-1"></i> Dispatch
                                      </button>
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

    <div class="modal fade" id="dispatchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-0 shadow-lg rounded-4">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2"></i>Assign Technician</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="process_dispatch.php" method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="request_id" id="modal_request_id">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small">SELECT MAINTENANCE STAFF</label>
                            <select name="technician_id" class="form-select bg-light border-0 py-2" required>
                                <option value="">-- Choose Specialist --</option>
                                <?php if (!empty($assignable_users)): ?>
                                    <?php foreach($assignable_users as $u): ?>
                                        <option value="<?= $u['id']; ?>">
                                            <?= htmlspecialchars($u['full_name']); ?> (<?= $u['user_role']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="submit" name="confirm_dispatch" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow">
                            Confirm & Dispatch Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
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
                <form action="process_core_task.php" method="POST">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">
                            <?php echo $is_production ? 'MACHINE / STATION NAME' : 'SUBJECT / CASE TITLE'; ?>
                        </label>
                        <input type="text" name="machine_name" class="form-control bg-light border-0 py-2" 
                               placeholder="Enter details here..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">
                            <i class="bi bi-tag-fill me-1"></i> TASK TYPE / CATEGORY
                        </label>
                        <select name="task_type" class="form-select bg-light border-0 py-2" required>
                            <?php if ($is_production): ?>
                                <option value=""> Select Production Task </option>
                                <option value="Production">Daily Production</option>
                                <option value="Quality">Quality Check / Lab Analysis</option>
                                <option value="Maintenance">Machine Maintenance</option>
                                <option value="Breakdown">Breakdown Repair</option>
                                <option value="Safety">Safety Inspection</option>
                            <?php else: ?>
                                <option value="">Select Admin Task</option>
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
                        <select name="assigned_to" class="form-select bg-light border-0 py-2">
                            <option value="">(Leave Pending)</option>
                            <?php if (!empty($assignable_users)): ?>
                                <?php foreach($assignable_users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>">
                                        <?php echo htmlspecialchars($u['full_name']); ?> (<?php echo $u['user_role']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">DESCRIPTION / INSTRUCTIONS</label>
                        <textarea name="issue_description" class="form-control bg-light border-0" rows="3" 
                                  placeholder="Write detailed instructions..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">DUE DATE</label>
                        <input type="datetime-local" name="due_date" class="form-control bg-light border-0 py-2" required>
                    </div>

                    <input type="hidden" name="status" value="Pending">
                    <input type="hidden" name="priority" value="Normal">

                    <button type="submit" name="submit_core_task" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm mt-2">
                        <i class="bi bi-check2-circle me-1"></i>
                        <?php echo $is_production ? 'Assign Production Task' : 'Delegate Admin Task'; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reqMaintenanceModal" tabindex="-1" aria-labelledby="reqMaintenanceModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass-card shadow border-0 rounded-4" style="border-top: 4px solid #0d6efd !important;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div>
                    <h5 class="modal-title fw-bold text-primary mb-1" id="reqMaintenanceModalLabel">
                        <i class="bi bi-send-arrow-up-fill me-2"></i>Cross-Department Request
                    </h5>
                    <p class="text-muted small mb-0">Send a formal request to another department for repairs, manpower, resources, or legal support.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-4">
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger border-0 rounded-3"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form action="process_maintenance.php" method="POST" id="crossDeptForm">

                    <!-- Row 1: Request Type + Target Department -->
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">
                                <i class="bi bi-list-ul me-1"></i>REQUEST TYPE <span class="text-danger">*</span>
                            </label>
                            <select name="request_type" id="dept_request_type" class="form-select bg-light border-0 py-2" required
                                    onchange="suggestTargetDept(this.value)">
                                <option value="">— Select Request Type —</option>
                                <option value="Repair">🔧 Repair / Machine Fix (→ Engineering)</option>
                                <option value="Manpower">👥 Manpower / Staffing (→ HR)</option>
                                <option value="Resource">📦 Resource / Spare Parts (→ Procurement)</option>
                                <option value="Legal">⚖️ Legal / Contract Review (→ Legal)</option>
                                <option value="Maintenance">🛠️ Preventive Maintenance (→ Engineering)</option>
                                <option value="Administrative">📋 Administrative Support</option>
                                <option value="Other">📌 Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">
                                <i class="bi bi-buildings me-1"></i>TARGET DEPARTMENT <span class="text-danger">*</span>
                            </label>
                            <select name="receiver_dept_id" id="receiver_dept_id" class="form-select bg-light border-0 py-2" required>
                                <option value="">— Select Receiving Department —</option>
                                <?php foreach ($all_departments as $d): ?>
                                    <?php if ($d['id'] == $dept_id) continue; ?>
                                    <option value="<?php echo (int)$d['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($d['dept_name']); ?>">
                                        <?php echo htmlspecialchars($d['dept_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Row 2: Subject + Priority -->
                    <div class="row g-3 mt-1">
                        <div class="col-md-8">
                            <label class="form-label text-muted small fw-bold">
                                <i class="bi bi-pencil me-1"></i>SUBJECT / ASSET / MACHINE NAME <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="machine_name" class="form-control bg-light border-0 py-2"
                                   placeholder="e.g., Ring Frame Machine #7 / Staff Request / Spare Belt" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold">
                                <i class="bi bi-speedometer2 me-1"></i>PRIORITY <span class="text-danger">*</span>
                            </label>
                            <select name="priority" class="form-select bg-light border-0 py-2" required>
                                <option value="Normal">Normal</option>
                                <option value="High">High</option>
                                <option value="Emergency">🚨 Emergency</option>
                                <option value="Urgent">⚡ Urgent</option>
                            </select>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mt-3">
                        <label class="form-label text-muted small fw-bold">
                            <i class="bi bi-card-text me-1"></i>DETAILED DESCRIPTION / INSTRUCTIONS <span class="text-danger">*</span>
                        </label>
                        <textarea name="issue_description" class="form-control bg-light border-0" rows="4"
                                  placeholder="Describe the issue, required resources, or support needed in detail..." required></textarea>
                    </div>

                    <!-- Hidden fields -->
                    <input type="hidden" name="task_type" value="Maintenance">

                    <!-- Preview banner -->
                    <div id="crossDeptPreview" class="alert alert-info border-0 rounded-3 mt-3 d-none" style="font-size:0.85rem;">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <span id="previewText"></span>
                    </div>

                    <button type="submit" name="submit_dept_request" id="crossDeptSubmitBtn"
                            class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow mt-4">
                        <i class="bi bi-send-fill me-2"></i>Submit Cross-Department Request
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
// ── Auto-suggest target department based on request type ─────────────────────
const deptSuggestions = {
    'Repair'         : 'Engineering',
    'Maintenance'    : 'Engineering',
    'Manpower'       : 'Human Resource (HR)',
    'Resource'       : 'Procurement / Property',
    'Legal'          : 'Legal Service',
    'Administrative' : '',
    'Other'          : ''
};

function suggestTargetDept(requestType) {
    const suggestion   = deptSuggestions[requestType] || '';
    const deptSelect   = document.getElementById('receiver_dept_id');
    const preview      = document.getElementById('crossDeptPreview');
    const previewText  = document.getElementById('previewText');

    // Try to auto-select the matching option
    let matched = false;
    for (const opt of deptSelect.options) {
        if (suggestion && opt.dataset.name && opt.dataset.name.includes(suggestion)) {
            opt.selected = true;
            matched = true;
            break;
        }
    }

    // Show preview banner
    if (requestType && matched) {
        previewText.textContent =
            `This "${requestType}" request will be routed to the ${deptSelect.options[deptSelect.selectedIndex].text} department.`;
        preview.classList.remove('d-none');
    } else if (requestType) {
        previewText.textContent = `Please manually select the target department for "${requestType}" requests.`;
        preview.classList.remove('d-none');
    } else {
        preview.classList.add('d-none');
    }
}

// ── Dispatch: mark request as In Progress via AJAX ───────────────────────────
// ── Dispatch: Open Technician Selection Modal ──────────────────────────────
function dispatchRequest(taskId) {
    // 1. የታስኩን ID በሞዳሉ ውስጥ ባለው Hidden input ላይ ይጭናል
    const modalInput = document.getElementById('modal_request_id');
    if (modalInput) {
        modalInput.value = taskId;
    }

    // 2. የ Dispatch ሞዳሉን ይከፍታል
    const dispatchModalEl = document.getElementById('dispatchModal');
    if (dispatchModalEl) {
        const myModal = new bootstrap.Modal(dispatchModalEl);
        myModal.show();
    } else {
        console.error("Dispatch Modal not found in the HTML!");
        showToast('Error: Dispatch modal missing.', 'danger');
    }
}
// ── Simple toast helper ───────────────────────────────────────────────────────
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer') ||
                      (() => {
                          const el = document.createElement('div');
                          el.id = 'toastContainer';
                          el.className = 'position-fixed bottom-0 end-0 p-3';
                          el.style.zIndex = 9999;
                          document.body.appendChild(el);
                          return el;
                      })();

    const id   = 'toast_' + Date.now();
    const html = `<div id="${id}" class="toast align-items-center text-bg-${type} border-0 show shadow rounded-3 mb-2" role="alert">
                    <div class="d-flex">
                        <div class="toast-body fw-semibold">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="document.getElementById('${id}').remove()"></button>
                    </div></div>`;
    container.insertAdjacentHTML('beforeend', html);
    setTimeout(() => { const el = document.getElementById(id); if(el) el.remove(); }, 4000);
}
function dispatchRequest(taskId) {
    // 1. የታስኩን ID በሞዳሉ ውስጥ ባለው hidden input ላይ መጫኑን ያረጋግጣል
    var input = document.getElementById('modal_request_id');
    if(input) {
        input.value = taskId;
        
        // 2. ሞዳሉን በ Bootstrap ለመክፈት
        var myModal = new bootstrap.Modal(document.getElementById('dispatchModal'));
        myModal.show();
    } else {
        alert("Error: Modal input not found!");
    }
}
</script>

<?php include '../includes/footer_glass.php'; ?>