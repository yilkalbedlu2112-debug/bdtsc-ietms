<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BDTSC-IETMS | Secure Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bdtsc-color: #28687F;
            --bdtsc-dark: #1e4f61;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #28687F 100%);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-gradient);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            width: 100%;
            max-width: 420px;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease;
        }

        .brand-icon {
            width: 64px;
            height: 64px;
            background: var(--bdtsc-color);
            color: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
            box-shadow: 0 10px 15px -3px rgba(40, 104, 127, 0.3);
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--bdtsc-color);
            box-shadow: 0 0 0 4px rgba(40, 104, 127, 0.1);
            outline: none;
        }

        .btn-bdtsc {
            background-color: var(--bdtsc-color);
            color: white;
            border-radius: 12px;
            padding: 14px;
            font-weight: 700;
            border: none;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-bdtsc:hover {
            background-color: var(--bdtsc-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 104, 127, 0.2);
            color: white;
        }

        .back-home {
            color: var(--bdtsc-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .back-home:hover {
            color: var(--bdtsc-dark);
            text-decoration: underline;
        }

        .alert {
            border: none;
            border-radius: 12px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-end mb-2">
        <a href="?lang=en" class="badge text-decoration-none border text-dark">EN</a>
        <a href="?lang=am" class="badge text-decoration-none border text-dark">አማ</a>
    </div>

    <div class="text-center mb-4">
        <img src="../assets/images/bdtsc_logo.png" alt="BDTSC Logo" style="width: 70px; margin-bottom: 15px;">
        <h3 class="fw-bold mb-1" style="color: var(--bdtsc-color);">BDTSC - IETMS</h3>
        <p class="text-muted small">Industrial workers work control system</p>
    </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success py-2 mb-4 rounded-3 small border-0 shadow-sm">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger py-2 mb-4 rounded-3 small border-0 shadow-sm">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <form action="login_process.php" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Email / ኢሜይል</label>
                <input type="email" name="email" class="form-control" placeholder="example@bdtsc.com" required>
            </div>
            
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label small fw-bold text-secondary mb-0">Password / የይለፍ ቃል</label>
                    <a href="forgot_password.php" class="small text-decoration-none fw-bold" style="color: var(--bdtsc-color);">Forgot? / ረስተዋል?</a>
                </div>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" name="login_btn" class="btn btn-bdtsc">
                    Login / ግባ <i class="bi bi-arrow-right-short ms-1"></i>
                </button>
            </div>
        </form>

        <div class="text-center mt-3">
            <a href="../index.php" class="back-home">
                <i class="bi bi-house-door"></i> Back to Home / ወደ ዋናው ገጽ ተመለስ
            </a>
        </div>

        <div class="text-center mt-4 pt-3 border-top">
            <p class="text-muted" style="font-size: 11px; margin-bottom: 0;">
                &copy; 2026 Bahir Dar Textile Share Company (BDTSC)
            </p>
        </div>
    </div>
</div>

</body>
</html>