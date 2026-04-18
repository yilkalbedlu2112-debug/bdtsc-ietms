<?php
session_start();
require_once '../includes/db.php';

$task_id = $_GET['id'] ?? null;
if (!$task_id) { header("Location: dashboard.php"); exit(); }

// የታስኩን ዝርዝር እና የሰራተኛውን ሪፖርት ማምጣት
$stmt = $pdo->prepare("SELECT mr.*, u.full_name as emp_name 
                     FROM maintenance_requests mr 
                     JOIN users u ON mr.employee_id = u.id 
                     WHERE mr.id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch();
include '../includes/header_glass.php';
?>

<div class="container py-5">
    <div class="card glass-card shadow border-0">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-4"><i class="bi bi-patch-check text-primary"></i> የስራ ፍተሻ (Verification)</h4>
            
            <div class="row">
                <div class="col-md-6">
                    <p><strong>ማሽን/ርዕስ:</strong> <?php echo htmlspecialchars($task['machine_name']); ?></p>
                    <p><strong>የተመደበው ሰራተኛ:</strong> <?php echo htmlspecialchars($task['emp_name']); ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <span class="badge bg-info p-2">Status: <?php echo $task['status']; ?></span>
                </div>
            </div>
            <hr>
            <div class="mb-4">
                <h6><strong>የሰራተኛው የጥገና ሪፖርት:</strong></h6>
                <div class="p-3 bg-light rounded border">
                    <?php echo nl2br(htmlspecialchars($task['completion_notes'] ?? 'ምንም ሪፖርት አልተጻፈም')); ?>
                </div>
            </div>

            <div class="d-flex gap-2">
                <form action="approve_task.php" method="POST">
                    <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                    <button type="submit" name="action" value="approve" class="btn btn-success px-4">
                        <i class="bi bi-check-circle"></i> ስራውን አጽድቅ (Approve)
                    </button>
                    <button type="submit" name="action" value="reject" class="btn btn-danger px-4">
                        <i class="bi bi-x-circle"></i> ስራው አልተጠናቀቀም (Reject)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer_glass.php'; ?>