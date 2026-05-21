<?php
// manager/save_task.php
// Saves internal core tasks (non-cross-dept) created via the Create Task page.
session_start();
require_once '../includes/db.php';
/** @var PDO $pdo */
if (!isset($_SESSION['user_role']) ||
    !in_array($_SESSION['user_role'], ['Department Manager', 'Engineering Manager'], true)) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: create_task.php");
    exit();
}

$user_id   = (int)($_SESSION['user_id']  ?? 0);
$dept_id   = (int)($_SESSION['dept_id']  ?? 0);
$user_role = $_SESSION['user_role'] ?? '';
$full_name = $_SESSION['full_name'] ?? 'Unknown';

$title       = trim($_POST['title']       ?? '');
$description = trim($_POST['description'] ?? '');
$priority    = $_POST['priority']         ?? 'Normal';
$task_type   = $_POST['task_type']        ?? 'Administrative';
$due_date    = $_POST['due_date']         ?? '';
$assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;

// Cross-dept fields (optional – only set when form includes them)
$request_type      = $_POST['request_type']      ?? 'Administrative';
$receiver_dept_id  = !empty($_POST['receiver_dept_id']) ? (int)$_POST['receiver_dept_id'] : $dept_id;

// Basic validation
if (empty($title) || empty($description) || empty($due_date)) {
    $_SESSION['error'] = "Please fill in all required fields.";
    header("Location: create_task.php");
    exit();
}

// Status logic
$status = ($user_role === 'Department Manager' || $user_role === 'Engineering Manager')
        ? ($assigned_to ? 'Assigned' : 'Approved')
        : 'Pending Approval';

// Cross-dept flag
$is_cross_dept = ($receiver_dept_id !== $dept_id) ? 1 : 0;

try {
    $sql = "INSERT INTO maintenance_requests
                (user_id, dept_id, sender_dept_id, receiver_dept_id,
                 assigned_to, machine_name, issue_description,
                 priority, status, task_type, request_type,
                 is_read_by_receiver, due_date, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user_id, $dept_id, $dept_id, $receiver_dept_id,
        $assigned_to, $title, $description,
        $priority, $status, $task_type, $request_type,
        $is_cross_dept ? 0 : 1,   // unread for receiver if cross-dept
        $due_date
    ]);

    $new_id = $pdo->lastInsertId();

    $log_detail = sprintf('task_id=%d; created_by=%d; dept_id=%d; cross_dept=%d; type=%s; title=%s', $new_id, $user_id, $dept_id, $is_cross_dept, $task_type, substr($title,0,200));
    if (class_exists('Database') && method_exists('Database', 'log_system_activity')) {
        Database::log_system_activity($pdo, $user_id, 'TASK_CREATED', $log_detail);
    } else {
        log_action($pdo, $user_id, 'Task Created', $log_detail);
    }

    $_SESSION['success'] = "Task created and logged successfully!";
    header("Location: dashboard.php");
    exit();

} catch (Exception $e) {
    error_log("save_task error: " . $e->getMessage());
    $_SESSION['error'] = "Error saving task: " . $e->getMessage();
    header("Location: create_task.php");
    exit();
}
