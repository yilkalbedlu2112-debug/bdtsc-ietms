<?php 
require_once '../includes/db.php';
include '../includes/manager_header.php';

// 1. ሴሽኑ መኖሩን ማረጋገጥ
$dept_id = isset($_SESSION['dept_id']) ? $_SESSION['dept_id'] : null;
$dept_name = "ያልታወቀ ክፍል";

// 2. የዲፓርትመንቱን ስም እና የብልሽት መጠኖችን ከዳታቤዝ ማምጣት
if ($dept_id) {
    // የዲፓርትመንት ስም
    $stmt = $pdo->prepare("SELECT dept_name FROM departments WHERE id = ?");
    $stmt->execute([$dept_id]);
    $my_dept = $stmt->fetch();
    if ($my_dept) { $dept_name = $my_dept['dept_name']; }

    // አዳዲስ (Pending) ጥያቄዎችን ለመቁጠር
    $stmt_pending = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND status = 'Pending'");
    $stmt_pending->execute([$dept_id]);
    $pending_count = $stmt_pending->fetchColumn();

    // በጥገና ላይ ያሉ (In Progress) ለመቁጠር
    $stmt_progress = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND status = 'In Progress'");
    $stmt_progress->execute([$dept_id]);
    $progress_count = $stmt_progress->fetchColumn();

    // የተጠናቀቁ (Completed) ለመቁጠር
    $stmt_completed = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND status = 'Completed'");
    $stmt_completed->execute([$dept_id]);
    $completed_count = $stmt_completed->fetchColumn();
} else {
    $pending_count = $progress_count = $completed_count = 0;
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm p-4 border-0" style="border-left: 5px solid #28687F !important;">
            <h2 class="text-primary">የ<?php echo htmlspecialchars($dept_name); ?> ማናጀር የሥራ ገጽ</h2>
            <p class="text-muted mb-0">እንኳን ደህና መጡ! በክፍልዎ ውስጥ ያሉ የጥገና ሁኔታዎች ዝርዝር ከዚህ በታች ቀርቧል።</p>
        </div>
    </div>
</div>

<div class="row mt-4 text-center">
    <div class="col-md-4 mb-3">
        <div class="card bg-white shadow-sm p-3 border-0">
            <h3 class="text-warning fw-bold"><?php echo $pending_count; ?></h3>
            <p class="text-secondary mb-0">አዳዲስ ብልሽቶች</p>
            <small class="text-muted">New Tasks</small>
            <div class="mt-2">
                <a href="view_requests.php" class="btn btn-sm btn-outline-warning">ዝርዝር እይ</a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card bg-white shadow-sm p-3 border-0">
            <h3 class="text-info fw-bold"><?php echo $progress_count; ?></h3>
            <p class="text-secondary mb-0">በጥገና ላይ ያሉ</p>
            <small class="text-muted">In Progress</small>
            <div class="mt-2">
                <a href="view_requests.php" class="btn btn-sm btn-outline-info">ዝርዝር እይ</a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card bg-white shadow-sm p-3 border-0">
            <h3 class="text-success fw-bold"><?php echo $completed_count; ?></h3>
            <p class="text-secondary mb-0">የተጠናቀቁ</p>
            <small class="text-muted">Completed</small>
            <div class="mt-2">
                <a href="view_requests.php" class="btn btn-sm btn-outline-success">ዝርዝር እይ</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>