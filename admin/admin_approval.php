<?php
session_start();
require_once '../includes/db.php';

// አስተዳዳሪ መሆኑን ማረጋገጥ
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'General Manager') {
    die("Unauthorized access!");
}

include '../includes/header_glass.php';

// --- 1. ማጽደቂያ ቁልፍ ሲጫን የሚሰራው ክፍል ---
if (isset($_GET['approve_id'])) {
    $id = $_GET['approve_id'];
    $admin_id = $_SESSION['user_id'] ?? 2; 

    $user_query = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $user_query->execute([$id]);
    $user_data = $user_query->fetch();
    $target_name = $user_data['full_name'];

    $extended_expiry = date("Y-m-d H:i:s", strtotime('+60 minutes'));

    $stmt = $pdo->prepare("UPDATE users SET reset_approved = 1, token_expiry = ? WHERE id = ?");
    
    if ($stmt->execute([$extended_expiry, $id])) {
        $action = "Password Reset Approved";
        $details = "Admin approved reset request for: " . $target_name . " (ID: $id)";
        log_action($pdo, $admin_id, $action, $details);

        header("Location: admin_approval.php?success=የ " . $target_name . " ጥያቄ ጸድቋል!");
        exit();
    }
}

// --- 2. የሰንጠረዦች ዳታ (ከ IF ብሎክ ውጭ መሆን አለባቸው) ---

// ገና ያልጸደቁ (Pending)
$stmt = $pdo->query("SELECT id, full_name, email, token_expiry FROM users WHERE reset_token IS NOT NULL AND reset_approved = 0");
$requests = $stmt->fetchAll();

// ቀድሞ የጸደቁ (Approved History) - ይህ ሰዓቱ ያላለፈባቸውን ብቻ ያሳያል
$stmt2 = $pdo->query("SELECT id, full_name, email FROM users WHERE reset_approved = 1 AND reset_token IS NOT NULL AND token_expiry > NOW() ORDER BY id DESC LIMIT 5");
$approved_list = $stmt2->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Approval - BDTSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow border-0 p-4">
        <h4 class="mb-4">Pending Password Reset Requests</h4>
        <table class="table table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td><?php echo $r['full_name']; ?></td>
                    <td><?php echo $r['email']; ?></td>
                    <td>
                        <a href="?approve_id=<?php echo $r['id']; ?>" class="btn btn-success btn-sm">Approve</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="card shadow border-0 p-4 mt-5">
    <h4 class="mb-4 text-success"><i class="bi bi-check-circle-fill"></i> Recently Approved Requests</h4>
    <table class="table table-sm table-striped">
        <thead class="table-success">
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($approved_list)): ?>
                <tr><td colspan="3" class="text-center">ምንም የጸደቀ ጥያቄ የለም።</td></tr>
            <?php else: ?>
                <?php foreach ($approved_list as $app): ?>
                <tr>
                    <td><?php echo $app['full_name']; ?></td>
                    <td><?php echo $app['email']; ?></td>
                    <td><span class="badge bg-success">Approved</span></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>