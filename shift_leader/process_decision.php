<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['req_id'])) {
    $req_id = $_POST['req_id'];
    $severity = $_POST['severity'];
    $shift_leader_id = $_SESSION['user_id'];
    $dept_id = $_SESSION['dept_id'];

    try {
        $pdo->beginTransaction();

        if (isset($_POST['to_engineering'])) {
            // 1. ብልሽቱ ቀላል ከሆነ - ወደ Engineering Department መላክ
            $status = 'Sent to Engineering';
            $assigned_dept = 'Engineering';
            
            $stmt = $pdo->prepare("UPDATE maintenance_requests 
                                   SET status = ?, severity = ?, assigned_to_dept = ? 
                                   WHERE id = ?");
            $stmt->execute([$status, $severity, $assigned_dept, $req_id]);

            // ለEngineering Manager ኖቲፊኬሽን መላክ
            $msg = "ከ Shift Leader አዲስ የጥገና ጥያቄ ቀርቧል። (Machine ID: $req_id)";
            // እዚህ ጋር የኢንጂነሪንግ ማናጀርን Role ፈልገን ኖቲፊኬሽን እንልካለን
            $notif = $pdo->prepare("INSERT INTO notifications (user_role, message, type) VALUES ('Engineering Manager', ?, 'new_request')");
            $notif->execute([$msg]);

        } elseif (isset($_POST['to_manager'])) {
            // 2. ብልሽቱ ከባድ ከሆነ - ወደ ራሱ ዲፓርትመንት ማናጀር መመለስ
            $status = 'Escalated to Manager';
            $assigned_dept = 'Internal Manager';

            $stmt = $pdo->prepare("UPDATE maintenance_requests 
                                   SET status = ?, severity = ?, assigned_to_dept = ? 
                                   WHERE id = ?");
            $stmt->execute([$status, $severity, $assigned_dept, $req_id]);

            // ለዲፓርትመንት ማናጀሩ ኖቲፊኬሽን መላክ
            $msg = "ከባድ ብልሽት ስለገጠመ ውሳኔ ይፈለጋል። (Request ID: $req_id)";
            $notif = $pdo->prepare("INSERT INTO notifications (dept_id, user_role, message) VALUES (?, 'Department Manager', ?)");
            $notif->execute([$dept_id, $msg]);
        }

        $pdo->commit();
        echo "<script>alert('ውሳኔዎ ተመዝግቧል!'); window.location.href='dashboard.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        die("ስህተት አጋጥሟል: " . $e->getMessage());
    }
} else {
    header("Location: dashboard.php");
    exit();
}