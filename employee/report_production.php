<?php
session_start();
require_once '../includes/db.php';

/** @var PDO $pdo */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$dept_id   = $_SESSION['dept_id'];
$dept_name = $_SESSION['dept_name']; // ይህ ከዳታቤዝ የመጣ የዲፓርትመንት ስም ነው

// --- 1. ዳይናሚክ ሎጂክ (Flexible Interface Logic) ---
$production_group        = ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'];
$technical_quality_group = ['Engineering', 'Quality Assurance'];
$finance_resource_group  = ['Finance Department', 'Procurement / Property'];
$admin_strategy_group    = ['General Management', 'Human Resource (HR)', 'Planning', 'Strategy & Innovation', 'System Research & Development', 'Legal Service', 'Audit & Inspection'];

// ተጠቃሚው ያለበትን ግሩፕ መለየት
if (in_array($dept_name, $production_group, true)) {
    $report_target_role = 'Shift Leader';
    $target_label = "Shift Leader (የሽፍት መሪ)";
} else {
    // ከፕሮዳክሽን ውጭ ለሆኑት ወደ ዲፓርትመንት ማናጀር ወይም ሱፐርቫይዘር ይላካል
    $report_target_role = 'Department Manager'; 
    $target_label = "Department Manager (የክፍል ኃላፊ)";
}

// --- 2. ሪፖርት የሚቀበሉ ሰዎችን (Target Users) መሳብ ---
// እንደየ ዲፓርትመንቱ ሁኔታ ሽፍት ሊደር ወይም ማናጀርን ይፈልጋል
$target_query = "SELECT id, full_name, user_role FROM users 
                 WHERE dept_id = ? AND user_role = ? AND status = 'Active'";
$target_stmt = $pdo->prepare($target_query);
$target_stmt->execute([$dept_id, $report_target_role]);
$target_users = $target_stmt->fetchAll();

$message = "";

// --- 3. ፎርሙ ሲላክ (Form Submission Logic) ---
if (isset($_POST['submit_report'])) {
    $machine_name = trim($_POST['machine_name'] ?? 'General Task');
    $quantity     = $_POST['quantity_produced'] ?? 0;
    $unit         = $_POST['unit'] ?? 'Task';
    $shift        = $_POST['shift'] ?? 'Office Hours';
    $target_id    = $_POST['target_user_id']; 
    $remarks      = trim($_POST['remarks'] ?? '');

    if (empty($target_id)) {
        $message = "<div class='alert alert-danger'>እባክዎ ሪፖርቱን የሚቀበለውን ኃላፊ ይምረጡ።</div>";
    } else {
        try {
            $pdo->beginTransaction();

            // ሪፖርቱን መመዝገብ
            $sql = "INSERT INTO production_reports (user_id, dept_id, machine_name, quantity_produced, unit, shift, reported_to, remarks, report_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $dept_id, $machine_name, $quantity, $unit, $shift, $target_id, $remarks]);

            // ለተመረጠው ኃላፊ ኖቲፊኬሽን መላክ
            $notif_msg = "አዲስ የሥራ ሪፖርት ከ " . $_SESSION['full_name'] . " ቀርቧል።";
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'report_submission', NOW())");
            $notif->execute([$target_id, $notif_msg]);

            $pdo->commit();
            $message = "<div class='alert alert-success border-0 shadow-sm'>ሪፖርቱ ለ" . $target_label . " በተሳካ ሁኔታ ተልኳል!</div>";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "<div class='alert alert-danger'>ስህተት፦ " . $e->getMessage() . "</div>";
        }
    }
}

// 4. የሪፖርት ታሪክን መሳብ
$history_query = "SELECT r.*, u.full_name as receiver_name 
                  FROM production_reports r 
                  LEFT JOIN users u ON r.reported_to = u.id 
                  WHERE r.user_id = ? 
                  ORDER BY r.report_date DESC";
$history_stmt = $pdo->prepare($history_query);
$history_stmt->execute([$user_id]);
$my_reports = $history_stmt->fetchAll();

include '../includes/header_glass.php';
?>

