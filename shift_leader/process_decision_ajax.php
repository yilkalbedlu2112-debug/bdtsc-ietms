<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Shift Leader') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
include '../includes/header_glass.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['req_id'])) {
    $req_id = $_POST['req_id'];
    $severity = $_POST['severity'] ?? 'Low';
    $action = $_POST['action'] ?? ''; // 'engineering' or 'manager'
    $dept_id = $_SESSION['dept_id'];

    try {
        $pdo->beginTransaction();

        if ($action === 'engineering') {
            $status = 'Sent to Engineering';
            $assigned_dept = 'Engineering';
            
            $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = ?, severity = ?, assigned_to_dept = ? WHERE id = ? AND dept_id = ?");
            $stmt->execute([$status, $severity, $assigned_dept, $req_id, $dept_id]);

            $msg = "ከ Shift Leader አዲስ የጥገና ጥያቄ ቀርቧል። (Machine ID: $req_id)";
            $notif = $pdo->prepare("INSERT INTO notifications (user_role, message, type) VALUES ('Engineering Manager', ?, 'new_request')");
            $notif->execute([$msg]);

        } elseif ($action === 'manager') {
            $status = 'Escalated to Manager';
            $assigned_dept = 'Internal Manager';

            $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = ?, severity = ?, assigned_to_dept = ? WHERE id = ? AND dept_id = ?");
            $stmt->execute([$status, $severity, $assigned_dept, $req_id, $dept_id]);

            $msg = "ከባድ ብልሽት ስለገጠመ ውሳኔ ይፈለጋል። (Request ID: $req_id)";
            $notif = $pdo->prepare("INSERT INTO notifications (dept_id, user_role, message) VALUES (?, 'Department Manager', ?)");
            $notif->execute([$dept_id, $msg]);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Decision recorded successfully.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>
