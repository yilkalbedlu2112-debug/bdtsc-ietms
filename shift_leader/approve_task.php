<?php
session_start();
require_once '../includes/db.php';

if (isset($_POST['approve'])) {
    $req_id = $_POST['req_id'];

    // 1. ስራው መረጋገጡን መመዝገብ
    $update = $pdo->prepare("UPDATE maintenance_requests SET is_verified = 1 WHERE id = ?");
    $update->execute([$req_id]);

    // 2. ለዲፓርትመንት ማናጀሩ ስራው መጠናቀቁን ማሳወቅ (Notification)
    $msg = "የማሽን ጥገና በስኬት ተጠናቆ በ Shift Leader ተረጋግጧል። (ID: $req_id)";
    $notif = $pdo->prepare("INSERT INTO notifications (user_role, dept_id, message) VALUES ('Department Manager', ?, ?)");
    $notif->execute([$_SESSION['dept_id'], $msg]);

    echo "<script>alert('ስራው መረጋገጡ ለዲፓርትመንት ማናጀሩ ተገልጿል!'); window.location.href='dashboard.php';</script>";
}