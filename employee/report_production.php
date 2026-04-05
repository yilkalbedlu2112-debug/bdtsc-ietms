<?php
session_start();
require_once '../includes/db.php';

if (isset($_POST['submit_prod'])) {
    $user_id = $_SESSION['user_id'];
    $dept_id = $_SESSION['dept_id'];
    $machine = $_POST['machine_name'];
    $qty = $_POST['quantity'];
    $shift = $_POST['shift'];

    $sql = "INSERT INTO production_reports (user_id, dept_id, machine_name, quantity_produced, shift) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    if($stmt->execute([$user_id, $dept_id, $machine, $qty, $shift])) {
        echo "<div class='alert alert-success'>የዛሬው ምርት በተሳካ ሁኔታ ተመዝግቧል!</div>";
    }
}
?>

<div class="container mt-4" style="max-width: 500px;">
    <div class="card p-4 shadow">
        <h4 class="text-center">የዕለት ምርት መመዝገቢያ</h4>
        <form method="POST">
            <div class="mb-3">
                <label>የማሽን ስም/ቁጥር</label>
                <input type="text" name="machine_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>የተመረተ መጠን (በሜትር/በኪሎ)</label>
                <input type="number" step="0.01" name="quantity" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>ፈረቃ (Shift)</label>
                <select name="shift" class="form-select">
                    <option value="Day">ቀን (Day)</option>
                    <option value="Night">ማታ (Night)</option>
                </select>
            </div>
            <button type="submit" name="submit_prod" class="btn btn-success w-100">ሪፖርት አቅርብ</button>
            <a href="dashboard.php" class="btn btn-link w-100">ተመለስ</a>
        </form>
    </div>
</div>