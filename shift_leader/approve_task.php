<?php
/**
 * Review Tasks — Shift Leader reviews employee-completed work.
 *
 * Three actions:
 *  1. APPROVE  → is_verified = 1  (task stays Completed)
 *  2. REDO     → status = 'Assigned', feedback sent back to employee
 *  3. ESCALATE → urgent notification to Supervisor (machine breakdown / obstacle)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Shift Leader') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';
/** @var PDO $pdo */
$user_id     = (int) $_SESSION['user_id'];
$dept_id     = isset($_SESSION['dept_id']) ? (int) $_SESSION['dept_id'] : 0;
$leader_name = (string) ($_SESSION['full_name'] ?? 'Shift Leader');
$dept_name   = trim((string) ($_SESSION['dept_name'] ?? ''));

if ($dept_name === '' && $dept_id > 0) {
    $dn = $pdo->prepare('SELECT dept_name FROM departments WHERE id = ?');
    $dn->execute([$dept_id]);
    $dept_name = (string) ($dn->fetchColumn() ?: '');
}

$notifHasIsRead = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'is_read'"
)->fetchColumn() > 0;

// ── Helper: insert notification ─────────────────────────────────────
function review_notify(PDO $pdo, int $uid, int $dept_id, string $msg, string $type, bool $hasIsRead): void
{
    if ($hasIsRead) {
        $s = $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type, is_read) VALUES (?, ?, ?, ?, 0)");
    } else {
        $s = $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type) VALUES (?, ?, ?, ?)");
    }
    $s->execute([$uid, $dept_id, $msg, $type]);
}

$success_msg = null;
$error_msg   = null;

