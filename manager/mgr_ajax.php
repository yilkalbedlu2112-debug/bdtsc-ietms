<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
/** @var PDO $pdo */
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Department Manager', 'Engineering Manager'], true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$dept_id = $_SESSION['dept_id'];
$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

$dept_stmt = $pdo->prepare("SELECT dept_name FROM departments WHERE id = ?");
$dept_stmt->execute([$dept_id]);
$dept_info = $dept_stmt->fetch();
$dept_name = trim($dept_info['dept_name'] ?? '');

$admin_departments = ['Human Resource (HR)', 'Planning', 'Strategy & Innovation', 'System Research & Development', 'Legal Service', 'Audit & Inspection'];
$use_title_column = in_array($dept_name, $admin_departments, true);

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
        if ($use_title_column) {
            $stmt = $pdo->prepare("INSERT INTO maintenance_requests (user_id, dept_id, assigned_to, title, issue_description, priority, status, task_type, due_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $insertParams = [$user_id, $dept_id, $assigned_to, $title, $description, $priority, $status, $task_type, $due_date];
        } else {
            $stmt = $pdo->prepare("INSERT INTO maintenance_requests (user_id, dept_id, assigned_to, machine_name, issue_description, priority, status, task_type, due_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $insertParams = [$user_id, $dept_id, $assigned_to, $title, $description, $priority, $status, $task_type, $due_date];
        }

        if($stmt->execute($insertParams)) {
            
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
    elseif ($action === 'dispatch_request') {
        // Only Engineering Manager can dispatch
        if ($_SESSION['user_role'] !== 'Engineering Manager') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized – Engineering Manager only']);
            exit();
        }

        $task_id = (int)($_POST['task_id'] ?? 0);
        if ($task_id < 1) {
            echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
            exit();
        }

        // Verify task is still Pending
        $check = $pdo->prepare("SELECT id, machine_name FROM maintenance_requests WHERE id = ? AND status = 'Pending'");
        $check->execute([$task_id]);
        $task = $check->fetch();

        if (!$task) {
            echo json_encode(['success' => false, 'message' => 'Task not found or already dispatched']);
            exit();
        }

        $upd = $pdo->prepare(
            "UPDATE maintenance_requests SET status = 'In Progress', assigned_to = ? WHERE id = ?");
        $upd->execute([$user_id, $task_id]);

        $full_name = $_SESSION['full_name'] ?? 'Eng. Manager';
        log_action($pdo, $user_id, 'Request Dispatched',
            "Engineering Manager ($full_name) dispatched request #$task_id ({$task['machine_name']}) to In Progress.");

        echo json_encode(['success' => true, 'message' => 'Request dispatched successfully']);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
?>
