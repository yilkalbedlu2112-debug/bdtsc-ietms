<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$dept_id = $_SESSION['dept_id'];
$dept_name = $_SESSION['dept_name'];
$user_role = $_SESSION['user_role'];

// --- UC-15: የተመደቡ ስራዎችን መፈለግ (Precondition Check) ---
// For production, no task required, but check department
$production_depts = [8, 9, 3, 10]; // Spinning, Weaving, Garment, Processing
$is_production_dept = in_array($dept_id, $production_depts);

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
$report_to = "Department Manager"; 

// --- ሎጂኩን እንደየ ግሩፑ ማስተካከል ---
if ($is_production_dept) {
    $is_production = true;
    $form_title = "የዕለት ምርት መመዝገቢያ";
    $field_label = "የምርት አይነት/ቁጥር";
    $report_to = "Shift Leader / Supervisor";
    
    if ($dept_id == 8) { $unit_label = "መጠን (በKg)"; $theme = "info"; $default_unit = "Kg"; }
    elseif ($dept_id == 9) { $unit_label = "መጠን (በMtr)"; $theme = "success"; $default_unit = "Meters"; }
    elseif ($dept_id == 3) { $unit_label = "መጠን (በPcs)"; $theme = "danger"; $default_unit = "Pieces"; }
    elseif ($dept_id == 10) { $unit_label = "መጠን (በMtr)"; $theme = "warning"; $default_unit = "Meters"; }
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
    if (!$is_production_dept) {
        $message = "<div class='alert alert-danger'>ስህተት፦ ይህ ፎርም ለምርት ክፍሎች ብቻ ነው።</div>";
    } else {
        $machine_name = trim($_POST['machine_name']);
        $quantity_produced = $_POST['quantity_produced'];
        $unit = $_POST['unit'];
        $shift = $_POST['shift'];
        $remarks = trim($_POST['remarks'] ?? '');

        if (empty($machine_name) || empty($quantity_produced) || empty($unit) || empty($shift)) {
            $message = "<div class='alert alert-danger'>ስህተት፦ ሁሉንም አስፈላጊ ቦታዎች ያስገቡ።</div>";
        } else {
            // Check for duplicate report for same machine in same shift today
            $check_sql = "SELECT id FROM production_reports WHERE user_id = ? AND machine_name = ? AND shift = ? AND DATE(report_date) = CURDATE()";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([$user_id, $machine_name, $shift]);
            if ($check_stmt->fetch()) {
                $message = "<div class='alert alert-warning'>ስህተት፦ ለዚህ ማሽን በዚህ ሽፍት አስቀድሞ ሪፖርት አለ።</div>";
            } else {
                try {
                    $pdo->beginTransaction();

                    // 1. Insert into production_reports
                    $sql = "INSERT INTO production_reports (user_id, dept_id, machine_name, quantity_produced, unit, shift, reported_to, remarks, report_date) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$user_id, $dept_id, $machine_name, $quantity_produced, $unit, $shift, $report_to, $remarks]);

                    // 2. Update daily_reports for aggregation
                    // Check if daily report exists for this dept/shift today
                    $daily_check = "SELECT id FROM daily_reports WHERE dept_id = ? AND shift_type = ? AND DATE(created_at) = CURDATE()";
                    $daily_stmt = $pdo->prepare($daily_check);
                    $daily_stmt->execute([$dept_id, $shift]);
                    $daily_row = $daily_stmt->fetch();

                    if ($daily_row) {
                        // Update existing
                        $update_daily = "UPDATE daily_reports SET total_tasks = total_tasks + 1, completed_tasks = completed_tasks + ?, report_summary = CONCAT(report_summary, ' | ', ?) WHERE id = ?";
                        $update_stmt = $pdo->prepare($update_daily);
                        $update_stmt->execute([$quantity_produced, "$machine_name: $quantity_produced $unit", $daily_row['id']]);
                    } else {
                        // Insert new
                        $insert_daily = "INSERT INTO daily_reports (dept_id, created_by, shift_type, report_summary, total_tasks, completed_tasks, created_at) 
                                        VALUES (?, ?, ?, ?, 1, ?, NOW())";
                        $insert_stmt = $pdo->prepare($insert_daily);
                        $insert_stmt->execute([$dept_id, $user_id, $shift, "$machine_name: $quantity_produced $unit", $quantity_produced]);
                    }

                    // 3. Notify Shift Leader
                    $shift_leader_sql = "SELECT id FROM users WHERE dept_id = ? AND user_role = 'Shift Leader' LIMIT 1";
                    $sl_stmt = $pdo->prepare($shift_leader_sql);
                    $sl_stmt->execute([$dept_id]);
                    $sl = $sl_stmt->fetch();
                    if ($sl) {
                        $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'production_report')");
                        $notif->execute([$sl['id'], "New production report submitted for $machine_name ($quantity_produced $unit) in $shift shift."]);
                    }

                    $pdo->commit();
                    $message = "<div class='alert alert-success border-0 shadow-sm'>
                                    <i class='bi bi-check-circle-fill me-2'></i> ሪፖርቱ በተሳካ ሁኔታ ተልኳል!
                                </div>";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $message = "<div class='alert alert-danger'>ስህተት፦ " . $e->getMessage() . "</div>";
                }
            }
        }
    }
}

