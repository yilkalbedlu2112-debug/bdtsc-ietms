<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Engineering Manager') {
    header("Location: ../auth/login.php");
    exit();
}

$em_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Engineering Manager';

// 1. Fetch Unassigned Maintenance/Technical Tasks
$unassigned_stmt = $pdo->prepare("
    SELECT m.*, d.dept_name 
    FROM maintenance_requests m 
    JOIN departments d ON m.dept_id = d.id 
    WHERE m.assigned_to IS NULL 
    ORDER BY CASE WHEN m.priority = 'Emergency' THEN 1 WHEN m.priority = 'High' THEN 2 ELSE 3 END, m.created_at ASC
");
$unassigned_stmt->execute();
$unassigned_tasks = $unassigned_stmt->fetchAll();

// 2. Fetch all available Technicians
$tech_stmt = $pdo->query("SELECT id, full_name, status FROM users WHERE role = 'Technician' AND status = 'Active'");
$technicians = $tech_stmt->fetchAll();

// 3. Fetch Currently active tasks assigned to technicians
$active_stmt = $pdo->prepare("
    SELECT m.*, d.dept_name, u.full_name as technician 
    FROM maintenance_requests m 
    JOIN departments d ON m.dept_id = d.id 
    JOIN users u ON m.assigned_to = u.id 
    WHERE m.status IN ('Assigned', 'In-Progress', 'Blocked') 
    ORDER BY m.updated_at DESC
");
$active_stmt->execute();
$active_tasks = $active_stmt->fetchAll();

include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-dark fw-bold"><i class="bi bi-tools text-primary me-2"></i>Engineering Command</h2>
            <p class="text-muted mb-0">Task Triage & Technician Dispatch</p>
        </div>
        <div class="text-muted fw-medium"><?php echo date('D, M d, Y'); ?></div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Triage / Unassigned Tasks Setup -->
        <div class="col-lg-12">
            <div class="card glass-card border-0 shadow-sm mb-4">
                <div class="card-header bg-danger bg-gradient text-white py-3">
                    <h5 class="mb-0"><i class="bi bi-inbox-fill me-2"></i>Pending Factory Requests <span class="badge bg-light text-danger rounded-pill ms-2"><?php echo count($unassigned_tasks); ?></span></h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>Asset / Machine</th>
                                <th>Description</th>
                                <th>Priority</th>
                                <th>Reported Time</th>
                                <th class="text-end">Assign Technician</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($unassigned_tasks) > 0): ?>
                                <?php foreach($unassigned_tasks as $task): ?>
                                    <tr class="<?php echo ($task['priority'] === 'Emergency') ? 'bg-danger bg-opacity-10' : ''; ?>">
                                        <td class="fw-medium"><?php echo htmlspecialchars($task['dept_name']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($task['machine_name']); ?></strong></td>
                                        <td>
                                            <span class="d-inline-block text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($task['issue_description']); ?>">
                                                <?php echo htmlspecialchars($task['issue_description']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $badge_class = 'bg-secondary';
                                            if($task['priority'] == 'Emergency') $badge_class = 'bg-danger animate-pulse';
                                            if($task['priority'] == 'High') $badge_class = 'bg-warning text-dark';
                                            ?>
                                            <span class="badge rounded-pill <?php echo $badge_class; ?> px-3 py-2"><?php echo htmlspecialchars($task['priority']); ?></span>
                                        </td>
                                        <td class="small text-muted"><?php echo date('M d, H:i', strtotime($task['created_at'])); ?></td>
                                        <td class="text-end">
                                            <form action="assign_task.php" method="POST" class="d-flex align-items-center justify-content-end gap-2">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <select name="technician_id" class="form-select form-select-sm" style="width: 200px;" required>
                                                    <option value="" disabled selected>Select Tech...</option>
                                                    <?php foreach($technicians as $tech): ?>
                                                        <option value="<?php echo $tech['id']; ?>"><?php echo htmlspecialchars($tech['full_name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary px-3 shadow-sm"><i class="bi bi-send-fill me-1"></i> Dispatch</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-check2-circle display-4 text-success opacity-50 mb-3 d-block"></i>
                                        <h5 class="text-muted">No pending requests!</h5>
                                        <p class="text-muted small">All factory issues have been successfully dispatched.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card glass-card border-0 shadow-sm">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="bi bi-activity me-2"></i>Active Operations Overview</h5>
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Tech Assigned</th>
                                <th>Location (Dept)</th>
                                <th>Asset</th>
                                <th>Current Status</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($active_tasks) > 0): ?>
                                <?php foreach($active_tasks as $task): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-2"><i class="bi bi-person-gear"></i></div>
                                            <strong><?php echo htmlspecialchars($task['technician']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($task['dept_name']); ?></td>
                                    <td><?php echo htmlspecialchars($task['machine_name']); ?></td>
                                    <td>
                                        <?php if($task['status'] === 'Blocked'): ?>
                                            <span class="badge bg-danger rounded-pill px-3 py-1"><i class="bi bi-x-octagon"></i> Blocked</span>
                                        <?php elseif($task['status'] === 'In-Progress'): ?>
                                            <span class="badge bg-info text-dark rounded-pill px-3 py-1"><i class="bi bi-arrow-repeat"></i> In Progress</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border rounded-pill px-3 py-1"><?php echo htmlspecialchars($task['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?php echo date('M d, H:i', strtotime($task['updated_at'] ?? $task['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No active field operations.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes pulseRed {
    0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}
.animate-pulse { animation: pulseRed 2s infinite; }
</style>

<?php include '../includes/footer_glass.php'; ?>