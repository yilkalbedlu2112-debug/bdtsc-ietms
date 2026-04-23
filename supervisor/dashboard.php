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

// የምርት ክፍሎችን መለየት (Production Departments Only)
$production_group = ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'];

if (!in_array($dept_name, $production_group)) {
    die("<div class='alert alert-danger'>Access Denied: This dashboard is only for Production Supervisors.</div>");
}
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
            <button class="btn btn-warning me-2" data-bs-toggle="modal" data-bs-target="#submitAlertModal">
                <i class="bi bi-exclamation-triangle"></i> Submit Alert
            </button>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                <i class="bi bi-tools"></i> Request Maintenance / Create Task
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
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND status = 'Pending'");
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
                        <th>Subject/Machine</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("SELECT mr.*, u.full_name as leader_name FROM maintenance_requests mr 
                                         LEFT JOIN users u ON mr.assigned_to = u.id 
                                         WHERE mr.dept_id = ? ORDER BY mr.created_at DESC");
                    $stmt->execute([$dept_id]);
                    while($row = $stmt->fetch()):
                    ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><strong><?php echo $row['title'] ?: $row['machine_name']; ?></strong></td>
                        <td><span class="badge bg-<?php echo ($row['priority']=='Emergency') ? 'danger' : 'info'; ?>"><?php echo $row['priority']; ?></span></td>
                        <td><span class="badge bg-secondary"><?php echo $row['status']; ?></span></td>
                        <td><?php echo $row['leader_name'] ?: '<span class="text-muted">Not Assigned</span>'; ?></td>
                        <td>
    <?php if ($row['status'] == 'Pending'): ?>
        <button class="btn btn-sm btn-primary" onclick="openAssignModal(<?php echo $row['id']; ?>)">
            <i class="bi bi-person-plus"></i> Assign to SL
        </button>
    <?php endif; ?>

    <form action="alert_shift_leader.php" method="POST" style="display:inline;">
        <input type="hidden" name="req_id" value="<?php echo $row['id']; ?>">
        <button type="submit" class="btn btn-sm btn-outline-danger" title="Send Urgent Alert">
            <i class="bi bi-exclamation-triangle"></i> Alert SL
        </button>
    </form>
</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal fade" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="createTaskForm">
            <div class="modal-content">
                <div class="modal-header"><h5>Create New Task/Request</h5></div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_task">
                    <div class="mb-3"><label>Machine/Subject</label><input type="text" name="title" class="form-control" required></div>
                    <div class="mb-3"><label>Description</label><textarea name="description" class="form-control" required></textarea></div>
                    <div class="mb-3">
                        <label>Priority</label>
                        <select name="priority" class="form-select">
                            <option value="Normal">Normal</option>
                            <option value="High">High</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>
                    <div class="mb-3"><label>Deadline</label><input type="datetime-local" name="deadline" class="form-control" required></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Submit to Department</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="submitAlertModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="submitAlertForm">
            <div class="modal-content">
                <div class="modal-header"><h5>Submit Alert to Shift Leader</h5></div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="submit_alert">
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

<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="assignTaskForm">
            <div class="modal-content">
                <div class="modal-header"><h5>Assign to Shift Leader</h5></div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_to_shift_leader">
                    <input type="hidden" name="task_id" id="task_id_field">
                    <div class="mb-3">
                        <label>Select Shift Leader</label>
                        <select name="shift_leader_id" class="form-select" required>
                            <?php
                            $sls = $pdo->prepare("SELECT id, full_name FROM users WHERE dept_id = ? AND user_role = 'Shift Leader'");
                            $sls->execute([$dept_id]);
                            foreach($sls->fetchAll() as $sl) {
                                echo "<option value='{$sl['id']}'>{$sl['full_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Confirm & Send Email</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openAssignModal(id) {
    $('#task_id_field').val(id);
    var myModal = new bootstrap.Modal(document.getElementById('assignModal'));
    myModal.show();
}

// AJAX for Create & Assign & Alert
$('#createTaskForm, #assignTaskForm, #submitAlertForm').on('submit', function(e){
    e.preventDefault();
    $.post('sup_ajax.php', $(this).serialize(), function(res){
        alert(res.message);
        if(res.status === 'success') location.reload();
    }, 'json');
});
</script>
</body>
</html>
<?php include '../includes/footer_glass.php'; ?>