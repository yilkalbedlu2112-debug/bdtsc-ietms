<?php
session_start();
require_once '../includes/db.php';
include '../includes/header_glass.php';
if (isset($_POST['req_id'])) {
    $req_id = $_POST['req_id'];
    $dept_id = $_SESSION['dept_id'];

    // ለዚህ ዲፓርትመንት Shift Leader መልዕክት መላክ
    $msg = "ትኩረት የሚሻ ስራ አለ! Supervisor እንዲመደብ ጠይቋል። (ID: $req_id)";
    
    $notif = $pdo->prepare("INSERT INTO notifications (dept_id, user_role, message, type) VALUES (?, 'Shift Leader', ?, 'urgent_alert')");
    $notif->execute([$dept_id, $msg]);

    echo "<script>alert('ለShift Leader መልዕክት ተልኳል!'); window.location.href='dashboard.php';</script>";
}