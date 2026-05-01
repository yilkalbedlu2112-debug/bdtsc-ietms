<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';

$user_id = intval($_SESSION['user_id'] ?? 0);
$dept_id = intval($_SESSION['dept_id'] ?? 0);
$dept_name = $_SESSION['dept_name'] ?? null;

function respond($status, $message, $data = []) {
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $data));
    exit();
}

function sanitize_text($value) {
    return trim(strip_tags((string)$value));
}

if (!$user_id || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Supervisor') {
    respond('error', 'Unauthorized access.');
}

$production_depts = [3, 8, 9, 10];
if (!in_array($dept_id, $production_depts, true)) {
    respond('error', 'This area is reserved for Production Supervisors.');
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;
if (!$action) {
    respond('error', 'No action specified.');
}

try {
    $pdo->beginTransaction();

    if ($action === 'list_requests') {
        $stmt = $pdo->prepare(
            "SELECT mr.*, d.dept_name AS source_dept, u.full_name AS assigned_to_name, su.full_name AS supervisor_name
             FROM maintenance_requests mr
             LEFT JOIN departments d ON mr.sender_dept_id = d.id
             LEFT JOIN users u ON mr.assigned_to = u.id
             LEFT JOIN users su ON mr.supervisor_id = su.id
             WHERE (mr.dept_id = ? OR mr.sender_dept_id = ?)
             ORDER BY mr.created_at DESC"
        );
        $stmt->execute([$dept_id, $dept_id]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pdo->commit();
        respond('success', 'Requests loaded.', ['requests' => $requests]);
    }

    if ($action === 'create_task' || $action === 'create_request') {
        $title = sanitize_text($_POST['title'] ?? $_POST['machine_name'] ?? '');
        $description = sanitize_text($_POST['description'] ?? $_POST['issue_description'] ?? '');
        $priority = sanitize_text($_POST['priority'] ?? 'Normal');
        $task_type = sanitize_text($_POST['task_type'] ?? 'Maintenance');
        $deadline_raw = trim($_POST['deadline'] ?? $_POST['due_date'] ?? '');
        $receiver_dept_id = intval($_POST['receiver_dept_id'] ?? $dept_id);

        if ($title === '' || $description === '') {
            respond('error', 'Title and description are required.');
        }

        $allowed_priorities = ['Emergency', 'High', 'Normal', 'Medium', 'Low'];
        if (!in_array($priority, $allowed_priorities, true)) {
            $priority = 'Normal';
        }

        $deadline = null;
        if ($deadline_raw !== '') {
            $deadline_date = date_create($deadline_raw);
            if (!$deadline_date) {
                respond('error', 'Invalid deadline format.');
            }
            $now = new DateTime('now');
            if ($deadline_date < $now) {
                respond('error', 'Deadline cannot be in the past.');
            }
            $deadline = $deadline_date->format('Y-m-d H:i:s');
        }

        $insert = $pdo->prepare(
            "INSERT INTO maintenance_requests 
             (task_type, user_id, dept_id, supervisor_id, title, description, machine_name, issue_description, priority, status, sender_dept_id, receiver_dept_id, request_type, due_date, deadline, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?, ?, ?, NOW())"
        );
        $insert->execute([
            $task_type,
            $user_id,
            $dept_id,
            $user_id,
            $title,
            $description,
            $title,
            $description,
            $priority,
            $dept_id,
            $receiver_dept_id,
            'Maintenance',
            $deadline,
            $deadline,
            $user_id,
        ]);

        $new_task_id = $pdo->lastInsertId();
        log_action($pdo, $user_id, 'Create Task', "Created task #$new_task_id '$title' with priority $priority" . ($deadline ? " and deadline $deadline" : ''));
        $pdo->commit();
        respond('success', 'Task created successfully.', ['task_id' => $new_task_id]);
    }

    if ($action === 'assign_task') {
        $request_id = intval($_POST['request_id'] ?? 0);
        $assignee_id = intval($_POST['assigned_to'] ?? $_POST['shift_leader_id'] ?? 0);

        if ($request_id < 1 || $assignee_id < 1) {
            respond('error', 'Invalid request or assignee.');
        }

        $assignee = $pdo->prepare("SELECT id, full_name, user_role, dept_id FROM users WHERE id = ? AND status = 'Active'");
        $assignee->execute([$assignee_id]);
        $assignee = $assignee->fetch(PDO::FETCH_ASSOC);

        if (!$assignee || !in_array($assignee['user_role'], ['Shift Leader', 'Technician'], true) || $assignee['dept_id'] !== $dept_id) {
            respond('error', 'Assignee must be an active Shift Leader or Technician in your production department.');
        }

        $check = $pdo->prepare("SELECT status FROM maintenance_requests WHERE id = ? AND (dept_id = ? OR sender_dept_id = ?)");
        $check->execute([$request_id, $dept_id, $dept_id]);
        $request = $check->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            respond('error', 'Request not found or cannot be assigned.');
        }

        if ($request['status'] !== 'Pending') {
            respond('error', 'Task already assigned or not pending.');
        }

        $update = $pdo->prepare(
            "UPDATE maintenance_requests
             SET assigned_to = ?, supervisor_id = ?, status = 'Assigned', assigned_to_dept = 'Internal', assigned_at = NOW(), updated_at = NOW()
             WHERE id = ?"
        );
        $update->execute([$assignee_id, $user_id, $request_id]);

        $message = "Task #$request_id has been assigned to you.";
        $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type, created_at) VALUES (?, ?, ?, 'Task Assignment', NOW())")
            ->execute([$assignee_id, $dept_id, $message]);

        log_action($pdo, $user_id, 'Assign Task', "Assigned task #$request_id to user #$assignee_id");
        $pdo->commit();
        respond('success', 'Task assigned successfully.');
    }

    if ($action === 'delegate_authority') {
        $delegate_to = intval($_POST['delegate_to'] ?? 0);
        $delegation_notes = sanitize_text($_POST['delegation_notes'] ?? '');

        if ($delegate_to < 1) {
            respond('error', 'Please select a shift leader to delegate authority to.');
        }

        $delegate = $pdo->prepare("SELECT id, full_name, user_role, dept_id FROM users WHERE id = ? AND status = 'Active'");
        $delegate->execute([$delegate_to]);
        $delegate = $delegate->fetch(PDO::FETCH_ASSOC);

        if (!$delegate || $delegate['user_role'] !== 'Shift Leader' || $delegate['dept_id'] !== $dept_id) {
            respond('error', 'Delegate must be an active Shift Leader in your department.');
        }

        $notification = "You have been delegated temporary supervisory authority by Supervisor {$_SESSION['full_name']}";
        if ($delegation_notes !== '') {
            $notification .= ". Notes: $delegation_notes";
        }

        $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type, created_at) VALUES (?, ?, ?, 'Delegation', NOW())")
            ->execute([$delegate_to, $dept_id, $notification]);

        log_action($pdo, $user_id, 'Delegate Authority', "Delegated authority to user #$delegate_to ({$delegate['full_name']})" . ($delegation_notes ? " with notes: $delegation_notes" : ''));
        $pdo->commit();
        respond('success', 'Delegation recorded and notification sent.');
    }

    if ($action === 'generate_report') {
        $period = sanitize_text($_POST['period'] ?? 'weekly');
        if ($period === 'daily') {
            $days = 1;
        } elseif ($period === 'monthly') {
            $days = 30;
        } else {
            $days = 7;
        }

        $report_stmt = $pdo->prepare(
            "SELECT d.dept_name AS source_dept,
                    COUNT(mr.id) AS request_count,
                    COUNT(DISTINCT mr.machine_name) AS machine_count,
                    SUM(CASE WHEN mr.status = 'Completed' THEN 1 ELSE 0 END) AS completed_count,
                    SUM(CASE WHEN mr.status IN ('Pending','Assigned','In Progress','Under Repair') THEN 1 ELSE 0 END) AS open_count
             FROM maintenance_requests mr
             LEFT JOIN departments d ON mr.sender_dept_id = d.id
             WHERE mr.sender_dept_id IN (8,9,10,3)
               AND mr.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY mr.sender_dept_id
             ORDER BY FIELD(mr.sender_dept_id, 8,9,10,3)"
        );
        $report_stmt->execute([$days]);
        $rows = $report_stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            respond('success', 'No requests found for the selected period.', ['report' => []]);
        }

        $report_lines = [];
        foreach ($rows as $row) {
            $report_lines[] = sprintf(
                "%s: %s requests, %s machines, %s completed, %s open",
                $row['source_dept'], $row['request_count'], $row['machine_count'], $row['completed_count'], $row['open_count']
            );
        }

        $summary = "General report ({$period}): " . implode(' | ', $report_lines);
        $pdo->prepare("INSERT INTO notifications (user_role, dept_id, message, type, created_at) VALUES ('Department Manager', ?, ?, 'Supervisor Report', NOW())")
            ->execute([$dept_id, $summary]);

        log_action($pdo, $user_id, 'Generate Report', "Generated {$period} supervisor report for manager.");
        $pdo->commit();
        respond('success', 'Report generated and sent to manager.', ['report' => $rows, 'summary' => $summary]);
    }

    $pdo->commit();
    respond('error', 'Unknown action.');
} catch (Exception $e) {
    $pdo->rollBack();
    respond('error', 'Server error: ' . $e->getMessage());
}
