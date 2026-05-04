<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Supervisor') {
    header('Location: ../auth/login.php');
    exit();
}

$dept_id = intval($_SESSION['dept_id'] ?? 0);
$production_depts = [3, 8, 9, 10]; // BDTSC Production Departments

if (!in_array($dept_id, $production_depts, true)) {
    die("<div class='container mt-5'><div class='alert alert-danger'>Access Denied.</div></div>");
}

// የቅርብ ጊዜ የተላኩ ሪፖርቶች ታሪክ
$reportLogsStmt = $pdo->prepare("SELECT details, created_at FROM audit_logs WHERE user_id = ? AND action = 'Generate Report' ORDER BY created_at DESC LIMIT 5");
$reportLogsStmt->execute([$_SESSION['user_id']]);
$reportLogs = $reportLogsStmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header_glass.php';
?>

<style>
    :root {
        --bdtsc-success: #28a745;
        --glass-bg: rgba(255, 255, 255, 0.95);
    }
    .report-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-radius: 15px;
        overflow: hidden;
    }
    .report-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
    }
    .history-card {
        border-radius: 15px;
        backdrop-filter: blur(10px);
    }
    .modal-content {
        border-radius: 20px;
        border: none;
    }
    .modal-header {
        border-top-left-radius: 20px;
        border-top-right-radius: 20px;
    }
    .btn-rounded {
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 600;
    }
    .status-badge {
        width: 10px;
        height: 10px;
        display: inline-block;
        border-radius: 50%;
        margin-right: 5px;
    }
</style>

