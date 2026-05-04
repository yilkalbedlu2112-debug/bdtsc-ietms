<?php
/**
 * Daily Shift Summary → Production Manager & Supervisor in same department (UC-16).
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
$leader_name = (string) ($_SESSION['full_name'] ?? 'Shift Leader');
$dept_name = trim((string) ($_SESSION['dept_name'] ?? ''));

if ($dept_name === '' && $dept_id > 0) {
    $dn = $pdo->prepare('SELECT dept_name FROM departments WHERE id = ?');
    $dn->execute([$dept_id]);
    $dept_name = (string) ($dn->fetchColumn() ?: '');
}

$notifHasIsRead = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'is_read'"
)->fetchColumn() > 0;

$success_msg = null;
$error_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $summary = trim((string) ($_POST['daily_shift_summary'] ?? ''));
    if ($summary === '') {
        $error_msg = 'Please enter your daily shift summary.';
    } elseif ($dept_id <= 0) {
        $error_msg = 'Your account must belong to a department to route this report.';
    } else {
        try {
            $pdo->beginTransaction();

            $recipients_stmt = $pdo->prepare(
                "SELECT id, full_name, user_role FROM users
                 WHERE status = 'Active'
                 AND dept_id = ?
                 AND user_role IN ('Production Manager', 'Supervisor')"
            );
            $recipients_stmt->execute([$dept_id]);
            $recipients = $recipients_stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($recipients)) {
                $pdo->rollBack();
                throw new RuntimeException(
                    'No active Production Manager or Supervisor found in your department. Assign those roles to users with the same dept_id.'
                );
            }

            $base_msg = 'Daily shift summary from ' . $leader_name . ': ' . $summary;

            if ($notifHasIsRead) {
                $ins = $pdo->prepare(
                    "INSERT INTO notifications (user_id, message, type, is_read) VALUES (?, ?, 'daily_shift_summary', 0)"
                );
            } else {
                $ins = $pdo->prepare(
                    "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'daily_shift_summary')"
                );
            }

            foreach ($recipients as $r) {
                $line = '[' . ($r['user_role'] ?? '') . '] ' . $base_msg;
                $ins->execute([(int) $r['id'], $line]);
            }

            $snippet = strlen($summary) > 200 ? substr($summary, 0, 200) . '...' : $summary;
            log_action(
                $pdo,
                $user_id,
                'SHIFT_REPORT_SUBMIT',
                'Dept ' . $dept_id . ' (' . $dept_name . '): Daily shift summary to Production Manager/Supervisor. Recipients: '
                . count($recipients) . '. Excerpt: ' . $snippet
            );

            $pdo->commit();
            $success_msg = 'Daily shift summary submitted. Production Manager and Supervisor in your department have been notified.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header_glass.php';

$dept_display = $dept_name !== '' ? $dept_name : ('Department #' . ($dept_id > 0 ? (string) $dept_id : '?'));
?>

<div class="container-fluid py-4" style="max-width: 800px;">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-file-earmark-text text-primary me-2"></i>Daily shift summary</h1>
            <p class="text-muted small mb-0">Submit a concise handover for your shift.</p>
        </div>
    </div>

    <?php if ($dept_id > 0): ?>
        <div class="alert alert-info border-0 shadow-sm mb-4">
            Report will be sent to the Production Manager and Supervisor of
            <strong><?php echo htmlspecialchars($dept_display, ENT_QUOTES, 'UTF-8'); ?></strong>.
        </div>
    <?php else: ?>
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            You need a department on your profile before this report can be routed.
        </div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="post" action="">
                <div class="mb-3">
                    <label for="daily_shift_summary" class="form-label fw-semibold">Daily shift summary</label>
                    <textarea class="form-control" name="daily_shift_summary" id="daily_shift_summary" rows="8" required
                        placeholder="Production output, downtime, quality notes, safety, and follow-up items."><?php
                        echo htmlspecialchars($_POST['daily_shift_summary'] ?? '', ENT_QUOTES, 'UTF-8');
                        ?></textarea>
                </div>
                <button type="submit" name="submit_report" value="1" class="btn btn-primary">
                    <i class="bi bi-send-fill me-1"></i> Submit daily shift summary
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_glass.php'; ?>
