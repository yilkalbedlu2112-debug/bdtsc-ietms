<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
include '../includes/header_glass.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Supervisor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$dept_id = $_SESSION['dept_id'];
$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'create_task') {
        $title = $_POST['title'];
        $desc = $_POST['desc'];
        $priority = $_POST['priority'];
        
        $stmt = $pdo->prepare("INSERT INTO maintenance_requests (user_id, dept_id, machine_name, issue_description, priority, status, task_type, created_at) VALUES (?, ?, ?, ?, ?, 'Pending', 'General', NOW())");
        if($stmt->execute([$user_id, $dept_id, $title, $desc, $priority])) {
            echo json_encode(['success' => true, 'message' => 'Detailed Task created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Insert failed']);
        }
    } 
    elseif ($action === 'assign_task') {
        $task_id = $_POST['task_id'];
        $employee_id = $_POST['employee_id'];
        
        // Verify employee
        $verify = $pdo->prepare("SELECT id FROM users WHERE id = ? AND dept_id = ?");
        $verify->execute([$employee_id, $dept_id]);
        if(!$verify->fetch()) { echo json_encode(['success'=>false, 'message'=>'Invalid Employee']); exit(); }
        
        $stmt = $pdo->prepare("UPDATE maintenance_requests SET assigned_to = ?, status = 'Assigned' WHERE id = ? AND dept_id = ?");
        if($stmt->execute([$employee_id, $task_id, $dept_id])) {
            // Send Notif
            $n = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'task')");
            $n->execute([$employee_id, "New task assigned: ID $task_id"]);
            echo json_encode(['success' => true]);
        }
    }
    elseif ($action === 'notify_shift_leader') {
        $task_id = $_POST['task_id'];
        $stmt = $pdo->prepare("UPDATE maintenance_requests SET priority = 'High' WHERE id = ? AND dept_id = ?");
        $stmt->execute([$task_id, $dept_id]);
        
        // Notify shift leader
        $n = $pdo->prepare("INSERT INTO notifications (dept_id, user_role, message) VALUES (?, 'Shift Leader', ?)");
        $n->execute([$dept_id, "Critical task requires attention: Task ID $task_id"]);
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'report_failure') {
        $machine = $_POST['machine'];
        $desc = $_POST['desc'];
        
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO maintenance_requests (user_id, dept_id, machine_name, issue_description, priority, status, assigned_to_dept, created_at) VALUES (?, ?, ?, ?, 'Emergency', 'Sent to Engineering', 'Engineering', NOW())");
        $stmt->execute([$user_id, $dept_id, $machine, $desc]);
        
        $n = $pdo->prepare("INSERT INTO notifications (user_role, message, type) VALUES ('Engineering Manager', ?, 'new_request')");
        $n->execute(["MACHINE FAILURE REPORTED in Dept ID $dept_id: $machine"]);
        $pdo->commit();
        
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'escalate_task') {
        $task_id = $_POST['task_id'];
        $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = 'Escalated to Manager' WHERE id = ? AND dept_id = ?");
        $stmt->execute([$task_id, $dept_id]);
        
        $n = $pdo->prepare("INSERT INTO notifications (dept_id, user_role, message) VALUES (?, 'Department Manager', ?)");
        $n->execute([$dept_id, "Task Escalated by Supervisor: Task ID $task_id"]);
        
        echo json_encode(['success' => true]);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
?>