// ── Handle POST actions ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dept_id > 0) {
    $task_id  = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
    $action   = trim((string) ($_POST['action'] ?? ''));
    $comment  = trim((string) ($_POST['comment'] ?? ''));

    if (!$task_id || !in_array($action, ['approve', 'redo', 'escalate'], true)) {
        $error_msg = 'Invalid request.';
    } elseif ($action === 'redo' && $comment === '') {
        $error_msg = 'Please provide a comment explaining what needs to be corrected.';
    } elseif ($action === 'escalate' && $comment === '') {
        $error_msg = 'Please describe the issue/breakdown before escalating.';
    } else {
        try {
            $pdo->beginTransaction();

            // Verify task belongs to this department
            $chk = $pdo->prepare(
                "SELECT mr.id, mr.status, mr.is_verified, mr.assigned_to,
                        COALESCE(mr.title, mr.machine_name, 'Untitled') AS task_title,
                        mr.completion_notes,
                        u.full_name AS employee_name
                 FROM maintenance_requests mr
                 LEFT JOIN users u ON mr.assigned_to = u.id
                 WHERE mr.id = ?
                   AND (mr.dept_id = ? OR mr.sender_dept_id = ? OR mr.receiver_dept_id = ?)
                 FOR UPDATE"
            );
            $chk->execute([$task_id, $dept_id, $dept_id, $dept_id]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $pdo->rollBack();
                throw new RuntimeException('Task not found in your department.');
            }

            $employee_id   = (int) ($row['assigned_to'] ?? 0);
            $employee_name = $row['employee_name'] ?? 'Employee';
            $task_title    = $row['task_title'];

            // ── ACTION: APPROVE (Perfect Quality) ────────────────────────────
            if ($action === 'approve') {
                // Mark task as verified and completed with perfect quality
                $upd = $pdo->prepare(
                    "UPDATE maintenance_requests
                     SET status = 'Completed', is_verified = 1, feedback = ?, updated_at = NOW()
                     WHERE id = ?"
                );
                $approval_comment = $comment !== '' ? $comment : '✅ Task completed perfectly and approved by Shift Leader.';
                $upd->execute([$approval_comment, $task_id]);

                // Notify employee of perfect completion
                if ($employee_id > 0) {
                    review_notify(
                        $pdo, $employee_id, $dept_id,
                        "🎉 EXCELLENT WORK! Your task \"{$task_title}\" has been approved with perfect quality by Shift Leader {$leader_name}.",
                        'task_approved_perfect', $notifHasIsRead
                    );
                }

                log_action($pdo, $user_id, 'TASK_APPROVED_PERFECT',
                    "Task #{$task_id} ({$task_title}) approved with perfect quality. Employee: {$employee_name}."
                );
                $success_msg = "🎉 Task #{$task_id} approved! Excellent work by {$employee_name}.";

            // ── ACTION: REDO (Quality Issues) ────────────────────────────────
            } elseif ($action === 'redo') {
                // Reset task to Assigned status for quality improvement
                $upd = $pdo->prepare(
                    "UPDATE maintenance_requests
                     SET status = 'Assigned', is_verified = 0, feedback = ?, updated_at = NOW()
                     WHERE id = ?"
                );
                $redo_comment = "❌ QUALITY ISSUE - Please redo this task with better quality: {$comment}";
                $upd->execute([$redo_comment, $task_id]);

                // Notify employee about quality issues
                if ($employee_id > 0) {
                    review_notify(
                        $pdo, $employee_id, $dept_id,
                        "⚠️ QUALITY IMPROVEMENT NEEDED: Your task \"{$task_title}\" was returned by Shift Leader {$leader_name}. Please redo with better quality. Details: {$comment}",
                        'task_quality_improvement', $notifHasIsRead
                    );
                }

                log_action($pdo, $user_id, 'TASK_RETURNED_QUALITY',
                    "Task #{$task_id} ({$task_title}) returned to {$employee_name} for quality improvement. Reason: {$comment}"
                );
                $success_msg = "⚠️ Task #{$task_id} returned to {$employee_name} for quality improvement.";

            // ── ACTION: ESCALATE ────────────────────────────────────
            } elseif ($action === 'escalate') {
                // Find Supervisor(s) in same department
                $sup_stmt = $pdo->prepare(
                    "SELECT id, full_name FROM users
                     WHERE dept_id = ? AND status = 'Active' AND user_role = 'Supervisor'"
                );
                $sup_stmt->execute([$dept_id]);
                $supervisors = $sup_stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($supervisors)) {
                    $pdo->rollBack();
                    throw new RuntimeException('No active Supervisor found in your department to escalate to.');
                }

                // Send urgent notification to each Supervisor
                $urgentMsg = "🚨 URGENT ESCALATION from Shift Leader {$leader_name} ({$dept_name}):\n"
                           . "Task: \"{$task_title}\" (#{$task_id})\n"
                           . "Issue: {$comment}";

                $sup_names = [];
                foreach ($supervisors as $sup) {
                    review_notify($pdo, (int) $sup['id'], $dept_id, $urgentMsg, 'escalation_urgent', $notifHasIsRead);
                    $sup_names[] = $sup['full_name'];
                }

                // Also update task feedback
                $upd = $pdo->prepare(
                    "UPDATE maintenance_requests SET feedback = ?, updated_at = NOW() WHERE id = ?"
                );
                $upd->execute(["ESCALATED: {$comment}", $task_id]);

                log_action($pdo, $user_id, 'TASK_ESCALATED',
                    "Task #{$task_id} ({$task_title}) escalated to Supervisor(s): " . implode(', ', $sup_names) . ". Reason: {$comment}"
                );
                $success_msg = "Task #{$task_id} escalated urgently to: " . implode(', ', $sup_names) . '.';
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = $e->getMessage();
        }
    }
}

