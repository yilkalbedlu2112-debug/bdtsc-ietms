<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BDTSC-IETMS | Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --bdtsc-color: #28687F; --bg-gradient: linear-gradient(135deg, #0f172a 0%, #28687F 100%); }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-gradient); height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .forgot-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); width: 100%; max-width: 420px; padding: 40px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .icon-circle { width: 70px; height: 70px; background: rgba(40, 104, 127, 0.1); color: var(--bdtsc-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 30px; }
        .form-control { border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; }
        .btn-bdtsc { background-color: var(--bdtsc-color); color: white; border-radius: 12px; padding: 12px; font-weight: 700; transition: 0.3s; width: 100%; border: none; }
        .btn-bdtsc:hover { background-color: #1e4f61; transform: translateY(-2px); color: white; }
    </style>
</head>
<body>
<div class="container d-flex justify-content-center px-3">
    <div class="forgot-card">
        <div class="text-center mb-4">
            <div class="icon-circle"><i class="bi bi-shield-lock"></i></div>
            <h3 class="fw-bold" style="color: var(--bdtsc-color);">Forgot Password?</h3>
            <p class="text-muted small">ኢሜይልዎን ያስገቡ እና ፓስወርድ መቀየሪያ ሊንክ እንልክልዎታለን።</p>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success py-2 small rounded-3"><i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger py-2 small rounded-3"><i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <form action="send_reset_link.php" method="POST">
            <div class="mb-4">
                <label class="form-label small fw-bold text-secondary">Email Address / ኢሜይል</label>
                <input type="email" name="email" class="form-control" placeholder="example@bdtsc.com" required>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" name="reset_request" class="btn btn-bdtsc">Send Reset Link / ሊንኩን ላክ</button>
                <a href="login.php" class="btn btn-light btn-sm mt-2" style="border-radius: 10px; color: #64748b;">
                    <i class="bi bi-arrow-left"></i> Back to Login / ተመለስ
                </a>
            </div>
        </form>
    </div>
</div>
</body>
</html>