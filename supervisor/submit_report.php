<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Supervisor') {
    header('Location: ../auth/login.php');
    exit();
}

$dept_name = $_SESSION['dept_name'] ?? 'Supervisor';
$dept_id = $_SESSION['dept_id'] ?? 0;
$production_group = ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'];
if (!in_array($dept_name, $production_group, true)) {
    die("<div class='alert alert-danger'>Access Denied: This page is only for Production Supervisors.</div>");
}

$reportLogsStmt = $pdo->prepare("SELECT details, created_at FROM audit_logs WHERE user_id = ? AND action = 'Generate Report' ORDER BY created_at DESC LIMIT 5");
$reportLogsStmt->execute([$_SESSION['user_id']]);
$reportLogs = $reportLogsStmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border-start border-success border-4">
                <div>
                    <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-bar-chart-line text-success me-2"></i>Supervisor Reports</h3>
                    <p class="text-muted mb-0 small">Send daily, weekly or monthly summary reports to department management.</p>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="mb-3">Generate New Report</h5>
                <div id="reportAlert"></div>
                <div class="d-grid gap-2">
                    <button id="sendDailyReportBtn" class="btn btn-outline-primary btn-lg">Send Daily Report</button>
                    <button id="sendWeeklyReportBtn" class="btn btn-outline-warning btn-lg">Send Weekly Report</button>
                    <button id="sendMonthlyReportBtn" class="btn btn-outline-success btn-lg">Send Monthly Report</button>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="mb-3">Recent Reports</h5>
                <?php if (empty($reportLogs)): ?>
                    <div class="text-muted">No reports generated yet.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($reportLogs as $log): ?>
                            <li class="list-group-item">
                                <div class="small text-muted"><?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?></div>
                                <div><?php echo htmlspecialchars($log['details']); ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('#sendDailyReportBtn, #sendWeeklyReportBtn, #sendMonthlyReportBtn').on('click', function() {
        var id = $(this).attr('id');
        var period = id === 'sendMonthlyReportBtn' ? 'monthly' : (id === 'sendWeeklyReportBtn' ? 'weekly' : 'daily');
        $.post('supervisor_controller.php', { action: 'generate_report', period: period }, function(res) {
            var alertClass = res.status === 'success' ? 'alert-success' : 'alert-danger';
            $('#reportAlert').html('<div class="alert ' + alertClass + '">' + res.message + '</div>');
        }, 'json').fail(function() {
            $('#reportAlert').html('<div class="alert alert-danger">Server error. Please try again.</div>');
        });
    });
});
</script>

<?php include '../includes/footer_glass.php'; ?>