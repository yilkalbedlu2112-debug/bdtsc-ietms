<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['req_id'])) {
    $req_id = $_POST['req_id'];
    $tech_id = $_POST['tech_id']; // የተመረጠው ኢንጂነሪንግ ቴክኒሻን
    $manager_id = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        // 1. የጥገና ጥያቄውን ሁኔታ ማደስ (Status Update)
        // ሁኔታውን ወደ 'In Progress' እንቀይረዋለን፣ ተረካቢውንም ቴክኒሻን እንመዘግባለን
        $stmt = $pdo->prepare("UPDATE maintenance_requests 
                               SET status = 'In Progress', 
                                   assigned_to = ?, 
                                   assigned_to_dept = 'Engineering' 
                               WHERE id = ?");
        $stmt->execute([$tech_id, $req_id]);

        // 2. ለቴክኒሻኑ ኖቲፊኬሽን መላክ
        $msg = "አዲስ የጥገና ስራ ከኢንጂነሪንግ ማናጀር ተመድቦልዎታል። (Request ID: $req_id)";
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'task_assignment')");
        $notif->execute([$tech_id, $msg]);

        $pdo->commit();
        echo "<script>alert('ስራው ለቴክኒሻኑ በተሳካ ሁኔታ ተሰጥቷል!'); window.location.href='dashboard.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        die("ስህተት ተፈጥሯል: " . $e->getMessage());
    }
} else {
    header("Location: dashboard.php");
    exit();
}