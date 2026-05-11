<?php
// bdtsc-ietms/includes/db.php

/**
 * Database Class
 * Handles system-wide database connections and logging using PDO
 */
class Database {
    // 1. የዳታቤዝ መረጃዎች (Private properties for security)
    private $host = 'localhost';
    private $db_name = 'bdtsc_db'; 
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    public $pdo;

    // 2. ሰዓቱን በቋሚነት ለማስተካከል (Constructor)
    public function __construct() {
        date_default_timezone_set('Africa/Addis_Ababa');
    }

    // 3. ከዳታቤዝ ጋር ግንኙነት መፍጠሪያ (Connection Method)
    public function connect() {
        $this->pdo = null;
        $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (\PDOException $e) {
            die("የዳታቤዝ ግንኙነት አልተሳካም፦ " . $e->getMessage());
        }

        return $this->pdo;
    }

    /**
     * Audit Log Method
     * በሲስተሙ ውስጥ የሚከናወኑ እንቅስቃሴዎችን ለመመዝገብ (Static method)
     *//**
     * Audit Log Method
     * @param PDO $pdo
     * @param int $user_id
     * @param string $action
     * @param string $details
     */
    public static function log_action(PDO $pdo, $user_id, $action, $details) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $ip]);
    }
}

// 4. አጠቃላይ ሲስተሙ እንዲጠቀምበት Instance መፍጠር
$database = new Database();
$pdo = $database->connect();
?>
