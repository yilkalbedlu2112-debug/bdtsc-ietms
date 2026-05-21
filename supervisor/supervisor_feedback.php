<?php
/**
 * Industrial Employee Task Management System (IETMS)
 * Supervisor Feedback & Shift Report Dashboard View
 * Expected Graduation: July 2026
 */
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 1. የደህንነት ማረጋገጫ (Authorization) - Supervisor መሆኑን ማረጋገጥ
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Supervisor') {
    header('Location: ../auth/login.php'); 
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$user_id   = (int) $_SESSION['user_id'];
$dept_id   = isset($_SESSION['dept_id']) ? (int) $_SESSION['dept_id'] : 0;
$user_name = (string) ($_SESSION['full_name'] ?? 'Supervisor');

$success_msg = null;
$error_msg = null;

// 2. ሱፐርቫይዘሩ ለሽፍት ሊደሩ የሚሰጠውን ግብረ-መልስ (Feedback) ማሰናዳት (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $notif_id = (int) ($_POST['notification_id'] ?? 0);
    $feedback_text = trim($_POST['feedback_comment'] ?? '');
    $shift_leader_id = (int) ($_POST['shift_leader_id'] ?? 0);

    if ($notif_id <= 0 || empty($feedback_text)) {
        $error_msg = 'እባክዎን አስተያየትዎን በትክክል ያስገቡ።';
    } else {
        try {
            $pdo->beginTransaction();

            // አስተያየቱን ለሽፍት ሊደሩ እንደ አዲስ ማሳወቂያ (Notification) መላክ
            $feedback_message = "🔔 ከሱፐርቫይዘር {$user_name} የተሰጠ ግብረ-መልስ (Feedback):\n\"{$feedback_text}\"\n(ስለ ሪፖርት መለያ ቁጥር: #{$notif_id})";
            
            // የ notifications ሰንጠረዥ 'is_read' እንዳለው ዳይናሚክ ማረጋገጥ
            $notifHasIsRead = (int) $pdo->query(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'is_read'"
            )->fetchColumn() > 0;

            if ($notifHasIsRead) {
                $ins = $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type, is_read) VALUES (?,?,?,'supervisor_feedback',0)");
            } else {
                $ins = $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type) VALUES (?,?,?,'supervisor_feedback')");
            }
            $ins->execute([$shift_leader_id, $dept_id, $feedback_message]);

            // Try to associate this supervisor feedback with the saved daily_report (if DRID marker exists)
            try {
                $orig = $pdo->prepare("SELECT message FROM notifications WHERE id = ?");
                $orig->execute([$notif_id]);
                $orig_msg = (string) $orig->fetchColumn();
                if (preg_match('/DRID:?(\d+)/', $orig_msg, $m)) {
                    $daily_report_id = (int) $m[1];
                    // Insert into daily_report_feedbacks if that table exists (non-fatal)
                    $fins = $pdo->prepare("INSERT INTO daily_report_feedbacks (daily_report_id, user_id, feedback, created_at) VALUES (?, ?, ?, NOW())");
                    $fins->execute([$daily_report_id, $user_id, $feedback_text]);
                    Database::log_action($pdo, $user_id, 'DAILY_REPORT_FEEDBACK_SAVE', "Saved feedback for daily_report #{$daily_report_id}");
                }
            } catch (Throwable $e) {
                Database::log_action($pdo, $user_id, 'DAILY_REPORT_FEEDBACK_SAVE_FAILED', 'Could not save feedback to daily_report_feedbacks: ' . $e->getMessage());
            }

            // የመጀመሪያውን የሽፍት ሪፖርት ማሳወቂያ እንደተነበበ (Read) አድርጎ ማርክ ማድረግ (ኮለሙ ካለ)
            if ($notifHasIsRead) {
                $upd = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                $upd->execute([$notif_id, $user_id]);
            }

            // 🔒 በምዕራፍ አራት መሠረት የደህንነት ኦዲት መዝገብ (Audit Log) ማስፈር
            Database::log_action($pdo, $user_id, 'SUPERVISOR_FEEDBACK_SUBMIT', "Feedback submitted for report #{$notif_id} sent to user #{$shift_leader_id}");

            $pdo->commit();
            $success_msg = 'ግብረ-መልስዎ (Feedback) በተሳካ ሁኔታ ለሽፍት ሊደሩ ተልኳል!';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = "ስህተት አጋጥሟል፦ " . $e->getMessage();
        }
    }
}

