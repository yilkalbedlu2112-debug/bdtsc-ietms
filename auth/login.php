<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BDTSC-IETMS | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; display: flex; align-items: center; height: 100vh; }
        .login-card { width: 100%; max-width: 400px; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: white; }
        .btn-bdtsc { background-color: #28687F; color: white; }
        .btn-bdtsc:hover { background-color: #1e4f61; color: white; }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="login-card">
        <div class="text-center mb-4">
            <h3 style="color: #28687F;">BDTSC - IETMS</h3>
            <p class="text-muted">የኢንዱስትሪ ሰራተኞች ስራ መቆጣጠሪያ</p>
        </div>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger py-2">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <form action="login_process.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Email / ኢሜይል</label>
                <input type="email" name="email" class="form-control" placeholder="example@bdtsc.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password / የይለፍ ቃል</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" name="login_btn" class="btn btn-bdtsc">Login / ግባ</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>