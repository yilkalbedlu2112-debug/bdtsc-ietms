<?php
require_once '../includes/db.php';
include '../includes/deputy_gm_header.php';

// Active Tasks: Count of tasks currently assigned to technicians (In Progress or Assigned)
$active_tasks_count = $pdo->query("SELECT count(*) FROM maintenance_requests WHERE status IN ('In Progress', 'Assigned')")->fetchColumn();

// Machine Status: Count damaged (pending/assigned) vs maintained (completed)
$damaged_machines = $pdo->query("SELECT COUNT(DISTINCT machine_name) FROM maintenance_requests WHERE status IN ('Pending', 'In Progress', 'Assigned')")->fetchColumn();
$maintained_machines = $pdo->query("SELECT COUNT(DISTINCT machine_name) FROM maintenance_requests WHERE status = 'Completed'")->fetchColumn();

// Technician Workload: Count tasks per technician
$technician_workload = $pdo->query("
    SELECT u.full_name, COUNT(m.id) as task_count
    FROM users u
    LEFT JOIN maintenance_requests m ON u.id = m.assigned_to AND m.status != 'Completed'
    WHERE u.role = 'Technician'
    GROUP BY u.id, u.full_name
    ORDER BY task_count DESC
    LIMIT 10
")->fetchAll();

?>

<div class="row">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1">Welcome, Deputy General Manager</h3>
                        <p class="mb-0 opacity-75">Bahir Dar Textile Share Company - Production & Technical Management</p>
                    </div>
                    <div class="text-end">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar-event fs-1 me-3 opacity-75"></i>
                            <div>
                                <small class="opacity-75">Today</small>
                                <div class="fw-bold"><?php echo date('M d, Y'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-uppercase fw-light opacity-75 mb-2">Active Tasks</h6>
                        <h2 class="mb-0"><?php echo $active_tasks_count; ?></h2>
                        <small class="opacity-75">Tasks in Progress</small>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-3 p-3">
                        <i class="bi bi-tools fs-2"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="../admin/reports.php" class="text-white text-decoration-none small">
                        <i class="bi bi-arrow-right me-1"></i>View Details
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-uppercase fw-light opacity-75 mb-2">Machine Status</h6>
                        <h4 class="mb-1">Damaged: <?php echo $damaged_machines; ?></h4>
                        <h4 class="mb-0">Maintained: <?php echo $maintained_machines; ?></h4>
                        <small class="opacity-75">Current Status</small>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-3 p-3">
                        <i class="bi bi-cpu fs-2"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="../admin/reports.php" class="text-white text-decoration-none small">
                        <i class="bi bi-arrow-right me-1"></i>View Reports
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-uppercase fw-light opacity-75 mb-2">Technician Workload</h6>
                        <div class="mt-2">
                            <?php foreach(array_slice($technician_workload, 0, 3) as $tech): ?>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small><?php echo htmlspecialchars($tech['full_name']); ?></small>
                                    <span class="badge bg-light text-dark"><?php echo $tech['task_count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-3 p-3">
                        <i class="bi bi-person-gear fs-2"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="../admin/manage_users.php" class="text-white text-decoration-none small">
                        <i class="bi bi-arrow-right me-1"></i>Manage Technicians
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional content can be added here -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('show');
}
</script>
</body>
</html>