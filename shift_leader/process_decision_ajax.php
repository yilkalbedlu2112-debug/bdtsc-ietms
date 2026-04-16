<?php
session_start();
// JSON ብቻ ስለምንመልስ header('Content-Type: application/json') ከላይ መሆኑ ትክክል ነው
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php'; // log_action እንዲሰራ

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Shift Leader') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// ማሳሰቢያ፡ እዚህ ጋር include '../includes/header_glass.php'; አያስፈልግም (JSON ስለሆነ)

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['req_id'])) {
    $req_id = $_POST['req_id'];
    $severity = $_POST['severity'] ?? 'Low';
    $action = $_POST['action'] ?? ''; 
    $dept_id = $_SESSION['dept_id'];
    $user_id = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        if ($action === 'engineering') {
            $status = 'Sent to Engineering';
            // በዳታቤዝህ ኮለምን ስም መሰረት (assigned_to_dept ወይም priority) ማስተካከልህን አረጋግጥ
            $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = ?, priority = ?, assigned_to_dept = 'Engineering' WHERE id = ? AND dept_id = ?");
            $stmt->execute([$status, $severity, $req_id, $dept_id]);

            $msg = "ከ Shift Leader አዲስ የጥገና ጥያቄ ቀርቧል። (Machine ID: $req_id)";
            $notif = $pdo->prepare("INSERT INTO notifications (user_role, message, type) VALUES ('Engineering Manager', ?, 'new_request')");
            $notif->execute([$msg]);
            
            log_action($pdo, $user_id, "Escalate to Engineering", "Task #$req_id sent to Engineering with $severity severity");

        } elseif ($action === 'manager') {
            $status = 'Escalated to Manager';
            $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = ?, priority = ?, assigned_to_dept = 'Internal Manager' WHERE id = ? AND dept_id = ?");
            $stmt->execute([$status, $severity, $req_id, $dept_id]);

            $msg = "ከባድ ብልሽት ስለገጠመ ውሳኔ ይፈለጋል። (Request ID: $req_id)";
            $notif = $pdo->prepare("INSERT INTO notifications (dept_id, user_role, message) VALUES (?, 'Department Manager', ?)");
            $notif->execute([$dept_id, $msg]);

            log_action($pdo, $user_id, "Escalate to Manager", "Task #$req_id escalated to Dept Manager");
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'ውሳኔው በተሳካ ሁኔታ ተመዝግቧል።']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
