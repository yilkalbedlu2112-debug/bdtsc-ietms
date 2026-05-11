<?php
// የዳታቤዝ ግንኙነት ፋይልህን እዚህ ጋር አካትት (ለምሳሌ db_config.php)
require_once '../includes/db.php'; 
session_start();
/** @var PDO $pdo */
if (isset($_POST['confirm_dispatch'])) {
    // ከሞዳሉ የመጡ ዳታዎችን መቀበል
    $request_id   = $_POST['request_id'];
    $technician_id = $_POST['technician_id'];
    $assigned_by  = $_SESSION['user_id']; // ማናጀሩ (አንተ)
    $assigned_at  = date('Y-m-d H:i:s');

    try {
        // 1. የጥያቄውን ሁኔታ (Status) ማደስ እና ሰራተኛ መመደብ
        // Status ወደ 'Assigned' ወይም 'In Progress' ይቀየራል
        $sql = "UPDATE maintenance_requests 
                SET status = 'Assigned', 
                    assigned_to = :tech_id, 
                    assigned_at = :assigned_at 
                WHERE id = :req_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':tech_id'     => $technician_id,
            ':assigned_at' => $assigned_at,
            ':req_id'      => $request_id
        ]);

        // 2. ለቴክኒሻኑ ኖቲፊኬሽን መላክ ከፈለግህ እዚህ ጋር መጨመር ትችላለህ

        // ስራው ሲያልቅ ወደ ዳሽቦርዱ ይመለሳል
        header("Location: dashboard.php?msg=dispatched_success");
        exit();

    } catch (PDOException $e) {
        // ስህተት ካለ ማሳያ
        die("Error updating record: " . $e->getMessage());
    }
} else {
    // በቀጥታ ፋይሉን ለመክፈት ከሞከሩ ወደ ዳሽቦርድ ይመለሳሉ
    header("Location: dashboard.php");
    exit();
}