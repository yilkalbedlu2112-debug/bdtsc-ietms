<?php
// functions.php መስመር 2 አካባቢ
if (!function_exists('log_action')) {
    function log_action($pdo, $user_id, $action, $details) {
        if (class_exists('Database') && method_exists('Database', 'log_system_activity')) {
            return Database::log_system_activity($pdo, $user_id, $action, $details);
        }
        // Fallback simple insert
        try {
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
            return $stmt->execute([$user_id, $action, $details]);
        } catch (Throwable $e) {
            error_log('fallback log_action failed: ' . $e->getMessage());
            return false;
        }
    }
}
?>