// 3. ከሽፍት ሊደሩ የተላኩ የዕለታዊ ማጠቃለያ ሪፖርቶችን (Daily Shift Summaries) ከዳታቤዝ መሳብ
// ማሳሰቢያ፦ የሪፖርቱ ላኪ (Shift Leader) ማን እንደሆነ ለማወቅና መልሰን ፊድባክ ለመስጠት JOIN አድርገናል።
$query = "SELECT n.id AS notif_id, n.message, n.created_at, u.id AS leader_id, u.full_name AS leader_name
          FROM notifications n
          JOIN users u ON n.dept_id = u.dept_id
          WHERE n.user_id = ? AND n.type = 'daily_shift_summary'
          ORDER BY n.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header_glass.php';
?>

<div class="container-fluid py-4" style="max-width: 1000px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><i class="bi bi-speedometer2 me-2 text-primary"></i>Supervisor Dashboard</h1>
            <p class="text-muted small mb-0">እንኳን በደህና መጡ፣ <strong><?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?></strong> | Department ID: #<?php echo $dept_id; ?></p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-house-door me-1"></i>ዋና ዳሽቦርድ</a>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <h2 class="h5 fw-bold mb-3 text-secondary"><i class="bi bi-envelope-paper text-warning me-2"></i>ከሽፍት ሊደሮች የደረሱ የዕለታዊ ሪፖርቶች ዝርዝር</h2>

    <?php if (empty($reports)): ?>
        <div class="card border-0 shadow-sm p-5 text-center bg-white">
            <i class="bi bi-folder-x text-muted display-4 mb-3"></i>
            <p class="text-muted mb-0">እስካሁን ከሽፍት ሊደሮች የተላከ ምንም አይነት የዕለታዊ ማጠቃለያ ሪፖርት አልተገኘም።</p>
        </div>
    <?php else: ?>
        <?php foreach ($reports as $rep): ?>
            <div class="card border-0 shadow-sm mb-4 bg-white overflow-hidden">
                <div class="card-header bg-light border-0 py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary me-2">ሪፖርት መለያ #<?php echo $rep['notif_id']; ?></span>
                        <strong class="text-dark"><i class="bi bi-person me-1"></i>ላኪ፦ <?php echo htmlspecialchars($rep['leader_name'], ENT_QUOTES, 'UTF-8'); ?> (Shift Leader)</strong>
                    </div>
                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($rep['created_at'])); ?></small>
                </div>
                
                <div class="card-body bg-white border-bottom">
                    <div class="p-3 bg-light rounded text-dark font-monospace" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6; border-left: 4px solid #0d6efd;">
                        <?php echo htmlspecialchars($rep['message'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>

                <div class="card-footer bg-white border-0 py-3">
                    <form method="post" action="">
                        <input type="hidden" name="notification_id" value="<?php echo $rep['notif_id']; ?>">
                        <input type="hidden" name="shift_leader_id" value="<?php echo $rep['leader_id']; ?>">
                        
                        <div class="row g-2 align-items-end">
                            <div class="col-md-9">
                                <label class="form-label small fw-bold text-secondary"><i class="bi bi-chat-left-dots me-1"></i>ለሽፍት ሊደሩ ግብረ-መልስ/አስተያየት ይጻፉ (Supervisor Feedback)</label>
                                <input type="text" name="feedback_comment" class="form-control form-control-sm" placeholder="ለምሳሌ፦ ሪፖርቱ ተገምግሟል፣ የጥራት ጉዳዩን ፈተሸነዋል፣ ወዘተ..." required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" name="submit_feedback" class="btn btn-success btn-sm w-100 shadow-sm">
                                    <i class="bi bi-reply-fill me-1"></i>ግብረ-መልስ ላክ (Feedback)
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_glass.php'; ?>