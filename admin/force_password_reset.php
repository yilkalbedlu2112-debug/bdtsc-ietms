<?php
session_start();
require_once '../includes/db.php';

// Check if user is GM
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'General Manager') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['force_reset_btn'])) {
    $target_user_id = $_POST['user_id'];
    $new_password = $_POST['new_password'];
    
    // Hash password securely
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Process update
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    if ($stmt->execute([$hashed_password, $target_user_id])) {
        log_action($pdo, $_SESSION['user_id'], "Force Password Reset", "GM naturally reset password for User ID: $target_user_id");
        $_SESSION['success_msg'] = "Password successfully force-reset!";
    } else {
        $_SESSION['error_msg'] = "An error occurred while resetting the password.";
    }
    header("Location: manage_users.php");
    exit();
}
header("Location: manage_users.php");
exit();
