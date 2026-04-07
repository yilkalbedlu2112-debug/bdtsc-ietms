<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Department Manager') {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'];
$dept_name = $_SESSION['dept_name'] ?? 'Department';
$full_name = $_SESSION['full_name'] ?? 'Manager';

include '../includes/manager_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="page-title mb-1">Generate Reports</h1>
                    <h5 class="text-muted"><?php echo htmlspecialchars($dept_name); ?> Department</h5>
                </div>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-pdf"></i> Task Report Generator</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="generate_report.php">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Task Status</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="report_type" id="all_tasks" value="all" checked>
                                    <label class="form-check-label" for="all_tasks">All Tasks</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="report_type" id="completed_only" value="completed">
                                    <label class="form-check-label" for="completed_only">Completed Tasks Only</label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Task Categories (Multiple Select)</label>
                                <select name="task_categories[]" class="form-select" multiple style="height: 100px;">
                                    <option value="Maintenance" selected>Maintenance</option>
                                    <option value="Production">Production</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Administration">Administration</option>
                                    <option value="ICT">ICT</option>
                                </select>
                                <small class="text-muted">ብዙ ለመምረጥ Ctrl ተጭነው ይጫኑ</small>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <button type="submit" name="generate_pdf" class="btn btn-danger w-100">
                                    <i class="bi bi-file-earmark-pdf"></i> Download PDF
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" name="export_excel" formaction="export_excel.php" class="btn btn-success w-100">
                                    <i class="bi bi-file-earmark-excel"></i> Download Excel
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

<?php include '../includes/admin_footer.php'; ?>

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

    // PDF HTML generation
    $html = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 10px; }
            th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <div class="header">
            <h3>BDTSC - Task Report</h3>
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