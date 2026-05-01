<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Security Check (BR-01)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Supervisor') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$dept_id = $_SESSION['dept_id'];
$response = ['status' => 'error', 'message' => 'Invalid action.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        $pdo->beginTransaction();

        // --- 1. Create New Maintenance Request (UC-06) ---
        if ($action === 'create_request') {
            $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
            $desc = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
            $machine = filter_var($_POST['machine_name'], FILTER_SANITIZE_STRING);

            $stmt = $pdo->prepare("INSERT INTO maintenance_requests (machine_name, title, issue_description, dept_id, status, created_by) VALUES (?, ?, ?, ?, 'Pending', ?)");
            $stmt->execute([$machine, $title, $desc, $dept_id, $user_id]);
            
            log_action($pdo, $user_id, "Create Request", "Created request for machine: $machine");
            $response = ['status' => 'success', 'message' => 'ጥያቄው በተሳካ ሁኔታ ተመዝግቧል!'];
        }

        // --- 2. Assign Task to Employee/Shift Leader (UC-07) ---
        elseif ($action === 'assign_task') {
            $request_id = filter_var($_POST['request_id'], FILTER_SANITIZE_NUMBER_INT);
            $assignee_id = filter_var($_POST['assigned_to'], FILTER_SANITIZE_NUMBER_INT);
            $deadline = $_POST['deadline'];

            // በ 'task' ሰንጠረዥ ላይ ስራውን መመዝገብ
            $task_stmt = $pdo->prepare("INSERT INTO task (request_id, assigned_to, deadline, status) VALUES (?, ?, ?, 'Assigned')");
            $task_stmt->execute([$request_id, $assignee_id, $deadline]);

            // የጥያቄውን ሁኔታ ማዘመን (Update status in maintenance_requests)
            $update_stmt = $pdo->prepare("UPDATE maintenance_requests SET status = 'In Progress' WHERE id = ? AND dept_id = ?");
            $update_stmt->execute([$request_id, $dept_id]);

            // ኖቲፊኬሽን መላክ
            $notif_msg = "አዲስ ስራ ተመድቦልዎታል። መለያ ቁጥር፡ #$request_id";
            $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'New Task')")->execute([$assignee_id, $notif_msg]);

            log_action($pdo, $user_id, "Assign Task", "Assigned Task #$request_id to User ID: $assignee_id");
            $response = ['status' => 'success', 'message' => 'ስራው ለባለሙያው ተመድቧል!'];
        }

        // --- 3. Generate & Send Performance Report to Manager ---
        elseif ($action === 'send_report') {
            $period = $_POST['period']; // 'weekly' or 'monthly'
            $days = ($period === 'weekly') ? 7 : 30;

            // JOIN Query: የጥያቄዎችን እና የተሰሩ ስራዎችን ሁኔታ ማጠቃለል
            $sql = "SELECT COUNT(t.id) as total_tasks, 
                           SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed,
                           SUM(CASE WHEN t.deadline < NOW() AND t.status != 'Completed' THEN 1 ELSE 0 END) as delayed
                    FROM task t
                    JOIN maintenance_requests mr ON t.request_id = mr.id
                    WHERE mr.dept_id = ? AND mr.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $report_stmt = $pdo->prepare($sql);
            $report_stmt->execute([$dept_id, $days]);
            $data = $report_stmt->fetch();

            $msg = "የ$period ሪፖርት ማጠቃለያ ለዲፓርትመንት $dept_id: ጠቅላላ ስራዎች: {$data['total_tasks']}, የተጠናቀቁ: {$data['completed']}, የዘገዩ: {$data['delayed']}";

            // ለManager ኖቲፊኬሽን መላክ
            $send_notif = $pdo->prepare("INSERT INTO notifications (user_role, dept_id, message, type) VALUES ('Manager', ?, ?, 'Performance Report')");
            $send_notif->execute([$dept_id, $msg]);

            log_action($pdo, $user_id, "Send Report", "Sent $period performance report to manager");
            $response = ['status' => 'success', 'message' => 'ሪፖርቱ ለማናጀሩ ተልኳል!'];
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $response = ['status' => 'error', 'message' => 'ስህተት፡ ' . $e->getMessage()];
    }
}

echo json_encode($response);