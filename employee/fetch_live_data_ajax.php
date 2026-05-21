<?php
session_start();
require_once '../includes/db.php';

/** @var PDO $pdo */
header('Content-Type: application/json');

// 1. Authorization Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['task_id'])) {
    $task_id = $_GET['task_id'];

    try {
        // 2. Fetch task details ensuring it belongs to the logged-in user
        $stmt = $pdo->prepare("SELECT id, title, description, deadline, priority, status, is_verified, feedback, completion_notes, updated_at, created_at, machine_name, issue_description FROM maintenance_requests WHERE id = ? AND assigned_to = ?");
        $stmt->execute([$task_id, $user_id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($task) {
            echo json_encode([
                'success' => true,
                'task' => $task
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Task not found or access denied']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid Request']);
?>