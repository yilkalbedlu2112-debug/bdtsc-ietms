<?php
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($task_id && $status) {
        // Validate if task belongs to user
        $check_stmt = $pdo->prepare("SELECT id FROM maintenance_requests WHERE id = ? AND assigned_to = ?");
        $check_stmt->execute([$task_id, $_SESSION['user_id']]);
        if ($check_stmt->fetch()) {
            $update = $pdo->prepare("UPDATE maintenance_requests SET status = ? WHERE id = ?");
            if ($update->execute([$status, $task_id])) {
                log_action($pdo, $_SESSION['user_id'], "Status Update", "Updated task $task_id to status: $status");
                echo json_encode(['success' => true]);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Task not found or not assigned to you']);
            exit();
        }
    }
}
echo json_encode(['success' => false, 'error' => 'Invalid request']);
