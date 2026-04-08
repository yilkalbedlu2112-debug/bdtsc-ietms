<?php
session_start();
require_once '../includes/db.php';

// Verify exact Engineering Manager role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Engineering Manager') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = intval($_POST['task_id']);
    $technician_id = intval($_POST['technician_id']);

    if (empty($task_id) || empty($technician_id)) {
        $_SESSION['error'] = 'Invalid selection. Please try again.';
        header("Location: dashboard.php");
        exit();
    }

    try {
        // Verify task exists and is not already completed
        $check = $pdo->prepare("SELECT id, status FROM maintenance_requests WHERE id = ?");
        $check->execute([$task_id]);
        $task = $check->fetch();

        if ($task) {
            // Update the task to 'Assigned' and tie to Technician
            $stmt = $pdo->prepare("UPDATE maintenance_requests SET assigned_to = ?, status = 'Assigned', updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$technician_id, $task_id])) {
                $_SESSION['success'] = 'Technician successfully dispatched.';
                log_action($pdo, $_SESSION['user_id'], 'Task Assigned', "Eng Manager assigned task #$task_id to Tech #$technician_id");
            } else {
                $_SESSION['error'] = 'Failed to assign the task in the database.';
            }
        } else {
            $_SESSION['error'] = 'Warning: Task not found or already removed.';
        }

    } catch (Exception $e) {
        $_SESSION['error'] = 'Database Error: ' . $e->getMessage();
    }
    
    header("Location: dashboard.php");
    exit();
} else {
    // If accessed via GET, redirect back cleanly
    header("Location: dashboard.php");
    exit();
}