include '../includes/header_glass.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <?= $message ?>

            <?php if (!$is_production_dept): ?>
                <div class="alert alert-warning border-0 shadow-lg p-4">
                    <h5 class="fw-bold"><i class="bi bi-shield-lock-fill me-2"></i> Access Denied</h5>
                    <p class="mb-0 small">ይህ ፎርም ለምርት ክፍሎች ብቻ ነው።</p>
                    <a href="dashboard.php" class="btn btn-sm btn-outline-warning mt-3 rounded-pill">ወደ ዳሽቦርድ ተመለስ</a>
                </div>
            <?php else: ?>
                <div class="card glass-card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-header bg-<?= $theme ?> text-white p-4 border-0">
                        <h4 class="mb-0 fw-bold text-center"><?= $form_title ?></h4>
                        <p class="small text-center opacity-75 mb-0"><?= $dept_name ?> | ሪፖርት ለ: <?= $report_to ?></p>
                    </div>
                    <div class="card-body p-4 bg-white text-start">
                        <form id="productionReportForm">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">ማሽን ስም (Machine Name)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="bi bi-gear"></i></span>
                                    <input type="text" name="machine_name" class="form-control bg-light border-0" placeholder="እዚህ ያስገቡ..." required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">የተምራ መጠን (Quantity Produced)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="bi bi-hash"></i></span>
                                    <input type="number" step="0.01" name="quantity_produced" class="form-control bg-light border-0" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">አሃድ (Unit)</label>
                                <select name="unit" class="form-select bg-light border-0" required>
                                    <option value="Meters">በሜትር (Meters)</option>
                                    <option value="Kg">በኪሎ ግራም (Kg)</option>
                                    <option value="Pieces">በቁጥር (Pieces)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">ፈረቃ (Shift)</label>
                                <select name="shift" class="form-select bg-light border-0" required>
                                    <option value="Day">ቀን (Day)</option>
                                    <option value="Night">ሌሊት (Night)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">አስተያየት (Remarks)</label>
                                <textarea name="remarks" class="form-control bg-light border-0" rows="3" placeholder="አስፈላጊ ከሆነ..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-<?= $theme ?> w-100 rounded-pill fw-bold">
                                <i class="bi bi-send me-2"></i> ሪፖርት ላክ
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="mt-4 p-3 bg-light rounded-4 border-start border-4 border-<?= $theme ?>">
                <small class="text-muted">
                    <strong>መረጃ፦</strong> ይህ ሪፖርት በቀጥታ ለ<strong><?= $report_to ?></strong> ይደርሳል። በሰነድ ቁጥር <strong>UC-15</strong> መሰረት በቋሚነት ይመዘገባል።
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle-fill me-2"></i> ሪፖርቱ በተሳካ ሁኔታ ተልኳል!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('#productionReportForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '',
            type: 'POST',
            data: $(this).serialize() + '&submit_report=1',
            dataType: 'html',
            success: function(response) {
                // Check if success message is in response
                if (response.includes('alert-success')) {
                    // Show toast
                    var toast = new bootstrap.Toast(document.getElementById('successToast'));
                    toast.show();
                    // Reset form
                    $('#productionReportForm')[0].reset();
                } else {
                    // Reload page to show error
                    location.reload();
                }
            },
            error: function() {
                alert('Error submitting report. Please try again.');
            }
        });
    });
});
</script>

<?php include '../includes/footer_glass.php'; ?>