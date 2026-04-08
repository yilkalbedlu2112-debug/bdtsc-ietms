<?php 
session_start();
require_once '../includes/db.php';

$dept_id = $_SESSION['dept_id'];

// 1. ከማናጀር የመጡ አዳዲስ ስራዎችን ማምጣት
$stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE dept_id = ? AND status = 'Pending'");
$stmt->execute([$dept_id]);
$new_requests = $stmt->fetchAll();

// 2. የሁሉንም ስራዎች ሂደት መከታተል (Monitor Progress)
$progress_stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE dept_id = ? AND status != 'Pending'");
$progress_stmt->execute([$dept_id]);
$all_tasks = $progress_stmt->fetchAll();
?>

include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-briefcase-fill text-primary"></i> Shift Leader Dashboard</h2>
        <span class="badge bg-info text-dark">Dept ID: <?php echo $dept_id; ?></span>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white fw-bold">አዳዲስ የጥገና ጥያቄዎች (Decision Needed)</div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>ማሽን</th>
                                <th>ብልሽት</th>
                                <th>ክብደት (Severity)</th>
                                <th>ውሳኔ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($new_requests as $req): ?>
                            <tr>
                                <td><?php echo $req['machine_name']; ?></td>
                                <td><?php echo $req['issue_description']; ?></td>
                                <form method="POST" action="process_decision.php">
                                    <input type="hidden" name="req_id" value="<?php echo $req['id']; ?>">
                                    <td>
                                        <select name="severity" class="form-select form-select-sm">
                                            <option value="Low">ቀላል (Low)</option>
                                            <option value="High">ከባድ (High)</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="submit" name="to_engineering" class="btn btn-sm btn-outline-primary">ለEngineering ላክ</button>
                                            <button type="submit" name="to_manager" class="btn btn-sm btn-outline-danger">ለማናጀር መልስ</button>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">የስራዎች ሁኔታ (Real-time Status)</div>
                <div class="list-group list-group-flush">
                    <?php foreach($all_tasks as $task): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-0"><?php echo $task['machine_name']; ?></h6>
                                <small class="text-muted">ተረካቢ፡ <?php echo $task['assigned_to_dept']; ?></small>
                            </div>
                            <span class="badge rounded-pill <?php echo ($task['status'] == 'In Progress') ? 'bg-warning' : 'bg-success'; ?>">
                                <?php echo $task['status']; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer_glass.php'; ?>