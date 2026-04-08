<?php
session_start();
require_once '../includes/db.php';

// Security: Check if user is Technician
if ($_SESSION['role'] !== 'Technician') {
    header("Location: ../auth/login.php");
    exit();
}

$tech_id = $_SESSION['user_id'];

// ለቴክኒሻኑ የተመደቡ እና ገና ያልተጠናቀቁ ስራዎችን ማምጣት
$stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE assigned_to = ? AND status != 'Completed' ORDER BY priority DESC");
$stmt->execute([$tech_id]);
$my_tasks = $stmt->fetchAll();
include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-tools text-dark"></i> የቴክኒሻን የሥራ ገጽ</h3>
        <span class="badge bg-secondary">Technician ID: <?php echo $tech_id; ?></span>
    </div>

    <div class="row">
        <?php if (count($my_tasks) > 0): ?>
            <?php foreach($my_tasks as $task): ?>
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm border-0 priority-<?php echo strtolower($task['priority']); ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title fw-bold"><?php echo $task['machine_name']; ?></h5>
                            <span class="badge <?php echo ($task['priority'] == 'Emergency') ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                <?php echo $task['priority']; ?>
                            </span>
                        </div>
                        <p class="card-text text-muted"><?php echo $task['issue_description']; ?></p>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bi bi-clock"></i> <?php echo date('M d, H:i', strtotime($task['created_at'])); ?></small>
                            
                            <?php if($task['status'] == 'Sent to Engineering' || $task['status'] == 'Pending'): ?>
                                <a href="update_task.php?id=<?php echo $task['id']; ?>&status=In Progress" class="btn btn-sm btn-primary">ጥገና ጀምር</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#completeModal<?php echo $task['id']; ?>">
                                    ጥገናው ተጠናቋል
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="completeModal<?php echo $task['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="complete_task.php" method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">የጥገና ሪፖርት ማጠቃለያ</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <div class="mb-3">
                                    <label class="form-label">የተወሰደ እርምጃ (Action Taken)</label>
                                    <textarea name="action_taken" class="form-control" rows="3" required placeholder="ምን እንደተጠገነ ይግለጹ..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">ጥቅም ላይ የዋሉ ዕቃዎች (Spare Parts)</label>
                                    <input type="text" name="spare_parts" class="form-control" placeholder="ምሳሌ፡ 2 Bearings, 1 Belt">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success w-100">ሪፖርቱን ላክ</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-check2-all display-1 text-muted"></i>
                <p class="mt-3 fs-4 text-muted">ለጊዜው የተመደበልህ አዲስ ስራ የለም።</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include '../includes/footer_glass.php'; ?>