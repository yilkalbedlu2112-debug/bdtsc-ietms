<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

// 1. የደህንነት ማረጋገጫ
$allowed_roles = ['General Manager', 'Deputy General Manager'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $current_user_id = $_SESSION['user_id'];
    $success_flag = false; // ለኦዲት ሎግ የምንጠቀምባት ማረጋገጫ

    try {
        // --- ሀ. አዲስ ውክልና ለመስጠት ---
        if ($action === 'delegate_authority') {
            $delegate_to = $_POST['delegate_to'];
            $remark = $_POST['delegation_notes'] ?? '';

            if (empty($delegate_to)) {
                echo json_encode(['success' => false, 'message' => 'Receiver not selected']);
                exit();
            }

            // የቆየ ንቁ ውክልና ካለ መዝጋት
            $pdo->prepare("UPDATE delegations SET status = 'Cancelled' WHERE delegated_by = ? AND status = 'Active'")
                ->execute([$current_user_id]);

            // አዲስ መመዝገብ
            $stmt = $pdo->prepare("INSERT INTO delegations (delegated_by, delegated_to, remark, status, created_at) VALUES (?, ?, ?, 'Active', NOW())");
            $success_flag = $stmt->execute([$current_user_id, $delegate_to, $remark]);
            
            $audit_action = "Authority Delegation";
            $audit_details = "User (ID: $current_user_id) delegated authority to User (ID: $delegate_to). Note: $remark";
        } 
        
        // --- ለ. ውክልናን ለመሰረዝ ---
        elseif ($action === 'cancel_delegation') {
            $delegation_id = $_POST['delegation_id'] ?? null;
            
            if (!$delegation_id) {
                echo json_encode(['success' => false, 'message' => 'Delegation ID missing']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE delegations SET status = 'Cancelled' WHERE id = ?");
            $success_flag = $stmt->execute([$delegation_id]);

            $audit_action = "Authority Reclaimed";
            $audit_details = "User (ID: $current_user_id) reclaimed authority. Delegation ID: $delegation_id";
        }

        // --- ሐ. ድርጊቱ ከተሳካ ኦዲት ሎግ መመዝገብ ---
        if ($success_flag) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $log_stmt->execute([$current_user_id, $audit_action, $audit_details, $ip]);

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Operation failed']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}