<?php
session_start();
require_once '../includes/db.php';

// Security: Check if user is Engineering Manager
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Engineering Manager') {
    header("Location: ../auth/login.php");
    exit();
}

$full_name = $_SESSION['full_name'] ?? 'Engineering Manager';

// --- DATA FETCHING (Global Engineering Stats) ---
// 1. ሁሉንም ወደ ኢንጂነሪንግ የተላኩ ጥያቄዎችን መቁጠር
$stats_stmt = $pdo->query("SELECT 
    COUNT(*) as total, 
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'Sent to Engineering' THEN 1 ELSE 0 END) as pending_eng
    FROM maintenance_requests WHERE status IN ('Sent to Engineering', 'In Progress', 'Completed')");
$stats = $stats_stmt->fetch();

// 2. ከሁሉም ዲፓርትመንት የመጡ ጥያቄዎችን ከዲፓርትመንት ስም ጋር ማምጣት
$tasks_stmt = $pdo->prepare("SELECT m.*, d.dept_name, u.full_name as technician 
    FROM maintenance_requests m 
    JOIN departments d ON m.dept_id = d.id
    LEFT JOIN users u ON m.assigned_to = u.id 
    WHERE m.status = 'Sent to Engineering' OR m.assigned_to_dept = 'Engineering'
    ORDER BY m.created_at DESC");
$tasks_stmt->execute();
$tasks = $tasks_stmt->fetchAll();

include '../includes/engineering_header.php'; // ለኢንጂነሪንግ የተለየ ሄደር ካለህ
?>

<div class="wrapper">
    <nav class="sidebar">
        <div class="p-4 border-bottom border-secondary mb-3">
            <h5 class="fw-bold text-white mb-0">BDTSC-IETMS</h5>
            <small class="text-warning">Engineering Manager</small>
        </div>
        <div class="nav flex-column" id="v-pills-tab" role="tablist">
            <a class="nav-link active" data-bs-toggle="pill" href="#overview"><i class="bi bi-speedometer"></i> Overview</a>
            <a class="nav-link" data-bs-toggle="pill" href="#pending-tasks"><i class="bi bi-tools"></i> Assign Technicians</a>
            <a class="nav-link" data-bs-toggle="pill" href="#monitor"><i class="bi bi-eye"></i> Factory-wide Progress</a>
            <hr class="mx-3 opacity-25">
            <a href="../auth/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
        </div>
    </nav>

    <main class="main-content">
        <div class="tab-content">
            
            <div class="tab-pane active" id="overview">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Engineering Control Center</h3>
                    <div class="badge bg-dark p-2"><?php echo date('D, M d, Y'); ?></div>
                </div>
                
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="card card-kpi shadow-sm p-4 bg-white border-start border-warning border-5">
                            <small class="text-muted text-uppercase fw-bold">Incoming Requests</small>
                            <h2 class="fw-bold mt-2"><?php echo $stats['pending_eng']; ?></h2>
                        </div>
                    </div>
                    </div>
            </div>

            <div class="tab-pane" id="pending-tasks">
                <div class="card border-0 shadow-sm p-4">
                    <h4 class="fw-bold mb-4">Pending Engineering Requests</h4>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Dept</th><th>Asset</th><th>Issue</th><th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($tasks as $t): if($t['status'] == 'Sent to Engineering'): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo $t['dept_name']; ?></span></td>
                                    <td><strong><?php echo $t['machine_name']; ?></strong></td>
                                    <td><?php echo $t['issue_description']; ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignModal<?php echo $t['id']; ?>">
                                            Assign Tech
                                        </button>
                                    </td>
                                </tr>
                                <?php endif; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?php foreach($tasks as $t): ?>
<div class="modal fade" id="assignModal<?php echo $t['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="assign_eng_task.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Technician for <?php echo $t['machine_name']; ?></h5>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="req_id" value="<?php echo $t['id']; ?>">
                    <label class="form-label">Select Engineering Expert</label>
                    <select name="tech_id" class="form-select" required>
                        <?php
                        // የኢንጂነሪንግ ቴክኒሻኖችን ብቻ ማምጣት
                        $eng_techs = $pdo->query("SELECT id, full_name FROM users WHERE role = 'Technician' AND dept_id = (SELECT id FROM departments WHERE dept_name = 'Engineering')");
                        while($et = $eng_techs->fetch()) {
                            echo "<option value='{$et['id']}'>{$et['full_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Confirm Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>