<style>
    :root { --bdtsc-color: #008080; }
    .bg-bdtsc { background-color: var(--bdtsc-color) !important; color: white; }
    .text-bdtsc { color: var(--bdtsc-color) !important; }
    .card-custom { border-top: 6px solid var(--bdtsc-color); border-radius: 15px; }
    .dept-badge { font-size: 0.9rem; letter-spacing: 1px; }
    .input-group-text { background-color: #f8f9fa; border-right: none; }
    .form-control, .form-select { border-left: none; }
    .form-control:focus, .form-select:focus { border-color: #dee2e6; box-shadow: none; }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            <div class="text-center mb-4">
                <h2 class="fw-bold text-dark">
                    <?= in_array($dept_name, $production_group) ? 'Daily Production Reporting' : 'Daily Task Reporting' ?>
                </h2>
                <div class="dept-badge badge bg-bdtsc px-3 py-2 mt-2 shadow-sm">
                    <i class="bi bi-building-fill me-2"></i> <?= htmlspecialchars($dept_name) ?>
                </div>
            </div>

            <?= $message ?>

            <div class="card card-custom border-0 shadow-lg">
                <div class="card-body p-4">
                    <form action="" method="POST">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-bdtsc">
                                Select <?= $target_label ?> <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person-check-fill text-bdtsc"></i></span>
                                <select name="target_user_id" class="form-select" required>
                                    <option value="">ስም ይምረጡ</option>
                                    <?php foreach ($target_users as $user): ?>
                                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['full_name']) ?> (<?= $user['user_role'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-secondary">
                                    <?= in_array($dept_name, $production_group) ? 'የማሽን መለያ / ቁጥር' : 'የሥራ/ፕሮጀክት መለያ' ?>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-tag text-muted"></i></span>
                                    <input type="text" name="machine_name" class="form-control" placeholder="ለምሳሌ፦ <?= in_array($dept_name, $production_group) ? 'M-01' : 'Task-45' ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-secondary">መለኪያ (Unit)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-rulers text-muted"></i></span>
                                    <select name="unit" class="form-select" required>
                                        <?php 
                                        switch ($dept_name) {
                                            case 'Spinning Department': echo '<option value="Kg">Kilograms (Kg)</option>'; break;
                                            case 'Weaving Department':
                                            case 'Processing Department': echo '<option value="Meters">Meters (Mtr)</option>'; break;
                                            case 'Garment Department': echo '<option value="Pieces">Pieces (Pcs)</option>'; break;
                                            case 'Engineering': echo '<option value="Hours">Working Hours</option>'; break;
                                            default:
                                                echo '<option value="Tasks">Completed Tasks</option>';
                                                echo '<option value="Percent">% Progress</option>';
                                                break;
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-secondary">የሥራ/የምርት መጠን</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-hash text-muted"></i></span>
                                    <input type="number" step="0.01" name="quantity_produced" class="form-control" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-secondary">ጊዜ/ሽፍት (Shift)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-clock text-muted"></i></span>
                                    <select name="shift" class="form-select">
                                        <option value="Day Shift">Day Shift</option>
                                        <option value="Day Shift">Afternoon Shift</option>
                                        <option value="Night Shift">Night Shift</option>
                                        <option value="Office Hours">Office Hours</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary">ተጨማሪ ማሳሰቢያ (Remarks)</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="ያጋጠመ ችግር ወይም ተጨማሪ መረጃ ካለ እዚህ ይጻፉ..."></textarea>
                        </div>

                        <button type="submit" name="submit_report" class="btn bg-bdtsc w-100 py-3 fw-bold shadow-sm">
                            <i class="bi bi-send-check-fill me-2"></i> ሪፖርቱን መዝግብ / SUBMIT REPORT
                        </button>

                    </form>
                </div>
            </div>

            <div class="mt-5">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history me-2"></i> የቅርብ ጊዜ ሪፖርቶችህ</h5>
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">ዝርዝር</th>
                                    <th>መጠን</th>
                                    <th class="text-end pe-4">ሁኔታ</th>
                                </tr>
                            </thead>
                          <tbody>
    <?php if (count($my_reports) > 0): ?>
        <?php foreach ($my_reports as $report): ?>
            <tr>
                <td class="ps-4">
                    <div class="fw-bold"><?= htmlspecialchars($report['machine_name'] ?? 'N/A') ?></div>
                    
                    <small class="text-muted">
                        ሪፖርት የተደረገለት፦ <?= htmlspecialchars($report['receiver_name'] ?? 'ያልተገለጸ') ?>
                    </small>
                </td>
                <td>
                    <span class="badge bg-light text-dark border">
                        <?= number_format($report['quantity_produced'] ?? 0, 2) ?> 
                        <?= htmlspecialchars($report['unit'] ?? '') ?>
                    </span>
                </td>
                <td class="text-end pe-4">
                    <div class="small fw-bold"><?= htmlspecialchars($report['shift'] ?? 'Office') ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;">
                        <?= isset($report['report_date']) ? date('M d, Y', strtotime($report['report_date'])) : '---' ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="3" class="text-center py-4 text-muted">ምንም የተመዘገበ ሪፖርት የለም።</td>
        </tr>
    <?php endif; ?>
</tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="successToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" style="border-radius: 12px;">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle-fill me-2"></i> 
                ሪፖርቱ ለተመረጠው ኃላፊ በተሳካ ሁኔታ ተልኳል!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#productionReportForm').on('submit', function(e) {
        e.preventDefault();

        // ሰራተኛው በተኑን ደጋግሞ እንዳይጫነው መዝጋት
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> በመላክ ላይ...');

        $.ajax({
            url: '', // አሁኑ ገጽ ላይ ይልካል
            type: 'POST',
            data: $(this).serialize() + '&submit_report=1',
            dataType: 'html',
            success: function(response) {
                // በ PHP የተላከው መልዕክት ውስጥ 'alert-success' ካለ መሳካቱን እናውቃለን
                if (response.indexOf('alert-success') !== -1) {
                    // Toast ማሳየት
                    var toastEl = document.getElementById('successToast');
                    var toast = new bootstrap.Toast(toastEl);
                    toast.show();

                    // ፎርሙን ማጽዳት
                    $('#productionReportForm')[0].reset();
                    
                    // የታሪክ ሰንጠረዡን በትንሹ ለማደስ (ከተቻለ) ገጹን ከ 2 ሰከንድ በኋላ ማደስ ይቻላል
                    setTimeout(function() {
                        location.reload(); 
                    }, 2500);
                } else {
                    // ስህተት ካለ ሙሉ ገጹን በማደስ ስህተቱን እንዲያሳይ ማድረግ
                    location.reload();
                }
            },
            error: function() {
                alert('ሪፖርቱን መላክ አልተቻለም። እባክዎ ኢንተርኔትዎን ቼክ አድርገው በድጋሚ ይሞክሩ።');
            },
            complete: function() {
                // በተኑን ወደ ነበረበት መመለስ
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>

<?php include '../includes/footer_glass.php'; ?>