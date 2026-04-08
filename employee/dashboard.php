<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE assigned_to = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$my_tasks = $stmt->fetchAll();
include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm">
            <div>
                <h4 class="fw-bold mb-0">ሰላም፣ <?= $_SESSION['full_name'] ?> 👋</h4>
                <small class="text-muted">የዛሬ ስራዎችህን እዚህ ማስተዳደር ትችላለህ</small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end">
                    <div class="fw-bold"><?= date('H:i A') ?></div>
                    <small class="text-muted"><?= date('D, M d') ?></small>
                </div>
                <img src="../assets/img/user.png" class="rounded-circle" width="45">
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <h5 class="fw-bold mb-3">የተመደቡልኝ ስራዎች (My Tasks)</h5>
            <?php foreach($my_tasks as $task): ?>
            <div class="glass-card p-4 mb-3 border-start border-4 border-primary" id="task-card-<?= $task['id'] ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($task['machine_name']) ?></h6>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($task['issue_description']) ?></p>
                        <div class="d-flex gap-2">
                            <span class="status-badge bg-soft-primary text-primary border" id="status-badge-<?= $task['id'] ?>">
                                <i class="bi bi-gear-fill me-1"></i> <?= htmlspecialchars($task['status']) ?>
                            </span>
                        </div>
                    </div>
                    <?php if($task['status'] !== 'Completed'): ?>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                        <ul class="dropdown-menu shadow border-0">
                            <li><button class="dropdown-item" onclick="updateTaskStatus(<?= $task['id'] ?>, 'In Progress')">መጀመሬን አሳውቅ (In Progress)</button></li>
                            <li><button class="dropdown-item text-danger" onclick="updateTaskStatus(<?= $task['id'] ?>, 'Blocked')">ስራው ተቋርጧል (Blocked)</button></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if($task['status'] !== 'Completed'): ?>
                <div class="mt-4 d-flex justify-content-end">
                    <button onclick="updateTaskStatus(<?= $task['id'] ?>, 'Completed')" class="btn btn-success btn-sm px-4 rounded-pill">
                        ስራውን አጠናቅቄአለሁ (Done)
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if(empty($my_tasks)): ?>
                <p class="text-muted">No tasks assigned to you right now.</p>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="glass-card p-4 border-0 text-white" style="background: var(--primary-gradient);">
                <h5 class="fw-bold mb-3"><i class="bi bi-megaphone-fill me-2"></i> ብልሽት ሪፖርት</h5>
                <p class="small opacity-75">ማሽንዎ ላይ ችግር ካጋጠመዎት ወዲያውኑ እዚህ ያሳውቁ።</p>
                <form action="submit_grievance.php" method="POST">
                    <div class="mb-3">
                        <label class="small">የብልሽቱ አይነት</label>
                        <select class="form-select border-0" name="type">
                            <option>ሜካኒካል ብልሽት</option>
                            <option>ኤሌክትሪክ ብልሽት</option>
                            <option>የጥሬ እቃ እጥረት</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <textarea class="form-control border-0" name="msg" rows="3" placeholder="ዝርዝር ሁኔታውን ይግለጹ..."></textarea>
                    </div>
                    <button class="btn btn-white w-100 fw-bold shadow-sm" style="background: white; color: #764ba2;">ሪፖርት ላክ</button>
                </form>
            </div>
        </div>
    </div>
</script>
<?php include '../includes/footer_glass.php'; ?>