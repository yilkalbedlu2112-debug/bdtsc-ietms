<?php
// 1. ሴሽን እና ዳታቤዝ መጀመሪያ መሆን አለባቸው
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db.php';

// 2. ደህንነት፡ ሱፐርቫይዘር መሆኑን ማረጋገጥ
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Supervisor') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$dept_id = $_SESSION['dept_id'];

// 3. የዲፓርትመንት መረጃን ማምጣት
$dept_stmt = $pdo->prepare("SELECT dept_name, dept_type FROM departments WHERE id = ?");
$dept_stmt->execute([$dept_id]);
$dept_info = $dept_stmt->fetch();
$dept_name = trim($_SESSION['dept_name'] ?? $dept_info['dept_name'] ?? 'Department');

// የዲፓርትመንት ቡድኖች (Exact Names from BDTSC DB)
$production_group = ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'];

// ለምርት ክፍል ሱፐርቫይዘሮች ብቻ መሆኑን ማረጋገጥ
if (!in_array($dept_name, $production_group, true)) {
    die("<div class='alert alert-danger m-5'>Access Denied: This dashboard is optimized for Production Supervisors (Spinning, Weaving, Processing, Garment).</div>");
}

// ---------------------------------------------------------
// 4. ዳይናሚክ ስታቲስቲክስ ስሌቶች (Stats)
// ---------------------------------------------------------

// ሀ. የጥገና ጥያቄዎች (በጥበቃ ላይ ያሉ)
$pending_maint_stmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE sender_dept_id = ? AND status = 'Pending'");
$pending_maint_stmt->execute([$dept_id]);
$pending_maintenance = $pending_maint_stmt->fetchColumn();

// ለ. ለሽፍት ሊደር የተሰጡ ስራዎች
$assigned_tasks_stmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE sender_dept_id = ? AND status = 'Assigned'");
$assigned_tasks_stmt->execute([$dept_id]);
$total_assigned = $assigned_tasks_stmt->fetchColumn();

// ሐ. ውጤታማነት (Efficiency) ስሌት
$eff_stmt = $pdo->prepare("SELECT 
    (SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) * 100 / NULLIF(COUNT(*), 0)) 
    FROM maintenance_requests WHERE sender_dept_id = ?");
$eff_stmt->execute([$dept_id]);
$efficiency_val = round($eff_stmt->fetchColumn() ?? 0);

