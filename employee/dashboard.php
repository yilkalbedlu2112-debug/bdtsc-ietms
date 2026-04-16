<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$dept_name = $_SESSION['dept_name']; // ከሴሽን የሚመጣ የዲፓርትመንት ስም
$user_role = $_SESSION['user_role']; 

// --- ዳይናሚክ ሎጅክ (በማናጀሩ ሎጅክ መሰረት) ---
$is_production = false;
$table_h = "Task / Subject";
$btn_extra_name = "Report Issue";
$btn_extra_icon = "bi-exclamation-triangle";
$theme_color = "primary";

// 1. Production Group
if (in_array($dept_name, ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'])) {
    $is_production = true;
    $table_h = "Machine / Work Station";
    $btn_extra_name = "Request Maintenance";
    $theme_color = (strpos($dept_name, 'Spinning') !== false) ? "info" : 
                  ((strpos($dept_name, 'Garment') !== false) ? "danger" : "success");
} 
// 2. Technical & Quality
elseif (in_array($dept_name, ['Engineering', 'Quality Assurance'])) {
    $is_production = true;
    $table_h = "Asset / Equipment ID";
    $btn_extra_name = "Technical Report";
    $theme_color = "warning";
}
// 3. Finance & Resource
elseif (in_array($dept_name, ['Finance Department', 'Procurement / Property'])) {
    $table_h = "Transaction / Item Ref";
    $btn_extra_name = "New Finance Request";
    $theme_color = "primary";
}
// 4. Admin & Strategy
elseif (in_array($dept_name, ['General Management', 'Human Resource (HR)', 'Planning', 'Strategy & Innovation', 'System Research & Development', 'Legal Service', 'Audit & Inspection'])) {
    $table_h = "Subject / Case Title";
    $btn_extra_name = "Case Update";
    $theme_color = "dark";
}

// ዳታ ማምጣት (UC-09: View Assigned Tasks)
$stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE assigned_to = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$my_tasks = $stmt->fetchAll();

include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm border-start border-5 border-<?= $theme_color ?>">
            <div>
                <h4 class="fw-bold mb-0">ሰላም፣ <?= $_SESSION['full_name'] ?> 👋</h4>
                <small class="text-muted fw-bold text-uppercase"><?= $dept_name ?> | <?= $user_role ?></small>
            </div>
            <div class="text-end">
                <div class="fw-bold fs-5"><?= date('H:i A') ?></div>
                <button class="btn btn-sm btn-outline-secondary rounded-pill mt-1">EN / አማ</button>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <h5 class="fw-bold mb-3"><i class="bi bi-card-checklist me-2"></i>የተመደቡልኝ ስራዎች (My Tasks)</h5>
            
            <?php foreach($my_tasks as $task): ?>
            <div class="glass-card p-4 mb-3 border-0 shadow-sm position-relative overflow-hidden" id="task-card-<?= $task['id'] ?>">
                <div class="position-absolute top-0 start-0 h-100 bg-<?= ($task['priority'] == 'High') ? 'danger' : $theme_color ?>" style="width: 5px;"></div>
                
                <div class="d-flex justify-content-between align-items-start">
                    <div class="ms-2">
                        <span class="badge bg-light text-dark border mb-2 small text-uppercase"><?= $table_h ?>: <?= htmlspecialchars($task['machine_name'] ?? 'N/A') ?></span>
                        <h6 class="fw-bold text-dark fs-5 mb-1"><?= htmlspecialchars($task['issue_description']) ?></h6>
                        <div class="d-flex gap-3 mt-3">
                            <span class="small text-muted"><i class="bi bi-calendar-event me-1"></i> <?= date('M d, Y', strtotime($task['created_at'])) ?></span>
                            <span id="status-badge-<?= $task['id'] ?>" class="badge rounded-pill px-3 py-2 <?= ($task['status'] == 'Completed') ? 'bg-success' : 'bg-soft-'.$theme_color.' text-'.$theme_color ?>">
                                <?= $task['status'] ?>
                            </span>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <?php if($task['status'] !== 'Completed'): ?>
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm shadow-sm px-3 rounded-pill dropdown-toggle" data-bs-toggle="dropdown">ሁኔታውን ቀይር</button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'In Progress')"><i class="bi bi-play-circle text-primary me-2"></i>In Progress</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'Blocked')"><i class="bi bi-slash-circle text-warning me-2"></i>Blocked (Issue)</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item fw-bold text-success" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'Completed')"><i class="bi bi-check-all me-2"></i>Mark as Completed</a></li>
                            </ul>
                        </div>
                        <?php else: ?>
                            <span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> የተጠናቀቀ</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(empty($my_tasks)): ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                    <i class="bi bi-clipboard-x fs-1 text-muted mb-2"></i>
                    <p class="text-muted">ለዛሬ የተመደበ ስራ የለም። (No tasks assigned for today.)</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-<?= $theme_color ?> text-white p-3 border-0 rounded-top-4">
                    <h5 class="mb-0 fw-bold"><i class="bi <?= $btn_extra_icon ?> me-2"></i> <?= $btn_extra_name ?></h5>
                </div>
                <div class="card-body p-4">
                    <form onsubmit="submitFeedback(event)" id="feedbackForm">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">የብልሽት/የችግር አይነት (Category)</label>
                            <select class="form-select bg-light border-0" id="issue_category" required>
                                <option value="Technical">Technical (የቴክኒክ ችግር)</option>
                                <option value="Material">Material (የጥሬ እቃ እጥረት)</option>
                                <option value="Administrative">Administrative (አስተዳደራዊ)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">ዝርዝር መግለጫ (Description)</label>
                            <textarea class="form-control bg-light border-0" id="issue_desc" rows="4" placeholder="ችግሩን እዚህ ይግለጹ..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-<?= $theme_color ?> w-100 py-2 fw-bold rounded-pill">
                            ሪፖርት ላክ (Submit Feedback)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// UC-10 & UC-11: Update Task Status
function updateTaskStatus(taskId, newStatus) {
    if(!confirm('እርግጠኛ ነዎት ሁኔታውን ወደ ' + newStatus + ' መቀየር ይፈልጋሉ?')) return;

    fetch('update_status_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ task_id: taskId, status: newStatus })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert('ሁኔታው ተቀይሯል: ' + newStatus);
            location.reload(); 
        } else {
            alert('ስህተት: ' + data.message);
        }
    });
}

// UC-15: Submit Feedback / Blocker
function submitFeedback(e) {
    e.preventDefault();
    const category = document.getElementById('issue_category').value;
    const desc = document.getElementById('issue_desc').value;

    fetch('submit_feedback_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ category: category, description: desc })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert('ሪፖርቱ ለShift Leader እና ለማናጀር ተልኳል!');
            document.getElementById('feedbackForm').reset();
        }
    });
}
</script>

<?php include '../includes/footer_glass.php'; ?>