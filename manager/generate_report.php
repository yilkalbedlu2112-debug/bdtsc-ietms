<?php
session_start();
require_once '../includes/db.php';

/** @var PDO $pdo */
// 1. Authentication
if (!isset($_SESSION['user_role']) || 
    !in_array($_SESSION['user_role'], ['Department Manager', 'Engineering Manager', 'General Manager'], true)) {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'] ?? 0;
$dept_name = $_SESSION['dept_name'] ?? 'General';

// ---------------------------------------------------------
// 2. PDF ማመንጫ ሎጂክ
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_pdf'])) {
    require_once '../vendor/autoload.php';
    
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $main_type = $_POST['main_report_type']; // 'tasks' ወይም 'audit' መሆኑን ይለያል

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8', 'format' => 'A4-L',
        'autoScriptToLang' => true, 'autoLangToFont' => true,
    ]);

    if ($main_type === 'audit') {
        // --- የኦዲት ሎግ ዳታ ማምጫ ---
        $report_title = "የሲስተም ኦዲት ታሪክ (System Audit Logs)";
        $query = "SELECT a.timestamp, u.full_name, a.action, a.details 
                  FROM audit_logs a 
                  JOIN users u ON a.user_id = u.id 
                  WHERE u.dept_id = ? AND DATE(a.timestamp) BETWEEN ? AND ?
                  ORDER BY a.timestamp DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$dept_id, $start_date, $end_date]);
        $rows = $stmt->fetchAll();

        $table_html = '<thead><tr style="background-color:#f2f2f2;">
            <th>ጊዜ (Timestamp)</th><th>ተጠቃሚ</th><th>ድርጊት (Action)</th><th>ዝርዝር (Details)</th>
        </tr></thead><tbody>';
        foreach ($rows as $row) {
            $table_html .= "<tr><td>{$row['timestamp']}</td><td>{$row['full_name']}</td><td>{$row['action']}</td><td>{$row['details']}</td></tr>";
        }
    } else {
        // --- የጥገና (Task) ዳታ ማምጫ ---
        $report_title = "የጥገና እና የክንውን ሪፖርት (Maintenance Tasks)";
        $report_status = $_POST['report_type'];
        $where = "m.dept_id = ? AND DATE(m.created_at) BETWEEN ? AND ?";
        $params = [$dept_id, $start_date, $end_date];

        if ($report_status === 'completed') { $where .= " AND m.status = 'Completed'"; }

        $query = "SELECT m.machine_name, m.task_type, m.priority, m.status, a.full_name as assigned_to, m.created_at 
                  FROM maintenance_requests m 
                  LEFT JOIN users a ON m.assigned_to = a.id 
                  WHERE $where ORDER BY m.created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $table_html = '<thead><tr style="background-color:#f2f2f2;">
            <th>ማሽን/ተግባር</th><th>አይነት</th><th>ቅድሚያ</th><th>ሁኔታ</th><th>ተረካቢ</th><th>ቀን</th>
        </tr></thead><tbody>';
        foreach ($rows as $row) {
            $table_html .= "<tr><td>{$row['machine_name']}</td><td>{$row['task_type']}</td><td>{$row['priority']}</td><td>{$row['status']}</td><td>".($row['assigned_to'] ?? 'ያልተመደበ')."</td><td>{$row['created_at']}</td></tr>";
        }
    }
    $table_html .= '</tbody>';

    $html = '<div style="text-align:center;">
        <h2>Bahir Dar Textile Share Company</h2>
        <h3>' . $report_title . '</h3>
        <p>Period: ' . $start_date . ' to ' . $end_date . ' | Dept: ' . $dept_name . '</p>
    </div>
    <table border="1" style="width:100%; border-collapse:collapse; font-size:10pt;">' . $table_html . '</table>';

    $mpdf->WriteHTML($html);
    $mpdf->Output("BDTSC_Report.pdf", "D");
    exit();
}

require_once '../includes/header_glass.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold"><i class="bi bi-file-earmark-bar-graph text-primary me-2"></i>Smart Report Center</h2>
            <p class="text-muted">የሚፈለገውን የሪፖርት አይነት መርጠው ያውርዱ።</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm p-4">
        <form method="POST">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-bold">የሪፖርት አይነት</label>
                    <select name="main_report_type" id="main_report_type" class="form-select" onchange="toggleFields()">
                        <option value="tasks">የጥገና ስራዎች (Maintenance Tasks)</option>
                        <option value="audit">የሲስተም ኦዲት (Audit Logs)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">ከ (Start Date)</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">እስከ (End Date)</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" required>
                </div>
            </div>

            <div id="task_options" class="mb-4">
                <label class="form-label fw-bold">የስራ ሁኔታ (Status)</label>
                <select name="report_type" class="form-select w-50">
                    <option value="all">ሁሉንም (All)</option>
                    <option value="completed">የተጠናቀቁ ብቻ (Completed)</option>
                </select>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <button type="submit" name="generate_pdf" class="btn btn-danger w-100 py-3">
                        <i class="bi bi-file-earmark-pdf me-2"></i>PDF አውርድ
                    </button>
                </div>
                <div class="col-md-6">
                    <button type="submit" name="export_excel" formaction="export_excel.php" class="btn btn-success w-100 py-3">
                        <i class="bi bi-file-earmark-excel me-2"></i>Excel አውርድ
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// "Audit" ሲመረጥ ለTask ብቻ የሚያገለግሉ ምርጫዎችን ለመደበቅ
function toggleFields() {
    const type = document.getElementById('main_report_type').value;
    document.getElementById('task_options').style.display = (type === 'tasks') ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('end_date').value = today;
    const lastMonth = new Date();
    lastMonth.setDate(lastMonth.getDate() - 30);
    document.getElementById('start_date').value = lastMonth.toISOString().split('T')[0];
});
</script>

<?php include '../includes/footer_glass.php'; ?>