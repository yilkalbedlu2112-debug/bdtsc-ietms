<?php 
session_start();
require_once '../includes/db.php';

// 1. ሴሽን እና የShift Leader ሚና ማረጋገጥ
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Shift Leader') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$dept_id = $_SESSION['dept_id'];
$full_name = $_SESSION['full_name'];
$dept_name = $_SESSION['dept_name'];

// 2. የገጽታ ቀለም (Theme) - ለፕሮዳክሽን ብቻ
$theme_color = "primary";
if (strpos($dept_name, 'Spinning') !== false) $theme_color = "info";
elseif (strpos($dept_name, 'Weaving') !== false) $theme_color = "success";
elseif (strpos($dept_name, 'Processing') !== false) $theme_color = "warning";
elseif (strpos($dept_name, 'Garment') !== false) $theme_color = "danger";

// --- የዳታ አሰባሰብ (Queries) ---

// ሀ. ከማናጀር/ሱፐርቫይዘር የተላኩ አዳዲስ ስራዎች (Assigned to this Shift Leader)
// እነዚህ ገና ለሰራተኛ ያልተመደቡ ስራዎች ናቸው
$alerts_stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE assigned_to = ? AND status = 'Pending Approval' ORDER BY created_at DESC");
$alerts_stmt->execute([$user_id]);
$pending_alerts = $alerts_stmt->fetchAll();

