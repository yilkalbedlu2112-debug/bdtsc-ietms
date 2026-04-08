<?php
session_start();
require_once '../includes/db.php';

// Security: Check if user is Supervisor
if ($_SESSION['role'] !== 'Supervisor') {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'];

// ለShift Leader እንዲመደቡ የሚጠባበቁ ዋና ዋና ስራዎችን ማምጣት
$stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE dept_id = ? AND status = 'Pending'");
$stmt->execute([$dept_id]);
$pending_tasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="am">
<head>
    <meta charset="UTF-8">
    <title>Supervisor Dashboard | BDTSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="container py-4">
    <h2 class="mb-4 text-secondary"><i class="bi bi-person-badge"></i> የSupervisor ዳሽቦርድ</h2>

    <div class="row">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-plus-circle"></i> አዲስ የጥገና ስራ መመዝገብ (Create Task)
                </div>
                <div class="card-body">
                    <form action="save_task_supervisor.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">የማሽን ስም</label>
                            <input type="text" name="machine_name" class="form-control" placeholder="ምሳሌ፡ Spinning Frame #2" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">የብልሽት አይነት (Instructions)</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-info w-100 text-white">ስራውን መዝግብ</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <i class="bi bi-megaphone"></i> ለShift Leader የሚመሩ ዋና ዋና ስራዎች
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>ማሽን</th>
                                <th>ሁኔታ</th>
                                <th>ተግባር</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pending_tasks as $task): ?>
                            <tr>
                                <td><?php echo $task['machine_name']; ?></td>
                                <td><span class="badge bg-warning text-dark">በጥበቃ ላይ</span></td>
                                <td>
                                    <form action="alert_shift_leader.php" method="POST">
                                        <input type="hidden" name="req_id" value="<?php echo $task['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-bell"></i> ለShift Leader አሳውቅ
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>