<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Department Manager') {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'];
$dept_name = $_SESSION['dept_name'] ?? 'Department';

// ማጣሪያዎች (Filters)
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';

$query = "SELECT m.*, u.full_name AS requester, a.full_name AS technician 
          FROM maintenance_requests m 
          LEFT JOIN users u ON m.user_id = u.id 
          LEFT JOIN users a ON m.assigned_to = a.id 
          WHERE m.dept_id = ?";

$params = [$dept_id];

if ($status_filter) {
    $query .= " AND m.status = ?";
    $params[] = $status_filter;
}
if ($priority_filter) {
    $query .= " AND m.priority = ?";
    $params[] = $priority_filter;
}

$query .= " ORDER BY m.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold"><i class="bi bi-tools me-2"></i> Maintenance & Task Logs</h2>
        <a href="create_task.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> New Request</a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Filter by Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="In Progress" <?php echo $status_filter == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Completed" <?php echo $status_filter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Filter by Priority</label>
                    <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Priorities</option>
                        <option value="Emergency" <?php echo $priority_filter == 'Emergency' ? 'selected' : ''; ?>>Emergency</option>
                        <option value="High" <?php echo $priority_filter == 'High' ? 'selected' : ''; ?>>High</option>
                        <option value="Medium" <?php echo $priority_filter == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <a href="maintenance_list.php" class="btn btn-sm btn-outline-secondary w-100">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Task ID</th>
                            <th>Title/Machine</th>
                            <th>Type</th>
                            <th>Assigned To</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td class="text-muted">#<?php echo $task['id']; ?></td>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($task['machine_name']); ?></div>
                                <small class="text-muted"><?php echo substr($task['issue_description'], 0, 40); ?>...</small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo $task['task_type']; ?></span></td>
                            <td>
                                <i class="bi bi-person-badge me-1"></i>
                                <?php echo htmlspecialchars($task['technician'] ?? 'Not Assigned'); ?>
                            </td>
                            <td>
                                <?php 
                                    if ($task['due_date']) {
                                        $isOverdue = (strtotime($task['due_date']) < time() && $task['status'] != 'Completed');
                                        echo "<span class='" . ($isOverdue ? 'text-danger fw-bold' : '') . "'>" . date('M d, Y', strtotime($task['due_date'])) . "</span>";
                                    } else {
                                        echo "No Deadline";
                                    }
                                ?>
                            </td>
                            <td>
                                <?php 
                                    $s_color = ($task['status'] == 'Completed') ? 'success' : (($task['status'] == 'Emergency') ? 'danger' : 'warning');
                                    echo "<span class='badge bg-$s_color'>{$task['status']}</span>";
                                ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="view_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                    <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer_glass.php'; ?>