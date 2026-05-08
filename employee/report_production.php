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

// --- የባህርዳር ጨርቃጨርቅ መለያ ከለር ---
$theme_color = "#008080"; 

// --- 1. በዚሁ ዲፓርትመንት ያሉ የShift Leader ዝርዝርን መሳብ ---
$sl_query = "SELECT id, full_name FROM users WHERE dept_id = ? AND user_role = 'Shift Leader'";
$sl_stmt = $pdo->prepare($sl_query);
$sl_stmt->execute([$dept_id]);
$shift_leaders = $sl_stmt->fetchAll();

$message = "";

// --- ፎርሙ ሲላክ ---
if (isset($_POST['submit_report'])) {
    $machine_name = trim($_POST['machine_name']);
    $quantity = $_POST['quantity_produced'];
    $unit = $_POST['unit'];
    $shift = $_POST['shift'];
    $selected_sl_id = $_POST['shift_leader_id']; // የመረጥነው ሽፍት ሊደር ID
    $remarks = trim($_POST['remarks'] ?? '');

    if (empty($machine_name) || empty($selected_sl_id)) {
        $message = "<div class='alert alert-danger'>እባክዎ ሽፍት ሊደር እና አስፈላጊ መረጃዎችን ይምረጡ።</div>";
    } else {
        try {
            $pdo->beginTransaction();

            // ሪፖርቱን መመዝገብ (የተመረጠውን ሽፍት ሊደር ስም ጨምሮ)
            $sql = "INSERT INTO production_reports (user_id, dept_id, machine_name, quantity_produced, unit, shift, reported_to_id, remarks, report_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $dept_id, $machine_name, $quantity, $unit, $shift, $selected_sl_id, $remarks]);

            // ለተመረጠው Shift Leader ኖቲፊኬሽን መላክ
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'production_report')");
            $notif->execute([$selected_sl_id, "አዲስ የምርት ሪፖርት ከ " . $_SESSION['full_name'] . " ቀርቧል።"]);

            $pdo->commit();
            $message = "<div class='alert alert-success border-0 shadow-sm'>ሪፖርቱ ለተመረጠው የሽፍት መሪ በተሳካ ሁኔታ ተልኳል!</div>";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "<div class='alert alert-danger'>ስህተት፦ " . $e->getMessage() . "</div>";
        }
    }
}

include '../includes/header_glass.php';
?>
<?php
// 1. የሰራተኛውን የሪፖርት ታሪክ ከዳታቤዝ መሳብ
// ማሳሰቢያ፡ 'product_name' የሚል ኮለም በዳታቤዝህ ከሌለ በ 'machine_name' ተካው
$history_query = "SELECT r.*, u.full_name as sl_name 
                  FROM production_reports r 
                  LEFT JOIN users u ON r.reported_to = u.id 
                  WHERE r.user_id = ? 
                  ORDER BY r.report_date DESC";

$history_stmt = $pdo->prepare($history_query);
$history_stmt->execute([$_SESSION['user_id']]);
$my_reports = $history_stmt->fetchAll(); // ተለዋዋጩ እዚህ ጋር ነው ዲፋይን የሚደረገው
?>

