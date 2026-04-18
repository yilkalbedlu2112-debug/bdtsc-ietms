<?php
session_start();
header('Content-Type: application/json'); // ለዳሽቦርዱ በ JSON እንዲመልስ
require_once '../includes/db.php';
require_once '../includes/functions.php';

// 1. Authorization Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['Department Manager', 'Shift Leader', 'Supervisor'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// 2. ዳታው መላኩን ማረጋገጥ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ከሁለቱም መንገድ (Form data ወይም URLSearchParams) ዳታ መቀበል እንዲችል
    $task_id = $_POST['task_id'] ?? null;
    $employee_id = $_POST['employee_id'] ?? null;
    $sl_id = $_SESSION['user_id'];
    $dept_id = $_SESSION['dept_id'];

    if (!$task_id || !$employee_id) {
        echo json_encode(['success' => false, 'message' => 'Missing Task ID or Employee ID.']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // ሀ. የታስኩን ሁኔታ ማዘመን (BR-04) - ዲፓርትመንቱ መመሳሰሉን በድጋሚ እናረጋግጣለን
        $stmt = $pdo->prepare("UPDATE maintenance_requests SET assigned_to = ?, status = 'Assigned', assigned_at = NOW() WHERE id = ? AND dept_id = ?");
        $stmt->execute([$employee_id, $task_id, $dept_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("ታስኩ አልተገኘም ወይም የእርስዎ ዲፓርትመንት አይደለም።");
        }

        // ለ. ለሰራተኛው ሲስተም ኖቲፊኬሽን መላክ (System Notification - Extended UC)
        $notif_msg = "አዲስ የጥገና ስራ ተሰጥቶዎታል (Task ID: #$task_id)።";
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'Task Assignment', NOW())");
        $notif->execute([$employee_id, $notif_msg]);

        // ሐ. ኦዲት ሎግ መመዝገብ (BR-08)
        if (function_exists('log_action')) {
            log_action($pdo, $sl_id, "Assign Task", "Shift Leader assigned task #$task_id to employee ID: $employee_id");
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'ስራው ለሰራተኛው በተሳካ ሁኔታ ተሰጥቷል።']);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'ስህተት፦ ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}