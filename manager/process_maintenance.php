<?php
// manager/process_maintenance.php
// Handles cross-departmental request submissions from the dashboard modal.
session_start();

require_once '../includes/db.php';

/** @var PDO $pdo */
// ── Auth guard ──────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_role']) ||
    !in_array($_SESSION['user_role'], ['Department Manager', 'Engineering Manager'], true)) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit_dept_request'])) {
    header("Location: dashboard.php");
    exit();
}

// ── Pull from session ────────────────────────────────────────────────────────
$user_id        = (int)($_SESSION['user_id']   ?? 0);
$sender_dept_id = (int)($_SESSION['dept_id']   ?? 0);
$full_name      = $_SESSION['full_name']        ?? 'Unknown';
$user_role      = $_SESSION['user_role']        ?? 'Unknown';

if (!$user_id || !$sender_dept_id) {
    die("Session expired – please <a href='../auth/login.php'>login again</a>.");
}

// ── Sanitise form inputs ─────────────────────────────────────────────────────
$machine_name      = trim($_POST['machine_name']      ?? '');
$issue_description = trim($_POST['issue_description'] ?? '');
$priority          = $_POST['priority']    ?? 'Normal';
$task_type         = $_POST['task_type']   ?? 'Maintenance';
$request_type      = $_POST['request_type'] ?? 'Repair';
$receiver_dept_id  = (int)($_POST['receiver_dept_id'] ?? 0);

// Allowed enum values for safety
$allowed_priorities    = ['Normal', 'High', 'Emergency', 'Urgent'];
$allowed_task_types    = ['Maintenance', 'Administrative', 'Production', 'Quality',
                          'Breakdown', 'Safety', 'Planning', 'HR', 'Procurement',
                          'Finance', 'Reporting', 'Audit', 'Legal', 'Other'];
$allowed_request_types = ['Repair', 'Manpower', 'Resource', 'Legal', 'Maintenance',
                          'Administrative', 'Other'];

if (!in_array($priority,     $allowed_priorities,    true)) $priority     = 'Normal';
if (!in_array($task_type,    $allowed_task_types,    true)) $task_type    = 'Maintenance';
if (!in_array($request_type, $allowed_request_types, true)) $request_type = 'Other';

// Validate mandatory fields
if (empty($machine_name) || empty($issue_description) || $receiver_dept_id < 1) {
    $_SESSION['error'] = "All required fields must be filled in.";
    header("Location: dashboard.php");
    exit();
}

// Confirm receiver department actually exists
$dept_check = $pdo->prepare("SELECT dept_name FROM departments WHERE id = ?");
$dept_check->execute([$receiver_dept_id]);
$receiver_dept = $dept_check->fetch();

if (!$receiver_dept) {
    $_SESSION['error'] = "Invalid target department selected.";
    header("Location: dashboard.php");
    exit();
}

// ── DB insert ────────────────────────────────────────────────────────────────
try {
    $sql = "INSERT INTO maintenance_requests
                (user_id,
                 dept_id,
                 sender_dept_id,
                 receiver_dept_id,
                 machine_name,
                 issue_description,
                 priority,
                 status,
                 task_type,
                 request_type,
                 is_read_by_receiver,
                 created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, 0, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user_id,
        $sender_dept_id,   // legacy dept_id (sender's dept)
        $sender_dept_id,   // sender_dept_id (explicit)
        $receiver_dept_id,
        $machine_name,
        $issue_description,
        $priority,
        $task_type,
        $request_type,
    ]);

    $new_id = $pdo->lastInsertId();

    // ── Audit log ────────────────────────────────────────────────────────────
    $receiver_name = htmlspecialchars($receiver_dept['dept_name']);
    $log_detail = "$user_role ($full_name) submitted a cross-dept '$request_type' request "
                . "(ID #$new_id) to $receiver_name dept. "
                . "Subject: $machine_name | Priority: $priority.";

    log_action($pdo, $user_id, 'Cross-Dept Request Submitted', $log_detail);

    $_SESSION['success'] = "Your $request_type request was sent to the {$receiver_dept['dept_name']} Department successfully.";
    header("Location: dashboard.php?success=sent");
    exit();

} catch (PDOException $e) {
    error_log("process_maintenance PDO error: " . $e->getMessage());
    $_SESSION['error'] = "Database error – please try again.";
    header("Location: dashboard.php");
    exit();
}
