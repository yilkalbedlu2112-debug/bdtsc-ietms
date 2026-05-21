<?php
/**
 * Industrial Employee Task Management System (IETMS)
 * Department Manager - Feedback & Notification Center
 * Expected Graduation: July 2026
 */
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 1. የደህንነት ማረጋገጫ (Manager መሆኑን ማረጋገጥ)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Department Manager') {
    header('Location: ../auth/login.php'); 
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$user_id   = (int) $_SESSION['user_id'];
$dept_id   = isset($_SESSION['dept_id']) ? (int) $_SESSION['dept_id'] : 0;
$user_name = (string) ($_SESSION['full_name'] ?? 'Manager');

// 2. የዲፓርትመንት ስም ማውጣት (ለግሩፕ መለያ ሎጂክ)
$deptStmt = $pdo->prepare("SELECT dept_name FROM departments WHERE id = ? LIMIT 1");
$deptStmt->execute([$dept_id]);
$my_dept_name = $deptStmt->fetchColumn() ?: '';

// 3. የዲፓርትመንት ግሩፖችን መወሰን (በጥያቄህ መሠረት)
$production_group        = ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'];
$technical_quality_group = ['Engineering', 'Quality Assurance'];
$finance_resource_group  = ['Finance Department', 'Procurement / Property'];
$admin_strategy_group    = ['General Management', 'Human Resource (HR)', 'Planning', 'Strategy & Innovation', 'System Research & Development', 'Legal Service', 'Audit & Inspection'];

// ማናጀሩ ያለበትን ዋና የስራ ዘርፍ (Group Type) መለየት
$group_type = 'General';
if (in_array($my_dept_name, $production_group, true)) {
    $group_type = 'Production Group';
} elseif (in_array($my_dept_name, $technical_quality_group, true)) {
    $group_type = 'Technical & Quality Group';
} elseif (in_array($my_dept_name, $finance_resource_group, true)) {
    $group_type = 'Finance & Resource Group';
} elseif (in_array($my_dept_name, $admin_strategy_group, true)) {
    $group_type = 'Admin & Strategy Group';
}

$success_msg = null;
$error_msg = null;

// 4. ማናጀሩ ለመጣው ሪፖርት ምላሽ ሲሰጥ (POST Action)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_manager_response'])) {
    $notif_id = (int) ($_POST['notification_id'] ?? 0);
    $response_comment = trim($_POST['response_comment'] ?? '');
    $sender_id = (int) ($_POST['sender_id'] ?? 0);

    if ($notif_id <= 0 || empty($response_comment)) {
        $error_msg = 'እባክዎን ምላሽዎን በትክክል ያስገቡ።';
    } else {
        try {
            $pdo->beginTransaction();

            $manager_message = "🔔 ከዲፓርትመንት ማናጀር ({$user_name}) የተሰጠ ምላሽ:\n\"{$response_comment}\"\n(ስለ ሪፖርት ቁጥር: #{$notif_id})";

            // የ is_read አምድ መኖሩን ማረጋገጥ
            $notifHasIsRead = (int) $pdo->query(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'is_read'"
            )->fetchColumn() > 0;

            if ($notifHasIsRead) {
                $ins = $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type, is_read) VALUES (?, ?, ?, 'manager_response', 0)");
            } else {
                $ins = $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type) VALUES (?, ?, ?, 'manager_response')");
            }
            $ins->execute([$sender_id, $dept_id, $manager_message]);

            // Attempt to associate manager response with a daily report (DRID marker in original notification)
            $daily_report_id = 0;
            try {
                $orig = $pdo->prepare("SELECT message FROM notifications WHERE id = ?");
                $orig->execute([$notif_id]);
                $orig_msg = (string) $orig->fetchColumn();
                if (preg_match('/DRID:?(\d+)/', $orig_msg, $m)) {
                    $daily_report_id = (int) $m[1];
                    $fins = $pdo->prepare("INSERT INTO daily_report_feedbacks (daily_report_id, user_id, feedback, created_at) VALUES (?, ?, ?, NOW())");
                    $fins->execute([$daily_report_id, $user_id, $response_comment]);
                    if (class_exists('Database')) {
                        Database::log_action($pdo, $user_id, 'DAILY_REPORT_FEEDBACK_SAVE', "Manager saved response for daily_report #{$daily_report_id}");
                    }
                    // Audit: record that feedback was recorded and linked to a daily report
                    if (method_exists('Database', 'log_system_activity')) {
                        $details = sprintf('daily_report_id=%d; notif_id=%d; sender_id=%d; comment=%s', $daily_report_id, $notif_id, $sender_id, substr($response_comment, 0, 200));
                        Database::log_system_activity($pdo, $user_id, 'FEEDBACK_RECORDED', $details);
                    }
                }
            } catch (Throwable $e) {
                if (class_exists('Database')) {
                    Database::log_action($pdo, $user_id, 'DAILY_REPORT_FEEDBACK_SAVE_FAILED', 'Could not save manager response to daily_report_feedbacks: ' . $e->getMessage());
                }
            }

            // የመጀመሪያውን ኖቲፊኬሽን ተነቧል ማድረግ
            if ($notifHasIsRead) {
                $upd = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                $upd->execute([$notif_id, $user_id]);
            }

            // 🔒 በምዕራፍ አራት መሠረት የደህንነት ኦዲት መዝገብ ማስፈር
            if (class_exists('Database')) {
                Database::log_action($pdo, $user_id, 'MANAGER_FEEDBACK_SUBMIT', "Manager responded to notification #{$notif_id}");
            }

            // Audit: record manager feedback action just before committing transaction
            if (method_exists('Database', 'log_system_activity')) {
                // try to capture the new notification id if available
                $new_notif_id = 0;
                try { $new_notif_id = (int) $pdo->lastInsertId(); } catch (Throwable $_) { /* ignore */ }
                $details = sprintf('notif_id=%d; new_notif_id=%d; daily_report_id=%d; sender_id=%d; comment=%s', $notif_id, $new_notif_id, $daily_report_id, $sender_id, substr($response_comment, 0, 200));
                Database::log_system_activity($pdo, $user_id, 'FEEDBACK_RECORDED', $details);
            }

            $pdo->commit();
            $success_msg = 'ምላሽዎ በተሳካ ሁኔታ ተልኳል!';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $error_msg = "ስህተት አጋጥሟል፦ " . $e->getMessage();
        }
    }
}

