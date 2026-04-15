<?php
session_start();
require_once '../includes/db.php';

// 1. Authentication & Role Check (Security First)
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['General Manager', 'Department Manager'])) {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'];
$user_role = $_SESSION['user_role'];
$full_name = $_SESSION['full_name'];

// ስህተትን ለመከላከል $logs አስቀድሞ ባዶ Array መሆኑን እናረጋግጥ
$logs = []; 
$where_clause = "";
$params = [];

// 2. Role-based Security (DM ከሆነ የራሱን ዲፓርትመንት ብቻ እንዲያይ መገደብ)
if ($user_role === 'Department Manager') {
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

// Header-ህን እናገናኝ
include '../includes/header_glass.php'; 
?>

<div class="container-fluid py-4" style="background: #f8f9fa; min-height: 100vh;">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-shield-shaded text-danger me-2"></i>System Audit Vault
            </h2>
            <p class="text-muted mb-0">Chronological activity feed & accountability logs (Immutable)</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <button onclick="window.print()" class="btn btn-white shadow-sm border rounded-pill px-4 me-2">
                <i class="bi bi-printer me-2"></i> Print Report
            </button>
            <button class="btn btn-primary rounded-pill px-4 shadow">
                <i class="bi bi-file-earmark-excel me-2"></i> Export CSV
            </button>
        </div>
    </div>

    <div class="card glass-card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-list-stars me-2"></i>Activity Stream</h6>
            <div class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">
                <i class="bi bi-lock-fill me-1"></i> Read-Only Data
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        <th class="ps-4">Timestamp</th>
                        <th>User Identity</th>
                        <th>Action Type</th>
                        <th>Log Description</th>
                        <th class="pe-4">Metadata</th>
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
                            <td class="ps-4">
                                <div class="fw-bold text-dark" style="font-size: 0.85rem;">
                                    <?php echo date('M d, Y', strtotime($log['timestamp'])); ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    <?php echo date('h:i A', strtotime($log['timestamp'])); ?>
                                </div>
                            </td>

                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2 bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-weight: bold; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                        <?php echo substr($log['full_name'], 0, 1); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($log['full_name']); ?></div>
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
                                <span class="badge <?php echo $badge_class; ?> rounded-pill px-3 py-2 shadow-sm" style="font-size: 0.65rem; min-width: 90px; letter-spacing: 0.5px;">
                                    <?php echo $act; ?>
                                </span>
                            </td>

                            <td class="small text-secondary" style="max-width: 350px;">
                                <div class="bg-light p-2 rounded border-start border-3 border-secondary" style="font-size: 0.8rem;">
                                    <?php echo htmlspecialchars($log['details']); ?>
                                </div>
                            </td>

                            <td class="pe-4 text-end">
                                <code class="text-muted small fw-normal bg-light px-2 py-1 rounded">
                                    <i class="bi bi-pci-card me-1"></i><?php echo $log['ip_address']; ?>
                                </code>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-3 border-0 text-center text-muted small">
            Showing latest 150 security events from the system's black box.
        </div>
    </div>
</div>

<style>
    /* Modern Glassmorphism & UI Enhancements */
    .glass-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .table thead th {
        font-weight: 700;
        border: none;
        color: #6c757d;
    }
    .table tbody tr {
        transition: all 0.2s ease;
    }
    .table tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.03);
        transform: scale(1.002);
    }
    .btn-white {
        background: #fff;
        color: #333;
    }
    .bg-success { background-color: #28a745 !important; }
    .bg-primary-subtle { background-color: #e7f1ff; }
    .bg-danger-subtle { background-color: #fceaea; }
    
    @media print {
        .sidebar, .btn, .language-switcher { display: none !important; }
        .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
        .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    }
</style>

<?php include '../includes/footer_glass.php'; ?>