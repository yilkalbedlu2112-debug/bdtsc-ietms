<?php
if (isset($_POST['assign_now'])) {
    $req_id = $_POST['req_id'];
    $tech_id = $_POST['tech_id'];
    $assigned_by = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        // 1. የጥያቄውን ሁኔታ ማደስ
        $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = 'In Progress', assigned_to = ? WHERE id = ?");
        $stmt->execute([$tech_id, $req_id]);

        // 2. ሪል-ታይም ኖቲፊኬሽን መመዝገብ
        $msg = "አዲስ የጥገና ስራ ተሰጥቶዎታል። እባክዎ ያረጋግጡ።";
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'task_assignment')");
        $notif->execute([$tech_id, $msg]);

        $pdo->commit();
        echo "<script>alert('ስራው ለቴክኒሻኑ ተሰጥቷል!'); window.location.href='dashboard.php';</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "ስህተት ተፈጥሯል: " . $e->getMessage();
    }
}
?>