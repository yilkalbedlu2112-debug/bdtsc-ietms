<?php
session_start();
require_once '../includes/db.php';

// 1. የደህንነት ቼክ
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$dept_name = $_SESSION['dept_name']; 
$user_role = $_SESSION['user_role']; 

// 2. ዳይናሚክ ሎጂክ (በማናጀሩ ግሩፒንግ መሰረት)
$production_group = ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'];
$technical_quality_group = ['Engineering', 'Quality Assurance'];
$finance_resource_group = ['Finance Department', 'Procurement / Property'];
$admin_strategy_group = ['General Management', 'Human Resource (HR)', 'Planning', 'Strategy & Innovation', 'System Research & Development', 'Legal Service', 'Audit & Inspection'];

$is_production = false;
$table_h = "Task / Subject";
$btn_extra_name = "Report Issue";
$btn_extra_icon = "bi-exclamation-triangle";
$theme_color = "primary";

if (in_array($dept_name, $production_group)) {
    $is_production = true;
    $table_h = "Machine / Work Station";
    $btn_extra_name = "Request Maintenance";
    $btn_extra_icon = "bi-tools";
    $theme_color = (strpos($dept_name, 'Spinning') !== false) ? "info" : 
                  ((strpos($dept_name, 'Garment') !== false) ? "danger" : "success");
} 
elseif (in_array($dept_name, $technical_quality_group)) {
    $is_production = true;
    $table_h = "Asset / Equipment ID";
    $btn_extra_name = "Technical Report";
    $btn_extra_icon = "bi-shield-check";
    $theme_color = "warning";
} 
elseif (in_array($dept_name, $finance_resource_group)) {
    $table_h = "Transaction / Item Ref";
    $btn_extra_name = "New Finance Request";
    $btn_extra_icon = "bi-cash-stack";
    $theme_color = "primary";
} 
elseif (in_array($dept_name, $admin_strategy_group)) {
    $table_h = "Subject / Case Title";
    $btn_extra_name = "Case Update";
    $btn_extra_icon = "bi-journal-bookmark";
    $theme_color = "dark";
}

// 3. ዳታ ማምጣት (የራሱን ስራ ብቻ እንዲያይ)
$stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE assigned_to = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$my_tasks = $stmt->fetchAll();

include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm border-start border-5 border-<?= $theme_color ?>">
            <div>
                <h4 class="fw-bold mb-0">ሰላም፣ <?= htmlspecialchars($_SESSION['full_name']) ?> 👋</h4>
                <small class="text-muted fw-bold text-uppercase"><?= htmlspecialchars($dept_name) ?> | <?= htmlspecialchars($user_role) ?></small>
            </div>
            <div class="text-end">
                <div class="fw-bold fs-5 text-uppercase"><?= date('h:i A') ?></div>
                <div class="small text-muted"><?= date('D, M d, Y') ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <h5 class="fw-bold mb-3"><i class="bi bi-card-checklist me-2 text-<?= $theme_color ?>"></i>የተመደቡልኝ ስራዎች (My Tasks)</h5>
            
            <?php foreach($my_tasks as $task): ?>
            <div class="glass-card p-4 mb-3 border-0 shadow-sm position-relative overflow-hidden bg-white rounded-4" id="task-card-<?= $task['id'] ?>">
                <div class="position-absolute top-0 start-0 h-100 bg-<?= ($task['priority'] == 'High') ? 'danger' : $theme_color ?>" style="width: 5px;"></div>
                
                <div class="d-flex justify-content-between align-items-start">
                    <div class="ms-2">
                        <span class="badge bg-light text-dark border mb-2 small text-uppercase">
                            <?= $table_h ?>: <?= htmlspecialchars($task['machine_name'] ?? 'N/A') ?>
                        </span>
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
                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'Blocked')"><i class="bi bi-slash-circle text-warning me-2"></i>Blocked</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item fw-bold text-success" href="javascript:void(0)" onclick="updateTaskStatus(<?= $task['id'] ?>, 'Completed')"><i class="bi bi-check-all me-2"></i>Mark as Completed</a></li>
                            </ul>
                        </div>
                        <?php else: ?>
                            <span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> ተጠናቋል</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(empty($my_tasks)): ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                    <i class="bi bi-clipboard-x fs-1 text-muted mb-2"></i>
                    <p class="text-muted">ለዛሬ የተመደበ ስራ የለም።</p>
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
                            <label class="form-label small fw-bold">የሪፖርት አይነት (Category)</label>
                            <select class="form-select bg-light border-0" id="issue_category" required>
                                <?php if($is_production): ?>
                                    <option value="Technical">Technical (የቴክኒክ ብልሽት)</option>
                                    <option value="Material">Material (የጥሬ እቃ እጥረት)</option>
                                    <option value="Safety">Safety (የደህንነት ስጋት)</option>
                                <?php else: ?>
                                    <option value="Administrative">Administrative (አስተዳደራዊ)</option>
                                    <option value="Resource">Resource (የግብዓት እጥረት)</option>
                                    <option value="System">System (የሲስተም ችግር)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">ዝርዝር መግለጫ (Description)</label>
                            <textarea class="form-control bg-light border-0" id="issue_desc" rows="4" placeholder="<?= $btn_extra_name ?>ን እዚህ ይግለጹ..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-<?= $theme_color ?> w-100 py-2 fw-bold rounded-pill text-white">
                            ሪፖርት ላክ (Submit)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateTaskStatus(taskId, newStatus) {
    if(!confirm('ሁኔታውን ወደ ' + newStatus + ' መቀየር ይፈልጋሉ?')) return;

    fetch('update_status_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ task_id: taskId, status: newStatus })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            location.reload(); 
        } else {
            alert('ስህተት: ' + data.message);
        }
    });
}

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
            alert('ሪፖርቱ በትክክል ተልኳል!');
            document.getElementById('feedbackForm').reset();
        } else {
            alert('ሪፖርት መላክ አልተቻለም።');
        }
    });
}
</script>

<?php include '../includes/footer_glass.php'; ?>