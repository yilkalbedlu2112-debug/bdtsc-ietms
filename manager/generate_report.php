<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_role']) ||
    !in_array($_SESSION['user_role'], ['Department Manager', 'Engineering Manager'], true)) {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'];
$dept_name = $_SESSION['dept_name'] ?? 'Department';
$full_name = $_SESSION['full_name'] ?? 'Manager';

require_once '../includes/header_glass.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-2">
                        <i class="bi bi-file-earmark-bar-graph text-primary me-2"></i>Generate Reports
                    </h2>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($dept_name); ?> Department</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Error Message -->
    <?php if (isset($_GET['error'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Report Generator Form -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-file-earmark-pdf text-primary me-2"></i>Task Report Generator
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="generate_report.php">

                        <!-- Date Range -->
                        <div class="row g-3 mb-4">
                            <div class="col-lg-6">
                                <label for="start_date" class="form-label text-muted small fw-bold">
                                    <i class="bi bi-calendar-date text-primary me-1"></i>Start Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="col-lg-6">
                                <label for="end_date" class="form-label text-muted small fw-bold">
                                    <i class="bi bi-calendar-date text-success me-1"></i>End Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>

                        <!-- Task Status -->
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">
                                <i class="bi bi-check-circle text-info me-1"></i>Task Status
                            </label>
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="report_type" id="all_tasks" value="all" checked>
                                        <label class="form-check-label" for="all_tasks">
                                            <i class="bi bi-list-check me-1"></i>All Tasks
                                        </label>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="report_type" id="completed_only" value="completed">
                                        <label class="form-check-label" for="completed_only">
                                            <i class="bi bi-check2-all me-1"></i>Completed Tasks Only
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Task Categories -->
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">
                                <i class="bi bi-tags text-warning me-1"></i>Task Categories (Multiple Select)
                            </label>
                            <select name="task_categories[]" class="form-select" multiple style="height: 120px;">
                                <option value="Maintenance" selected>Maintenance</option>
                                <option value="Production">Production</option>
                                <option value="Finance">Finance</option>
                                <option value="Administration">Administration</option>
                                <option value="ICT">ICT</option>
                            </select>
                            <small class="text-muted">ብዙ ለመምረጥ Ctrl ተጭነው ይጫኑ</small>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <button type="submit" name="generate_pdf" class="btn btn-outline-danger w-100">
                                    <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF
                                </button>
                            </div>
                            <div class="col-lg-6">
                                <button type="submit" name="export_excel" formaction="export_excel.php" class="btn btn-outline-success w-100">
                                    <i class="bi bi-file-earmark-excel me-1"></i>Download Excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 30);

    document.getElementById('end_date').value = today.toISOString().split('T')[0];
    document.getElementById('start_date').value = thirtyDaysAgo.toISOString().split('T')[0];
});
</script>

<?php include '../includes/footer_glass.php'; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_pdf'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $report_type = $_POST['report_type'];
    $selected_categories = $_POST['task_categories'] ?? [];

    if (empty($start_date) || empty($end_date)) {
        header("Location: generate_report.php?error=Please select dates");
        exit();
    }

    $where_clause = "m.dept_id = ? AND DATE(m.created_at) BETWEEN ? AND ?";
    $params = [$dept_id, $start_date, $end_date];

    if ($report_type === 'completed') {
        $where_clause .= " AND m.status = 'Completed'";
    }

    // ብዙ ካቴጎሪዎችን ለመፈለግ
    if (!empty($selected_categories)) {
        $placeholders = implode(',', array_fill(0, count($selected_categories), '?'));
        $where_clause .= " AND m.task_type IN ($placeholders)";
        $params = array_merge($params, $selected_categories);
    }

    $tasks_stmt = $pdo->prepare("
        SELECT m.*, u.full_name AS requested_by, a.full_name AS assigned_to_name
        FROM maintenance_requests m
        LEFT JOIN users u ON m.user_id = u.id
        LEFT JOIN users a ON m.assigned_to = a.id
        WHERE $where_clause
        ORDER BY m.created_at DESC
    ");
    $tasks_stmt->execute($params);
    $tasks = $tasks_stmt->fetchAll();

    $logo_path = __DIR__ . '/../assets/images/Bahr dar Textile.png';
    $logo_data = '';
    if (file_exists($logo_path)) {
        $type = pathinfo($logo_path, PATHINFO_EXTENSION);
        $data = file_get_contents($logo_path);
        $logo_data = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    
    // PDF HTML generation
    $html = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
            .logo { max-height: 80px; margin-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 10px; }
            th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <div class="header">';
    if ($logo_data !== '') {
        $html .= '<img src="' . $logo_data . '" class="logo" alt="BDTSC Logo"><br>';
    }
    $html .= '
            <h3>Bahir Dar Textile Share Company (BDTSC)</h3>
            <p>Task/Maintenance Internal Report</p>
            <p>Period: ' . $start_date . ' to ' . $end_date . ' | Dept: ' . $dept_name . '</p>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Task Title</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Created Date</th>
                </tr>
            </thead>
            <tbody>';
    foreach ($tasks as $task) {
        $html .= '<tr>
            <td>' . htmlspecialchars($task['machine_name']) . '</td>
            <td>' . htmlspecialchars($task['task_type']) . '</td>
            <td>' . htmlspecialchars($task['priority']) . '</td>
            <td>' . htmlspecialchars($task['status']) . '</td>
            <td>' . htmlspecialchars($task['assigned_to_name'] ?? 'Not Assigned') . '</td>
            <td>' . htmlspecialchars($task['created_at']) . '</td>
        </tr>';
    }
    $html .= '</tbody></table></body></html>';

    if (file_exists('../vendor/autoload.php')) {
        require_once '../vendor/autoload.php';
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream("BDTSC_Report.pdf", array('Attachment' => true));
        exit();
    } else {
        echo $html; exit();
    }
}
?>