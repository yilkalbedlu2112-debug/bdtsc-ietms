<?php
/**
 * Review tasks for employees in the same department: Under Review / Grievance.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Shift Leader') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$user_id = (int) $_SESSION['user_id'];
$dept_id = isset($_SESSION['dept_id']) ? (int) $_SESSION['dept_id'] : 0;

$assigneeCol = ((int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'assigned_to'"
)->fetchColumn() > 0) ? 'assigned_to' : 'assigned_employee';

$titleCol = ((int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'title'"
)->fetchColumn() > 0) ? 'title' : 'task_title';

$hasLeaderComment = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'leader_comment'"
)->fetchColumn() > 0;

$hasEmployeeReport = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'employee_report'"
)->fetchColumn() > 0;

$notifHasIsRead = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'is_read'"
)->fetchColumn() > 0;

$success_msg = null;
$error_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
    $decision = $_POST['decision'] ?? '';
    $comment = trim((string) ($_POST['leader_comment'] ?? ''));

    if ($dept_id <= 0) {
        $error_msg = 'Department context is required to review tasks.';
    } elseif (!$task_id || !in_array($decision, ['approve', 'reject'], true)) {
        $error_msg = 'Invalid request.';
    } elseif ($decision === 'reject' && $comment === '') {
        $error_msg = 'Leader comment is required when sending a task back for redo.';
    } else {
        try {
            $pdo->beginTransaction();

            $sql = "SELECT t.id, t.status, t.`{$titleCol}` AS task_title, t.`{$assigneeCol}` AS assignee_id
                    FROM tasks t
                    INNER JOIN users u ON u.id = t.`{$assigneeCol}`
                    WHERE t.id = ? AND t.status IN ('Under Review','Grievance')
                    AND u.dept_id = ?";
            $chk = $pdo->prepare($sql . ' FOR UPDATE');
            $chk->execute([$task_id, $dept_id]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $pdo->rollBack();
                throw new RuntimeException('Task not found or assignee is not in your department.');
            }

            $assignee_id = (int) $row['assignee_id'];
            if ($assignee_id <= 0) {
                $pdo->rollBack();
                throw new RuntimeException('Task has no assignee.');
            }

            $new_status = $decision === 'approve' ? 'Completed' : 'Redo';
            $final_comment = $comment;

            if ($hasLeaderComment) {
                $upd = $pdo->prepare(
                    'UPDATE tasks SET status = ?, leader_comment = ?, updated_at = NOW() WHERE id = ?'
                );
                $upd->execute([$new_status, $final_comment, $task_id]);
            } else {
                $upd = $pdo->prepare('UPDATE tasks SET status = ?, updated_at = NOW() WHERE id = ?');
                $upd->execute([$new_status, $task_id]);
            }

            $notif_text = $decision === 'approve'
                ? 'Your task was approved and marked completed: ' . ($row['task_title'] ?? ('#' . $task_id))
                : 'Your task was returned for redo. Leader comment: ' . $comment;

            if ($notifHasIsRead) {
                $n = $pdo->prepare(
                    "INSERT INTO notifications (user_id, message, type, is_read) VALUES (?, ?, 'task_review', 0)"
                );
                $n->execute([$assignee_id, $notif_text]);
            } else {
                $n = $pdo->prepare(
                    "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'task_review')"
                );
                $n->execute([$assignee_id, $notif_text]);
            }

            $log_details = sprintf(
                'Task #%d (%s): %s → %s. leader_comment: %s',
                $task_id,
                $row['status'],
                $decision === 'approve' ? 'APPROVE' : 'REJECT_REDO',
                $new_status,
                $final_comment
            );
            log_action($pdo, $user_id, 'TASK_REVIEW', $log_details);

            $pdo->commit();
            $success_msg = 'Review saved successfully.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = $e->getMessage();
        }
    }
}

$pending_reviews = [];
if ($dept_id > 0) {
    $listSql = "SELECT t.id, t.`{$titleCol}` AS task_title, t.status, t.`{$assigneeCol}` AS assignee_id,
                u.full_name AS emp_name";
    if ($hasEmployeeReport) {
        $listSql .= ', t.employee_report';
    }
    $listSql .= " FROM tasks t
        INNER JOIN users u ON u.id = t.`{$assigneeCol}`
        WHERE t.status IN ('Under Review','Grievance')
        AND u.dept_id = ?";
    $stmt = $pdo->prepare($listSql . ' ORDER BY t.updated_at DESC');
    $stmt->execute([$dept_id]);
    $pending_reviews = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h1 class="h4 fw-bold mb-0"><i class="bi bi-check2-square text-primary me-2"></i>Review &amp; grievance</h1>
                        <p class="text-muted small mb-0">Tasks from employees in your department only.</p>
                    </div>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Dashboard</a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($dept_id <= 0): ?>
        <div class="alert alert-warning border-0 shadow-sm">Your account has no department. Reviews cannot be scoped.</div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($dept_id > 0 && empty($pending_reviews)): ?>
        <div class="alert alert-info border-0 shadow-sm">No tasks under review or in grievance for your department.</div>
    <?php elseif ($dept_id > 0): ?>
        <div class="row g-4">
            <?php foreach ($pending_reviews as $task): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <strong class="text-truncate me-2"><?php echo htmlspecialchars((string) ($task['task_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span class="badge bg-info text-dark"><?php echo htmlspecialchars((string) $task['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="card-body">
                        <p class="mb-2 small"><strong>Employee:</strong> <?php echo htmlspecialchars((string) ($task['emp_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if ($hasEmployeeReport && !empty($task['employee_report'])): ?>
                            <p class="small text-muted border rounded p-2 bg-light"><strong>Employee report</strong><br>
                            <?php echo nl2br(htmlspecialchars((string) $task['employee_report'], ENT_QUOTES, 'UTF-8')); ?></p>
                        <?php endif; ?>

                        <form method="post" class="mb-3">
                            <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
                            <input type="hidden" name="decision" value="approve">
                            <label class="form-label small">Comment (optional)</label>
                            <textarea name="leader_comment" class="form-control form-control-sm mb-2" rows="2" placeholder="Optional"></textarea>
                            <button type="submit" class="btn btn-success btn-sm w-100">Approve — completed</button>
                        </form>
                        <form method="post">
                            <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
                            <input type="hidden" name="decision" value="reject">
                            <label class="form-label small text-danger">Leader comment (required)</label>
                            <textarea name="leader_comment" class="form-control form-control-sm mb-2" rows="3" required
                                placeholder="What must be redone?"></textarea>
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">Reject / redo</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_glass.php'; ?>
