<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Supervisor') {
    header('Location: ../auth/login.php');
    exit();
}

$dept_id = $_SESSION['dept_id'] ?? 0;
$dept_name = $_SESSION['dept_name'] ?? 'Supervisor';

// 1. የሚመደቡ (Pending) ስራዎችን መፈለግ
$pendingTasksStmt = $pdo->prepare(
    "SELECT id, title, machine_name, issue_description, priority, created_at 
     FROM maintenance_requests 
     WHERE sender_dept_id = ? AND status = 'Pending' 
     ORDER BY created_at DESC"
);
$pendingTasksStmt->execute([$dept_id]);
$pendingTasks = $pendingTasksStmt->fetchAll(PDO::FETCH_ASSOC);

// 2. ተቀባዮችን (Receivers) ማዘጋጀት
// ሀ. የራሱ ዲፓርትመንት ሽፍት ሊደሮች
$shiftLeadersStmt = $pdo->prepare("SELECT id, full_name, user_role FROM users WHERE dept_id = ? AND user_role = 'Shift Leader' AND status = 'Active'");
$shiftLeadersStmt->execute([$dept_id]);
$receivers = $shiftLeadersStmt->fetchAll(PDO::FETCH_ASSOC);

// ለ. የኢንጅነሪንግ ማናጀር (ለጥገና ስራዎች)
$engStmt = $pdo->prepare("SELECT id, full_name, user_role FROM users WHERE user_role = 'Engineering Manager' AND status = 'Active' LIMIT 1");
$engStmt->execute();
$engManager = $engStmt->fetch(PDO::FETCH_ASSOC);
if ($engManager) { $receivers[] = $engManager; }

// 3. አስቀድሞ የተመደቡ (Assigned) ስራዎችን መፈለግ (ለታችኛው ሰንጠረዥ)
$assignedTasksStmt = $pdo->prepare(
    "SELECT r.id, r.title, r.machine_name, u.full_name as assigned_to, r.status, r.priority 
     FROM maintenance_requests r 
     LEFT JOIN users u ON r.assigned_to = u.id 
     WHERE r.sender_dept_id = ? AND r.status != 'Pending' 
     ORDER BY r.created_at DESC LIMIT 10"
);
$assignedTasksStmt->execute([$dept_id]);
$assignedTasks = $assignedTasksStmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white p-3 shadow-sm">
                <h3 class="mb-0"><i class="bi bi-send-check me-2"></i>Task Assignment Center</h3>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- አዲስ መመደቢያ ፎርም -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">Assign New Request</div>
                <div class="card-body">
                    <div id="assignAlert"></div>
                    <form id="assignTaskForm">
                        <input type="hidden" name="action" value="assign_task">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">1. Select Recipient (Shift Leader or Engineering)</label>
                            <select name="assigned_to" class="form-select" required>
                                <option value="">Choose Who to Assign</option>
                                <?php foreach ($receivers as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['full_name'] . " (" . $user['user_role'] . ")"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">2. Select Pending Task</label>
                            <div class="table-responsive border rounded" style="max-height: 300px;">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Select</th>
                                            <th>Task/Machine</th>
                                            <th>Priority</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingTasks as $task): ?>
                                            <tr>
                                                <td><input type="radio" name="request_id" value="<?php echo $task['id']; ?>" required></td>
                                                <td><?php echo htmlspecialchars($task['title'] ?: $task['machine_name']); ?></td>
                                                <td><span class="badge bg-info"><?php echo $task['priority']; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Confirm Assignment</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- የተመደቡ ስራዎች ዝርዝር (Assigned Tasks) -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Recently Assigned</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Assigned To</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignedTasks as $at): ?>
                                    <tr>
                                        <td class="small"><?php echo htmlspecialchars($at['machine_name']); ?></td>
                                        <td class="small"><?php echo htmlspecialchars($at['assigned_to'] ?: 'Engineering'); ?></td>
                                        <td><span class="badge bg-success small"><?php echo $at['status']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#assignTaskForm').on('submit', function(e) {
        e.preventDefault();
        
        // ዳታውን እናዘጋጅ
        const formData = $(this).serialize();
        
        // ቁልፉን ለጊዜው Disable እናድርገው (Double click ለመከላከል)
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

        $.ajax({
            url: 'supervisor_controller.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    // ስኬት ሲሆን አረንጓዴ መልዕክት እናሳይ
                    $('#assignAlert').html('<div class="alert alert-success">' + res.message + '</div>');
                    setTimeout(function() {
                        location.reload();
                    }, 1500); // ከ 1.5 ሰከንድ በኋላ ገጹን ያድሰዋል
                } else {
                    // ስህተት ሲኖር መልዕክቱን እናሳይ
                    $('#assignAlert').html('<div class="alert alert-danger">' + res.message + '</div>');
                    submitBtn.prop('disabled', false).text('Confirm Assignment');
                }
            },
            error: function(xhr, status, error) {
                console.error("Server Response:", xhr.responseText);
                $('#assignAlert').html('<div class="alert alert-danger">የሰርቨር ስህተት አጋጥሟል። እባክዎ ኮንሶል (F12) ላይ ይመልከቱ።</div>');
                submitBtn.prop('disabled', false).text('Confirm Assignment');
            }
        });
    });
});
</script>