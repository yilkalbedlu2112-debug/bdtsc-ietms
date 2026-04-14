<?php
// bdtsc-ietms/includes/db.php
// 1. የኢትዮጵያን ሰዓት በቋሚነት ለመጠቀም
date_default_timezone_set('Africa/Addis_Ababa');
$host = 'localhost';
$db   = 'bdtsc_db'; // በ phpMyAdmin የፈጠርከው ዳታቤዝ ስም
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Database connection failed: " . $e->getMessage());
}
function log_action($pdo, $user_id, $action, $details) {
    $ip = $_SERVER['REMOTE_ADDR']; // የኮምፒውተሩን አድራሻ ለመያዝ
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $details, $ip]);
}
?>