// Verification pending tasks
$verification_stmt = $pdo->prepare("SELECT mr.*, u.full_name as emp_name FROM maintenance_requests mr 
                                   JOIN users u ON mr.assigned_to = u.id 
                                   WHERE mr.dept_id = ? AND mr.status = 'In Progress' AND mr.is_verified = 0 
                                   ORDER BY mr.updated_at DESC");
$verification_stmt->execute([$dept_id]);
$verification_tasks = $verification_stmt->fetchAll();

// ለ. ከራሱ ሰራተኞች የመጡ የምርት ሪፖርቶች እና ፊድባኮች (Daily Reports)
// ለ. ከራሱ ሰራተኞች የመጡ የምርት ሪፖርቶች
$report_stmt = $pdo->prepare("SELECT pr.*, u.full_name as employee_name 
                             FROM production_reports pr 
                             JOIN users u ON pr.user_id = u.id 
                             WHERE pr.dept_id = ? AND pr.reported_to LIKE '%Shift Leader%'
                             ORDER BY pr.report_date DESC LIMIT 10"); // እዚህ ጋር 'report_date' መሆኑን አረጋግጥ
$report_stmt->execute([$dept_id]);
$employee_reports = $report_stmt->fetchAll();

// ሐ. ለስራ ምደባ ዝግጁ የሆኑ ሰራተኞች (የዚህ ሽፍት ሰራተኞች ብቻ)
$emp_stmt = $pdo->prepare("SELECT id, full_name, user_role FROM users WHERE dept_id = ? AND user_role = 'Employee'");
$emp_stmt->execute([$dept_id]);
$my_employees = $emp_stmt->fetchAll();

// መ. ወደ ኢንጂነሪንግ የተላኩ የጥገና ጥያቄዎች ሁኔታ (Maintenance Tracking)
// 'requested_by' የሚለውን በ 'user_id' (ወይም በዳታቤዝህ ውስጥ ባለው ስም) ተካው
$maint_stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE user_id = ? ORDER BY id DESC LIMIT 5");
$maint_stmt->execute([$user_id]);
$my_maintenance_requests = $maint_stmt->fetchAll();

// --- ይህ መስመር ነው የጎደለው ---
// ከሰራተኞች የመጡ እና ውሳኔ የሚጠብቁ የጥገና ጥያቄዎችን (Pending Maintenance) ለማግኘት
$new_req_stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE dept_id = ? AND status = 'Pending'");
$new_req_stmt->execute([$dept_id]);
$new_requests = $new_req_stmt->fetchAll(); 

// New Alerts: Pending Approval assigned to this Shift Leader
$alerts_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM maintenance_requests WHERE assigned_to = ? AND status = 'Pending Approval' AND is_read_by_receiver = 0");
$alerts_stmt->execute([$user_id]);
$new_alerts_count = $alerts_stmt->fetch()['count'];

// Verification Pending: In Progress, not verified, in department
$verification_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM maintenance_requests WHERE dept_id = ? AND status = 'In Progress' AND is_verified = 0");
$verification_stmt->execute([$dept_id]);
$verification_pending_count = $verification_stmt->fetch()['count'];
// ---------------------------------

include '../includes/header_glass.php';
?>

<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1055;">
    <div id="liveToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body fw-semibold" id="toastMessage">ተሳክቷል።</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
</div>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 glass-card shadow-sm border-start border-5 border-<?php echo $theme_color; ?>">
        <div>
            <h2 class="text-dark fw-bold mb-0">
                <i class="bi bi-person-badge-fill text-<?php echo $theme_color; ?> me-2"></i>
                <?php echo htmlspecialchars($dept_name); ?> - Dashboard
            </h2>
            <div class="mt-2">
                <?php if ($new_alerts_count > 0): ?>
                    <span class="badge bg-warning text-dark me-2">
                        <i class="bi bi-exclamation-triangle"></i> New Alerts: <?php echo $new_alerts_count; ?>
                    </span>
                <?php endif; ?>
                <?php if ($verification_pending_count > 0): ?>
                    <span class="badge bg-info text-white">
                        <i class="bi bi-check-circle"></i> Verification Pending: <?php echo $verification_pending_count; ?>
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-muted mb-0">መስመር፦ <strong><?php echo $full_name; ?></strong> | ሪፖርት ለ፦ <strong>Supervisor/Manager</strong></p>
        </div>
        <div class="text-end">
            <span class="badge bg-<?php echo $theme_color; ?> rounded-pill px-3 py-2 shadow-sm mb-1">
                <i class="bi bi-clock-history me-1"></i> Active Shift
            </span>
            <div class="small text-muted fw-bold"><?php echo date('l, M d, Y'); ?></div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card glass-card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0"><i class="bi bi-list-task text-primary me-2"></i>Pending Alerts & Tasks</h5>
                </div>
                <div class="card-body">
                    <!-- New Alerts -->
                    <h6 class="text-warning"><i class="bi bi-exclamation-triangle"></i> New Alerts (Pending Approval)</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Machine</th>
                                    <th>Issue</th>
                                    <th>Priority</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($pending_alerts)): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-2">No new alerts.</td></tr>
                                <?php endif; ?>
                                <?php foreach($pending_alerts as $alert): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($alert['machine_name']); ?></td>
                                    <td><?php echo htmlspecialchars($alert['issue_description']); ?></td>
                                    <td><span class="badge bg-<?php echo ($alert['priority']=='Emergency') ? 'danger' : 'warning'; ?>"><?php echo $alert['priority']; ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="processAlert(<?php echo $alert['id']; ?>, 'assign')">Assign to Employee</button>
                                        <button class="btn btn-sm btn-secondary" onclick="processAlert(<?php echo $alert['id']; ?>, 'escalate')">Escalate</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Verification Pending -->
                    <h6 class="text-info"><i class="bi bi-check-circle"></i> Verification Pending</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Task ID</th>
                                    <th>Machine</th>
                                    <th>Assigned Employee</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($verification_tasks)): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-2">No tasks pending verification.</td></tr>
                                <?php endif; ?>
                                <?php foreach($verification_tasks as $task): ?>
                                <tr>
                                    <td>#<?php echo $task['id']; ?></td>
                                    <td><?php echo htmlspecialchars($task['machine_name']); ?></td>
                                    <td><?php echo htmlspecialchars($task['emp_name']); ?></td>
                                    <td>
                                        <a href="verify_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-success">Verify & Close</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card glass-card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0"><i class="bi bi-journal-text text-success me-2"></i>ከሰራተኞች የመጡ ሪፖርቶች</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ሰራተኛ</th>
                                    <th>ማሽን</th>
                                    <th>መጠን</th>
                                    <th>ድርጊት</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($employee_reports as $report): ?>
                                <tr>
                                    <td><strong><?php echo $report['employee_name']; ?></strong></td>
                                    <td><?php echo $report['machine_name']; ?></td>
                                    <td><span class="badge bg-light text-dark"><?php echo $report['quantity_produced']; ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary rounded-circle" title="ወደ ሱፐርቫይዘር ላክ"><i class="bi bi-arrow-up-right"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card glass-card border-0 shadow-sm h-100 border-top border-4 border-danger">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-tools me-2"></i>የጥገና ጥያቄዎች</h5>
                </div>
                <div class="card-body p-0" id="alertsContainer">
                    <div class="list-group list-group-flush">
                        <?php if(empty($new_requests)): ?>
                            <div class="text-center py-5 text-muted" id="noAlertsMsg">አዲስ የጥገና ጥያቄ የለም</div>
                        <?php endif; ?>
                        <?php foreach($new_requests as $alert): ?>
                        <div class="list-group-item bg-transparent py-3 border-bottom" id="alert-item-<?php echo $alert['id']; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($alert['machine_name']); ?></h6>
                                <select id="severity_<?php echo $alert['id']; ?>" class="form-select form-select-sm w-auto border-0 bg-light text-danger py-0 px-2" style="font-size: 0.7rem;">
                                    <option value="Low">Low</option>
                                    <option value="Normal" selected>Normal</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                            <p class="small text-muted mb-2"><?php echo htmlspecialchars($alert['issue_description']); ?></p>
                            
                            <div class="d-flex gap-2">
                                <button onclick="submitDecision(<?php echo $alert['id']; ?>, 'engineering')" class="btn btn-sm btn-outline-info w-50 rounded-pill" style="font-size: 0.75rem;">
                                    <i class="bi bi-gear"></i> ኢንጂነሪንግ
                                </button>
                                <button onclick="submitDecision(<?php echo $alert['id']; ?>, 'manager')" class="btn btn-sm btn-outline-danger w-50 rounded-pill" style="font-size: 0.75rem;">
                                    <i class="bi bi-shield-exclamation"></i> ማናጀር
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 text-center pb-4 mt-3">
                    <button class="btn btn-sm btn-light w-100 rounded-pill border" data-bs-toggle="modal" data-bs-target="#newMaintModal">
                        <i class="bi bi-plus-circle me-1"></i> አዲስ የጥገና ጥያቄ ፍጠር
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals for Alert Processing -->
<div class="modal fade" id="assignAlertModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Assign Alert to Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignAlertForm">
                <div class="modal-body">
                    <input type="hidden" name="req_id" id="alert_req_id">
                    <div class="mb-3">
                        <label>Select Employee</label>
                        <select name="employee_id" class="form-select" required>
                            <?php foreach($my_employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="escalateAlertModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Escalate Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="escalateAlertForm">
                <div class="modal-body">
                    <input type="hidden" name="req_id" id="escalate_req_id">
                    <p>Are you sure you want to escalate this alert to Engineering/Manager?</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Escalate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function processAlert(reqId, action) {
    if (action === 'assign') {
        document.getElementById('alert_req_id').value = reqId;
        var modal = new bootstrap.Modal(document.getElementById('assignAlertModal'));
        modal.show();
    } else if (action === 'escalate') {
        document.getElementById('escalate_req_id').value = reqId;
        var modal = new bootstrap.Modal(document.getElementById('escalateAlertModal'));
        modal.show();
    }
}

// AJAX for alert processing
$('#assignAlertForm').on('submit', function(e){
    e.preventDefault();
    $.post('process_decision.php', $(this).serialize() + '&assign_to_employee=1', function(res){
        alert('Alert assigned successfully!');
        location.reload();
    });
});

$('#escalateAlertForm').on('submit', function(e){
    e.preventDefault();
    $.post('process_decision.php', $(this).serialize() + '&escalate=1', function(res){
        alert('Alert escalated successfully!');
        location.reload();
    });
});
function assignTask(event, taskId) {
    event.preventDefault();
    
    const empId = document.getElementById('empSelect' + taskId).value;
    if (!empId) {
        alert("እባክዎ ሰራተኛ ይምረጡ!");
        return;
    }

    // ወደ ዳታቤዝ ለመላክ (AJAX)
    fetch('assign_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `task_id=${taskId}&employee_id=${empId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // ስራው ስለተመደበ ከዝርዝሩ ውስጥ እንዲጠፋ ማድረግ
            document.getElementById('task-row-' + taskId).remove();
            
            // የቶስት መልዕክት ማሳየት
            const toast = new bootstrap.Toast(document.getElementById('liveToast'));
            document.getElementById('toastMessage').innerText = "ስራው ለሰራተኛው በተሳካ ሁኔታ ተመድቧል!";
            toast.show();
            
            // ሞዳሉን መዝጋት
            const modal = bootstrap.Modal.getInstance(document.getElementById('assignModal' + taskId));
            modal.hide();
        } else {
            alert("ስህተት ተከስቷል፦ " + data.message);
        }
    });
}
</script>

<?php include '../includes/footer_glass.php'; ?>