<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db.php';

// ደህንነት፡ ሱፐርቫይዘር መሆኑን ማረጋገጥ
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Supervisor') {
    header("Location: ../auth/login.php");
    exit();
}

$dept_name = $_SESSION['dept_name'];
$dept_id = $_SESSION['dept_id'];

// የምርት ክፍሎችን እና እንጅነሪንግን ለማረጋገጥ
$production_group = ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'];

if (!in_array($dept_name, $production_group, true)) {
    die("<div class='alert alert-danger'>Access Denied: This dashboard is only for Production Supervisors.</div>");
}

$report_logs_stmt = $pdo->prepare("SELECT action, details, created_at FROM audit_logs WHERE user_id = ? AND action = 'Generate Report' ORDER BY created_at DESC LIMIT 5");
$report_logs_stmt->execute([$_SESSION['user_id']]);
$report_logs = $report_logs_stmt->fetchAll(PDO::FETCH_ASSOC);

$manager_tasks_stmt = $pdo->prepare(
    "SELECT mr.*, u.full_name AS assigned_to_name, d.dept_name AS source_dept
     FROM maintenance_requests mr
     LEFT JOIN users u ON mr.assigned_to = u.id
     LEFT JOIN departments d ON mr.sender_dept_id = d.id
     WHERE mr.receiver_dept_id = ? OR mr.assigned_to = ?
     ORDER BY mr.created_at DESC"
);
$manager_tasks_stmt->execute([$dept_id, $_SESSION['user_id']]);
$manager_tasks = $manager_tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header_glass.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supervisor Dashboard - BDTSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-person-badge"></i> <?php echo $dept_name; ?> Supervisor Panel</h2>
        <div>
            <a href="create_task.php" class="btn btn-danger me-2">
                <i class="bi bi-send-check"></i> Create Task
            </a>
            <a href="assign_task.php" class="btn btn-primary me-2">
                <i class="bi bi-person-plus"></i> Assign Task
            </a>
            <a href="submit_report.php" class="btn btn-success me-2">
                <i class="bi bi-file-earmark-plus"></i> Submit Report
            </a>
            <button class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#delegateModal">
                <i class="bi bi-person-badge"></i> Delegate Authority
            </button>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#submitAlertModal">
                <i class="bi bi-exclamation-triangle"></i> Submit Alert
            </button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body">
                    <h5>Production Efficiency</h5>
                    <h3>87%</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-warning text-dark">
                <div class="card-body">
                    <h5>Pending Tasks</h5>
                    <?php 
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE receiver_dept_id = ? AND status = 'Pending'");
                        $stmt->execute([$dept_id]);
                        echo "<h3>" . $stmt->fetchColumn() . "</h3>";
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-success text-white">
                <div class="card-body">
                    <h5>Active Machines</h5>
                    <h3>24/30</h3>
                </div>
            </div>
        </div>
    </div>
        

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <h5 class="mb-0">Machine / Work Station Tasks</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Source Dept</th>
                        <th>Subject/Machine</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("SELECT mr.*, d.dept_name AS source_dept, u.full_name as leader_name FROM maintenance_requests mr 
                                         LEFT JOIN departments d ON mr.sender_dept_id = d.id 
                                         LEFT JOIN users u ON mr.assigned_to = u.id 
                                         WHERE mr.receiver_dept_id = ? OR mr.dept_id = ?
                                         ORDER BY mr.created_at DESC");
                    $stmt->execute([$dept_id, $dept_id]);
                    while($row = $stmt->fetch()):
                    ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo $row['source_dept'] ?: '<span class="text-muted">Unknown</span>'; ?></td>
                        <td><strong><?php echo $row['title'] ?: $row['machine_name']; ?></strong></td>
                        <td><span class="badge bg-<?php echo ($row['priority']=='Emergency') ? 'danger' : 'info'; ?>"><?php echo $row['priority']; ?></span></td>
                        <td><span class="badge bg-secondary"><?php echo $row['status']; ?></span></td>
                        <td><?php echo $row['leader_name'] ?: '<span class="text-muted">Not Assigned</span>'; ?></td>
               
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal fade" id="submitAlertModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="submitAlertForm">
            <div class="modal-content">
                <div class="modal-header"><h5>Submit Alert to Shift Leader</h5></div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_request">
                    <div class="mb-3"><label>Machine Name</label><input type="text" name="machine_name" class="form-control" required></div>
                    <div class="mb-3"><label>Issue Description</label><textarea name="issue_description" class="form-control" required></textarea></div>
                    <div class="mb-3">
                        <label>Priority</label>
                        <select name="priority" class="form-select">
                            <option value="Normal">Normal</option>
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Submit Alert</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="delegateModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="delegateForm">
            <div class="modal-content">
                <div class="modal-header"><h5>Delegate Authority to Shift Leader</h5></div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="delegate_authority">
                    <div class="mb-3">
                        <label>Select Shift Leader</label>
                        <select name="delegate_to" class="form-select" required>
                            <?php
                            $delegates = $pdo->prepare("SELECT id, full_name FROM users WHERE dept_id = ? AND user_role = 'Shift Leader'");
                            $delegates->execute([$dept_id]);
                            foreach($delegates->fetchAll() as $delegate) {
                                echo "<option value='{$delegate['id']}'>" . htmlspecialchars($delegate['full_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Delegation Notes</label>
                        <textarea name="delegation_notes" class="form-control" placeholder="Optional note for the delegate"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info">Delegate Authority</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$('#sendDailyReportBtn, #sendWeeklyReportBtn, #sendMonthlyReportBtn').on('click', function() {
    var id = $(this).attr('id');
    var period = id === 'sendMonthlyReportBtn' ? 'monthly' : (id === 'sendWeeklyReportBtn' ? 'weekly' : 'daily');
    $.post('supervisor_controller.php', { action: 'generate_report', period: period }, function(res) {
        alert(res.message);
    }, 'json');
});

$('#delegateForm').on('submit', function(e) {
    e.preventDefault();
    $.post('supervisor_controller.php', $(this).serialize(), function(res) {
        alert(res.message);
        if (res.status === 'success') location.reload();
    }, 'json');
});
</script>
</body>
</html>
<?php include '../includes/footer_glass.php'; ?>