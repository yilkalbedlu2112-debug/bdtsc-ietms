<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';

    if (empty($current) || empty($new)) {
        echo json_encode(['success' => false, 'error' => 'All fields are required.']);
        exit();
    }

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user && password_verify($current, $user['password'])) {
        // Hash and Update
        $hashed = password_hash($new, PASSWORD_BCRYPT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        
        if ($update->execute([$hashed, $_SESSION['user_id']])) {
            log_action($pdo, $_SESSION['user_id'], 'Security', 'User updated their password via Profile Manager.');
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Current password is incorrect.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid Request']);
}