// ── Fetch tasks to review ───────────────────────────────────────────
// Tasks completed by employees (is_verified = 0) + tasks still In Progress
$review_tasks = [];
$in_progress_tasks = [];
if ($dept_id > 0) {
    // Completed but not verified → needs SL review
    $r1 = $pdo->prepare(
        "SELECT mr.id,
                COALESCE(mr.title, mr.machine_name, 'Untitled') AS task_title,
                mr.issue_description, mr.completion_notes, mr.priority,
                mr.status, mr.is_verified, mr.task_type, mr.created_at, mr.updated_at,
                u.full_name AS employee_name
         FROM maintenance_requests mr
         LEFT JOIN users u ON mr.assigned_to = u.id
         WHERE mr.status = 'Completed' AND mr.is_verified = 0
           AND (mr.dept_id = ? OR mr.sender_dept_id = ? OR mr.receiver_dept_id = ?)
         ORDER BY mr.updated_at DESC"
    );
    $r1->execute([$dept_id, $dept_id, $dept_id]);
    $review_tasks = $r1->fetchAll(PDO::FETCH_ASSOC);

    // In Progress → SL can monitor or escalate
    $r2 = $pdo->prepare(
        "SELECT mr.id,
                COALESCE(mr.title, mr.machine_name, 'Untitled') AS task_title,
                mr.issue_description, mr.priority, mr.task_type,
                mr.status, mr.created_at, mr.updated_at,
                u.full_name AS employee_name
         FROM maintenance_requests mr
         LEFT JOIN users u ON mr.assigned_to = u.id
         WHERE mr.status = 'In Progress'
           AND (mr.dept_id = ? OR mr.sender_dept_id = ? OR mr.receiver_dept_id = ?)
         ORDER BY mr.updated_at DESC"
    );
    $r2->execute([$dept_id, $dept_id, $dept_id]);
    $in_progress_tasks = $r2->fetchAll(PDO::FETCH_ASSOC);
}

