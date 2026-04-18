<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// 1. Authorization: Shift Leader ብቻ እንዲገባ
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Shift Leader') {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'];
$user_id = $_SESSION['user_id'];
$message = "";

// 2. የዕለቱን ስራዎች አጠቃላይ መረጃ ከዳታቤዝ ማግኘት
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'Blocked' THEN 1 ELSE 0 END) as blocked,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as ongoing
    FROM maintenance_requests 
    WHERE dept_id = ? AND DATE(created_at) = CURDATE()
");
$stats_stmt->execute([$dept_id]);
$stats = $stats_stmt->fetch();

// 3. ሪፖርቱን መመዝገብና ለሁለቱም አካላት ማሳወቅ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $report_content = $_POST['report_content'];
    $shift_type = $_POST['shift_type']; 
    
    try {
        $pdo->beginTransaction();

        // ሪፖርቱን በ daily_reports ሰንጠረዥ ውስጥ ማስቀመጥ
        $stmt = $pdo->prepare("INSERT INTO daily_reports (dept_id, created_by, shift_type, report_summary, total_tasks, completed_tasks, blocked_tasks, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $dept_id, 
            $user_id, 
            $shift_type, 
            $report_content, 
            $stats['total'], 
            $stats['completed'], 
            $stats['blocked']
        ]);

        // ሀ. ለማናጀሩ ኖቲፊኬሽን መላክ
        $notif_manager = $pdo->prepare("INSERT INTO notifications (dept_id, user_role, message, type) VALUES (?, 'Department Manager', ?, 'Daily Report')");
        $notif_manager->execute([$dept_id, "አዲስ የሽፍት ሪፖርት ቀርቧል። (Shift: $shift_type)"]);

        // ለ. ለሱፐርቫይዘሩ ኖቲፊኬሽን መላክ (Supervisor)
        $notif_supervisor = $pdo->prepare("INSERT INTO notifications (dept_id, user_role, message, type) VALUES (?, 'Supervisor', ?, 'Daily Report')");
        $notif_supervisor->execute([$dept_id, "የሽፍት ሊደሩ የዕለቱን ሪፖርት አቅርቧል። እባክዎ ያረጋግጡ።"]);

        $pdo->commit();
        $message = "<div class='alert alert-success fw-bold rounded-pill text-center shadow-sm'><i class='bi bi-send-check me-2'></i> ሪፖርቱ ለማናጀሩ እና ለሱፐርቫይዘሩ በተሳካ ሁኔታ ተልኳል!</div>";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "<div class='alert alert-danger'>ስህተት፦ " . $e->getMessage() . "</div>";
    }
}

include '../includes/header_glass.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <?= $message ?>
            <div class="card glass-card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white p-4">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text-fill me-2"></i> የሽፍት ሪፖርት ማቅረቢያ (Submit Report)</h4>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <div class="row g-3 mb-4">
                        <div class="col-4">
                            <div class="p-3 bg-light rounded-4 text-center border-bottom border-primary border-3">
                                <h3 class="fw-bold text-primary mb-0"><?= $stats['total'] ?></h3>
                                <small class="text-muted small fw-bold">ጠቅላላ ስራ</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 bg-light rounded-4 text-center border-bottom border-success border-3">
                                <h3 class="fw-bold text-success mb-0"><?= $stats['completed'] ?></h3>
                                <small class="text-muted small fw-bold">የተጠናቀቁ</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 bg-light rounded-4 text-center border-bottom border-danger border-3">
                                <h3 class="fw-bold text-danger mb-0"><?= $stats['blocked'] ?></h3>
                                <small class="text-muted small fw-bold">የተቆሙ</small>
                            </div>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="bi bi-clock me-2 text-primary"></i>የሽፍት አይነት (Shift)</label>
                            <select name="shift_type" class="form-select border-0 bg-light py-3 rounded-3 shadow-sm" required>
                                <option value="Day">Day Shift (Morning)</option>
                                <option value="Afternoon">Afternoon Shift</option>
                                <option value="Night">Night Shift</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>ዝርዝር ማጠቃለያ (Report Summary)</label>
                            <textarea name="report_content" class="form-control border-0 bg-light p-3 rounded-3 shadow-sm" rows="6" placeholder="ስለ ማሽኖች ሁኔታ፣ የሰራተኛ አፈጻጸም እና የገጠሙ ችግሮችን እዚህ ይግለጹ..." required></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="submit_report" class="btn btn-primary btn-lg py-3 fw-bold rounded-pill shadow">
                                <i class="bi bi-cloud-upload-fill me-2"></i> ሪፖርት ላክ (Submit Report)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer_glass.php'; ?>