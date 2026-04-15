<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Shift Leader') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'] ?? null;
    $employee_id = $_POST['employee_id'] ?? null;
    $dept_id = $_SESSION['dept_id'];

    if ($task_id && $employee_id) {
        try {
            // Verify employee is in same dept
            $emp_stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND dept_id = ?");
            $emp_stmt->execute([$employee_id, $dept_id]);
            if (!$emp_stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Employee not found in your department.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE maintenance_requests SET assigned_to = ?, status = 'Assigned' WHERE id = ? AND dept_id = ?");
            if ($stmt->execute([$employee_id, $task_id, $dept_id])) {
                echo json_encode(['success' => true, 'message' => 'Task assigned successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to assign task.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