// 5. ሎጂክ መለያ፦ ማናጀሩ ፕሮዳክሽን ግሩፕ ከሆነ ከሱፐርቫይዘር (Superior) የመጣውን ፊድባክ ያያል፣ ካልሆነ ከሰራተኛ (Employee) የመጣውን ያያል
if ($group_type === 'Production Group') {
    // ፕሮዳክሽን ማናጀሮች ከሱፐርቫይዘር (Superior) የተላከላቸውን ሪፖርቶች ያያሉ
    $query = "SELECT n.id AS notif_id, n.message, n.created_at, u.id AS sender_id, u.full_name AS sender_name, u.user_role AS sender_role
              FROM notifications n
              JOIN users u ON n.dept_id = u.dept_id
              WHERE n.user_id = ? AND n.type IN ('Supervisor Report', 'supervisor_report', 'Generate Report')
              ORDER BY n.id DESC";
} else {
    // ሌሎች ማናጀሮች (Technical, Finance, Admin) ከሰራተኞች (Employee) የመጡ ጥያቄዎችን/ሪፖርቶችን ያያሉ
    $query = "SELECT n.id AS notif_id, n.message, n.created_at, u.id AS sender_id, u.full_name AS sender_name, u.user_role AS sender_role
              FROM notifications n
              JOIN users u ON n.dept_id = u.dept_id
              WHERE n.user_id = ? AND u.user_role = 'Employee'
              ORDER BY n.id DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$incoming_feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header_glass.php';
?>

<div class="container-fluid py-4" style="max-width: 1100px;">
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white shadow-sm rounded-3">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1"><i class="bi bi-shield-check text-success me-2"></i>Manager Feedback Hub</h1>
            <p class="text-muted small mb-0">
                ክፍል፦ <strong><?php echo htmlspecialchars($my_dept_name); ?></strong> | 
                የምድብ ዘርፍ፦ <span class="badge bg-info text-dark"><?php echo $group_type; ?></span>
            </p>
        </div>
        <span class="text-muted small">ማናጀር፦ <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger border-0 shadow-sm"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm bg-white">
        <div class="card-header bg-light py-3">
            <h5 class="card-title h6 fw-bold mb-0 text-secondary">
                <i class="bi bi-chat-square-quote-fill me-2 text-primary"></i>
                <?php echo ($group_type === 'Production Group') ? 'ከሱፐርቫይዘሮች የደረሱ የክፍል ማጠቃለያ ሪፖርቶች' : 'ከሰራተኞች (Employees) የደረሱ ቀጥታ ማሳወቂያዎች እና አስተያየቶች'; ?>
            </h5>
        </div>
        
        <div class="card-body p-4">
            <?php if (empty($incoming_feedbacks)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-chat-left-x text-muted display-4 d-block mb-3"></i>
                    <p class="text-muted">እስካሁን ለዚህ ክፍል የቀረበ ምንም አይነት ግብረ-መልስ (Feedback) አልተገኘም።</p>
                </div>
            <?php else: ?>
                <?php foreach ($incoming_feedbacks as $fb): ?>
                    <div class="p-3 mb-4 rounded border bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span class="badge bg-secondary me-2">ID: #<?php echo $fb['notif_id']; ?></span>
                                <strong class="text-dark"><?php echo htmlspecialchars($fb['sender_name']); ?></strong> 
                                <span class="text-muted small">(<?php echo htmlspecialchars($fb['sender_role']); ?>)</span>
                            </div>
                            <small class="text-muted"><i class="bi bi-clock me-1"></i><?php echo date('M d, Y H:i', strtotime($fb['created_at'])); ?></small>
                        </div>
                        
                        <div class="p-3 bg-white border-start border-primary border-4 rounded text-dark font-monospace mb-3" style="white-space: pre-wrap; font-size: 0.95rem;">
                            <?php echo htmlspecialchars($fb['message']); ?>
                        </div>

                        <form method="post" action="" class="mt-2">
                            <input type="hidden" name="notification_id" value="<?php echo $fb['notif_id']; ?>">
                            <input type="hidden" name="sender_id" value="<?php echo $fb['sender_id']; ?>">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-reply text-success"></i></span>
                                <input type="text" name="response_comment" class="form-control" placeholder="ለላኪው ምላሽ ወይም መመሪያ እዚህ ይጻፉ..." required>
                                <button type="submit" name="submit_manager_response" class="btn btn-success">ምላሽ ላክ</button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_glass.php'; ?>