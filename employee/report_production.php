<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$dept_name = $_SESSION['dept_name'];
$user_role = $_SESSION['user_role'];

// --- የዲፓርትመንት ግሩፖች ---
$production_group = ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'];
$technical_quality_group = ['Engineering', 'Quality Assurance'];
$finance_resource_group = ['Finance Department', 'Procurement / Property'];
$admin_strategy_group = ['General Management', 'Human Resource (HR)', 'Planning', 'Strategy & Innovation', 'System Research & Development', 'Legal Service', 'Audit & Inspection'];

// --- ዳይናሚክ እሴቶች (Defaults) ---
$form_title = "የስራ ሪፖርት መመዝገቢያ";
$field_label = "የስራው ርዕስ / መለያ";
$unit_label = "የተከናወነ ስራ (መጠን)";
$theme = "primary";
$is_production = false;
$report_to = "Department Manager"; // ነባሪ ለሁሉም

// --- ሎጂኩን እንደየ ግሩፑ ማስተካከል ---
if (in_array($dept_name, $production_group)) {
    $is_production = true;
    $form_title = "የዕለት ምርት መመዝገቢያ";
    $field_label = "የማሽን ስም/ቁጥር";
    $report_to = "Shift Leader / Supervisor";
    
    if (strpos($dept_name, 'Spinning') !== false) { $unit_label = "መጠን (በKg)"; $theme = "info"; }
    elseif (strpos($dept_name, 'Weaving') !== false) { $unit_label = "መጠን (በMtr)"; $theme = "success"; }
    elseif (strpos($dept_name, 'Garment') !== false) { $unit_label = "መጠን (በPcs)"; $theme = "danger"; }
} 
elseif (in_array($dept_name, $technical_quality_group)) {
    $form_title = "የቴክኒክ/ጥራት ሪፖርት";
    $field_label = "የመሳሪያ/አሴት መለያ";
    $unit_label = "የጥገና/ምርመራ ውጤት";
    $theme = "warning";
} 
elseif (in_array($dept_name, $finance_resource_group)) {
    $form_title = "የፋይናንስ/ንብረት ሪፖርት";
    $field_label = "የሰነድ/ትራንዛክሽን ቁጥር";
    $unit_label = "የገንዘብ/ንብረት መጠን";
    $theme = "primary";
} 
elseif (in_array($dept_name, $admin_strategy_group)) {
    $form_title = "የአስተዳደር ስራ ሪፖርት";
    $field_label = "የጉዳዩ/ፋይሉ ርዕስ";
    $unit_label = "የአፈጻጸም ደረጃ (%)";
    $theme = "dark";
}

$message = "";

// --- ፎርሙ ሲላክ (Form Submission) ---
if (isset($_POST['submit_report'])) {
    $user_id = $_SESSION['user_id'];
    $dept_id = $_SESSION['dept_id'];
    $item_name = htmlspecialchars($_POST['item_name']);
    $quantity = $_POST['quantity'];
    $shift = $_POST['shift'] ?? 'N/A';
    $remark = htmlspecialchars($_POST['remark'] ?? '');

    try {
        $sql = "INSERT INTO daily_reports (user_id, dept_id, item_identifier, quantity_value, shift, remarks, reported_to, reported_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        if($stmt->execute([$user_id, $dept_id, $item_name, $quantity, $shift, $remark, $report_to])) {
            $message = "<div class='alert alert-success border-0 shadow-sm'>
                            <i class='bi bi-check-circle-fill me-2'></i> ሪፖርቱ ለ{$report_to} በተሳካ ሁኔታ ተልኳል!
                        </div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>ስህተት፦ " . $e->getMessage() . "</div>";
    }
}

include '../includes/header_glass.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <?= $message ?>
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-<?= $theme ?> text-white p-4 border-0">
                    <h4 class="mb-0 fw-bold text-center"><?= $form_title ?></h4>
                    <p class="small text-center opacity-75 mb-0"><?= $dept_name ?> | ሪፖርት ለ: <?= $report_to ?></p>
                </div>
                <div class="card-body p-4 bg-white text-start">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold"><?= $field_label ?></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-tag"></i></span>
                                <input type="text" name="item_name" class="form-control bg-light border-0" placeholder="እዚህ ያስገቡ..." required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold"><?= $unit_label ?></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-hash"></i></span>
                                <input type="number" step="0.01" name="quantity" class="form-control bg-light border-0" placeholder="0.00" required>
                            </div>
                        </div>

                        <?php if($is_production): ?>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">ፈረቃ (Shift)</label>
                            <select name="shift" class="form-select bg-light border-0">
                                <option value="Day">ቀን (Day)</option>
                                <option value="Night">ማታ (Night)</option>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">ተጨማሪ አስተያየት (Remarks)</label>
                            <textarea name="remark" class="form-control bg-light border-0" rows="3" placeholder="ማሳሰቢያ ካለ እዚህ ይጻፉ..."></textarea>
                        </div>

                        <button type="submit" name="submit_report" class="btn btn-<?= $theme ?> w-100 py-2 fw-bold rounded-pill shadow-sm mb-3 text-white">
                            <i class="bi bi-send-check me-2"></i> ሪፖርት አቅርብ
                        </button>
                        
                        <div class="text-center">
                            <a href="dashboard.php" class="text-decoration-none text-muted small">
                                <i class="bi bi-arrow-left me-1"></i> ወደ ዳሽቦርድ ተመለስ
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="mt-4 p-3 bg-light rounded-4 border-start border-4 border-<?= $theme ?>">
                <small class="text-muted">
                    <strong>መረጃ፦</strong> ይህ ሪፖርት በቀጥታ ለ<strong><?= $report_to ?></strong> ይደርሳል። ለውጥ ማድረግ ካስፈለገ ለበላይ አካል ያሳውቁ።
                </small>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer_glass.php'; ?>