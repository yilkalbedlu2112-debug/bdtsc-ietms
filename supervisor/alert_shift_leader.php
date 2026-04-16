<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php'; // log_action እንዲሰራ

// ደህንነት፡ ሱፐርቫይዘር መሆኑን ማረጋገጥ
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Supervisor') {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_POST['req_id'])) {
    $req_id = $_POST['req_id'];
    $dept_id = $_SESSION['dept_id'];
    $user_id = $_SESSION['user_id'];

    // መልዕክቱን በአማርኛ እና በእንግሊዝኛ አቀናጅተን
    $msg = "Urgent Alert! Task ID #$req_id needs immediate attention. Supervisor has requested a quick assignment.";
    
    try {
        // 1. ለዲፓርትመንቱ ሽፍት ሊደሮች በሙሉ ኖቲፊኬሽን መላክ
        // ቀደም ብለን በሰራነው መሰረት 'user_role' ኮለምን እንጠቀማለን
        $notif = $pdo->prepare("INSERT INTO notifications (dept_id, user_role, message, type) VALUES (?, 'Shift Leader', ?, 'Urgent Alert')");
        $notif->execute([$dept_id, $msg]);

        // 2. ድርጊቱን በ Audit Log መመዝገብ (ለ Graduation Projectህ ወሳኝ ነው)
        log_action($pdo, $user_id, "Urgent Alert", "Supervisor sent an urgent alert for Task ID: $req_id");

        echo "<script>alert('ለShift Leader አስቸኳይ መልዕክት ተልኳል!'); window.location.href='dashboard.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('ስህተት ተከስቷል፦ " . $e->getMessage() . "'); window.location.href='dashboard.php';</script>";
    }
} else {
    header("Location: dashboard.php");
    exit();
}