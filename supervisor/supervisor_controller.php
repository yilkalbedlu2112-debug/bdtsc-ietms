<?php
/**
 * Industrial Employee Task Management System (IETMS)
 * Supervisor AJAX Controller Engine (Fixed ONLY_FULL_GROUP_BY & Enhanced Transaction Security)
 * Expected Graduation: July 2026
 */
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

header('Content-Type: application/json; charset=utf-8');
require_once '../includes/db.php';
require_once '../includes/functions.php';

/** @var PDO $pdo */
$user_id   = intval($_SESSION['user_id'] ?? 0);
$dept_id   = intval($_SESSION['dept_id'] ?? 0);
$dept_name = $_SESSION['dept_name'] ?? null;

function respond($status, $message, $data = []) {
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $data), JSON_UNESCAPED_UNICODE);
    exit();
}

function sanitize_text($value) {
    return trim(strip_tags((string)$value));
}

// 1. የደህንነት እና ሚና ማረጋገጫ (Authentication & Authorization)
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
    // 🔒 የአቶሚክ ባህሪን (ACID Principles) ለማረጋገጥ ትራንዛክሽን መጀመር
    $pdo->beginTransaction();

    // [ACTION: LIST REQUESTS]
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

    // [ACTION: CREATE TASK / REQUEST]
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
            $task_type, $user_id, $dept_id, $user_id, $title, $description,
            $title, $description, $priority, $dept_id, $receiver_dept_id,
            'Maintenance', $deadline, $deadline, $user_id
        ]);

        $new_task_id = $pdo->lastInsertId();
        
        // 🔒 የደህንነት ኦዲት መዝገብ ማስፈር
        $details = sprintf('task_id=%d; created_by=%d; dept_id=%d; title=%s; priority=%s', $new_task_id, $user_id, $dept_id, substr($title,0,200), $priority);
        if (class_exists('Database') && method_exists('Database', 'log_system_activity')) {
            Database::log_system_activity($pdo, $user_id, 'TASK_CREATED', $details);
        } else {
            log_action($pdo, $user_id, 'Create Task', "Created task #$new_task_id '$title' with priority $priority");
        }

        $pdo->commit();
        respond('success', 'Task created successfully.', ['task_id' => $new_task_id]);
    }

    // [ACTION: ASSIGN TASK]
    if ($action === 'assign_task') {
        $request_id = intval($_POST['request_id'] ?? 0);
        $assignee_id = intval($_POST['assigned_to'] ?? $_POST['shift_leader_id'] ?? 0);

        if ($request_id < 1 || $assignee_id < 1) {
            respond('error', 'Invalid request or assignee.');
        }

        $assignee = $pdo->prepare("SELECT id, full_name, user_role, dept_id FROM users WHERE id = ? AND status = 'Active'");
        $assignee->execute([$assignee_id]);
        $assignee = $assignee->fetch(PDO::FETCH_ASSOC);

        if (!$assignee) {
            respond('error', 'ተረካቢው በሲስተሙ ውስጥ አልተገኘም።');
        }

        $is_my_shift_leader = ($assignee['user_role'] === 'Shift Leader' && intval($assignee['dept_id']) === $dept_id);
        $is_engineering = ($assignee['user_role'] === 'Engineering Manager');

        if (!$is_my_shift_leader && !$is_engineering) {
            respond('error', 'ስራ መመደብ የሚችሉት ለዲፓርትመንትዎ ሽፍት ሊደር ወይም ለኢንጅነሪንግ ማናጀር ብቻ ነው።');
        }

        $check = $pdo->prepare("SELECT status FROM maintenance_requests WHERE id = ? AND (dept_id = ? OR sender_dept_id = ?)");
        $check->execute([$request_id, $dept_id, $dept_id]);
        $request = $check->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            respond('error', 'ጥያቄው አልተገኘም ወይም የመመደብ ስልጣን የሎትም።');
        }

        $update = $pdo->prepare(
            "UPDATE maintenance_requests 
             SET assigned_to = ?, supervisor_id = ?, status = 'Assigned', assigned_at = NOW(), updated_at = NOW() 
             WHERE id = ?"
        );
        $update->execute([$assignee_id, $user_id, $request_id]);

        // የ notifications ሰንጠረዥ 'is_read' አምድ እንዳለው በዳይናሚክ ማረጋገጥ
        $notifHasIsRead = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'is_read'"
        )->fetchColumn() > 0;

        $message = "አዲስ ስራ ተመድቦልዎታል፡ Request #$request_id";
        if ($notifHasIsRead) {
            $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type, is_read, created_at) VALUES (?, ?, ?, 'Task Assignment', 0, NOW())")
                ->execute([$assignee_id, $assignee['dept_id'], $message]);
        } else {
            $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type, created_at) VALUES (?, ?, ?, 'Task Assignment', NOW())")
                ->execute([$assignee_id, $assignee['dept_id'], $message]);
        }

        $details = sprintf('task_id=%d; assigned_to=%d; assigned_by=%d', $request_id, $assignee_id, $user_id);
        if (class_exists('Database') && method_exists('Database', 'log_system_activity')) {
            Database::log_system_activity($pdo, $user_id, 'TASK_ASSIGNED', $details);
        } else {
            log_action($pdo, $user_id, 'Assign Task', "Assigned task #$request_id to user #$assignee_id");
        }

        $pdo->commit();
        respond('success', 'ስራው በተሳካ ሁኔታ ተመድቧል!');
    }

    // [ACTION: DELEGATE AUTHORITY]
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

        $notifHasIsRead = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'is_read'"
        )->fetchColumn() > 0;

        if ($notifHasIsRead) {
            $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type, is_read, created_at) VALUES (?, ?, ?, 'Delegation', 0, NOW())")
                ->execute([$delegate_to, $dept_id, $notification]);
        } else {
            $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type, created_at) VALUES (?, ?, ?, 'Delegation', NOW())")
                ->execute([$delegate_to, $dept_id, $notification]);
        }

        $details = sprintf('delegated_by=%d; delegated_to=%d; notes=%s', $user_id, $delegate_to, substr($delegation_notes,0,200));
        if (class_exists('Database') && method_exists('Database', 'log_system_activity')) {
            Database::log_system_activity($pdo, $user_id, 'AUTHORITY_DELEGATED', $details);
        } else {
            log_action($pdo, $user_id, 'Delegate Authority', "Delegated authority to user #$delegate_to");
        }

        $pdo->commit();
        respond('success', 'Delegation recorded and notification sent.');
    }

    // [ACTION: GENERATE REPORT — FIXED ONLY_FULL_GROUP_BY SCRIPT]
    if ($action === 'generate_report') {
        $period = sanitize_text($_POST['period'] ?? 'daily');
        $summary_note = sanitize_text($_POST['summary_note'] ?? 'ምንም ተጨማሪ ማብራሪያ አልተሰጠም።');

        $days = ($period === 'monthly') ? 30 : (($period === 'weekly') ? 7 : 1);

        // ✅ ማስተካከያ፡ 'GROUP BY d.dept_name' በማከል የ MySQL Strict Mode ህግን ሙሉ በሙሉ አሟልተናል!
        $report_stmt = $pdo->prepare(
            "SELECT d.dept_name,
                    COUNT(mr.id) AS total_requests,
                    SUM(CASE WHEN mr.status = 'Completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN mr.status IN ('Pending','Assigned','In Progress','Under Repair') THEN 1 ELSE 0 END) AS active
             FROM maintenance_requests mr
             LEFT JOIN departments d ON mr.sender_dept_id = d.id
             WHERE mr.sender_dept_id = ? 
               AND mr.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY d.dept_name"
        );
        $report_stmt->execute([$dept_id, $days]);
        $data = $report_stmt->fetch(PDO::FETCH_ASSOC);

        $stats_text = sprintf(
            "ስራዎች፡ ጠቅላላ (%d), የተጠናቀቁ (%d), ገና ያልተጠናቀቁ (%d)",
            $data['total_requests'] ?? 0, $data['completed'] ?? 0, $data['active'] ?? 0
        );
        
        $final_summary = "[$period ሪፖርት] " . $stats_text . " | ተጨማሪ ማስታወሻ፡ " . $summary_note;

        // የ ማናጀር ID በሲስተም መፈለግ (ካለ በቀጥታ ለሱ እንዲደርሰው)
        $mgrStmt = $pdo->prepare("SELECT id FROM users WHERE dept_id = ? AND user_role = 'Department Manager' AND status = 'Active' LIMIT 1");
        $mgrStmt->execute([$dept_id]);
        $manager_id = (int) $mgrStmt->fetchColumn();

        $notifHasIsRead = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'is_read'"
        )->fetchColumn() > 0;

        if ($manager_id > 0) {
            if ($notifHasIsRead) {
                $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type, is_read, created_at) VALUES (?, ?, ?, 'Generate Report', 0, NOW())");
                $notif_stmt->execute([$manager_id, $dept_id, $final_summary]);
            } else {
                $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type, created_at) VALUES (?, ?, ?, 'Generate Report', NOW())");
                $notif_stmt->execute([$manager_id, $dept_id, $final_summary]);
            }
        } else {
            // ማናጀር በተለየ ካልተገኘ ሚናውን መሠረት አድርጎ መላክ
            if ($notifHasIsRead) {
                $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_role, dept_id, message, type, is_read, created_at) VALUES ('Department Manager', ?, ?, 'Generate Report', 0, NOW())");
            } else {
                $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_role, dept_id, message, type, created_at) VALUES ('Department Manager', ?, ?, 'Generate Report', NOW())");
            }
            $notif_stmt->execute([$dept_id, $final_summary]);
        }

        $details = sprintf('dept_id=%d; period=%s; generated_by=%d; note=%s', $dept_id, $period, $user_id, substr($summary_note,0,200));
        if (class_exists('Database') && method_exists('Database', 'log_system_activity')) {
            Database::log_system_activity($pdo, $user_id, 'REPORT_GENERATED', $details);
        } else {
            log_action($pdo, $user_id, 'Generate Report', "Generated $period report for department manager. Note: $summary_note");
        }
        
        $pdo->commit();
        respond('success', 'ሪፖርቱ ለዲፓርትመንት ማናጀርዎ በተሳካ ሁኔታ ተልኳል።', ['summary' => $final_summary]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) { 
        $pdo->rollBack(); 
    }
    respond('error', 'An error occurred: ' . $e->getMessage());
}