// መ. በስራ ላይ ያሉ ማሽኖች (Active Machines) ስሌት
$total_machines = 30; 
$down_stmt = $pdo->prepare("SELECT COUNT(DISTINCT machine_name) FROM maintenance_requests 
                           WHERE sender_dept_id = ? AND status != 'Completed'");
$down_stmt->execute([$dept_id]);
$down_count = $down_stmt->fetchColumn();
$active_machines = $total_machines - $down_count;

// ---------------------------------------------------------

// 5. ሄደሩን ማካተት (ከላይ ያሉት ስሌቶች አልቀው PHP ከመዘጋቱ በፊት)
include '../includes/header_glass.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supervisor Dashboard - BDTSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .card-stats { transition: transform 0.3s; cursor: default; }
        .card-stats:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
    <?php
// የተጠቃሚውን ስም ከመጀመሪያው ስም ብቻ ለመውሰድ (ለምሳሌ አቶ አበበ)

$gender = $_SESSION['gender'] ?? 'Male'; // default Male ቢሆን
$title = ($gender === 'Female') ? 'ወይዘሮ/ሪት' : 'አቶ';

$full_name = $_SESSION['full_name'] ?? "Supervisor";
$first_name = explode(' ', trim($full_name))[0];
// በሰዓቱ ላይ የተመሰረተ ሰላምታ
$hour = date('H');
if ($hour >= 5 && $hour < 12) {
    $greeting = "Good Morning";
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}
?>

<div class="d-flex align-items-center mb-4">
    <!-- የዲፓርትመንት ስም እና አይኮን -->
    <div>
        <h2 class="fw-bold text-primary mb-1">
            <i class="bi bi-building"></i> <?php echo htmlspecialchars($dept_name); ?>
        </h2>
        <!-- ዳይናሚክ ሰላምታ እና ስም -->
        <p class="text-muted mb-0">
             <i class="bi bi-person-check"></i> 
             <?php echo $greeting; ?>፣ <strong>ሱፐርቫይዘር <?php echo htmlspecialchars($first_name); ?></strong> | Supervisor Panel
        </p>
    </div>
</div>
        <div class="btn-group">
            <button class="btn btn-danger shadow-sm" data-bs-toggle="modal" data-bs-target="#maintenanceModal">
                <i class="bi bi-tools"></i> Request Maintenance
            </button>
            <a href="assign_task.php" class="btn btn-primary shadow-sm ms-2">
                <i class="bi bi-person-plus"></i> Assign to Shift Leader
            </a>
            <button class="btn btn-success shadow-sm ms-2" data-bs-toggle="modal" data-bs-target="#reportModal">
                <i class="bi bi-file-earmark-bar-graph"></i> Send Manager Report
            </button>
            <button class="btn btn-info shadow-sm ms-2" data-bs-toggle="modal" data-bs-target="#delegateModal">
                <i class="bi bi-shield-check"></i> Delegate Authority
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-stats shadow-sm border-0 bg-white p-3">
                <div class="d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-clock-history text-warning fs-3"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">Pending Repair</h6>
                        <h3 class="mb-0 fw-bold"><?php echo $pending_maintenance; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stats shadow-sm border-0 bg-white p-3">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-list-task text-primary fs-3"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">Active Tasks</h6>
                        <h3 class="mb-0 fw-bold"><?php echo $total_assigned; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <!-- Efficiency and Active Machines (Placeholder for now) -->
        <!-- Efficiency Card -->
<div class="col-md-3">
    <div class="card card-stats shadow-sm border-0 bg-white p-3">
        <div class="d-flex align-items-center">
            <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3">
                <i class="bi bi-lightning-charge text-success fs-3"></i>
            </div>
            <div>
                <h6 class="mb-0 text-muted">ውጤታማነት</h6>
                <h3 class="mb-0 fw-bold"><?php echo $efficiency_val; ?>%</h3>
            </div>
        </div>
    </div>
</div>

<!-- Active Machines Card -->
<div class="col-md-3">
    <div class="card card-stats shadow-sm border-0 bg-white p-3">
        <div class="d-flex align-items-center">
            <div class="bg-info bg-opacity-10 p-3 rounded-circle me-3">
                <i class="bi bi-cpu text-info fs-3"></i>
            </div>
            <div>
                <h6 class="mb-0 text-muted">ኦፕሬሽናል ማሽኖች</h6>
                <h3 class="mb-0 fw-bold"><?php echo $active_machines . "/" . $total_machines; ?></h3>
            </div>
        </div>
    </div>
</div>
    </div>

    <!-- Tasks Table -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">Recent Maintenance & Tasks</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Machine/Task</th>
                            <th>Target Dept</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT mr.*, d.dept_name AS target_dept, u.full_name as leader_name 
                                             FROM maintenance_requests mr 
                                             LEFT JOIN departments d ON mr.receiver_dept_id = d.id 
                                             LEFT JOIN users u ON mr.assigned_to = u.id 
                                             WHERE mr.sender_dept_id = ? OR mr.receiver_dept_id = ?
                                             ORDER BY mr.created_at DESC LIMIT 10");
                        $stmt->execute([$dept_id, $dept_id]);
                        while($row = $stmt->fetch()):
                            $p_color = ($row['priority'] == 'Emergency' || $row['priority'] == 'Urgent') ? 'danger' : 'info';
                        ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($row['machine_name'] ?: $row['title']); ?></div>
                                <small class="text-muted"><?php echo substr($row['issue_description'], 0, 40); ?>...</small>
                            </td>
                            <td><?php echo $row['target_dept']; ?></td>
                            <td><span class="badge bg-<?php echo $p_color; ?>"><?php echo $row['priority']; ?></span></td>
                            <td>
                                <span class="badge rounded-pill border border-secondary text-secondary">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $row['leader_name'] ?: '<span class="text-muted">Not Assigned</span>'; ?></td>
                            <td><?php echo date('M d, H:i', strtotime($row['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Maintenance Request (For Engineering) -->
<div class="modal fade" id="maintenanceModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="maintenanceForm">
            <div class="modal-content shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-tools"></i> Request Engineering Repair</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_request">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Machine Name / ID</label>
                        <input type="text" name="machine_name" class="form-control" placeholder="e.g. Spinning Frame #04" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Issue Description</label>
                        <textarea name="issue_description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Priority Level</label>
                        <select name="priority" class="form-select">
                            <option value="Normal">Normal</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                            <option value="Emergency">Emergency (Line Stop)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger w-100 shadow-sm">Send to Engineering</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Delegate Authority -->
<div class="modal fade" id="delegateModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="delegateForm">
            <div class="modal-content shadow">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="bi bi-shield-lock"></i> Delegate Shift Authority</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="delegate_authority">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Shift Leader</label>
                        <select name="delegate_to" class="form-select" required>
                            <option value="">Choose Shift Leader </option>
                            <?php
                            $leaders = $pdo->prepare("SELECT id, full_name FROM users WHERE dept_id = ? AND user_role = 'Shift Leader'");
                            $leaders->execute([$dept_id]);
                            foreach($leaders->fetchAll() as $leader) {
                                echo "<option value='{$leader['id']}'>" . htmlspecialchars($leader['full_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Instructions / Notes</label>
                        <textarea name="delegation_notes" class="form-control" placeholder="Specific instructions for this delegation"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info w-100">Confirm Delegation</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Submit Report (For Manager) -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="reportForm">
            <div class="modal-content shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Submit Performance Report to Manager</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="generate_report">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Period</label>
                        <select name="period" class="form-select">
                            <option value="daily">Daily Progress Report</option>
                            <option value="weekly">Weekly Summary</option>
                            <option value="monthly">Monthly Performance</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Summary Highlights</label>
                        <textarea name="report_details" class="form-control" rows="4" placeholder="Briefly explain production goals and machine status..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success w-100 shadow-sm">Submit to General Manager</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// AJAX form handling
$('#maintenanceForm, #delegateForm, #reportForm').on('submit', function(e) {
    e.preventDefault();
    const form = $(this);
    $.post('supervisor_controller.php', form.serialize(), function(res) {
        alert(res.message);
        if (res.status === 'success') location.reload();
    }, 'json').fail(function() {
        alert("Server Error: Please check supervisor_controller.php");
    });
});
</script>

</body>
</html>
<?php include '../includes/footer_glass.php'; ?>