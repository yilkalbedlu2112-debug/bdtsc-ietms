<?php
function log_action($pdo, $user_id, $action, $details) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $details, $ip]);
}
?>