include __DIR__ . '/../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h1 class="h4 fw-bold mb-0"><i class="bi bi-check2-square text-primary me-2"></i>Review Tasks</h1>
                        <p class="text-muted small mb-0">Review completed work, return for corrections, or escalate issues.</p>
                    </div>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($dept_id <= 0): ?>
        <div class="alert alert-warning border-0 shadow-sm">
            <i class="bi bi-exclamation-triangle me-1"></i>Your account has no department. Reviews cannot be scoped.
        </div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle me-1"></i><?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-exclamation-circle me-1"></i><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- SECTION 1: Completed tasks awaiting review (is_verified = 0) -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-success text-white fw-semibold py-3">
            <i class="bi bi-clipboard-check me-2"></i>Completed — Awaiting Your Review
            <?php if (count($review_tasks) > 0): ?>
                <span class="badge bg-light text-success ms-2"><?php echo count($review_tasks); ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($review_tasks)): ?>
                <p class="text-muted mb-0"><i class="bi bi-check-circle me-1"></i>No tasks awaiting your review.</p>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($review_tasks as $task): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card border shadow-sm h-100">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                            <strong class="text-truncate me-2">#<?php echo (int) $task['id']; ?> — <?php echo htmlspecialchars($task['task_title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php
                            $pColor = match($task['priority'] ?? '') {
                                'Emergency','Urgent' => 'danger',
                                'High' => 'warning',
                                default => 'info',
                            };
                            ?>
                            <span class="badge bg-<?php echo $pColor; ?>"><?php echo htmlspecialchars($task['priority'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="card-body">
                            <p class="small mb-2">
                                <i class="bi bi-person-fill text-primary me-1"></i>
                                <strong>Employee:</strong> <?php echo htmlspecialchars($task['employee_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                            <?php if (!empty($task['completion_notes'])): ?>
                            <div class="border rounded p-2 bg-light mb-3 small">
                                <strong class="text-success"><i class="bi bi-chat-left-text me-1"></i>Employee's Notes:</strong><br>
                                <?php echo nl2br(htmlspecialchars($task['completion_notes'], ENT_QUOTES, 'UTF-8')); ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($task['issue_description'])): ?>
                            <div class="small text-muted mb-3">
                                <strong>Original task:</strong>
                                <?php echo htmlspecialchars(mb_substr($task['issue_description'], 0, 120), ENT_QUOTES, 'UTF-8'); ?>…
                            </div>
                            <?php endif; ?>

                            <!-- ── Communication Actions ─────────────────────── -->
                            <div class="d-grid gap-2 mb-2">
                                <a href="task_feedback.php?task_id=<?php echo (int) $task['id']; ?>" class="btn btn-info btn-sm">
                                    <i class="bi bi-chat-dots me-1"></i>Ask Question / Send Feedback
                                </a>
                            </div>

                            <!-- ── Approve Perfect Quality ──────────────────────── -->
                            <form method="post" class="mb-2">
                                <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <textarea name="comment" class="form-control form-control-sm mb-2" rows="1"
                                    placeholder="Praise for excellent work (optional)"></textarea>
                                <button type="submit" class="btn btn-success btn-sm w-100">
                                    <i class="bi bi-trophy me-1"></i>✅ Approve - Perfect Quality!
                                </button>
                            </form>

                            <!-- ── Discard & Return for Quality Improvement ──────── -->
                            <form method="post" class="mb-2">
                                <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
                                <input type="hidden" name="action" value="redo">
                                <textarea name="comment" class="form-control form-control-sm mb-2" rows="2" required
                                    placeholder="Describe quality issues that need improvement (required)"></textarea>
                                <button type="submit" class="btn btn-danger btn-sm w-100">
                                    <i class="bi bi-x-circle me-1"></i>❌ Discard - Redo for Quality
                                </button>
                            </form>

                            <!-- ── Escalate ─────────────────────────────── -->
                            <form method="post">
                                <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
                                <input type="hidden" name="action" value="escalate">
                                <textarea name="comment" class="form-control form-control-sm mb-2" rows="2" required
                                    placeholder="Describe the machine breakdown or obstacle (required)"></textarea>
                                <button type="submit" class="btn btn-danger btn-sm w-100">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Escalate to Supervisor
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- SECTION 2: In Progress tasks (monitor + escalate if needed)  -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-warning bg-opacity-75 fw-semibold py-3">
            <i class="bi bi-arrow-repeat me-2"></i>In Progress — Monitoring
            <?php if (count($in_progress_tasks) > 0): ?>
                <span class="badge bg-dark ms-2"><?php echo count($in_progress_tasks); ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($in_progress_tasks)): ?>
                <p class="text-muted mb-0">No tasks currently in progress.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Employee</th>
                            <th>Priority</th>
                            <th>Type</th>
                            <th style="min-width: 280px;">Escalate if needed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($in_progress_tasks as $task): ?>
                        <tr>
                            <td>#<?php echo (int) $task['id']; ?></td>
                            <td class="fw-semibold"><?php echo htmlspecialchars($task['task_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <i class="bi bi-person-fill text-primary me-1"></i>
                                <?php echo htmlspecialchars($task['employee_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td>
                                <?php
                                $pColor = match($task['priority'] ?? '') {
                                    'Emergency','Urgent' => 'danger',
                                    'High' => 'warning',
                                    default => 'info',
                                };
                                ?>
                                <span class="badge bg-<?php echo $pColor; ?>"><?php echo htmlspecialchars($task['priority'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($task['task_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td>
                                <div class="d-flex gap-2 align-items-center">
                                    <a href="task_feedback.php?task_id=<?php echo (int) $task['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="bi bi-chat-dots"></i>
                                    </a>
                                    <form method="post" class="d-flex gap-2 align-items-end">
                                        <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
                                        <input type="hidden" name="action" value="escalate">
                                        <input type="text" name="comment" class="form-control form-control-sm" required
                                            placeholder="Machine breakdown / obstacle...">
                                        <button type="submit" class="btn btn-danger btn-sm text-nowrap">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Escalate
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Empty state -->
    <?php if (empty($review_tasks) && empty($in_progress_tasks)): ?>
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body text-center py-5">
            <i class="bi bi-clipboard-check fs-1 text-muted mb-3 d-block"></i>
            <h5 class="text-muted">All clear</h5>
            <p class="text-muted small">No tasks to review or monitor at this time.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_glass.php'; ?>
