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

    /**
     * Robust system activity logger — safe, non-throwing and captures client IP.
     * Returns true on success, false on failure.
     */
    public static function log_system_activity(PDO $pdo, $user_id, string $action, ?string $details = null): bool {
        try {
            $server = $_SERVER ?? [];
            $candidates = [];
            if (!empty($server['HTTP_X_FORWARDED_FOR'])) {
                foreach (explode(',', $server['HTTP_X_FORWARDED_FOR']) as $ip) { $candidates[] = trim($ip); }
            }
            if (!empty($server['HTTP_X_REAL_IP'])) { $candidates[] = trim($server['HTTP_X_REAL_IP']); }
            if (!empty($server['HTTP_CLIENT_IP'])) { $candidates[] = trim($server['HTTP_CLIENT_IP']); }
            if (!empty($server['REMOTE_ADDR'])) { $candidates[] = trim($server['REMOTE_ADDR']); }

            $ip = null;
            foreach ($candidates as $candidate) {
                if (filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    $ip = $candidate; break;
                }
            }
            if ($ip === null) {
                foreach ($candidates as $candidate) {
                    if (filter_var($candidate, FILTER_VALIDATE_IP)) { $ip = $candidate; break; }
                }
            }

            $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (:user_id, :action, :details, :ip)');
            $stmt->bindValue(':user_id', $user_id === null ? null : (int)$user_id, $user_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':action', mb_substr($action, 0, 255), PDO::PARAM_STR);
            $stmt->bindValue(':details', $details !== null ? mb_substr($details, 0, 2000) : null, PDO::PARAM_STR);
            $stmt->bindValue(':ip', $ip !== null ? $ip : null, PDO::PARAM_STR);
            $stmt->execute();
            return true;
        } catch (Throwable $e) {
            error_log('log_system_activity failed: ' . $e->getMessage());
            return false;
        }
    }
}

// 4. አጠቃላይ ሲስተሙ እንዲጠቀምበት Instance መፍጠር
$database = new Database();
$pdo = $database->connect();
?>
