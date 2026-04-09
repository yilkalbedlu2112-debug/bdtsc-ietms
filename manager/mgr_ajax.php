<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Department Manager') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$dept_id = $_SESSION['dept_id'];
$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'create_task') {
        $task_type = $_POST['task_type'] ?? 'General';
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'Medium';
        $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

        if (empty($title) || empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Title and description required']);
            exit();
        }

        if ($assigned_to) {
            $check = $pdo->prepare("SELECT id FROM users WHERE id = ? AND dept_id = ?");
            $check->execute([$assigned_to, $dept_id]);
            if (!$check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'User not in this department']);
                exit();
            }
        }

        $status = $assigned_to ? 'Assigned' : 'Pending Approval';
        $stmt = $pdo->prepare("INSERT INTO maintenance_requests (user_id, dept_id, assigned_to, machine_name, issue_description, priority, status, task_type, due_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if($stmt->execute([$user_id, $dept_id, $assigned_to, $title, $description, $priority, $status, $task_type, $due_date])) {
            
            if($assigned_to) {
                $n = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'task')");
                $n->execute([$assigned_to, "You have been assigned a new task: $title"]);
            }
            echo json_encode(['success' => true, 'message' => 'Core Task created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create task']);
        }
    }
    elseif ($action === 'request_engineering') {
        $machine = trim($_POST['machine'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if(empty($machine) || empty($desc)) {
            echo json_encode(['success' => false, 'message' => 'Fields cannot be empty']);
            exit();
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO maintenance_requests (user_id, dept_id, machine_name, issue_description, priority, status, assigned_to_dept, created_at) VALUES (?, ?, ?, ?, 'Emergency', 'Sent to Engineering', 'Engineering', NOW())");
        $stmt->execute([$user_id, $dept_id, $machine, $desc]);
        
        $n = $pdo->prepare("INSERT INTO notifications (user_role, message, type) VALUES ('Engineering Manager', ?, 'new_request')");
        $n->execute(["URGENT Engineering Requested from Dept ID $dept_id: $machine"]);
        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Engineering Maintenance Alerted']);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
?>