<!-- ከዚያ የሰንጠረዡ HTML እዚህ ጋር ይቀጥላል -->
<div class="row mt-5">
    <div class="col-12">
        <?php if (count($my_reports) > 0): ?>
            <?php foreach ($my_reports as $report): ?>
                <!-- የሰንጠረዥ ዳታ እዚህ ይገባል -->
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    :root { --bdtsc-color: #008080; }
    .bg-bdtsc { background-color: var(--bdtsc-color) !important; color: white; }
    .text-bdtsc { color: var(--bdtsc-color) !important; }
    .card-custom { border-top: 6px solid var(--bdtsc-color); border-radius: 15px; }
    .dept-badge { font-size: 0.9rem; letter-spacing: 1px; }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            
            <div class="text-center mb-4">
                <h2 class="fw-bold text-dark">Daily Production Reporting </h2>
                <!-- ዲፓርትመንቱን ለይቶ የሚያሳይ Badge -->
                <div class="dept-badge badge bg-bdtsc px-3 py-2 mt-2 shadow-sm">
                    <i class="bi bi-building-fill me-2"></i> Department <?= htmlspecialchars($dept_name) ?>
                </div>
            </div>

            <?= $message ?>

            <div class="card card-custom border-0 shadow-lg">
                <div class="card-body p-4">
                    <form action="" method="POST">
                        
                        <!-- 1. የሽፍት ሊደር ምርጫ (Dropdown) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-bdtsc">Select their Shift Leader <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-person-check text-bdtsc"></i></span>
                                <select name="shift_leader_id" class="form-select" required>
                                    <option value="">Select their Shift Leader </option>
                                    <?php foreach ($shift_leaders as $sl): ?>
                                        <option value="<?= $sl['id'] ?>"><?= htmlspecialchars($sl['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">የማሽን መለያ / ቁጥር</label>
                                <input type="text" name="machine_name" class="form-control" placeholder="ለምሳሌ፦ M-01" required>
                            </div>

                            <div class="col-md-6 mb-3">
    <label class="form-label fw-bold">(Unit)</label>
    <select name="unit" class="form-select" required>
        <?php 
        // በዲፓርትመንት ስም ላይ ተመስርቶ መለኪያውን መወሰን
        switch ($dept_name) {
            case 'Spinning Department':
                echo '<option value="Kg">Kilograms (Kg)</option>';
                break;
            case 'Weaving Department':
            case 'Processing Department':
                echo '<option value="Meters">Meters (Mtr)</option>';
                break;
            case 'Garment Department':
                echo '<option value="Pieces">Pieces (Pcs)</option>';
                break;
            default:
                // ሌሎች ዲፓርትመንቶች ካሉ እንደ አማራጭ ሁሉንም ያሳያል
                echo '<option value="Kg">Kg</option>';
                echo '<option value="Meters">Meters</option>';
                echo '<option value="Pieces">Pieces</option>';
                break;
        }
        ?>
    </select>
</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">የምርት መጠን</label>
                                <input type="number" step="0.01" name="quantity_produced" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">መለኪያ</label>
                                <select name="unit" class="form-select">
                                    <option value="Kg">Kg</option>
                                    <option value="Meters">Meters</option>
                                    <option value="Pieces">Pieces</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">ተጨማሪ ማሳሰቢያ</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="ያጋጠመ ችግር ካለ..."></textarea>
                        </div>

                        <button type="submit" name="submit_report" class="btn bg-bdtsc w-100 py-2 fw-bold text-white shadow">
                            <i class="bi bi-send-check-fill me-2"></i> ሪፖርቱን መዝግብ
                        </button>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <tbody class="border-top-0">
                    <?php if (count($my_reports) > 0): ?>
                        <?php foreach ($my_reports as $report): ?>
                            <tr>
                                <td class="ps-4" style="width: 60px;">
                                    <div class="bg-bdtsc rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; opacity: 0.1;">
                                        <i class="bi bi-box-seam text-bdtsc fs-5"></i>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <!-- የምርት ስም ባይኖር የማሽኑን ስም እንዲያሳይ -->
                                        <?= htmlspecialchars($report['product_name'] ?? $report['machine_name'] ?? 'ያልተገለጸ ምርት') ?>
                                    </div>
                                    <small class="text-muted d-block">
                                        መለያ፦ <?= htmlspecialchars($report['machine_name'] ?? '---') ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="bi bi-person me-1"></i>
                                        <?= htmlspecialchars($report['sl_name'] ?? 'ያልተመደበ') ?>
                                    </small>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="fw-bold text-dark">
                                        <?= number_format($report['quantity_produced'], 2) ?> <?= htmlspecialchars($report['unit']) ?>
                                    </div>
                                    <div class="small text-muted">
                                        <?= htmlspecialchars($report['shift']) ?> | <?= date('d/m/Y', strtotime($report['report_date'])) ?>
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