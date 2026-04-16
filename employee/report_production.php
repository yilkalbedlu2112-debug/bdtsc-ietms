<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$dept_name = $_SESSION['dept_name'];
$unit = "መጠን"; // Default

// የክፍሉን መለኪያ መለየት (Business Logic)
if (strpos($dept_name, 'Spinning') !== false) { $unit = "በኪሎ (Kg)"; $theme = "info"; }
elseif (strpos($dept_name, 'Weaving') !== false) { $unit = "በሜትር (Mtr)"; $theme = "success"; }
elseif (strpos($dept_name, 'Garment') !== false) { $unit = "በቁጥር (Pcs)"; $theme = "danger"; }
else { $unit = "ብዛት"; $theme = "primary"; }

$message = "";

if (isset($_POST['submit_prod'])) {
    $user_id = $_SESSION['user_id'];
    $dept_id = $_SESSION['dept_id'];
    $machine = htmlspecialchars($_POST['machine_name']);
    $qty = $_POST['quantity'];
    $shift = $_POST['shift'];

    try {
        $sql = "INSERT INTO production_reports (user_id, dept_id, machine_name, quantity_produced, shift, reported_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        if($stmt->execute([$user_id, $dept_id, $machine, $qty, $shift])) {
            $message = "<div class='alert alert-success border-0 shadow-sm animate__animated animate__fadeIn'>
                            <i class='bi bi-check-circle-fill me-2'></i> የዛሬው ምርት በተሳካ ሁኔታ ተመዝግቧል!
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
        <div class="col-md-5">
            <?= $message ?>
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-<?= $theme ?> text-white p-4 border-0">
                    <h4 class="mb-0 fw-bold text-center">የዕለት ምርት መመዝገቢያ</h4>
                    <p class="small text-center opacity-75 mb-0"><?= $dept_name ?></p>
                </div>
                <div class="card-body p-4 bg-white">
                    <form method="POST" class="needs-validation">
                        <div class="mb-3 text-start">
                            <label class="form-label small fw-bold">የማሽን ስም/ቁጥር</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-cpu"></i></span>
                                <input type="text" name="machine_name" class="form-control bg-light border-0" placeholder="ለምሳሌ: SM-01" required>
                            </div>
                        </div>

                        <div class="mb-3 text-start">
                            <label class="form-label small fw-bold">የተመረተ መጠን (<?= $unit ?>)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-hash"></i></span>
                                <input type="number" step="0.01" name="quantity" class="form-control bg-light border-0" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="mb-4 text-start">
                            <label class="form-label small fw-bold">ፈረቃ (Shift)</label>
                            <div class="row g-2">
                                <div class="col">
                                    <input type="radio" class="btn-check" name="shift" id="dayShift" value="Day" checked>
                                    <label class="btn btn-outline-<?= $theme ?> w-100 rounded-pill" for="dayShift">ቀን (Day)</label>
                                </div>
                                <div class="col">
                                    <input type="radio" class="btn-check" name="shift" id="nightShift" value="Night">
                                    <label class="btn btn-outline-dark w-100 rounded-pill" for="nightShift">ማታ (Night)</label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="submit_prod" class="btn btn-<?= $theme ?> w-100 py-2 fw-bold rounded-pill shadow-sm mb-3">
                            <i class="bi bi-cloud-upload me-2"></i> ሪፖርት አቅርብ
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
                    <strong>ማሳሰቢያ፦</strong> የምርት መረጃው አንዴ ከተላከ በኋላ ለውጥ ማድረግ አይቻልም። ስህተት ካለ ለ <strong>Shift Leader</strong> ያሳውቁ።
                </small>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer_glass.php'; ?>