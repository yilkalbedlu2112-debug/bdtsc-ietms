<?php
session_start();
require_once '../includes/db.php';

// 1. ቴክኒሻን መሆኑን ማረጋገጥ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Technician') {
    header("Location: ../auth/login.php");
    exit();
}

$tech_id = $_SESSION['user_id'];

// 2. ለዚህ ቴክኒሻን የተመደቡ እና ገና ያልተጠናቀቁ ስራዎችን ማምጣት
// ከ departments ሰንጠረዥ ጋር በማገናኘት የዲፓርትመንቱን ስም እናሳያለን
$query = "SELECT m.*, d.dept_name 
          FROM maintenance_requests m 
          JOIN departments d ON m.dept_id = d.id 
          WHERE m.assigned_to = ? AND m.status != 'Completed' 
          ORDER BY m.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$tech_id]);
$assigned_tasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="am">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard | BDTSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .task-card { border-left: 5px solid #ffc107; transition: 0.3s; }
        .task-card:hover { transform: scale(1.01); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <span class="navbar-brand">የጥገና ባለሙያ ገጽ (Technician)</span>
        <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">ውጣ</a>
    </div>
</nav>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>እንኳን ደህና መጡ፣ <span class="text-primary"><?php echo $_SESSION['full_name']; ?></span></h4>
        <span class="badge bg-secondary">ባለሙያ ID: <?php echo $tech_id; ?></span>
    </div>

    <h5 class="mb-3">የተመደቡልኝ የጥገና ስራዎች</h5>

    <?php if (empty($assigned_tasks)): ?>
        <div class="alert alert-info shadow-sm">
            አሁን ላይ የተመደበልዎት አዲስ የጥገና ስራ የለም።
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach($assigned_tasks as $task): ?>
                <div class="col-md-12 mb-3">
                    <div class="card task-card shadow-sm">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="card-title text-dark">ማሽን፦ <?php echo $task['machine_name']; ?></h5>
                                    <p class="mb-1 text-muted"><strong>ዲፓርትመንት፦</strong> <?php echo $task['dept_name']; ?></p>
                                    <p class="mb-2"><strong>ያጋጠመ ችግር፦</strong> <?php echo $task['issue_description']; ?></p>
                                    <span class="badge bg-warning text-dark">ሁኔታ፦ <?php echo $task['status']; ?></span>
                                </div>
                                <div class="col-md-4 border-start">
                                    <form action="complete_task.php" method="POST">
                                        <input type="hidden" name="req_id" value="<?php echo $task['id']; ?>">
                                        <div class="mb-2">
                                            <label class="form-label small">የተሰራው ጥገና/አስተያየት</label>
                                            <textarea name="feedback" class="form-control form-control-sm" rows="2" placeholder="ለምሳሌ፡ ሞተር ተቀይሯል..." required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success btn-sm w-100">ጥገናው ተጠናቋል</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>