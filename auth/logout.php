<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
// Audit: user logout
if (class_exists('Database') && method_exists('Database', 'log_system_activity')) {
    Database::log_system_activity($pdo, $uid, 'USER_LOGOUT', 'User logged out');
} elseif (function_exists('log_action')) {
    log_action($pdo, $uid, 'USER_LOGOUT', 'User logged out');
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();
header("Location: login.php");
exit();