<div class="container-fluid py-5" style="background: #f8f9fa; min-height: 100vh;">
    <!-- የርዕስ ክፍል -->
    <div class="row mb-5 justify-content-center">
        <div class="col-lg-8 text-center">
            <div class="d-inline-block p-3 bg-white shadow-sm rounded-circle mb-3">
                <i class="bi bi-journal-check text-success display-6"></i>
            </div>
            <h2 class="fw-bold text-dark">የምርት ክፍል ሪፖርት ማእከል</h2>
            <div class="mx-auto bg-success mb-3" style="height: 4px; width: 60px; border-radius: 2px;"></div>
            <p class="text-muted lead">በዲፓርትመንትዎ ውስጥ ያሉ የጥገና እና የስራ እንቅስቃሴዎችን በማጠቃለል ለዲፓርትመንት ማናጀርዎ ያሳውቁ።</p>
        </div>
    </div>

    <!-- የሪፖርት መምረጫ ካርዶች -->
    <div class="row g-4 justify-content-center mb-5">
        <!-- Daily Report -->
        <div class="col-md-5 col-lg-4">
            <div class="card report-card border-0 shadow-sm h-100">
                <div class="card-body p-4 text-center">
                    <div class="p-3 d-inline-block bg-primary bg-opacity-10 rounded-pill mb-3">
                        <i class="bi bi-calendar-day text-primary fs-2"></i>
                    </div>
                    <h4 class="fw-bold">Daily Report</h4>
                    <p class="text-muted small">የዛሬውን የምርት እና የጥገና ሁኔታዎችን የሚያጠቃልል ዕለታዊ ሪፖርት።</p>
                    <button class="btn btn-primary w-100 btn-rounded open-report-modal" data-period="Daily">
                        <i class="bi bi-plus-circle me-2"></i>ሪፖርት አዘጋጅ
                    </button>
                </div>
            </div>
        </div>

        <!-- Weekly Report -->
        <div class="col-md-5 col-lg-4">
            <div class="card report-card border-0 shadow-sm h-100">
                <div class="card-body p-4 text-center">
                    <div class="p-3 d-inline-block bg-warning bg-opacity-10 rounded-pill mb-3">
                        <i class="bi bi-calendar-week text-warning fs-2"></i>
                    </div>
                    <h4 class="fw-bold">Weekly Report</h4>
                    <p class="text-muted small">የሳምንቱን አጠቃላይ የስራ አፈጻጸም እና ማነቆዎችን የሚያሳይ ሳምንታዊ ሪፖርት።</p>
                    <button class="btn btn-warning w-100 btn-rounded text-white open-report-modal" data-period="Weekly">
                        <i class="bi bi-plus-circle me-2"></i>ሪፖርት አዘጋጅ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- የሪፖርት ታሪክ ሰንጠረዥ -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card history-card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-success"></i>የተላኩ ሪፖርቶች ታሪክ</h5>
                    <span class="badge bg-light text-dark border">የቅርብ 5 ሪፖርቶች</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">ቀን እና ሰዓት</th>
                                    <th>የሪፖርቱ ዝርዝር ማጠቃለያ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportLogs)): ?>
                                    <tr>
                                        <td colspan="2" class="text-center py-5">
                                            <i class="bi bi-folder2-open display-4 text-muted d-block mb-2"></i>
                                            <span class="text-muted">እስካሁን የተላከ ምንም አይነት ሪፖርት የለም።</span>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reportLogs as $log): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold small text-dark">
                                                    <?php echo date('M d, Y', strtotime($log['created_at'])); ?>
                                                </div>
                                                <div class="text-muted" style="font-size: 0.75rem;">
                                                    <?php echo date('h:i A', strtotime($log['created_at'])); ?>
                                                </div>
                                            </td>
                                            <td class="py-3">
                                                <div class="p-2 rounded bg-light border-start border-success border-4 small">
                                                    <?php echo htmlspecialchars($log['details']); ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Submission Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white px-4">
                <h5 class="modal-title fw-bold" id="modalTitle">
                    <i class="bi bi-send-fill me-2"></i>ሪፖርት መላኪያ
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="detailedReportForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="generate_report">
                    <input type="hidden" name="period" id="modalPeriod">
                    
                    <div class="alert alert-info border-0 bg-info bg-opacity-10 d-flex align-items-center mb-4">
                        <i class="bi bi-person-badge-fill fs-4 me-3 text-info"></i>
                        <div>
                            <small class="d-block text-muted">ተቀባይ፡</small>
                            <strong class="text-dark">Department Manager (ቀጥታ ይላካል)</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">የሪፖርት ማጠቃለያ (Summary Note)</label>
                        <textarea name="summary_note" class="form-control shadow-sm" rows="6" 
                                  style="resize: none; border-radius: 12px;"
                                  placeholder="ዛሬ በዲፓርትመንትዎ ውስጥ የተከናወኑ ዋና ዋና የጥገና ስራዎችን፣ ውጤቶችን እና ያጋጠሙ ችግሮችን እዚህ በአጭሩ ይግለጹ..." required></textarea>
                    </div>

                    <div class="card bg-light border-0">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-info-circle-fill text-success me-2 mt-1"></i>
                                <small class="text-muted">
                                    <strong>ማሳሰቢያ፡</strong> ሲስተሙ የእያንዳንዱን ስራ ሁኔታ (Pending/Completed) እና የዲፓርትመንትዎን መረጃ በራስ-ሰር አያይዞ ስለሚልክ እዚህ ጋር መደጋገም አያስፈልግዎትም።
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light btn-rounded flex-grow-1" data-bs-dismiss="modal">ይቅር</button>
                    <button type="submit" class="btn btn-success btn-rounded flex-grow-1 shadow-sm" id="sendBtn">
                        <i class="bi bi-check2-circle me-1"></i>አረጋግጥና ላክ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 1. መጀመሪያ jQuery መጫኑን አረጋግጥ -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- 2. Bootstrap Bundle JS (ይህ ካልተጨመረ .modal() አይሰራም) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- 3. ያንተ የሪፖርት መቆጣጠሪያ ኮድ -->
<script>
$(document).ready(function() {
    // Modal መክፈቻ
    $('.open-report-modal').on('click', function() {
        // የ Bootstrap ሞዳልን በጃቫስክሪፕት ለመክፈት አዲሱን መንገድ (BS5) መጠቀም ይሻላል
        var myModal = new bootstrap.Modal(document.getElementById('reportModal'));
        
        const period = $(this).data('period');
        $('#modalPeriod').val(period.toLowerCase());
        $('#modalTitle').text(period + ' Report ማዘጋጃ');
        
        myModal.show();
    });

    // በ AJAX ሪፖርቱን መላክ
    $('#detailedReportForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#sendBtn');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> በመላክ ላይ...');

        $.post('supervisor_controller.php', $(this).serialize(), function(res) {
            if (res.status === 'success') {
                alert(res.message);
                location.reload();
            } else {
                alert('ስህተት ተከስቷል: ' + res.message);
                btn.prop('disabled', false).text('አረጋግጥና ላክ');
            }
        }, 'json');
    });
});
</script>