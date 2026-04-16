<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'] ?? null;
    $employee_id = $_POST['employee_id'] ?? null;
    $sl_id = $_SESSION['user_id'];

    if (!$task_id || !$employee_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required data.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. የታስኩን ስታተስ እና ሰራተኛ ማዘመን
        $stmt = $pdo->prepare("UPDATE maintenance_requests SET employee_id = ?, status = 'Assigned', assigned_at = NOW() WHERE id = ?");
        $stmt->execute([$employee_id, $task_id]);

        // 2. ለሰራተኛው ኖቲፊኬሽን መላክ (Real-time Notification)
        $notif_msg = "A new task (ID: #$task_id) has been assigned to you.";
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'Task Assignment')");
        $notif->execute([$employee_id, $notif_msg]);

        // 3. ኦዲት ሎግ መመዝገብ (BR-08)
        log_action($pdo, $sl_id, "Assign Task", "Shift Leader assigned task #$task_id to employee ID: $employee_id");

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Task assigned successfully!']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
