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
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE assigned_to_dept = ? AND (assigned_employee IS NULL OR assigned_employee = 0) ORDER BY created_at DESC");
$stmt->execute([$dept_id]);
$pending_tasks = $stmt->fetchAll();

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
                    <h5 class="fw-bold mb-0"><i class="bi bi-list-task text-primary me-2"></i>ከበላይ የመጡ ስራዎች (Pending)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>የስራው አይነት</th>
                                    <th>ቅድሚያ የሚሰጠው</th>
                                    <th class="text-center">ድርጊት</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($pending_tasks)): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-4">መደብ የሚጠብቅ አዲስ ስራ የለም።</td></tr>
                                <?php endif; ?>
                                <?php foreach($pending_tasks as $task): ?>
                                <tr id="task-row-<?php echo $task['id']; ?>">
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($task['task_title'] ?? 'General Task'); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($task['description'] ?? ''); ?></small>
                                    </td>
                                    <td><span class="badge bg-soft-warning text-warning border border-warning">Normal</span></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-<?php echo $theme_color; ?> rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#assignModal<?php echo $task['id']; ?>">
                                            <i class="bi bi-person-plus"></i> ሰራተኛ መድብ
                                        </button>
                                        
                                        <div class="modal fade" id="assignModal<?php echo $task['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content glass-card shadow">
                                                    <div class="modal-header border-0"><h5 class="fw-bold">ስራ መመደቢያ</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                    <div class="modal-body text-start">
                                                        <form onsubmit="assignTask(event, <?php echo $task['id']; ?>)">
                                                            <div class="mb-3">
                                                                <label class="form-label">ሰራተኛ ይምረጡ</label>
                                                                <select id="empSelect<?php echo $task['id']; ?>" class="form-select bg-light border-0" required>
                                                                    <option value="">-- ምረጥ --</option>
                                                                    <?php foreach($my_employees as $emp): ?>
                                                                        <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['full_name']); ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <button type="submit" class="btn btn-primary w-100 rounded-pill">መደብ አረጋግጥ</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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

<script>
function showToast(message, isError = false) {
    const toastEl = document.getElementById('liveToast');
    const toastBody = document.getElementById('toastMessage');
    toastBody.textContent = message;
    toastEl.classList.remove('bg-success', 'bg-danger');
    toastEl.classList.add(isError ? 'bg-danger' : 'bg-success');
    new bootstrap.Toast(toastEl).show();
}

// ሰራተኛ ለመመደብ (assign_task_ajax.php)
function assignTask(event, taskId) {
    event.preventDefault();
    const empId = document.getElementById('empSelect' + taskId).value;
    
    fetch('assign_task_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ task_id: taskId, employee_id: empId })
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            showToast(data.message);
            bootstrap.Modal.getInstance(document.getElementById('assignModal' + taskId)).hide();
            document.getElementById('task-row-' + taskId).style.opacity = '0.5';
            document.getElementById('task-row-' + taskId).querySelector('button').disabled = true;
            document.getElementById('task-row-' + taskId).querySelector('button').innerHTML = 'ተመድቧል';
        } else {
            showToast(data.message, true);
        }
    });
}

// የጥገና ውሳኔ (process_decision_ajax.php)
function submitDecision(reqId, action) {
    const severity = document.getElementById('severity_' + reqId).value;
    if(!confirm('እርግጠኛ ነዎት ይህን ውሳኔ ማስተላለፍ ይፈልጋሉ?')) return;

    fetch('process_decision_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ req_id: reqId, severity: severity, action: action })
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            showToast(data.message);
            const item = document.getElementById('alert-item-' + reqId);
            item.style.transform = 'scale(0.8)';
            item.style.opacity = '0';
            setTimeout(() => { 
                item.remove(); 
                if(document.querySelectorAll('#alertsContainer .list-group-item').length === 0) {
                    document.getElementById('alertsContainer').innerHTML = '<div class="text-center py-5 text-muted">አዲስ የጥገና ጥያቄ የለም</div>';
                }
            }, 300);
        } else {
            showToast(data.message, true);
        }
    });
}
</script>

<?php include '../includes/footer_glass.php'; ?>