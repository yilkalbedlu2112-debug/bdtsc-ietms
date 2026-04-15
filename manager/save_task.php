<?php
// manager/save_task.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Department Manager') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $machine_name = trim($_POST['machine_name'] ?? '');
    $priority = $_POST['priority'] ?? 'Medium';
    $instructions = trim($_POST['instructions'] ?? '');
    
    $dept_id = $_SESSION['dept_id'];
    $user_id = $_SESSION['user_id'];
    
    // Find Engineering Department Manager or just save the request unassigned to be handled by Engineering
    $stmt = $pdo->prepare("SELECT id FROM departments WHERE dept_name = 'Engineering' LIMIT 1");
    $stmt->execute();
    $eng_dept_id = $stmt->fetchColumn();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO maintenance_requests (user_id, dept_id, target_dept_id, machine_name, issue_description, priority, status, task_type, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Pending', 'Maintenance', NOW())");
        $stmt->execute([$user_id, $dept_id, $eng_dept_id ?: null, $machine_name, $instructions, $priority]);
        
        log_action($pdo, $user_id, "Maintenance Request", "Requested maintenance for $machine_name");
        header("Location: dashboard.php?msg=RequestSubmitted");
        exit();
    } catch (Exception $e) {
        die("Error processing request: " . $e->getMessage());
    }
}
