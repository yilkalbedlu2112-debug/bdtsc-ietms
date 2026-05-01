<?php
session_start();
require_once '../includes/db.php';

// 1. Authentication & Role Check
$allowed_roles = ['General Manager', 'Department Manager', 'Engineering Manager'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles, true)) {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id   = (int)($_SESSION['dept_id']   ?? 0);
$user_role = $_SESSION['user_role'];
$full_name = $_SESSION['full_name'];

$logs         = [];
$where_clause = "";
$params       = [];

// 2. Role-based scoping
// General Manager → sees all; Dept Manager & Engineering Manager → own dept only
if (in_array($user_role, ['Department Manager', 'Engineering Manager'], true)) {
    $where_clause = " WHERE u.dept_id = ? ";
    $params[] = $dept_id;
}

// 3. Fetch Audit Logs from Database
try {
    // ዳታቤዝህ ላይ ባለው ስም (action, details, timestamp) የተሰራ Query
    $query = "SELECT 
                a.id, 
                a.user_id, 
                a.action, 
                a.details, 
                a.ip_address, 
                a.timestamp, 
                u.full_name, 
                d.dept_name 
              FROM audit_logs a 
              JOIN users u ON a.user_id = u.id 
              JOIN departments d ON u.dept_id = d.id 
              $where_clause 
              ORDER BY a.timestamp DESC 
              LIMIT 150";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // በዴቨሎፕመንት ጊዜ ስህተቱን ለማየት ይረዳል (ከተጠናቀቀ በኋላ ግን አጥፋው)
    error_log("Audit Log Fetch Error: " . $e->getMessage());
}

include '../includes/header_glass.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4 align-items-center">
        <div class="col-12 col-lg-6">
            <h2 class="fw-bold mb-1">
                <i class="bi bi-shield-shaded text-danger me-2"></i>System Audit Vault
            </h2>
            <p class="text-muted mb-0">Chronological activity feed & accountability logs (Immutable)</p>
        </div>
       
    </div>

    <!-- Audit Logs Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-list-stars text-primary me-2"></i>Activity Stream
            </h5>
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                <i class="bi bi-lock-fill me-1"></i>Read-Only Data
            </span>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 fw-bold">
                                <i class="bi bi-clock text-muted me-1"></i>Timestamp
                            </th>
                            <th class="border-0 fw-bold">
                                <i class="bi bi-person text-muted me-1"></i>User Identity
                            </th>
                            <th class="border-0 fw-bold">
                                <i class="bi bi-activity text-muted me-1"></i>Action Type
                            </th>
                            <th class="border-0 fw-bold">
                                <i class="bi bi-file-text text-muted me-1"></i>Log Description
                            </th>
                            <th class="border-0 fw-bold">
                                <i class="bi bi-info-circle text-muted me-1"></i>Metadata
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="bi bi-database-exclamation fs-1 text-muted d-block mb-3"></i>
                                        <h5 class="text-muted">No audit logs found.</h5>
                                        <p class="small text-muted">System activities will appear here once recorded.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($logs as $log): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark small">
                                        <?php echo date('M d, Y', strtotime($log['timestamp'])); ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?php echo date('h:i A', strtotime($log['timestamp'])); ?>
                                    </div>
                                </td>

                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 38px; height: 38px; font-weight: bold;">
                                            <?php echo substr($log['full_name'], 0, 1); ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold mb-0"><?php echo htmlspecialchars($log['full_name']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($log['dept_name']); ?></div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <?php
                                        $act = strtoupper($log['action']);
                                        $badge_class = 'bg-secondary';
                                        if(strpos($act, 'LOGIN') !== false) $badge_class = 'bg-success';
                                        if(strpos($act, 'UPDATE') !== false) $badge_class = 'bg-warning text-dark';
                                        if(strpos($act, 'RESET') !== false) $badge_class = 'bg-info';
                                        if(strpos($act, 'REGISTRATION') !== false) $badge_class = 'bg-primary';
                                        if(strpos($act, 'DELETE') !== false) $badge_class = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?> rounded-pill px-3 py-2">
                                        <?php echo $act; ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="bg-light p-2 rounded small">
                                        <?php echo htmlspecialchars($log['details']); ?>
                                    </div>
                                </td>

                                <td class="text-end">
                                    <code class="text-muted small bg-light px-2 py-1 rounded">
                                        <i class="bi bi-pci-card me-1"></i><?php echo $log['ip_address']; ?>
                                    </code>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-transparent border-0 text-center text-muted small">
            Showing latest 150 security events from the system's black box.
        </div>
    </div>
</div>

<style>
    @media print {
        .sidebar, .btn, .language-switcher { display: none !important; }
        .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
        .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    }
</style>

<?php include '../includes/footer_glass.php'; ?>