<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_id = $_POST['task_id'];
    $action = $_POST['action'];
    $user_id = $_SESSION['user_id'];

    try {
        if ($action === 'approve') {
            // ስራውን ሙሉ በሙሉ ማጠናቀቅ
            $status = 'Verified & Closed';
            $msg = "ስራው በሽፍት ሊደር ጸድቋል።";
        } else {
            // ስራው አልተሰራም ተብሎ ወደ ሰራተኛው እንዲመለስ ማድረግ
            $status = 'In Progress';
            $msg = "ስራው አልተጠናቀቀም ተብሎ በሽፍት ሊደር ተመልሷል። እባክዎ በድጋሚ ይስሩ።";
        }

        $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $task_id]);

        // ለሰራተኛው ኖቲፊኬሽን መላክ
        $task_data = $pdo->prepare("SELECT employee_id FROM maintenance_requests WHERE id = ?");
        $task_data->execute([$task_id]);
        $emp_id = $task_data->fetch()['employee_id'];

        $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'task_update')");
        $notif->execute([$emp_id, $msg]);

        log_action($pdo, $user_id, "Verify Task", "Task #$task_id status updated to $status");

        echo "<script>alert('ውሳኔው ተመዝግቧል!'); window.location.href='dashboard.php';</script>";
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}
