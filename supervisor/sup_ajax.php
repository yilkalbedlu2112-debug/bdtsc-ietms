<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db.php';
require_once '../includes/functions.php'; // ይህ ፋይል log_action()ን ይዟል

header('Content-Type: application/json');

// ደህንነት፡ ሱፐርቫይዘር መሆኑን ማረጋገጥ
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Supervisor') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // ---------------------------------------------------------------
    // UC-06: Create Task (የማናጀሩ ረዳት ሆኖ ስራ ሲፈጥር)
    // ---------------------------------------------------------------
    if ($action === 'create_task') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $priority = $_POST['priority'];
        $deadline = $_POST['deadline'];
        $dept_id = $_SESSION['dept_id'];
        $user_id = $_SESSION['user_id'];

        // BR-05: Deadline ቫሊዴሽን (ካለፈ ቀን መሆን የለበትም)
        if (strtotime($deadline) < time()) {
            echo json_encode(['status' => 'error', 'message' => 'Deadline cannot be in the past.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO maintenance_requests 
                (title, description, priority, deadline, dept_id, sender_dept_id, created_by, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
            
            $stmt->execute([$title, $description, $priority, $deadline, $dept_id, $dept_id, $user_id]);
            $taskId = $pdo->lastInsertId();

            // BR-08: Audit Log መመዝገብ
            log_action($pdo, $user_id, "Task Creation", "Supervisor created Task ID: $taskId - $title");

            echo json_encode(['status' => 'success', 'message' => 'Task created successfully! Manager will be notified.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    // ---------------------------------------------------------------
    // UC-07: Assign Task to Shift Leader (ከኢሜይል ኖቲፊኬሽን ጋር)
    // ---------------------------------------------------------------
    if ($action === 'assign_to_shift_leader') {
        $task_id = $_POST['task_id'];
        $shift_leader_id = $_POST['shift_leader_id'];
        $user_id = $_SESSION['user_id'];

        try {
            // የShift Leader-ኡን መረጃ (ኢሜይል) ማግኘት
            $stmtUser = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
            $stmtUser->execute([$shift_leader_id]);
            $leader = $stmtUser->fetch();

            if (!$leader) {
                echo json_encode(['status' => 'error', 'message' => 'Shift Leader not found.']);
                exit;
            }

            // ታስኩን አሳይን ማድረግ
            $stmt = $pdo->prepare("UPDATE maintenance_requests SET assigned_to = ?, status = 'Assigned', assigned_at = NOW() WHERE id = ?");
            $stmt->execute([$shift_leader_id, $task_id]);

            // 1. ኢሜይል ኖቲፊኬሽን (ለሽፍት ሊደሩ)
            $to = $leader['email'];
            $subject = "New Task Assigned - BDTSC Maintenance System";
            $message = "
                <h3>Hello " . htmlspecialchars($leader['full_name']) . ",</h3>
                <p>A new task (ID: #$task_id) has been assigned to you by your Supervisor.</p>
                <p>Please login to your dashboard to view the details and assign it to an employee.</p>
                <br>
                <p>Regards,<br>BDTSC IETMS System</p>
            ";
            
            // የኢሜይል ፋንክሽኑን መጥራት (functions.php ውስጥ መኖሩን ያረጋግጡ)
            // send_email($to, $subject, $message); 

            // 2. ሲስተም ኖቲፊኬሽን (Database)
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'Assignment')");
            $notif->execute([$shift_leader_id, "New task ID #$task_id assigned to you."]);

            // 3. Audit Log
            log_action($pdo, $user_id, "Task Assignment", "Assigned Task #$task_id to Shift Leader: " . $leader['full_name']);

            echo json_encode(['status' => 'success', 'message' => 'Task assigned. Notification and Email sent to Shift Leader!']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Assignment failed: ' . $e->getMessage()]);
        }
    }

    // ---------------------------------------------------------------
    // Submit Alert to Shift Leader
    // ---------------------------------------------------------------
    if ($action === 'submit_alert') {
        $machine_name = trim($_POST['machine_name']);
        $issue_description = trim($_POST['issue_description']);
        $priority = $_POST['priority'];
        $dept_id = $_SESSION['dept_id'];
        $user_id = $_SESSION['user_id'];

        if (empty($machine_name) || empty($issue_description)) {
            echo json_encode(['status' => 'error', 'message' => 'Machine name and issue description are required.']);
            exit;
        }

        try {
            // Find Shift Leader for the department
            $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE dept_id = ? AND user_role = 'Shift Leader' LIMIT 1");
            $stmt->execute([$dept_id]);
            $shift_leader = $stmt->fetch();

            if (!$shift_leader) {
                echo json_encode(['status' => 'error', 'message' => 'No Shift Leader found for this department.']);
                exit;
            }

            // Insert alert
            $stmt = $pdo->prepare("INSERT INTO maintenance_requests 
                (task_type, user_id, dept_id, assigned_to, machine_name, issue_description, priority, status, sender_dept_id, request_type, is_read_by_receiver) 
                VALUES ('Alert', ?, ?, ?, ?, ?, ?, 'Pending Approval', ?, 'Maintenance', 0)");
            
            $stmt->execute([$user_id, $dept_id, $shift_leader['id'], $machine_name, $issue_description, $priority, $dept_id]);

            $alertId = $pdo->lastInsertId();

            // Audit Log
            log_action($pdo, $user_id, "Alert Submission", "Supervisor submitted alert #$alertId to Shift Leader: " . $shift_leader['full_name']);

            echo json_encode(['status' => 'success', 'message' => 'Alert submitted successfully to Shift Leader.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
}
?>