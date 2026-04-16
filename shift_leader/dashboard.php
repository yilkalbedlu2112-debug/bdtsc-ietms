<?php 
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php'; // ለትርጉም __('key') ፋንክሽን

// 1. ሴሽን መኖሩን እና ሚናው Shift Leader መሆኑን ማረጋገጥ
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Shift Leader') {
    header("Location: ../login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'];
$full_name = $_SESSION['full_name'];

// 2. የዲፓርትመንቱን ስም ዳይናሚክ ለማድረግ (ከዲቢ)
$dept_stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
$dept_stmt->execute([$dept_id]);
$dept_info = $dept_stmt->fetch();
$dept_name = $dept_info['name'] ?? "Production Unit";

// 3. እንደ ዲፓርትመንቱ አይነት የገጽታ ቀለም (Theme) መምረጥ
$theme_color = "primary";
if (strpos($dept_name, 'Spinning') !== false) $theme_color = "info";
elseif (strpos($dept_name, 'Weaving') !== false) $theme_color = "success";
elseif (strpos($dept_name, 'Processing') !== false) $theme_color = "warning";
elseif (strpos($dept_name, 'Garment') !== false) $theme_color = "danger";

// 4. ውሳኔ የሚያስፈልጋቸው አዳዲስ ጥያቄዎች (Decision Needed)
$stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE dept_id = ? AND status = 'Pending'");
$stmt->execute([$dept_id]);
$new_requests = $stmt->fetchAll();

// 5. በሂደት ላይ ያሉ ስራዎች (Live Task Status)
$progress_stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE dept_id = ? AND status != 'Pending' ORDER BY id DESC");
$progress_stmt->execute([$dept_id]);
$all_tasks = $progress_stmt->fetchAll();

// 6. ለስራ ምደባ ሰራተኞችን ማግኘት
$emp_stmt = $pdo->prepare("SELECT id, full_name, user_role FROM users WHERE dept_id = ? AND user_role IN ('Employee', 'Technician')");
$emp_stmt->execute([$dept_id]);
$employees = $emp_stmt->fetchAll();

include '../includes/header_glass.php';
?>

<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1055;">
    <div id="liveToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body fw-semibold" id="toastMessage">Action completed.</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
</div>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 glass-card shadow-sm border-start border-5 border-<?php echo $theme_color; ?>">
        <div>
            <h2 class="text-dark fw-bold mb-0">
                <i class="bi bi-person-badge-fill text-<?php echo $theme_color; ?> me-2"></i>
                <?php echo htmlspecialchars($dept_name); ?> - Shift Leader Dashboard
            </h2>
            <p class="text-muted mb-0">ሪፖርት ለ፦ <strong><?php echo $dept_name; ?> Manager</strong></p>
        </div>
        <div class="text-end">
            <span class="badge bg-<?php echo $theme_color; ?> rounded-pill px-3 py-2 shadow-sm mb-1">
                <i class="bi bi-building me-1"></i> Dept ID: <?php echo htmlspecialchars($dept_id); ?>
            </span>
            <div class="small text-muted fw-bold"><?php echo date('l, M d, Y'); ?></div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card glass-card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0"><i class="bi bi-activity text-success me-2"></i><?php echo __('active_tasks'); ?> (የስራ ሁኔታ)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo __('tasks'); ?> / Machine</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($all_tasks)): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-4">No active tasks.</td></tr>
                                <?php endif; ?>
                                <?php foreach($all_tasks as $task): ?>
                                <tr id="task-row-<?php echo $task['id']; ?>">
                                    <td>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($task['machine_name']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($task['issue_description']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill px-3 task-status-badge <?php echo ($task['status'] === 'Completed') ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                            <?php echo htmlspecialchars($task['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if($task['status'] === 'Pending' || $task['status'] === 'Decision Taken'): ?>
                                        <button class="btn btn-sm btn-outline-<?php echo $theme_color; ?> rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#assignModal<?php echo $task['id']; ?>">
                                            <i class="bi bi-person-plus me-1"></i> ሰራተኛ መድብ
                                        </button>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="bi bi-check2-all"></i> በሂደት ላይ</span>
                                        <?php endif; ?>

                                        <div class="modal fade" id="assignModal<?php echo $task['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content glass-card shadow">
                                                    <div class="modal-header border-0">
                                                        <h5 class="modal-title fw-bold">ሰራተኛ መድብ</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body text-start">
                                                        <form onsubmit="assignTask(event, <?php echo $task['id']; ?>)" id="assignForm<?php echo $task['id']; ?>">
                                                            <div class="mb-4 text-start">
                                                                <label class="form-label text-muted fw-semibold">ሰራተኛ ይምረጡ</label>
                                                                <select name="employee_id" class="form-select bg-light border-0" required id="empSelect<?php echo $task['id']; ?>">
                                                                    <option value="">-- ሰራተኛ ምረጥ --</option>
                                                                    <?php foreach($employees as $emp): ?>
                                                                    <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['full_name']); ?> (<?php echo $emp['user_role']; ?>)</option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="d-grid">
                                                                <button type="submit" class="btn btn-<?php echo $theme_color; ?> rounded-pill py-2 fw-semibold">መደብ አረጋግጥ</button>
                                                            </div>
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
        </div>

        <div class="col-lg-4">
            <div class="card glass-card border-0 shadow-sm h-100 border-top border-4 border-danger">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>ብልሽቶች (ውሳኔ ይፈልጋሉ)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="alertsContainer">
                        <?php if(empty($new_requests)): ?>
                            <div class="list-group-item bg-transparent text-muted py-4 text-center border-0" id="noAlertsMsg">
                                <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>
                                አዲስ የጥገና ጥያቄ የለም
                            </div>
                        <?php endif; ?>
                        <?php foreach($new_requests as $alert): ?>
                        <div class="list-group-item bg-transparent py-3 border-bottom" id="alert-item-<?php echo $alert['id']; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($alert['machine_name']); ?></h6>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill px-2">ጥያቄ</span>
                            </div>
                            <p class="mb-3 small text-secondary lh-sm"><?php echo htmlspecialchars($alert['issue_description']); ?></p>
                            
                            <div class="mb-3">
                                <select id="severity_<?php echo $alert['id']; ?>" class="form-select form-select-sm bg-light border-0">
                                    <option value="Low">ቀላል ብልሽት (Low)</option>
                                    <option value="High">ከባድ ብልሽት (High)</option>
                                </select>
                            </div>
                            <div class="d-flex gap-2">
                                <button onclick="submitDecision(<?php echo $alert['id']; ?>, 'engineering')" class="btn btn-sm btn-outline-primary w-50 rounded-pill" title="Send to Engineering">
                                    <i class="bi bi-send"></i> Eng
                                </button>
                                <button onclick="submitDecision(<?php echo $alert['id']; ?>, 'manager')" class="btn btn-sm btn-outline-danger w-50 rounded-pill" title="Escalate to Manager">
                                    <i class="bi bi-arrow-up"></i> Manager
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 1. ለተጠቃሚው መልእክት ማሳያ (Toast Notification)
function showToast(message, isError = false) {
    const toastEl = document.getElementById('liveToast');
    const toastBody = document.getElementById('toastMessage');
    toastBody.textContent = message;
    
    if (isError) {
        toastEl.classList.remove('bg-success');
        toastEl.classList.add('bg-danger');
    } else {
        toastEl.classList.remove('bg-danger');
        toastEl.classList.add('bg-success');
    }
    
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}

// 2. ሰራተኛ ለመመደብ (Assign Task)
function assignTask(event, taskId) {
    event.preventDefault();
    const empId = document.getElementById('empSelect' + taskId).value;
    
    if(!empId) {
        showToast("እባክዎ ሰራተኛ ይምረጡ", true);
        return;
    }

    fetch('assign_task_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ task_id: taskId, employee_id: empId })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showToast(data.message);
            // ሞዳሉን መዝጋት
            const modalEl = document.getElementById('assignModal' + taskId);
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
            
            // ገጹን ሳናድስ (Refresh ሳናደርግ) ሁኔታውን መቀየር
            const row = document.getElementById('task-row-' + taskId);
            const badge = row.querySelector('.task-status-badge');
            badge.className = 'badge rounded-pill px-3 task-status-badge bg-info text-white';
            badge.textContent = 'Assigned'; 
            row.querySelector('td:nth-child(3)').innerHTML = '<span class="text-muted small"><i class="bi bi-check2-all"></i> Assigned</span>';
        } else {
            showToast(data.message, true);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('የኔትወርክ ስህተት አጋጥሟል', true);
    });
}

// 3. የጥገና ውሳኔዎችን ለመመዝገብ (Engineering/Manager)
function submitDecision(reqId, action) {
    const severity = document.getElementById('severity_' + reqId).value;
    
    // ለተጠቃሚው ማረጋገጫ መጠየቅ (ከተፈለገ)
    if(!confirm('እርግጠኛ ነዎት ይህን ውሳኔ መመዝገብ ይፈልጋሉ?')) return;

    fetch('process_decision_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ req_id: reqId, severity: severity, action: action })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showToast(data.message);
            // የጥያቄውን ካርድ በስላሳ ሁኔታ ማስወገድ (Fade out)
            const alertItem = document.getElementById('alert-item-' + reqId);
            alertItem.style.transition = 'all 0.4s ease';
            alertItem.style.opacity = '0';
            alertItem.style.transform = 'translateX(20px)';
            
            setTimeout(() => {
                alertItem.remove();
                // ሁሉም ጥያቄዎች ካለቁ "ጥያቄ የለም" የሚል መልእክት ማሳየት
                const container = document.getElementById('alertsContainer');
                if(container.querySelectorAll('.list-group-item:not(#noAlertsMsg)').length === 0) {
                    container.innerHTML = '<div class="list-group-item bg-transparent text-muted py-4 text-center border-0" id="noAlertsMsg"><i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>አዲስ የጥገና ጥያቄ የለም</div>';
                }
            }, 400);
        } else {
            showToast(data.message, true);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('የሰርቨር ስህተት አጋጥሟል', true);
    });
}

function updateDecision(e, reqId) {
    e.preventDefault();
}
</script>
<?php include '../includes/footer_glass.php'; ?>