<?php
session_start();
require_once '../includes/db.php';

$task_id = $_GET['id'] ?? null;
if (!$task_id) { header("Location: dashboard.php"); exit(); }

// Handle POST for verify & close
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_close') {
    $task_id = $_POST['task_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE maintenance_requests SET is_verified = 1, status = 'Completed', completed_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$task_id]);
        
        // Audit Log
        log_action($pdo, $user_id, "Task Verification", "Shift Leader verified and closed task #$task_id");
        
        echo "<script>alert('Task verified and closed successfully!'); window.location.href='dashboard.php';</script>";
        exit();
    } catch (PDOException $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.location.href='dashboard.php';</script>";
        exit();
    }
}

// የታስኩን ዝርዝር እና የሰራተኛውን ሪፖርት ማምጣት
$stmt = $pdo->prepare("SELECT mr.*, u.full_name as emp_name 
                     FROM maintenance_requests mr 
                     JOIN users u ON mr.assigned_to = u.id 
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
                <form action="verify_task.php" method="POST">
                    <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                    <button type="submit" name="action" value="verify_close" class="btn btn-success px-4">
                        <i class="bi bi-check-circle"></i> Verify & Close
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer_glass.php'; ?>