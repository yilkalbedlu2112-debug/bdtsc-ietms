<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_id = $_POST['task_id'];
    $action = $_POST['action_taken'];
    $parts = $_POST['spare_parts'];

    try {
        $pdo->beginTransaction();

        // 1. የጥገና ጥያቄውን ሁኔታ ማደስ
        $stmt = $pdo->prepare("UPDATE maintenance_requests 
                               SET status = 'Completed', 
                                   completion_notes = ?, 
                                   spare_parts_used = ?, 
                                   completed_at = NOW() 
                               WHERE id = ?");
        $stmt->execute([$action, $parts, $task_id]);

        // 2. ለShift Leader ስራው መጠናቀቁን ማሳወቅ
        // መጀመሪያ የጥያቄውን መረጃ ማግኘት
        $get_req = $pdo->prepare("SELECT dept_id, machine_name FROM maintenance_requests WHERE id = ?");
        $get_req->execute([$task_id]);
        $req_data = $get_req->fetch();

        $msg = "ቴክኒሻኑ የ{$req_data['machine_name']} ጥገና አጠናቋል። እባክዎ ያረጋግጡ።";
        $notif = $pdo->prepare("INSERT INTO notifications (dept_id, user_role, message) VALUES (?, 'Shift Leader', ?)");
        $notif->execute([$req_data['dept_id'], $msg]);

        $pdo->commit();
        echo "<script>alert('ሪፖርቱ በተሳካ ሁኔታ ተልኳል!'); window.location.href='dashboard.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        die("ስህተት፡ " . $e->getMessage());
    }
}