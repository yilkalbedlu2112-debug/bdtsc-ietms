<?php 
require_once '../includes/db.php';
session_start();

// ሰራተኛ መሆኑን ማረጋገጫ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../auth/login.php");
    exit();
}

$success_msg = "";
if (isset($_POST['submit_request'])) {
    $machine = $_POST['machine_name'];
    $issue = $_POST['issue_description'];
    $priority = $_POST['priority'];
    $user_id = $_SESSION['user_id'];
    $dept_id = $_SESSION['dept_id'];

    $sql = "INSERT INTO maintenance_requests (user_id, dept_id, machine_name, issue_description, priority) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$user_id, $dept_id, $machine, $issue, $priority])) {
        $success_msg = "ብልሽቱ በትክክል ተመዝግቧል! / Request Submitted!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee - Report Issue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">BDTSC | Employee Panel</a>
        <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="row">
        <div class="col-md-5">
            <div class="card shadow-sm p-4">
                <h4 class="mb-3">የብልሽት መመዝገቢያ (Report Issue)</h4>
                <?php if($success_msg): ?>
                    <div class="alert alert-success"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label>የማሽኑ ስም (Machine Name)</label>
                        <input type="text" name="machine_name" class="form-control" required placeholder="ለምሳሌ፡ Spinning Machine #04">
                    </div>
                    <div class="mb-3">
                        <label>የብልሽቱ አይነት (Issue Description)</label>
                        <textarea name="issue_description" class="form-control" rows="3" required placeholder="ችግሩን እዚህ ይግለጹ..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label>አስቸኳይነት (Priority)</label>
                        <select name="priority" class="form-select">
                            <option value="Low">Low (ቀላል)</option>
                            <option value="Medium" selected>Medium (መካከለኛ)</option>
                            <option value="High">High (ከፍተኛ)</option>
                            <option value="Urgent">Urgent (በጣም አስቸኳይ)</option>
                        </select>
                    </div>
                    <button type="submit" name="submit_request" class="btn btn-danger w-100">ብልሽቱን መዝግብ (Submit)</button>
                </form>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm p-4">
                <h4>የእርስዎ የጥገና ጥያቄዎች</h4>
                <table class="table mt-3">
                    <thead>
                        <tr>
                            <th>ማሽን</th>
                            <th>ሁኔታ (Status)</th>
                            <th>ቀን</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE user_id = ? ORDER BY created_at DESC");
                        $stmt->execute([$_SESSION['user_id']]);
                        while($row = $stmt->fetch()):
                        ?>
                        <tr>
                            <td><?php echo $row['machine_name']; ?></td>
                            <td>
                                <span class="badge bg-secondary"><?php echo $row['status']; ?></span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>