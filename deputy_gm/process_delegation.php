<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ሀ. ውክልና ለመስጠት (Confirm Delegation)
    if ($action === 'delegate_authority') {
        $delegate_to = $_POST['delegate_to'];
        $remark = $_POST['delegation_notes'];
        $delegated_by = $_SESSION['user_id'];

        // መጀመሪያ የነበረ ንቁ ውክልና ካለ ወደ 'Cancelled' ቀይረው
        $pdo->prepare("UPDATE delegations SET status = 'Cancelled' WHERE delegated_by = ? AND status = 'Active'")->execute([$delegated_by]);

        // አዲሱን መመዝገብ
        $stmt = $pdo->prepare("INSERT INTO delegations (delegated_by, delegated_to, remark, status) VALUES (?, ?, ?, 'Active')");
        if ($stmt->execute([$delegated_by, $delegate_to, $remark])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    }

    // ለ. ውክልናን ለመሰረዝ (Cancel Delegation)
    if ($action === 'cancel_delegation') {
        $id = $_POST['delegation_id'];
        $stmt = $pdo->prepare("UPDATE delegations SET status = 'Cancelled' WHERE id = ?");
        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }
    exit;
}