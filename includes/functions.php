<?php
// functions.php መስመር 2 አካባቢ
if (!function_exists('log_action')) {
    function log_action($pdo, $user_id, $action, $details) {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $action, $details]);
    }
}
?>