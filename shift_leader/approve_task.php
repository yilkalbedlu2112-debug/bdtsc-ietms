<?php
// 1. መጀመሪያ Session እና DB ፋይሎችን ጥራ (ከማንኛውም HTML በፊት)
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// 2. የተጠቃሚውን ፍቃድ አረጋግጥ
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Shift Leader') {
    header("Location: ../auth/login.php");
    exit();
}

// 3. የፅድቅ (Approval) ሎጂክ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['task_id'])) {
    $task_id = $_POST['task_id'];
    $action = $_POST['action']; // 'approve' ወይም 'reject'
    $user_id = $_SESSION['user_id'];

    try {
        if ($action === 'approve') {
            $status = 'Verified & Closed';
            $msg = "ስራው በሽፍት ሊደር ተረጋግጦ ተዘግቷል። (Verified)";
        } else {
            $status = 'In Progress';
            $msg = "ስራው አልተጠናቀቀም ተብሎ በሽፍት ሊደር ተመልሷል። (Rejected)";
        }

        // ሀ. በ 'maintenance_requests' ሰንጠረዥ ውስጥ ሁኔታውን ማዘመን
        $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $task_id]);

        // ለ. የታስኩን ባለቤት (የሰራተኛውን ID) ማግኘት
        // ማሳሰቢያ፡ በዳታቤዝህ ኮለሙ 'assigned_to' መሆኑን አረጋግጥ
        $task_data = $pdo->prepare("SELECT assigned_to FROM maintenance_requests WHERE id = ?");
        $task_data->execute([$task_id]);
        $emp = $task_data->fetch();
        $emp_id = $emp['assigned_to'] ?? null;

        // ሐ. ለሰራተኛው ኖቲፊኬሽን መላክ
        if ($emp_id) {
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'task_status', NOW())");
            $notif->execute([$emp_id, $msg]);
        }

        // መ. በታሪክ መዝገብ (Audit Log) ላይ ማስፈር
        if (function_exists('log_action')) {
            log_action($pdo, $user_id, "Task Verification", "Task #$task_id was $action d by Shift Leader.");
        }

        // ገጹን ወደ ዳሽቦርድ መመለስ
        header("Location: dashboard.php?status=updated");
        exit();

    } catch (PDOException $e) {
        die("የዳታቤዝ ስህተት አጋጥሟል፦ " . $e->getMessage());
    }
} else {
    // ያለ ፎርም በቀጥታ ገጹ ቢከፈት ወደ ዳሽቦርድ ይመልሰው
    header("Location: dashboard.php");
    exit();
}