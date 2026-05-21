<?php
session_start();

require_once '../includes/db.php';
/** @var PDO $pdo */
// 1. የመግቢያ ፈቃድ ማረጋገጫ
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'General Manager') {
    header("Location: ../auth/login.php");
    exit();
}

$full_name = $_SESSION['full_name'] ?? 'Admin';


$user_count = $pdo->query("SELECT count(*) FROM users")->fetchColumn();
$dept_count = $pdo->query("SELECT count(*) FROM departments")->fetchColumn();
$pending_count = $pdo->query("SELECT count(*) FROM maintenance_requests WHERE status = 'Pending'")->fetchColumn();
$completed_count = $pdo->query("SELECT count(*) FROM maintenance_requests WHERE status = 'Completed'")->fetchColumn();

// Productivity Dashboard - Maintenance Request Status
$status_query = $pdo->query("SELECT
    CASE
        WHEN status = 'Pending' THEN 'Pending'
        WHEN status = 'In Progress' THEN 'In Progress'
        WHEN status = 'Completed' THEN 'Completed'
        ELSE 'Other'
    END as status_group,
    COUNT(*) AS total
    FROM maintenance_requests
    GROUP BY status_group
    ORDER BY
        CASE status_group
            WHEN 'Pending' THEN 1
            WHEN 'In Progress' THEN 2
            WHEN 'Completed' THEN 3
            ELSE 4
        END");
$status_data = $status_query->fetchAll();

$dept_data = $pdo->query("SELECT d.dept_name, COUNT(m.id) AS total FROM maintenance_requests m JOIN departments d ON m.dept_id = d.id GROUP BY m.dept_id ORDER BY total DESC LIMIT 10")->fetchAll();

include '../includes/header_glass.php';
?>
<div class="row">
    <div class="col-md-12">
        <div class="card glass-card border-0 rounded-4 mb-4" style="background: linear-gradient(135deg, rgba(8, 29, 123, 0.85) 0%, rgba(118,75,162,0.85) 100%); color: white;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
            <h2 class="fw-bold mb-1">
                <i class="bi bi-person-workspace me-2"></i>
                Welcome, <?php echo htmlspecialchars($full_name); ?>
            </h2>
            <p class="mb-0" style="opacity: 0.85; letter-spacing: 0.5px;">
                <span class="badge rounded-pill bg-warning text-dark px-3 py-2 fw-bold me-2">
                    <i class="bi bi-star-fill"></i> General Manager
                </span>
                <i class="bi bi-building-check me-1"></i> 
                Bahir Dar Textile Share Company - IETMS
                <span class="ms-3 border-start ps-3 opacity-75">
                    <i class="bi bi-clock-history me-1"></i> <?php echo date('l, F j, Y'); ?>
                </span>
            </p>
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
<div class="col-md-4">
    <div class="card shadow-sm border-0 p-3 mb-3 text-center" style="border-radius: 15px;">
        <i class="bi bi-key-fill text-warning" style="font-size: 2rem;"></i>
        <h5 class="mt-2">Reset Requests</h5>
        <p class="text-muted small">Pending approvals for password reset</p>
        <a href="admin_approval.php" class="btn btn-outline-primary btn-sm rounded-pill">View Requests</a>
    </div>
</div>
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card glass-card border-0 rounded-4 h-100" style="background: linear-gradient(135deg, rgba(102,126,234,0.8) 0%, rgba(118,75,162,0.8) 100%); color: white;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-uppercase fw-light opacity-75 mb-2">ጠቅላላ ሰራተኞች</h6>
                        <h2 class="mb-0"><?php echo $user_count; ?></h2>
                        <small class="opacity-75">Total Users</small>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-3 p-3">
                        <i class="bi bi-people-fill fs-2"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="manage_users.php" class="text-white text-decoration-none small">
                        <i class="bi bi-arrow-right me-1"></i>Manage Users
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card glass-card border-0 rounded-4 h-100" style="background: linear-gradient(135deg, rgba(240,147,251,0.8) 0%, rgba(245,87,108,0.8) 100%); color: white;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-uppercase fw-light opacity-75 mb-2">ያልተጠናቀቁ ስራዎች</h6>
                        <h2 class="mb-0"><?php echo $pending_count; ?></h2>
                        <small class="opacity-75">Pending Tasks</small>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-3 p-3">
                        <i class="bi bi-clock-history fs-2"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="reports.php" class="text-white text-decoration-none small">
                        <i class="bi bi-arrow-right me-1"></i>View Details
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card glass-card border-0 rounded-4 h-100" style="background: linear-gradient(135deg, rgba(79,172,254,0.8) 0%, rgba(0,242,254,0.8) 100%); color: white;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-uppercase fw-light opacity-75 mb-2">የተጠናቀቁ ስራዎች</h6>
                        <h2 class="mb-0"><?php echo $completed_count; ?></h2>
                        <small class="opacity-75">Completed Tasks</small>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-3 p-3">
                        <i class="bi bi-check-circle-fill fs-2"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="reports.php" class="text-white text-decoration-none small">
                        <i class="bi bi-arrow-right me-1"></i>View Reports
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card glass-card border-0 rounded-4 h-100" style="background: linear-gradient(135deg, rgba(250,112,154,0.8) 0%, rgba(254,225,64,0.8) 100%); color: white;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-uppercase fw-light opacity-75 mb-2">ዲፓርትመንቶች</h6>
                        <h2 class="mb-0"><?php echo $dept_count; ?></h2>
                        <small class="opacity-75">Departments</small>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-3 p-3">
                        <i class="bi bi-building fs-2"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="manage_departments.php" class="text-white text-decoration-none small">
                        <i class="bi bi-arrow-right me-1"></i>Manage Depts
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0"><i class="bi bi-bar-chart-line"></i> Maintenance Request Status (Productivity KPI)</h6>
                    <small class="text-light">Current status of all maintenance requests</small>
                </div>
                <button class="btn btn-outline-light btn-sm" onclick="window.print();"><i class="bi bi-printer"></i> Export</button>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <canvas id="statusChart" height="250"></canvas>
                    </div>
                    <div class="col-md-6">
                        <div class="mt-4">
                            <h6 class="text-muted mb-3">Status Breakdown</h6>
                            <?php foreach ($status_data as $status): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-<?php
                                        echo $status['status_group'] === 'Pending' ? 'warning' :
                                             ($status['status_group'] === 'In Progress' ? 'info' :
                                             ($status['status_group'] === 'Completed' ? 'success' : 'secondary'));
                                    ?>"><?php echo htmlspecialchars($status['status_group']); ?></span>
                                    <strong><?php echo $status['total']; ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0"><i class="bi bi-building"></i> Requests by Department</h6>
            </div>
            <div class="card-body">
                <canvas id="departmentChart" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const statusData = {
    labels: [<?php echo implode(',', array_map(function($row){
        return '"'.htmlspecialchars($row['status_group']).'"';
    }, $status_data)); ?>],
    datasets: [{
        label: 'Requests',
        data: [<?php echo implode(',', array_map(function($row){ return $row['total']; }, $status_data)); ?>],
        backgroundColor: [
            '#ffc107', // Pending - warning yellow
            '#0dcaf0', // In Progress - info blue
            '#198754', // Completed - success green
            '#6c757d'  // Other - secondary gray
        ],
        borderColor: '#ffffff',
        borderWidth: 2
    }]
};

const departmentData = {
    labels: [<?php echo implode(',', array_map(function($row){ return '"'.htmlspecialchars($row['dept_name']).'"'; }, $dept_data)); ?>],
    datasets: [{
        label: 'Requests',
        data: [<?php echo implode(',', array_map(function($row){ return $row['total']; }, $dept_data)); ?>],
        backgroundColor: '#28687F',
        borderColor: '#1e4f61',
        borderWidth: 1
    }]
};

new Chart(document.getElementById('statusChart'), {
    type: 'pie',
    data: statusData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

new Chart(document.getElementById('departmentChart'), {
    type: 'bar',
    data: departmentData,
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>

<?php include '../includes/footer_glass.php'; ?>