<?php 
require_once '../includes/db.php';
include '../includes/admin_header.php';

// ሁሉንም እንቅስቃሴዎች ከነ ሰራተኛው ስም ማምጣት
$logs = $pdo->query("SELECT l.*, u.full_name, u.role FROM audit_logs l 
                     LEFT JOIN users u ON l.user_id = u.id 
                     ORDER BY l.created_at DESC LIMIT 100")->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-shield-lock-fill text-danger"></i> Audit Trail (የስርዓት እንቅስቃሴ ክትትል)</h2>
        <button class="btn btn-outline-dark btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> ሪፖርት አትም</button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ጊዜ (Timestamp)</th>
                            <th>ተጠቃሚ (User)</th>
                            <th>ስልጣን (Role)</th>
                            <th>ድርጊት (Action)</th>
                            <th>ዝርዝር (Details)</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="small text-muted"><?php echo $log['created_at']; ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></td>
                            <td><span class="badge bg-secondary"><?php echo $log['role'] ?? 'N/A'; ?></span></td>
                            <td><span class="text-primary fw-bold"><?php echo htmlspecialchars($log['action']); ?></span></td>
                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                            <td class="small text-muted"><?php echo $log['ip_address']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>