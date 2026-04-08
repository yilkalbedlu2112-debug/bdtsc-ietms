<?php
session_start();
require_once '../includes/db.php';

// ተጠቃሚው መግባቱን ማረጋገጥ
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

if (isset($_POST['update_pw_btn'])) {
    $current_pw = $_POST['current_password'];
    $new_pw = $_POST['new_password'];
    $confirm_pw = $_POST['confirm_password'];
    $user_id = $_SESSION['user_id'];

    // 1. የድሮውን ፓስወርድ ከዳታቤዝ ማምጣት
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($current_pw, $user['password'])) {
        if ($new_pw === $confirm_pw) {
            // 2. አዲሱን ፓስወርድ ሴቭ ማድረግ
            $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([$hashed, $user_id]);
            $message = "ፓስወርድዎ በተሳካ ሁኔታ ተቀይሯል!";
        } else {
            $error = "አዲሶቹ ፓስወርዶች አይመሳሰሉም!";
        }
    } else {
        $error = "የአሁኑ ፓስወርድዎ የተሳሳተ ነው።";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f6; font-family: sans-serif; }
        .card { border-radius: 15px; border: none; margin-top: 50px; }
        .btn-bdtsc { background: #28687F; color: white; }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow p-4">
                <h4 class="text-center mb-4" style="color: #28687F;">Change Password</h4>
                
                <?php if($message): ?> <div class="alert alert-success small"><?php echo $message; ?></div> <?php endif; ?>
                <?php if($error): ?> <div class="alert alert-danger small"><?php echo $error; ?></div> <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small">Current Password (የአሁኑ)</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label small">New Password (አዲስ)</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small">Confirm New Password (ድገሙት)</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="update_pw_btn" class="btn btn-bdtsc w-100">Update Password</button>
                    <a href="../index.php" class="btn btn-light w-100 mt-2">Back to Dashboard</a>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>