<?php
require_once '../includes/db.php';

$error = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("ሊንኩ የተሳሳተ ነው። እባክዎ እንደገና ከኢሜይልዎ ያረጋግጡ።");
}

// 1. Token-ኑ ትክክል መሆኑን እና ጊዜው አለማለፉን ማረጋገጥ
// ማሳሰቢያ፡ ዳታቤዝህ ላይ 'token_expiry' ከሆነ ስሙ እዚህ ጋር እንዳትረሳው
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $error = "ሊንኩ ጊዜው አልፎበታል (ከ30 ደቂቃ በላይ ሆኖታል) ወይም ቀደም ብሎ ጥቅም ላይ ውሏል። እባክዎ እንደገና ይጠይቁ።";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BDTSC-IETMS | Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --bdtsc-color: #28687F; --bg-gradient: linear-gradient(135deg, #0f172a 0%, #28687F 100%); }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-gradient); height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .reset-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); width: 100%; max-width: 420px; padding: 40px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .btn-bdtsc { background-color: var(--bdtsc-color); color: white; border-radius: 12px; padding: 12px; font-weight: 700; transition: 0.3s; border: none; }
        .btn-bdtsc:hover { background-color: #1e4f61; transform: translateY(-2px); color: white; }
    </style>
</head>
<body>
<div class="container d-flex justify-content-center px-3">
    <div class="reset-card">
        <div class="text-center mb-4">
            <h3 class="fw-bold" style="color: var(--bdtsc-color);">Set New Password</h3>
            <p class="text-muted small">አዲስ እና ጠንካራ የይለፍ ቃል ያስገቡ።</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small rounded-3 text-center">
                <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo $error; ?>
                <br><a href="forgot_password.php" class="alert-link">እንደገና ሞክር</a>
            </div>
        <?php else: ?>
            <form action="update_password_process.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">New Password</label>
                    <input type="password" name="password" class="form-control" placeholder="አዲስ ፓስወርድ" required minlength="6">
                </div>
                
                <div class="mb-4">
                    <label class="form-label small fw-bold text-secondary">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="ፓስወርዱን ይድገሙት" required minlength="6">
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="update_password" class="btn btn-bdtsc">Update Password / ቀይር</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>